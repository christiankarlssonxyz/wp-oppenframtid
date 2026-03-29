<?php
/**
 * inc/roles.php – Roller och behörigheter
 *
 * Roller:
 * - Prenumerant  (subscriber)  — standard, läsa/kommentera/gilla/följa/spara
 * - Skribent     (author)      — kan publicera egna inlägg
 * - Moderator    (custom)      — kan hantera kommentarer + granska insändare
 * - Administratör (administrator) — full kontroll, utser roller
 */

// ── Skapa Moderator-rollen vid temats aktivering ───────────────────────────────
add_action('after_switch_theme', 'blogtree_register_roles');

function blogtree_register_roles(): void {
    // Ta bort och återskapa för att säkerställa rätt behörigheter
    remove_role('moderator');

    add_role('moderator', 'Moderator', [
        'read'                   => true,
        'edit_posts'             => false,
        'delete_posts'           => false,
        'moderate_comments'      => true,
        'edit_others_posts'      => false,
        'delete_others_posts'    => false,
        'manage_categories'      => false,
        'upload_files'           => true,
        // Insändare: kan se och granska pending-inlägg
        'edit_published_posts'   => false,
        'publish_posts'          => false,
        'blogtree_moderate'      => true,   // Custom capability
    ]);
}

// Om rollen saknas (t.ex. vid ny installation utan tema-switch) ──────────────────
add_action('init', function () {
    if (!get_role('moderator')) {
        blogtree_register_roles();
    }
});

// ── Hjälpfunktioner ────────────────────────────────────────────────────────────
function blogtree_is_moderator(int $user_id = 0): bool {
    $user = $user_id ? get_user_by('id', $user_id) : wp_get_current_user();
    if (!$user) return false;
    return in_array('moderator', (array) $user->roles, true);
}

function blogtree_is_skribent(int $user_id = 0): bool {
    $user = $user_id ? get_user_by('id', $user_id) : wp_get_current_user();
    if (!$user) return false;
    return in_array('author', (array) $user->roles, true);
}

function blogtree_is_admin(int $user_id = 0): bool {
    $user = $user_id ? get_user_by('id', $user_id) : wp_get_current_user();
    if (!$user) return false;
    return $user->has_cap('manage_options');
}

function blogtree_can_manage_members(int $user_id = 0): bool {
    return blogtree_is_admin($user_id) || blogtree_is_moderator($user_id);
}

// ── AJAX: Ändra roll ───────────────────────────────────────────────────────────
add_action('wp_ajax_blogtree_change_role', function () {
    check_ajax_referer('blogtree_change_role', 'nonce');

    if (!blogtree_can_manage_members()) {
        wp_send_json_error('Behörighet saknas.');
    }

    $target_id   = (int) ($_POST['user_id'] ?? 0);
    $new_role    = sanitize_key($_POST['role'] ?? '');
    $allowed     = ['subscriber', 'author', 'moderator'];

    if (!$target_id || !in_array($new_role, $allowed, true)) {
        wp_send_json_error('Ogiltiga parametrar.');
    }

    $target = get_user_by('id', $target_id);
    if (!$target) {
        wp_send_json_error('Användaren hittades inte.');
    }

    // Moderatorer kan inte ändra andra moderatorer eller admins
    if (!blogtree_is_admin() && (blogtree_is_moderator($target_id) || blogtree_is_admin($target_id))) {
        wp_send_json_error('Du kan inte ändra den här användarens roll.');
    }

    // Ingen kan göra någon till admin via frontend
    $target->set_role($new_role);

    wp_send_json_success([
        'message'   => 'Roll uppdaterad.',
        'role'      => $new_role,
        'role_label' => blogtree_role_label($new_role),
    ]);
});

// ── AJAX: Inaktivera / ta bort konto (endast admin) ───────────────────────────
add_action('wp_ajax_blogtree_delete_user', function () {
    check_ajax_referer('blogtree_delete_user', 'nonce');

    if (!blogtree_is_admin()) {
        wp_send_json_error('Behörighet saknas.');
    }

    $target_id = (int) ($_POST['user_id'] ?? 0);
    if (!$target_id || $target_id === get_current_user_id()) {
        wp_send_json_error('Ogiltigt användar-ID.');
    }

    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_delete_user($target_id);

    wp_send_json_success(['message' => 'Användaren borttagen.']);
});

// ── Hjälp: rollnamn på svenska ─────────────────────────────────────────────────
function blogtree_role_label(string $role): string {
    return match($role) {
        'administrator' => 'Administratör',
        'moderator'     => 'Moderator',
        'author'        => 'Skribent',
        'subscriber'    => 'Prenumerant',
        default         => ucfirst($role),
    };
}

function blogtree_user_role_label(WP_User $user): string {
    $roles = (array) $user->roles;
    return blogtree_role_label($roles[0] ?? 'subscriber');
}
