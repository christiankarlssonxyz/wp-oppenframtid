<?php
/**
 * inc/enqueue.php – Laddar CSS och JavaScript
 *
 * Alla stilar och skript registreras här.
 * Versionen är temats version – ändra den när du uppdaterar CSS/JS
 * så att webbläsaren inte cachar gamla filer.
 */

add_action('wp_enqueue_scripts', function () {

    $theme   = wp_get_theme();
    $version = $theme->get('Version');
    $uri     = get_template_directory_uri();

    // ── CSS ───────────────────────────────────────────────────────────────────
    wp_enqueue_style('blogtree-base',       $uri . '/assets/css/base.css',       [], $version);
    wp_enqueue_style('blogtree-layout',     $uri . '/assets/css/layout.css',     ['blogtree-base'], $version);
    wp_enqueue_style('blogtree-components', $uri . '/assets/css/components.css', ['blogtree-layout'], $version);

    // ── JS ────────────────────────────────────────────────────────────────────
    // Gilla-knapp
    wp_enqueue_script('blogtree-likes', $uri . '/assets/js/likes.js', [], $version, true);
    wp_localize_script('blogtree-likes', 'blogtreeAjax', [
        'url'   => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('blogtree_like'),
    ]);

    // Följ ämne
    wp_enqueue_script('blogtree-follow', $uri . '/assets/js/follow.js', [], $version, true);
    wp_localize_script('blogtree-follow', 'blogtreeFollowAjax', [
        'url'   => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('blogtree_follow'),
    ]);

    // Spara inlägg + kopiera länk (single + konto/sparade)
    if (is_singular('post') || is_page(['konto-sparade'])) {
        wp_enqueue_script('blogtree-saved', $uri . '/assets/js/saved.js', [], $version, true);
        if (is_user_logged_in()) {
            wp_localize_script('blogtree-saved', 'blogtreeSaved', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('blogtree_save'),
            ]);
        }
    }

    // Kommentarer + rapportera kommentar
    if (is_singular('post')) {
        wp_enqueue_script('blogtree-comments', $uri . '/assets/js/comments.js', [], $version, true);
        $current_user = wp_get_current_user();
        wp_localize_script('blogtree-comments', 'blogtreeComments', [
            'ajaxurl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('blogtree_comment'),
            'loggedIn'    => is_user_logged_in(),
            'displayName' => $current_user->display_name ?? '',
            'avatarUrl'   => get_avatar_url($current_user->ID, ['size' => 36]),
        ]);

        wp_enqueue_script('blogtree-reports', $uri . '/assets/js/reports.js', [], $version, true);
    }

    // Obesvarade kommentarer – admin
    if (is_page_template('page-medlemmar-kommentarer-obesvarade.php') && current_user_can('manage_options')) {
        $current_user = wp_get_current_user();
        wp_enqueue_script('blogtree-admin-comments', $uri . '/assets/js/admin-comments.js', [], $version, true);
        wp_localize_script('blogtree-admin-comments', 'blogtreeAdminComments', [
            'ajaxurl' => admin_url('admin-ajax.php'),
        ]);
        wp_enqueue_script('blogtree-comments', $uri . '/assets/js/comments.js', [], $version, true);
        wp_localize_script('blogtree-comments', 'blogtreeComments', [
            'ajaxurl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('blogtree_comment'),
            'loggedIn'    => true,
            'displayName' => $current_user->display_name,
            'avatarUrl'   => get_avatar_url($current_user->ID, ['size' => 36]),
        ]);
    }

    // Kontosidor
    if (is_page('konto') && is_user_logged_in()) {
        wp_enqueue_script('blogtree-konto', $uri . '/assets/js/konto.js', [], $version, true);
        wp_localize_script('blogtree-konto', 'blogtreeKonto', [
            'ajaxurl'     => admin_url('admin-ajax.php'),
            'avatarNonce' => wp_create_nonce('blogtree_avatar'),
        ]);
    }

    // Mikroinlägg – skriva-sida
    if (is_page('skriva') || is_singular('mikroinlagg') || is_post_type_archive('mikroinlagg')) {
        wp_enqueue_script('blogtree-mikro', $uri . '/assets/js/mikroinlagg.js', [], $version, true);
        wp_localize_script('blogtree-mikro', 'blogtreeMikro', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('blogtree_mikro_save'),
        ]);
    }

    // Sökning
    wp_enqueue_script('blogtree-search', $uri . '/assets/js/search.js', [], $version, true);
    wp_localize_script('blogtree-search', 'blogtreeSearch', [
        'restUrl' => esc_url_raw(rest_url()),
    ]);

    // Användarmeny dropdown + mörkt/ljust läge
    wp_add_inline_script('blogtree-follow', "
(function () {
    // ── Dropdown ──────────────────────────────────────────────────────────────
    var btn  = document.querySelector('.nav-avatar');
    var menu = document.querySelector('.nav-user__menu');
    if (btn && menu) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = menu.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open);
        });
        document.addEventListener('click', function () {
            menu.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        });
        menu.addEventListener('click', function (e) { e.stopPropagation(); });
    }

    // ── Mörkt/Ljust läge ──────────────────────────────────────────────────────
    var stored      = localStorage.getItem('blogtree-theme');
    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    var isDark      = stored === 'dark' || (!stored && prefersDark);

    document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');

    document.querySelectorAll('#theme-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            isDark = !isDark;
            localStorage.setItem('blogtree-theme', isDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
        });
    });
})();
    ");

});
