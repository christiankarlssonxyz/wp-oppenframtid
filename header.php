<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <script>
    (function(){
        var t = localStorage.getItem('blogtree-theme');
        var d = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.setAttribute('data-theme', d ? 'dark' : 'light');
    })();
    </script>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">

    <!-- ── Rad 1: Logo + Sök + Avatar ───────────────────────────────────────── -->
    <div class="site-header__inner">

        <a href="<?php echo esc_url(home_url('/')); ?>" class="site-header__logo">
            <?php bloginfo('name'); ?>
        </a>

        <button class="site-header__menu-btn" aria-label="Meny" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <div class="site-header__actions">
            <a href="<?php echo esc_url(home_url('/sok/')); ?>" class="search-trigger" aria-label="Gå till söksidan">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <span>Sök</span>
            </a>

            <?php if (is_user_logged_in()):
                $user        = wp_get_current_user();
                $is_admin    = current_user_can('manage_options');
                $profile_url = esc_url(get_permalink(get_page_by_path('profil')));
                $new_post    = esc_url(admin_url('post-new.php'));
                $logout_url  = esc_url(wp_logout_url(home_url()));
                $admin_url   = esc_url(admin_url()); ?>
                <div class="nav-user" aria-haspopup="true">
                    <button class="nav-avatar" aria-expanded="false" aria-label="Användarmeny">
                        <?php echo get_avatar($user->ID, 32); ?>
                    </button>
                    <div class="nav-user__menu" role="menu">

                        <div class="nav-user__greeting">Hej <?php echo esc_html($user->display_name); ?></div>

                        <div class="nav-user__section">
                            <a href="<?php echo $profile_url; ?>" class="nav-user__account-btn">Mitt konto</a>
                        </div>

                        <div class="nav-user__divider"></div>

                        <div class="nav-user__heading">Mitt innehåll</div>
                        <button class="nav-user__item nav-user__theme-toggle" id="theme-toggle" aria-label="Byt tema">
                            <span class="theme-toggle__icon">☀️</span>
                            <span class="theme-toggle__label">Ljust läge</span>
                        </button>

                        <?php if ($is_admin): ?>
                        <div class="nav-user__divider"></div>
                        <div class="nav-user__heading">Admin</div>
                        <a href="<?php echo $new_post; ?>" class="nav-user__item" role="menuitem">Skriv nytt inlägg</a>
                        <?php endif; ?>

                        <div class="nav-user__divider"></div>
                        <a href="<?php echo $logout_url; ?>" class="nav-user__item nav-user__item--logout" role="menuitem">Logga ut</a>

                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="nav-link nav-link--login">Logga in</a>
            <?php endif; ?>
        </div>

    </div>

    <!-- ── Rad 2: Centrerad nav (desktop) / Mobilmeny ───────────────────────── -->
    <div class="site-header__nav-row">
        <nav class="site-header__nav" aria-label="Huvudmeny">
            <?php wp_nav_menu([
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'nav-list',
                'fallback_cb'    => false,
            ]); ?>
        </nav>
    </div>

</header>

