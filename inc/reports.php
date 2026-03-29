<?php
/**
 * inc/reports.php – Kommentarsrapportering
 *
 * - Rapportera kommentar (inloggad + utloggad med e-postverifiering)
 * - En rapport per person per kommentar
 * - Auto-döljning vid tröskel
 * - E-postnotis till moderatorer
 * - Ignorera rapport (återställer räknaren, mailar rapportörer)
 * - Överklagan
 * - Admin-inställningar: orsaker + tröskel
 */

// ── Standardvärden ─────────────────────────────────────────────────────────────
define('BLOGTREE_REPORT_META_KEY',    'blogtree_reports');
define('BLOGTREE_REPORTED_BY_KEY',    'blogtree_reported_by');
define('BLOGTREE_REPORT_REASONS_OPT', 'blogtree_report_reasons');
define('BLOGTREE_REPORT_THRESHOLD',   'blogtree_report_threshold');

function blogtree_default_reasons(): array {
    return [
        'stotande'  => 'Stötande innehåll',
        'spam'      => 'Spam',
        'trakasserier' => 'Trakasserier',
        'felaktig'  => 'Felaktig information',
    ];
}

function blogtree_get_reasons(): array {
    $saved = get_option(BLOGTREE_REPORT_REASONS_OPT, []);
    return !empty($saved) ? $saved : blogtree_default_reasons();
}

function blogtree_get_threshold(): int {
    return (int) get_option(BLOGTREE_REPORT_THRESHOLD, 10);
}

// ── Admin-sida: Rapporteringsinställningar ─────────────────────────────────────
add_action('admin_menu', function () {
    add_options_page(
        'Rapporteringsorsaker',
        'Rapporteringsorsaker',
        'manage_options',
        'blogtree-reports',
        'blogtree_reports_settings_page'
    );
});

function blogtree_reports_settings_page(): void {
    if (isset($_POST['blogtree_reports_nonce']) &&
        wp_verify_nonce($_POST['blogtree_reports_nonce'], 'blogtree_reports_save')) {

        // Spara tröskel
        $threshold = max(1, (int) ($_POST['threshold'] ?? 10));
        update_option(BLOGTREE_REPORT_THRESHOLD, $threshold);

        // Spara orsaker
        $keys   = array_map('sanitize_key',        $_POST['reason_keys']   ?? []);
        $labels = array_map('sanitize_text_field',  $_POST['reason_labels'] ?? []);
        $reasons = [];
        foreach ($keys as $i => $key) {
            if ($key && isset($labels[$i]) && $labels[$i]) {
                $reasons[$key] = $labels[$i];
            }
        }
        update_option(BLOGTREE_REPORT_REASONS_OPT, $reasons);
        echo '<div class="updated"><p>Inställningarna sparade.</p></div>';
    }

    $reasons   = blogtree_get_reasons();
    $threshold = blogtree_get_threshold();
    ?>
    <div class="wrap">
        <h1>Rapporteringsinställningar</h1>
        <form method="post">
            <?php wp_nonce_field('blogtree_reports_save', 'blogtree_reports_nonce'); ?>

            <h2>Automatisk döljning</h2>
            <table class="form-table">
                <tr>
                    <th><label for="threshold">Antal rapporter för automatisk döljning</label></th>
                    <td>
                        <input type="number" id="threshold" name="threshold"
                               value="<?php echo esc_attr($threshold); ?>" min="1" class="small-text">
                    </td>
                </tr>
            </table>

            <h2>Rapporteringsorsaker</h2>
            <table class="widefat" id="reasons-table">
                <thead><tr><th>Nyckel (slug)</th><th>Etikett</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($reasons as $key => $label): ?>
                <tr>
                    <td><input type="text" name="reason_keys[]" value="<?php echo esc_attr($key); ?>" class="regular-text"></td>
                    <td><input type="text" name="reason_labels[]" value="<?php echo esc_attr($label); ?>" class="regular-text"></td>
                    <td><button type="button" class="button remove-reason-row">Ta bort</button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="button" class="button" id="add-reason-row">+ Lägg till orsak</button>
            </p>
            <?php submit_button('Spara inställningar'); ?>
        </form>
    </div>
    <script>
    document.getElementById('add-reason-row').addEventListener('click', function () {
        var tbody = document.querySelector('#reasons-table tbody');
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><input type="text" name="reason_keys[]" class="regular-text"></td>'
                     + '<td><input type="text" name="reason_labels[]" class="regular-text"></td>'
                     + '<td><button type="button" class="button remove-reason-row">Ta bort</button></td>';
        tbody.appendChild(tr);
    });
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-reason-row')) {
            e.target.closest('tr').remove();
        }
    });
    </script>
    <?php
}

