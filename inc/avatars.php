<?php
/**
 * inc/avatars.php – Lokala avatarer
 *
 * Ersätter Gravatar med en lokal bild som användaren laddar upp
 * på sin profilsida. Max 1 MB, endast JPG/PNG/GIF/WebP tillåts.
 *
 * Avataren sparas i user meta som en WordPress attachment-ID.
 */

// ── Visa lokal avatar om den finns, annars Gravatar ───────────────────────────
add_filter('pre_get_avatar_data', function ($args, $id_or_email) {
    $user_id = 0;

    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
    } elseif ($id_or_email instanceof WP_User) {
        $user_id = $id_or_email->ID;
    } elseif ($id_or_email instanceof WP_Comment) {
        $user_id = (int) $id_or_email->user_id;
    }

    if (!$user_id) return $args;

    $attachment_id = (int) get_user_meta($user_id, 'blogtree_avatar', true);
    if (!$attachment_id) return $args;

    $url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
    if (!$url) return $args;

    $args['url']          = $url;
    $args['found_avatar'] = true;

    return $args;
}, 10, 2);

// ── Ladda upp avatar via AJAX ──────────────────────────────────────────────────
add_action('wp_ajax_blogtree_upload_avatar', 'blogtree_handle_avatar_upload');

function blogtree_handle_avatar_upload(): void {
    check_ajax_referer('blogtree_avatar', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error('Inte inloggad');
    }

    if (empty($_FILES['avatar'])) {
        wp_send_json_error('Ingen fil uppladdad');
    }

    $file = $_FILES['avatar'];

    // Kontrollera filstorlek (max 1 MB)
    if ($file['size'] > 1 * 1024 * 1024) {
        wp_send_json_error('Filen är för stor. Max 1 MB.');
    }

    // Kontrollera MIME-typ med finfo (säkrare än filändelse)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mime     = $finfo->file($file['tmp_name']);
    $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($mime, $allowed, true)) {
        wp_send_json_error('Ogiltigt filformat. Tillåtna: JPG, PNG, GIF, WebP.');
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $attachment_id = media_handle_upload('avatar', 0);

    if (is_wp_error($attachment_id)) {
        wp_send_json_error('Uppladdning misslyckades: ' . $attachment_id->get_error_message());
    }

    update_user_meta(get_current_user_id(), 'blogtree_avatar', $attachment_id);

    wp_send_json_success([
        'url' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
    ]);
}
