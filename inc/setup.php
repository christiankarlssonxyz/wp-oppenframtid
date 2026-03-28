<?php
/**
 * inc/setup.php – Grundinställningar för temat
 *
 * - Aktiverar WordPress-funktioner (titlar, menyer, bilder osv.)
 * - Registrerar navigationsmenyer
 * - Lägger till bildstorlekar
 * - Sociala länkfält i Customizer
 */

add_action('after_setup_theme', function () {

    // WordPress hanterar <title>-taggen
    add_theme_support('title-tag');

    // Stöd för inläggsbild (featured image)
    add_theme_support('post-thumbnails');

    // Stöd för logotyp i Customizer
    add_theme_support('custom-logo', [
        'height'      => 200,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ]);

    // HTML5-markup för formulär, sök osv.
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);

    // Registrera menyer
    register_nav_menus([
        'primary' => 'Huvudmeny',
        'footer'  => 'Sidfotsmeny',
    ]);

    // Bildstorlekar
    add_image_size('blogtree-card', 600, 400, true);
    add_image_size('blogtree-hero', 1200, 600, true);

});

// ── Sociala länkar i Customizer ────────────────────────────────────────────────
add_action('customize_register', function ($wp_customize) {

    $wp_customize->add_section('blogtree_social', [
        'title'    => 'Sociala medier',
        'priority' => 30,
    ]);

    $social_fields = [
        'mastodon' => 'Mastodon-URL',
        'threads'  => 'Threads-URL',
        'x'        => 'X / Twitter-URL',
        'github'   => 'GitHub-URL',
        'linkedin' => 'LinkedIn-URL',
    ];

    foreach ($social_fields as $key => $label) {
        $wp_customize->add_setting('blogtree_' . $key, ['sanitize_callback' => 'esc_url_raw']);
        $wp_customize->add_control('blogtree_' . $key, [
            'label'   => $label,
            'section' => 'blogtree_social',
            'type'    => 'url',
        ]);
    }
});