// ── Lokalisera rapportdata till JS ─────────────────────────────────────────────
add_action('wp_enqueue_scripts', function () {
    if (!is_singular('post')) return;
    wp_add_inline_script('blogtree-follow', 'var blogtreeReports = ' . json_encode([
        'ajaxurl'  => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('blogtree_report'),
        'reasons'  => blogtree_get_reasons(),
        'loggedIn' => is_user_logged_in(),
    ]) . ';', 'before');
});

// ── AJAX: Skicka rapport (inloggad) ───────────────────────────────────────────
add_action('wp_ajax_blogtree_report_comment', function () {
    check_ajax_referer('blogtree_report', 'nonce');

    $comment_id = (int) ($_POST['comment_id'] ?? 0);
    $reason_key = sanitize_key($_POST['reason'] ?? '');
    $user_id    = get_current_user_id();

    if (!$comment_id || !$reason_key) {
        wp_send_json_error('Ogiltig förfrågan.');
    }

    $reasons = blogtree_get_reasons();
    if (!isset($reasons[$reason_key])) {
        wp_send_json_error('Ogiltig orsak.');
    }

    // Spärr: en rapport per användare per kommentar
    $reported_by = (array) get_comment_meta($comment_id, BLOGTREE_REPORTED_BY_KEY, true);
    $reporter_key = 'user_' . $user_id;
    if (isset($reported_by[$reporter_key])) {
        wp_send_json_error('Du har redan rapporterat den här kommentaren.');
    }

    blogtree_save_report($comment_id, $reporter_key, $reason_key, $reported_by);
});

// ── AJAX: Skicka rapport (utloggad) — steg 1: begär verifiering ──────────────
add_action('wp_ajax_nopriv_blogtree_report_comment', function () {
    check_ajax_referer('blogtree_report', 'nonce');

    $comment_id = (int) ($_POST['comment_id'] ?? 0);
    $reason_key = sanitize_key($_POST['reason'] ?? '');
    $email      = sanitize_email($_POST['email'] ?? '');

    if (!$comment_id || !$reason_key || !is_email($email)) {
        wp_send_json_error('Fyll i alla fält korrekt.');
    }

    $reasons = blogtree_get_reasons();
    if (!isset($reasons[$reason_key])) {
        wp_send_json_error('Ogiltig orsak.');
    }

    // Spärr
    $reported_by = (array) get_comment_meta($comment_id, BLOGTREE_REPORTED_BY_KEY, true);
    $reporter_key = 'email_' . md5($email);
    if (isset($reported_by[$reporter_key])) {
        wp_send_json_error('Den här e-postadressen har redan rapporterat kommentaren.');
    }

    // Skapa verifieringstoken
    $token = wp_generate_password(32, false);
    set_transient('blogtree_report_' . $token, [
        'comment_id'   => $comment_id,
        'reason_key'   => $reason_key,
        'reporter_key' => $reporter_key,
        'email'        => $email,
    ], DAY_IN_SECONDS);

    $verify_url = add_query_arg([
        'blogtree_verify_report' => $token,
    ], home_url('/'));

    wp_mail(
        $email,
        'Bekräfta din kommentarsrapport — ' . get_bloginfo('name'),
        "Klicka på länken nedan för att bekräfta din rapport:\n\n" . $verify_url .
        "\n\nLänken gäller i 24 timmar. Om du inte skickade in en rapport kan du ignorera detta mail."
    );

    wp_send_json_success(['message' => 'Ett verifieringsmail har skickats till ' . $email . '.']);
});

