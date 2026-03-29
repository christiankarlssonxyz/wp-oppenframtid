<?php
/**
 * inc/auth.php – Autentisering
 *
 * - Redirectar wp-login.php till egna sidor
 * - Skyddar admin-panelen för icke-admins
 * - Hanterar logout-redirect
 */

// ── Omdirigera wp-login.php till egna sidor ────────────────────────────────────
add_filter('login_url', function ($url, $redirect) {
    $page = get_page_by_path('logga-in');
    if (!$page) return $url;
    $login = home_url('/logga-in/');
    return $redirect ? add_query_arg('redirect_to', urlencode($redirect), $login) : $login;
}, 10, 2);

add_filter('register_url', function () {
    return home_url('/registrera/');
});

// ── Redirect efter utloggning ──────────────────────────────────────────────────
add_filter('logout_redirect', function () {
    return home_url('/');
});

// ── Redirect inloggad användare som besöker wp-login.php ──────────────────────
add_action('init', function () {
    if (is_admin() || wp_doing_ajax()) return;

    $uri    = $_SERVER['REQUEST_URI'] ?? '';
    $action = $_GET['action'] ?? '';

    // Låt logout-länken gå igenom wp-login.php ostörd
    if (str_contains($uri, 'wp-login.php') && $action !== 'logout' && is_user_logged_in()) {
        wp_safe_redirect(home_url('/konto/'));
        exit;
    }
});

// ── Blockera wp-admin för icke-admins (utom AJAX och admin-post.php) ──────────
add_action('admin_init', function () {
    if (wp_doing_ajax()) return;

    // admin-post.php hanterar frontend-formulär — låt det köra klart
    global $pagenow;
    if ($pagenow === 'admin-post.php') return;

    if (current_user_can('manage_options') || current_user_can('edit_posts') || current_user_can('moderate_comments')) return;

    wp_safe_redirect(home_url('/konto/'));
    exit;
});

// ── Hantera profiluppdatering ─────────────────────────────────────────────────
add_action('admin_post_blogtree_update_profile', function () {
    if (!is_user_logged_in()) {
        wp_safe_redirect(wp_login_url());
        exit;
    }
    if (!wp_verify_nonce($_POST['blogtree_profile_nonce'] ?? '', 'blogtree_update_profile')) {
        wp_safe_redirect(add_query_arg('fel', '1', home_url('/konto/')));
        exit;
    }

    $user_id = get_current_user_id();
    $data    = ['ID' => $user_id];

    if (!empty($_POST['display_name'])) {
        $data['display_name'] = sanitize_text_field($_POST['display_name']);
    }
    if (!empty($_POST['user_email']) && is_email($_POST['user_email'])) {
        $data['user_email'] = sanitize_email($_POST['user_email']);
    }
    if (!empty($_POST['pass1']) && $_POST['pass1'] === ($_POST['pass2'] ?? '')) {
        $data['user_pass'] = $_POST['pass1'];
    }

    $password_changed = isset($data['user_pass']);
    $result           = wp_update_user($data);

    // Lösenordsbyte ogiltigförklarar sessionen — sätt ny auth-cookie direkt
    if (!is_wp_error($result) && $password_changed) {
        wp_set_auth_cookie($user_id, true, is_ssl());
    }

    // Profilbild (fallback om AJAX-upload inte användes)
    if (!empty($_FILES['avatar_file']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $att_id = media_handle_upload('avatar_file', 0);
        if (!is_wp_error($att_id)) {
            update_user_meta($user_id, 'blogtree_avatar', $att_id);
        }
    }

    if (is_wp_error($result)) {
        wp_safe_redirect(add_query_arg('fel', '1', home_url('/konto/')));
    } else {
        wp_safe_redirect(add_query_arg('uppdaterad', '1', home_url('/konto/')));
    }
    exit;
});

// ── Hantera moderatorinställningar ────────────────────────────────────────────
add_action('admin_post_blogtree_save_mod_settings', function () {
    if (!is_user_logged_in() || !blogtree_can_manage_members()) {
        wp_safe_redirect(home_url('/konto/'));
        exit;
    }
    if (!wp_verify_nonce($_POST['blogtree_mod_nonce'] ?? '', 'blogtree_mod_settings')) {
        wp_safe_redirect(add_query_arg('fel', '1', home_url('/konto/')));
        exit;
    }
    $value = !empty($_POST['mod_notifications']) ? '1' : '0';
    update_user_meta(get_current_user_id(), 'blogtree_mod_notifications', $value);
    wp_safe_redirect(add_query_arg('uppdaterad', '1', home_url('/konto/')));
    exit;
});

// ── Hantera inloggningsformuläret ──────────────────────────────────────────────
add_action('template_redirect', function () {
    if (!is_page('logga-in') || !isset($_POST['blogtree_login_nonce'])) return;
    if (!wp_verify_nonce($_POST['blogtree_login_nonce'], 'blogtree_login')) return;

    $username = sanitize_user($_POST['log'] ?? '');
    $password = $_POST['pwd'] ?? '';
    $remember = !empty($_POST['rememberme']);
    $redirect = esc_url_raw($_POST['redirect_to'] ?? home_url('/konto/'));

    $user = wp_signon([
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember,
    ], is_ssl());

    if (is_wp_error($user)) {
        wp_safe_redirect(add_query_arg('fel', '1', wp_login_url()));
        exit;
    }

    wp_safe_redirect($redirect);
    exit;
});

// ── Hantera registreringsformuläret ───────────────────────────────────────────
add_action('template_redirect', function () {
    if (!is_page('registrera') || !isset($_POST['blogtree_register_nonce'])) return;
    if (!wp_verify_nonce($_POST['blogtree_register_nonce'], 'blogtree_register')) return;

    if (!get_option('users_can_register')) {
        update_option('users_can_register', 1);
    }

    $username = sanitize_user($_POST['user_login'] ?? '');
    $email    = sanitize_email($_POST['user_email'] ?? '');
    $password = $_POST['user_pass'] ?? '';
    $errors   = [];

    if (empty($username)) $errors[] = 'tom_anvandare';
    if (empty($email) || !is_email($email)) $errors[] = 'ogiltig_epost';
    if (strlen($password) < 8) $errors[] = 'kort_losenord';
    if (username_exists($username)) $errors[] = 'anvandare_finns';
    if (email_exists($email)) $errors[] = 'epost_finns';

    if ($errors) {
        wp_safe_redirect(add_query_arg('fel', implode(',', $errors), home_url('/registrera/')));
        exit;
    }

    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        wp_safe_redirect(add_query_arg('fel', 'okant', home_url('/registrera/')));
        exit;
    }

    // Sätt standardroll
    $user = new WP_User($user_id);
    $user->set_role('subscriber');

    // Logga in direkt
    wp_signon([
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => false,
    ], is_ssl());

    wp_safe_redirect(home_url('/konto/'));
    exit;
});
