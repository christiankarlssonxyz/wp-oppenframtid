<?php
/**
 * inc/saved.php – Spara inlägg
 *
 * Inloggade användare kan spara/ta bort inlägg.
 * Sparade post-IDs lagras i user_meta (blogtree_saved_posts).
 * Hanteras via AJAX.
 */

// GDPR – rensa sparade inlägg vid kontoborttagning
add_action('delete_user', function (int $user_id): void {
    delete_user_meta($user_id, 'blogtree_saved_posts');
});

// ── Lokalisera nonce till JS ───────────────────────────────────────────────────
// Lokalisering hanteras i inc/enqueue.php tillsammans med script-registreringen

// ── AJAX: Spara / ta bort ─────────────────────────────────────────────────────
add_action('wp_ajax_blogtree_save_post', function () {
    check_ajax_referer('blogtree_save', 'nonce');

    $post_id = (int) ($_POST['post_id'] ?? 0);
    $user_id = get_current_user_id();

    if (!$post_id || !$user_id) {
        wp_send_json_error('Ogiltig förfrågan.');
    }

    $saved  = (array) get_user_meta($user_id, 'blogtree_saved_posts', true);
    $saved  = array_map('intval', $saved);

    if (in_array($post_id, $saved, true)) {
        $saved  = array_values(array_diff($saved, [$post_id]));
        $is_saved = false;
    } else {
        $saved[]  = $post_id;
        $is_saved = true;
    }

    update_user_meta($user_id, 'blogtree_saved_posts', $saved);

    wp_send_json_success(['saved' => $is_saved]);
});