// ── Verifiera rapport via token (GET) ─────────────────────────────────────────
add_action('init', function () {
    $token = sanitize_text_field($_GET['blogtree_verify_report'] ?? '');
    if (!$token) return;

    $data = get_transient('blogtree_report_' . $token);
    if (!$data) {
        wp_die('Länken har gått ut eller är ogiltig.', 'Ogiltig länk', ['response' => 400]);
    }

    delete_transient('blogtree_report_' . $token);

    $reported_by = (array) get_comment_meta($data['comment_id'], BLOGTREE_REPORTED_BY_KEY, true);
    if (!isset($reported_by[$data['reporter_key']])) {
        blogtree_save_report($data['comment_id'], $data['reporter_key'], $data['reason_key'], $reported_by, $data['email']);
    }

    wp_safe_redirect(add_query_arg('rapport', 'bekraftad', home_url('/')));
    exit;
});

// ── Spara rapport + kontrollera tröskel ───────────────────────────────────────
function blogtree_save_report(int $comment_id, string $reporter_key, string $reason_key, array $reported_by, string $email = ''): void {
    // Markera rapportören
    $reported_by[$reporter_key] = [
        'reason' => $reason_key,
        'time'   => time(),
        'email'  => $email,
    ];
    update_comment_meta($comment_id, BLOGTREE_REPORTED_BY_KEY, $reported_by);

    // Räkna rapporter
    $reports = (array) get_comment_meta($comment_id, BLOGTREE_REPORT_META_KEY, true);
    $reports[$reason_key] = ($reports[$reason_key] ?? 0) + 1;
    update_comment_meta($comment_id, BLOGTREE_REPORT_META_KEY, $reports);

    $total = array_sum($reports);

    // Kontrollera tröskel
    if ($total >= blogtree_get_threshold()) {
        $comment = get_comment($comment_id);
        if ($comment && $comment->comment_approved !== '0') {
            wp_set_comment_status($comment_id, 'hold');
            blogtree_notify_moderators($comment_id, $total);
        }
    }

    wp_send_json_success(['message' => 'Tack för din rapport. Den granskas av en moderator.']);
}

// ── Notifiera moderatorer ──────────────────────────────────────────────────────
function blogtree_notify_moderators(int $comment_id, int $count): void {
    $comment  = get_comment($comment_id);
    $post_url = get_permalink($comment->comment_post_ID);
    $mod_url  = home_url('/medlemmar/kommentarer/');

    $mods = get_users(['role__in' => ['administrator', 'moderator']]);
    foreach ($mods as $mod) {
        if (!get_user_meta($mod->ID, 'blogtree_mod_notifications', true)) continue;
        wp_mail(
            $mod->user_email,
            'Kommentar flaggad automatiskt — ' . get_bloginfo('name'),
            "En kommentar har dolts automatiskt efter {$count} rapporter.\n\n" .
            "Inlägg: {$post_url}\n" .
            "Granska: {$mod_url}"
        );
    }
}

