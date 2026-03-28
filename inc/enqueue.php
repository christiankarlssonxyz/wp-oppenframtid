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
    var toggleBtn   = document.getElementById('theme-toggle');
    var icon        = toggleBtn ? toggleBtn.querySelector('.theme-toggle__icon')  : null;
    var label       = toggleBtn ? toggleBtn.querySelector('.theme-toggle__label') : null;
    var stored      = localStorage.getItem('blogtree-theme');
    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    var isDark      = stored === 'dark' || (!stored && prefersDark);

    function applyTheme(dark) {
        document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
        if (icon)  icon.textContent  = dark ? '🌙' : '☀️';
        if (label) label.textContent = dark ? 'Mörkt läge' : 'Ljust läge';
    }

    applyTheme(isDark);

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            isDark = !isDark;
            localStorage.setItem('blogtree-theme', isDark ? 'dark' : 'light');
            applyTheme(isDark);
        });
    }
})();
    ");

});
