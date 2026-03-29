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
    add_image_size('blogtree-frontpage-banner', 1200, 500, true);
    add_image_size('blogtree-topic-banner', 1200, 400, true);

});

// ── Stäng av admin-toolbar för alla användare ──────────────────────────────────
add_filter('show_admin_bar', '__return_false');

// ── Startsida – färger i Customizer ──────────────────────────────────────────
add_action('customize_register', function ($wp_customize) {

    $wp_customize->add_section('blogtree_frontpage_colors', [
        'title'    => 'Startsida – färger',
        'priority' => 19,
    ]);

    $wp_customize->add_setting('blogtree_frontpage_color', [
        'default'           => '#2c7be5',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'blogtree_frontpage_color', [
        'label'   => 'Primärfärg (hero + hover)',
        'section' => 'blogtree_frontpage_colors',
    ]));

    $wp_customize->add_setting('blogtree_frontpage_gradient_color', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'blogtree_frontpage_gradient_color', [
        'label'       => 'Gradientfärg (hero)',
        'description' => 'Lämna tom för att använda enbart primärfärgen.',
        'section'     => 'blogtree_frontpage_colors',
    ]));
});

// ── Startsida – bannerbild i Customizer ───────────────────────────────────────
add_action('customize_register', function ($wp_customize) {

    $wp_customize->add_section('blogtree_frontpage_banner', [
        'title'       => 'Startsida – bannerbild',
        'description' => 'Bild som visas ovanför senaste inlägg. Rekommenderat format: 1 200 × 500 px (2,4:1). Ladda upp minst 1 200 px bred för bäst kvalitet.',
        'priority'    => 20,
    ]);

    $wp_customize->add_setting('blogtree_frontpage_banner_id', [
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'blogtree_frontpage_banner_id', [
        'label'     => 'Välj bild',
        'section'   => 'blogtree_frontpage_banner',
        'mime_type' => 'image',
    ]));

    $wp_customize->add_setting('blogtree_frontpage_banner_caption', [
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
    ]);
    $wp_customize->add_control('blogtree_frontpage_banner_caption', [
        'label'       => 'Bildtext',
        'description' => 'Stödjer HTML: &lt;strong&gt;, &lt;em&gt;, &lt;a href=""&gt;, &lt;br&gt;',
        'section'     => 'blogtree_frontpage_banner',
        'type'        => 'textarea',
    ]);
});

// ── Sidebar-textbox i Customizer ───────────────────────────────────────────────
add_action('customize_register', function ($wp_customize) {

    $wp_customize->add_section('blogtree_sidebar_text', [
        'title'    => 'Sidebar – textbox',
        'priority' => 25,
    ]);

    $wp_customize->add_setting('blogtree_sidebar_text_title', [
        'default'           => 'Integritet &amp; öppen källkod',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('blogtree_sidebar_text_title', [
        'label'   => 'Rubrik',
        'section' => 'blogtree_sidebar_text',
        'type'    => 'text',
    ]);

    $wp_customize->add_setting('blogtree_sidebar_text_body', [
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
    ]);
    $wp_customize->add_control('blogtree_sidebar_text_body', [
        'label'   => 'Text',
        'section' => 'blogtree_sidebar_text',
        'type'    => 'textarea',
    ]);
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