// ── AJAX: Moderera — godkänn / ta bort / ignorera ─────────────────────────────
add_action('wp_ajax_blogtree_moderate_comment', function () {
    check_ajax_referer('blogtree_moderate', 'nonce');

    if (!blogtree_can_manage_members()) {
        wp_send_json_error('Behörighet saknas.');
    }

    $comment_id = (int) ($_POST['comment_id'] ?? 0);
    $action_val = sanitize_key($_POST['mod_action'] ?? '');

    if (!$comment_id || !in_array($action_val, ['approve', 'delete', 'ignore'], true)) {
        wp_send_json_error('Ogiltig förfrågan.');
    }

    switch ($action_val) {
        case 'approve':
            wp_set_comment_status($comment_id, 'approve');
            delete_comment_meta($comment_id, BLOGTREE_REPORT_META_KEY);
            delete_comment_meta($comment_id, BLOGTREE_REPORTED_BY_KEY);
            wp_send_json_success(['message' => 'Kommentaren godkänd och rapporter rensade.']);
            break;

        case 'delete':
            wp_delete_comment($comment_id, true);
            wp_send_json_success(['message' => 'Kommentaren borttagen.']);
            break;

        case 'ignore':
            // Återställ räknaren
            wp_set_comment_status($comment_id, 'approve');
            $reported_by = (array) get_comment_meta($comment_id, BLOGTREE_REPORTED_BY_KEY, true);
            delete_comment_meta($comment_id, BLOGTREE_REPORT_META_KEY);
            delete_comment_meta($comment_id, BLOGTREE_REPORTED_BY_KEY);

            // Maila rapportörerna
            blogtree_notify_reporters_ignored($comment_id, $reported_by);
            wp_send_json_success(['message' => 'Rapport ignorerad, räknaren återställd.']);
            break;
    }
});

// ── Maila rapportörer vid ignorerad rapport ────────────────────────────────────
function blogtree_notify_reporters_ignored(int $comment_id, array $reported_by): void {
    $appeal_url = add_query_arg([
        'blogtree_appeal' => $comment_id,
    ], home_url('/'));

    foreach ($reported_by as $key => $data) {
        if (empty($data['email'])) {
            // Inloggad — hämta e-post via user_id
            if (str_starts_with($key, 'user_')) {
                $user_id = (int) str_replace('user_', '', $key);
                $user    = get_user_by('id', $user_id);
                if ($user) {
                    wp_mail(
                        $user->user_email,
                        'Din rapport granskades — ' . get_bloginfo('name'),
                        "Din rapport av en kommentar har granskats. Kommentaren bedömdes inte bryta mot våra riktlinjer och behålls.\n\n" .
                        "Om du vill överklaga beslutet kan du göra det här:\n" . $appeal_url
                    );
                }
            }
        } else {
            wp_mail(
                $data['email'],
                'Din rapport granskades — ' . get_bloginfo('name'),
                "Din rapport av en kommentar har granskats. Kommentaren bedömdes inte bryta mot våra riktlinjer och behålls.\n\n" .
                "Om du vill överklaga beslutet kan du göra det här:\n" . $appeal_url
            );
        }
    }
}

// ── Överklagan (GET-länk + POST-formulär) ─────────────────────────────────────
add_action('wp_ajax_blogtree_appeal', 'blogtree_handle_appeal');
add_action('wp_ajax_nopriv_blogtree_appeal', 'blogtree_handle_appeal');

function blogtree_handle_appeal(): void {
    check_ajax_referer('blogtree_appeal', 'nonce');

    $comment_id = (int) ($_POST['comment_id'] ?? 0);
    $message    = sanitize_textarea_field($_POST['message'] ?? '');
    $email      = sanitize_email($_POST['email'] ?? '');

    if (!$comment_id || !$message) {
        wp_send_json_error('Fyll i alla fält.');
    }

    $comment  = get_comment($comment_id);
    $post_url = $comment ? get_permalink($comment->comment_post_ID) : home_url('/');

    $mods = get_users(['role__in' => ['administrator', 'moderator']]);
    foreach ($mods as $mod) {
        if (!get_user_meta($mod->ID, 'blogtree_mod_notifications', true)) continue;
        wp_mail(
            $mod->user_email,
            'Överklagan inkommen — ' . get_bloginfo('name'),
            "En användare har överklagat ett rapportbeslut.\n\n" .
            "Kommentar-ID: {$comment_id}\n" .
            "Inlägg: {$post_url}\n" .
            ($email ? "Från: {$email}\n" : '') .
            "\nMeddelande:\n{$message}\n\n" .
            "Granska: " . home_url('/medlemmar/kommentarer/')
        );
    }

    wp_send_json_success(['message' => 'Din överklagan har skickats till moderatorerna.']);
}
