<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="site-header__inner">

        <a href="<?php echo esc_url(home_url('/')); ?>" class="site-header__logo">
            <?php bloginfo('name'); ?>
        </a>

        <button class="site-header__menu-btn" aria-label="Meny" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <nav class="site-header__nav" aria-label="Huvudmeny">
            <?php wp_nav_menu([
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'nav-list',
                'fallback_cb'    => false,
            ]); ?>

            <?php if (is_user_logged_in()):
                $user = wp_get_current_user(); ?>
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('profil'))); ?>" class="nav-avatar">
                    <?php echo get_avatar($user->ID, 32); ?>
                </a>
            <?php else: ?>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="nav-link nav-link--login">Logga in</a>
            <?php endif; ?>
        </nav>

    </div>
</header>
