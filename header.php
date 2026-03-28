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
                $user        = wp_get_current_user();
                $is_admin    = current_user_can('manage_options');
                $profile_url = esc_url(get_permalink(get_page_by_path('profil')));
                $new_post    = esc_url(admin_url('post-new.php'));
                $logout_url  = esc_url(wp_logout_url(home_url('/')));
                $admin_url   = esc_url(admin_url()); ?>
                <div class="nav-user" aria-haspopup="true">
                    <button class="nav-avatar" aria-expanded="false" aria-label="Användarmeny">
                        <?php echo get_avatar($user->ID, 32); ?>
                    </button>
                    <div class="nav-user__menu" role="menu">
                        <a href="<?php echo $profile_url; ?>" class="nav-user__item" role="menuitem">Användarprofil</a>
                        <?php if ($is_admin): ?>
                        <a href="<?php echo $admin_url; ?>" class="nav-user__item" role="menuitem">Admin</a>
                        <?php endif; ?>
                        <a href="<?php echo $new_post; ?>" class="nav-user__item" role="menuitem">Skriv nytt inlägg</a>
                        <div class="nav-user__divider"></div>
                        <a href="<?php echo $logout_url; ?>" class="nav-user__item nav-user__item--logout" role="menuitem">Logga ut</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="nav-link nav-link--login">Logga in</a>
            <?php endif; ?>
        </nav>

    </div>
</header>
