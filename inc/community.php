<?php
/**
 * inc/community.php – Insändare
 *
 * Inloggade användare kan skicka in egna inlägg.
 * Inläggen sparas som "pending" (granskning krävs) och
 * publiceras av admin.
 *
 * Hanteras via AJAX från en insändar-sida.
 */

add_action('wp_ajax_blogtree_submit_post', 'blogtree_handle_submission');

function blogtree_handle_submission(): void {
    check_ajax_referer('blogtree_submit', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error('Du måste vara inloggad');
    }

    $title   = sanitize_text_field(wp_unslash($_POST['title']   ?? ''));
    $content = wp_kses_post(wp_unslash($_POST['content'] ?? ''));
    $topic   = (int) ($_POST['topic'] ?? 0);

    if (!$title || !$content) {
        wp_send_json_error('Titel och innehåll krävs');
    }

    $post_id = wp_insert_post([
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'pending',      // Väntar på granskning
        'post_author'  => get_current_user_id(),
        'post_type'    => 'post',
    ]);

    if (is_wp_error($post_id)) {
        wp_send_json_error('Kunde inte spara inlägget');
    }

    // Koppla till ämne om valt
    if ($topic) {
        wp_set_post_terms($post_id, [$topic], 'topic');
    }

    wp_send_json_success(['message' => 'Tack! Din insändare har skickats och granskas innan publicering.']);
}
