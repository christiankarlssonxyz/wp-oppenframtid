<?php
/**
 * inc/likes.php – Gilla-knapp på inlägg
 *
 * Inloggade användare kan gilla ett inlägg en gång.
 * Antalet gillar sparas i post meta.
 * Hanteras via AJAX så sidan inte laddas om.
 */

add_action('wp_ajax_blogtree_like', 'blogtree_handle_like');

function blogtree_handle_like(): void {
    check_ajax_referer('blogtree_like', 'nonce');

    $post_id = (int) ($_POST['post_id'] ?? 0);
    $user_id = get_current_user_id();

    if (!$post_id || !$user_id) {
        wp_send_json_error('Ogiltig förfrågan');
    }

    $liked_by = (array) get_post_meta($post_id, 'blogtree_liked_by', true);

    if (in_array($user_id, $liked_by)) {
        // Ta bort gilla
        $liked_by = array_values(array_diff($liked_by, [$user_id]));
        $liked = false;
    } else {
        // Lägg till gilla
        $liked_by[] = $user_id;
        $liked = true;
    }

    update_post_meta($post_id, 'blogtree_liked_by', $liked_by);
    update_post_meta($post_id, 'blogtree_likes', count($liked_by));

    wp_send_json_success([
        'liked' => $liked,
        'count' => count($liked_by),
    ]);
}
