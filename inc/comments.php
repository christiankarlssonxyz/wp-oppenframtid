<?php
/**
 * inc/comments.php – Kommentarsystem
 *
 * - Inloggade: direkt publicering via AJAX
 * - Gäster: pending → e-postverifiering → moderatorkö
 * - WP Cron: rensar overifierade gästkommentarer efter 24h
 */

// Avregistrera WP:s inbyggda comment-reply script
add_action('wp_enqueue_scripts', function () {
    wp_deregister_script('comment-reply');
}, 20);

// ── AJAX: inloggad kommentar ───────────────────────────────────────────────────
add_action('wp_ajax_blogtree_post_comment', 'blogtree_handle_comment');

function blogtree_handle_comment(): void {
    check_ajax_referer('blogtree_comment', 'nonce');

    $post_id   = (int) ($_POST['post_id']   ?? 0);
    $parent_id = (int) ($_POST['parent_id'] ?? 0);
    $content   = wp_kses_post(wp_unslash($_POST['content'] ?? ''));
    $user_id   = get_current_user_id();

    if (!$post_id || !$content) {
        wp_send_json_error('Fyll i alla fält.');
    }

    // Rate-limiting: max 10 per användare per timme
    $rate_key   = 'blogtree_comment_rate_' . $user_id;
    $rate_count = (int) get_transient($rate_key);
    if ($rate_count >= 10) {
        wp_send_json_error('Du har kommenterat för mycket. Försök igen om en stund.');
    }
    set_transient($rate_key, $rate_count + 1, HOUR_IN_SECONDS);

    $user = wp_get_current_user();

    $comment_id = wp_insert_comment([
        'comment_post_ID'  => $post_id,
        'comment_content'  => $content,
        'comment_parent'   => $parent_id,
        'user_id'          => $user_id,
        'comment_author'   => $user->display_name,
        'comment_approved' => 1,
    ]);

    if (!$comment_id) {
        wp_send_json_error('Kunde inte spara kommentaren.');
    }

    wp_send_json_success([
        'comment_id' => $comment_id,
        'content'    => $content,
    ]);
}

// ── AJAX: gästkommentar ────────────────────────────────────────────────────────
add_action('wp_ajax_nopriv_blogtree_post_guest_comment', 'blogtree_handle_guest_comment');

function blogtree_handle_guest_comment(): void {
    check_ajax_referer('blogtree_comment', 'nonce');

    $post_id = (int)   ($_POST['post_id'] ?? 0);
    $name    = sanitize_text_field($_POST['guest_name']  ?? '');
    $email   = sanitize_email($_POST['guest_email'] ?? '');
    $content = wp_kses_post(wp_unslash($_POST['content'] ?? ''));

    if (!$post_id || !$name || !$content) {
        wp_send_json_error('Fyll i alla fält.');
    }
    if (!is_email($email)) {
        wp_send_json_error('Ange en giltig e-postadress.');
    }

    // Rate-limiting per IP: max 5 per timme
    $rate_key   = 'blogtree_guest_rate_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $rate_count = (int) get_transient($rate_key);
    if ($rate_count >= 5) {
        wp_send_json_error('För många kommentarer. Försök igen om en stund.');
    }
    set_transient($rate_key, $rate_count + 1, HOUR_IN_SECONDS);

    // Spara som pending (ej godkänd, ej verifierad)
    $comment_id = wp_insert_comment([
        'comment_post_ID'      => $post_id,
        'comment_content'      => $content,
        'comment_author'       => $name,
        'comment_author_email' => $email,
        'comment_approved'     => 0,
        'user_id'              => 0,
    ]);

    if (!$comment_id) {
        wp_send_json_error('Kunde inte spara kommentaren.');
    }

    // Meta: e-post + verifierad-flagga + tidsstämpel för cron-rensning
    update_comment_meta($comment_id, 'blogtree_pending_email',    $email);
    update_comment_meta($comment_id, 'blogtree_pending_verified', '0');
    update_comment_meta($comment_id, 'blogtree_pending_time',     time());

    // Verifieringstoken (giltig 24h)
    $token = wp_generate_password(32, false);
    set_transient('blogtree_verify_comment_' . $token, $comment_id, DAY_IN_SECONDS);

    $verify_url = add_query_arg('blogtree_verify_comment', $token, home_url('/'));

    wp_mail(
        $email,
        'Bekräfta din kommentar — ' . get_bloginfo('name'),
        "Hej {$name},\n\n" .
        "Klicka på länken nedan för att bekräfta din kommentar:\n\n" .
        $verify_url . "\n\n" .
        "Länken gäller i 24 timmar. Din e-postadress raderas automatiskt efter verifiering.\n\n" .
        "Om du inte skickade en kommentar kan du ignorera det här mailet."
    );

    wp_send_json_success([
        'message' => 'Ett verifieringsmail har skickats till ' . $email . '. Klicka på länken i mailet för att publicera din kommentar.',
    ]);
}

