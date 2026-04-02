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

            <?php if (!is_user_logged_in()): ?>
            <button class="theme-mode-btn" id="theme-toggle" aria-label="Byt färgläge">
                <span class="theme-mode-btn__light" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                    <span>Ljust läge</span>
                </span>
                <span class="theme-mode-btn__dark" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                    <span>Mörkt läge</span>
                </span>
            </button>
            <?php endif; ?>

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
                            <a href="<?php echo esc_url(home_url('/konto/')); ?>" class="nav-user__account-btn">Mitt konto</a>
                        </div>

                        <?php if (current_user_can('manage_options')): ?>
                        <div class="nav-user__divider"></div>
                        <a href="<?php echo esc_url(home_url('/skriva/')); ?>" class="nav-user__item" role="menuitem">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            Skriv mikroinlägg
                        </a>
                        <?php endif; ?>

                        <div class="nav-user__divider"></div>

                        <div class="nav-user__heading">Mitt innehåll</div>
                        <a href="<?php echo esc_url(home_url('/konto/sparade/')); ?>" class="nav-user__item" role="menuitem">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                            Sparade inlägg
                        </a>
                        <a href="<?php echo esc_url(home_url('/konto/foljer/')); ?>" class="nav-user__item" role="menuitem">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                            Följer
                        </a>
                        <a href="<?php echo esc_url(home_url('/konto/nyhetsbrev/')); ?>" class="nav-user__item" role="menuitem">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            Nyhetsbrev
                        </a>
                        <div class="nav-user__divider"></div>
                        <div class="nav-user__heading">Inställningar</div>
                        <button class="nav-user__item nav-user__theme-toggle" id="theme-toggle" aria-label="Byt tema">
                            <span class="theme-toggle__icon">☀️</span>
                            <span class="theme-toggle__label">Ljust läge</span>
                        </button>

                        <?php if (blogtree_can_manage_members()): ?>
                        <div class="nav-user__divider"></div>
                        <div class="nav-user__heading">Hantering</div>
                        <a href="<?php echo esc_url(home_url('/medlemmar/')); ?>" class="nav-user__item" role="menuitem">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            Medlemmar
                        </a>
                        <a href="<?php echo esc_url(home_url('/medlemmar/kommentarer/')); ?>" class="nav-user__item" role="menuitem">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                            Kommentarer
                        </a>
                        <?php if ($is_admin): ?>
                        <a href="<?php echo $admin_url; ?>" class="nav-user__item" role="menuitem">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            WP Dashboard
                        </a>
                        <a href="<?php echo $new_post; ?>" class="nav-user__item" role="menuitem">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Skriv nytt inlägg
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>

                        <div class="nav-user__divider"></div>
                        <a href="<?php echo $logout_url; ?>" class="nav-user__item nav-user__item--logout" role="menuitem">Logga ut</a>

                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo esc_url(home_url('/registrera/')); ?>" class="nav-register-btn">Skapa konto</a>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="nav-login-btn">Logga in</a>
            <?php endif; ?>
        </div>

    </div>

    <!-- ── Rad 2: Centrerad nav (desktop) / Mobilmeny ───────────────────────── -->
    <div class="site-header__nav-row">
        <nav class="site-header__nav" aria-label="Huvudmeny">
            <button class="nav-mobile-close" aria-label="Stäng meny">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
            <?php wp_nav_menu([
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'nav-list',
                'fallback_cb'    => false,
            ]); ?>
            <a href="<?php echo esc_url(home_url('/mikroinlagg/')); ?>"
               class="nav-list__mikro-link<?php echo (is_post_type_archive('mikroinlagg') || get_post_type() === 'mikroinlagg') ? ' current-menu-item' : ''; ?>">Mikroinlägg</a>

            <?php if (!is_user_logged_in()): ?>
            <div class="nav-mobile-auth">
                <button class="theme-mode-btn" id="theme-toggle" aria-label="Byt färgläge">
                    <span class="theme-mode-btn__light" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <circle cx="12" cy="12" r="5"/>
                            <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                            <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                        </svg>
                        <span>Ljust läge</span>
                    </span>
                    <span class="theme-mode-btn__dark" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                        <span>Mörkt läge</span>
                    </span>
                </button>
                <div class="nav-mobile-auth__btns">
                    <a href="<?php echo esc_url(home_url('/registrera/')); ?>" class="nav-register-btn">Skapa konto</a>
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="nav-login-btn">Logga in</a>
                </div>
            </div>
            <?php endif; ?>
        </nav>
    </div>

</header>

