<?php
/**
 * inc/follow.php – Följ ämnen
 *
 * Inloggade användare kan följa ämnen.
 * Följda ämnen sparas i user meta.
 * Hanteras via AJAX.
 */

// GDPR Art. 17 – rensa följda ämnen vid kontoborttagning
add_action('delete_user', function (int $user_id): void {
    delete_user_meta($user_id, 'blogtree_followed_topics');
});

add_action('wp_ajax_blogtree_follow_topic', 'blogtree_handle_follow');

function blogtree_handle_follow(): void {
    check_ajax_referer('blogtree_follow', 'nonce');

    $term_id = (int) ($_POST['term_id'] ?? 0);
    $user_id = get_current_user_id();

    if (!$term_id || !$user_id) {
        wp_send_json_error('Ogiltig förfrågan');
    }

    $followed = (array) get_user_meta($user_id, 'blogtree_followed_topics', true);

    if (in_array($term_id, $followed)) {
        // Sluta följa
        $followed = array_values(array_diff($followed, [$term_id]));
        $following = false;
    } else {
        // Börja följa
        $followed[] = $term_id;
        $following = true;
    }

    update_user_meta($user_id, 'blogtree_followed_topics', $followed);

    wp_send_json_success(['following' => $following]);
}
