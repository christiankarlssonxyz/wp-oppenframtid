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
    <div class="site-header__inner">

        <a href="<?php echo esc_url(home_url('/')); ?>" class="site-header__logo">
            <?php bloginfo('name'); ?>
        </a>

        <button class="search-trigger" id="search-trigger" aria-label="Öppna sökning" aria-expanded="false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <span>Sök</span>
        </button>

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
        </nav>

    </div>
</header>

<!-- ── Sökoverlay ──────────────────────────────────────────────────────────── -->
<div class="search-overlay" id="search-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Sökning">
    <div class="search-overlay__backdrop" id="search-backdrop"></div>
    <div class="search-overlay__panel">

        <div class="search-overlay__top">
            <h2 class="search-overlay__heading">Sök</h2>
            <button class="search-overlay__close" id="search-close" aria-label="Stäng sökning">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <input type="search" id="search-input" class="search-overlay__input"
               placeholder="Skriv för att söka…" autocomplete="off" spellcheck="false" aria-label="Sökterm">

        <div class="search-filter-box">
            <h3 class="search-filter-box__title">Inlägg</h3>
            <div class="search-filter-box__row">
                <div class="search-filter-col">
                    <label class="search-filter__label" for="search-sort">Sortering</label>
                    <select id="search-sort" class="search-filter__select">
                        <option value="newest">Nyast</option>
                        <option value="oldest">Äldst</option>
                    </select>
                </div>
                <div class="search-filter-col">
                    <label class="search-filter__label" for="search-date">Datum</label>
                    <select id="search-date" class="search-filter__select">
                        <option value="">När som helst</option>
                        <option value="today">Idag</option>
                        <option value="week">Senaste veckan</option>
                        <option value="month">Senaste månaden</option>
                        <option value="halfyear">6 månader</option>
                    </select>
                </div>
                <div class="search-filter-col search-filter-col--check">
                    <label class="search-filter__checkbox">
                        <input type="checkbox" id="search-title-only">
                        <span>Sök endast rubriker</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="search-results" id="search-results" aria-live="polite" aria-atomic="true"></div>

    </div>
</div>