// ── Verifiera gästkommentar via token (GET) ────────────────────────────────────
add_action('init', function () {
    $token = sanitize_text_field($_GET['blogtree_verify_comment'] ?? '');
    if (!$token) return;

    $comment_id = get_transient('blogtree_verify_comment_' . $token);
    if (!$comment_id) {
        wp_die('Länken har gått ut eller är ogiltig.', 'Ogiltig länk', ['response' => 400]);
    }

    delete_transient('blogtree_verify_comment_' . $token);

    // Markera som verifierad (fortfarande pending = moderatorkö)
    update_comment_meta($comment_id, 'blogtree_pending_verified', '1');
    // Radera e-postadressen från meta (behåll på själva kommentaren för moderatorn)
    // E-post sparas kvar på kommentar-objektet tills moderator agerar

    // Notifiera moderatorer
    blogtree_notify_mods_pending($comment_id);

    $comment = get_comment($comment_id);
    $redirect = $comment ? get_permalink($comment->comment_post_ID) : home_url('/');
    wp_safe_redirect(add_query_arg('kommentar', 'bekraftad', $redirect));
    exit;
});

// ── Notifiera moderatorer om väntande gästkommentar ───────────────────────────
function blogtree_notify_mods_pending(int $comment_id): void {
    $comment  = get_comment($comment_id);
    $post_url = $comment ? get_permalink($comment->comment_post_ID) : '';
    $mod_url  = home_url('/medlemmar/kommentarer/granska/');

    $mods = get_users(['role__in' => ['administrator', 'moderator']]);
    foreach ($mods as $mod) {
        if (!get_user_meta($mod->ID, 'blogtree_mod_notifications', true)) continue;
        wp_mail(
            $mod->user_email,
            'Ny kommentar väntar godkännande — ' . get_bloginfo('name'),
            "En gästkommentar har verifierats och väntar på ditt godkännande.\n\n" .
            ($post_url ? "Inlägg: {$post_url}\n" : '') .
            "Granska: {$mod_url}"
        );
    }
}

// ── WP Cron: registrera schema ─────────────────────────────────────────────────
add_action('init', function () {
    if (!wp_next_scheduled('blogtree_cleanup_unverified_comments')) {
        wp_schedule_event(time(), 'hourly', 'blogtree_cleanup_unverified_comments');
    }
});

add_action('blogtree_cleanup_unverified_comments', function () {
    global $wpdb;

    // Hitta kommentarer som är overifierade och äldre än 24h
    $cutoff = time() - DAY_IN_SECONDS;

    $comment_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT cm.comment_id
         FROM {$wpdb->commentmeta} cm
         INNER JOIN {$wpdb->commentmeta} cm2 ON cm.comment_id = cm2.comment_id
         WHERE cm.meta_key  = 'blogtree_pending_verified' AND cm.meta_value  = '0'
           AND cm2.meta_key = 'blogtree_pending_time'     AND cm2.meta_value < %d",
        $cutoff
    ));

    foreach ($comment_ids as $id) {
        wp_delete_comment((int) $id, true);
    }
});

