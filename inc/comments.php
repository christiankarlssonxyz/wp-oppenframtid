<?php
/**
 * inc/comments.php – Kommentarsystem
 *
 * Hanterar:
 * - Avregistrering av WordPress standard comment-reply.js
 * - Eget svar-system via AJAX
 * - Svar visas nästlat under förälderkommentaren
 */

// Avregistrera WP:s inbyggda comment-reply script (vi hanterar det själva)
add_action('wp_enqueue_scripts', function () {
    wp_deregister_script('comment-reply');
}, 20);

// ── AJAX: posta kommentar/svar ─────────────────────────────────────────────────
add_action('wp_ajax_blogtree_post_comment',        'blogtree_handle_comment');
add_action('wp_ajax_nopriv_blogtree_post_comment', 'blogtree_handle_comment');

function blogtree_handle_comment(): void {
    check_ajax_referer('blogtree_comment', 'nonce');

    $post_id   = (int)    ($_POST['post_id']    ?? 0);
    $parent_id = (int)    ($_POST['parent_id']  ?? 0);
    $content   = wp_kses_post(wp_unslash($_POST['content'] ?? ''));
    $user_id   = get_current_user_id();

    if (!$post_id || !$content) {
        wp_send_json_error('Fyll i alla fält');
    }

    if (!is_user_logged_in()) {
        wp_send_json_error('Du måste vara inloggad för att kommentera');
    }

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
        wp_send_json_error('Kunde inte spara kommentaren');
    }

    wp_send_json_success(['comment_id' => $comment_id]);
}
