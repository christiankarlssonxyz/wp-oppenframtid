<?php
/**
 * inc/likes.php – Gilla-knapp på inlägg
 *
 * Inloggade användare kan gilla ett inlägg en gång.
 * Antalet gillar sparas i post meta.
 * Hanteras via AJAX så sidan inte laddas om.
 */

// Skydda blogtree_liked_by från REST API (innehåller user IDs = personuppgifter)
add_action('init', function () {
    register_post_meta('post', 'blogtree_liked_by', [
        'type'         => 'array',
        'single'       => true,
        'show_in_rest' => false,
        'auth_callback' => '__return_false',
    ]);
    register_post_meta('post', 'blogtree_likes', [
        'type'         => 'integer',
        'single'       => true,
        'show_in_rest' => false,
    ]);
});

// GDPR Art. 17 – rensa user ID från alla liked_by-listor vid kontoborttagning
add_action('delete_user', function (int $user_id): void {
    global $wpdb;
    $post_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'blogtree_liked_by' AND meta_value LIKE %s",
            '%' . $wpdb->esc_like('"' . $user_id . '"') . '%'
        )
    );
    foreach ($post_ids as $post_id) {
        $liked_by = (array) get_post_meta((int) $post_id, 'blogtree_liked_by', true);
        $liked_by = array_values(array_diff($liked_by, [$user_id]));
        update_post_meta((int) $post_id, 'blogtree_liked_by', $liked_by);
        update_post_meta((int) $post_id, 'blogtree_likes', count($liked_by));
    }
});

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