// ── AJAX: admin markera som läst (toggle) ─────────────────────────────────────
add_action('wp_ajax_blogtree_admin_mark_read', function () {
    check_ajax_referer('blogtree_admin_comment', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Behörighet saknas.');

    $comment_id = (int) ($_POST['comment_id'] ?? 0);
    if (!$comment_id) wp_send_json_error('Ogiltig förfrågan.');

    $is_read = get_comment_meta($comment_id, 'blogtree_admin_read', true);
    if ($is_read) {
        delete_comment_meta($comment_id, 'blogtree_admin_read');
        wp_send_json_success(['read' => false]);
    } else {
        update_comment_meta($comment_id, 'blogtree_admin_read', '1');
        wp_send_json_success(['read' => true]);
    }
});

// ── AJAX: admin gilla (toggle) ────────────────────────────────────────────────
add_action('wp_ajax_blogtree_admin_like', function () {
    check_ajax_referer('blogtree_admin_comment', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Behörighet saknas.');

    $comment_id = (int) ($_POST['comment_id'] ?? 0);
    if (!$comment_id) wp_send_json_error('Ogiltig förfrågan.');

    $liked = get_comment_meta($comment_id, 'blogtree_admin_liked', true);
    if ($liked) {
        delete_comment_meta($comment_id, 'blogtree_admin_liked');
        wp_send_json_success(['liked' => false]);
    } else {
        update_comment_meta($comment_id, 'blogtree_admin_liked', '1');
        wp_send_json_success(['liked' => true]);
    }
});

// ── Hjälp: hämta admin-user-IDs som kommaseparerad sträng ─────────────────────
function blogtree_admin_user_ids_sql(): string {
    $ids = get_users(['role' => 'administrator', 'fields' => 'ID']);
    if (empty($ids)) return '0'; // fallback: exkludera user_id 0 (gäster) — inga admins finns
    return implode(',', array_map('intval', $ids));
}

// ── Tab-navigation (delas av alla modereringssidor) ───────────────────────────
function blogtree_mod_tab_nav(string $active): void {
    global $wpdb;
    $is_admin = current_user_can('manage_options');

    $reported = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT comment_id) FROM {$wpdb->commentmeta} WHERE meta_key = %s",
        BLOGTREE_REPORT_META_KEY
    ));

    $pending = (int) get_comments([
        'status'     => 'hold',
        'count'      => true,
        'meta_query' => [['key' => 'blogtree_pending_email', 'compare' => 'EXISTS']],
    ]);

    if ($is_admin) {
        $admin_ids = blogtree_admin_user_ids_sql();
        $unread = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT c.comment_ID)
             FROM {$wpdb->comments} c
             LEFT JOIN {$wpdb->commentmeta} cm
               ON c.comment_ID = cm.comment_id AND cm.meta_key = 'blogtree_admin_read'
             WHERE c.comment_approved = '1'
               AND cm.meta_id IS NULL
               AND c.user_id NOT IN ({$admin_ids})"
        );
    }

    $tabs = [
        'rapporterade' => [
            'label' => 'Rapporterade',
            'url'   => home_url('/medlemmar/kommentarer/'),
            'count' => $reported,
            'admin' => false,
        ],
        'granska' => [
            'label' => 'Väntar godkännande',
            'url'   => home_url('/medlemmar/kommentarer/granska/'),
            'count' => $pending,
            'admin' => false,
        ],
        'obesvarade' => [
            'label' => 'Obesvarade',
            'url'   => home_url('/medlemmar/kommentarer/obesvarade/'),
            'count' => $unread ?? 0,
            'admin' => true,
        ],
        'alla' => [
            'label' => 'Alla',
            'url'   => home_url('/medlemmar/kommentarer/alla/'),
            'count' => 0,
            'admin' => true,
        ],
    ];

    echo '<div class="mod-tabs">';
    foreach ($tabs as $key => $tab) {
        if ($tab['admin'] && !$is_admin) continue;
        $cls = 'mod-tab' . ($active === $key ? ' mod-tab--active' : '');
        echo '<a href="' . esc_url($tab['url']) . '" class="' . $cls . '">';
        echo esc_html($tab['label']);
        if ($tab['count']) {
            echo '<span class="mod-tab__badge">' . (int) $tab['count'] . '</span>';
        }
        echo '</a>';
    }
    echo '</div>';
}
