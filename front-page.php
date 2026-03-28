<?php
/**
 * front-page.php – Startsidan
 *
 * Ny besökare (ingen kaka):
 *   – Full hero: vem är du, vad är sidan, för vem
 *   – Fokusområden (ämnen)
 *   – Senaste inlägg
 *
 * Återkommande besökare (kaka finns):
 *   – Kompakt hälsning
 *   – Inlägg sedan senaste besök
 *   – Alla senaste inlägg
 *
 * Kakan "blogtree_visited" innehåller en Unix-tidsstämpel för senaste besök.
 * Den sätts/uppdateras innan get_header() så att inga headers har skickats ännu.
 */

// ── Läs och uppdatera kakan ────────────────────────────────────────────────────
$last_visit   = isset($_COOKIE['blogtree_visited']) ? (int) $_COOKIE['blogtree_visited'] : 0;
$is_returning = $last_visit > 0;

// ── Admin-användare (undviker hårdkodat user ID 1) ─────────────────────────────
$_admin_user = get_user_by('email', get_option('admin_email'));
$_admin_id   = $_admin_user ? $_admin_user->ID : 1;

// Spara senaste besök (1 år)
setcookie('blogtree_visited', time(), [
    'expires'  => time() + 365 * DAY_IN_SECONDS,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);

get_header();
?>

<?php if ($is_returning): ?>

    <!-- ════════════════════════════════════════════════════════════════════════
         ÅTERKOMMANDE BESÖKARE
         ════════════════════════════════════════════════════════════════════ -->

    <section class="returning-hero">
        <div class="container">
            <div class="returning-hero__inner">
                <?php echo get_avatar($_admin_id, 48, '', '', ['class' => 'returning-hero__avatar']); ?>
                <div>
                    <p class="returning-hero__welcome">Välkommen tillbaka</p>
                    <p class="returning-hero__since">
                        Senast här: <time datetime="<?php echo date('c', $last_visit); ?>">
                            <?php echo date_i18n('j F Y', $last_visit); ?>
                        </time>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="content-with-sidebar container">
    <div>

    <?php
    // ── Nytt sedan senaste besök ───────────────────────────────────────────
    $new_posts = new WP_Query([
        'posts_per_page'      => 10,
        'ignore_sticky_posts' => true,
        'date_query'          => [['after' => date('Y-m-d H:i:s', $last_visit)]],
    ]);

    if ($new_posts->have_posts()): ?>
    <section class="posts-section">
        <h2 class="section-title">Nytt sedan ditt senaste besök</h2>
        <div class="post-grid">
            <?php while ($new_posts->have_posts()): $new_posts->the_post();
                $topics      = get_the_terms(get_the_ID(), 'topic');
                $first_topic = (!is_wp_error($topics) && !empty($topics)) ? $topics[0] : null;
            ?>
            <article class="post-card">
                <?php if (has_post_thumbnail()): ?>
                <a href="<?php the_permalink(); ?>" class="post-card__image" tabindex="-1" aria-hidden="true">
                    <?php the_post_thumbnail('blogtree-card'); ?>
                </a>
                <?php endif; ?>
                <div class="post-card__body">
                    <?php if ($first_topic): ?>
                    <a href="<?php echo esc_url(get_term_link($first_topic)); ?>" class="post-card__topic">
                        <?php echo esc_html($first_topic->name); ?>
                    </a>
                    <?php endif; ?>
                    <h3 class="post-card__title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>
                    <p class="post-card__excerpt"><?php echo wp_trim_words(get_the_excerpt(), 15, ''); ?></p>
                    <time class="post-card__date" datetime="<?php echo get_the_date('c'); ?>">
                        <?php echo get_the_date(); ?>
                    </time>
                </div>
            </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Alla senaste inlägg -->
    <section class="posts-section">
        <h2 class="section-title">Senaste</h2>
        <?php
        $all_posts = new WP_Query([
            'posts_per_page'      => 9,
            'ignore_sticky_posts' => true,
        ]);
        if ($all_posts->have_posts()): ?>
        <div class="post-grid">
            <?php while ($all_posts->have_posts()): $all_posts->the_post();
                $topics      = get_the_terms(get_the_ID(), 'topic');
                $first_topic = (!is_wp_error($topics) && !empty($topics)) ? $topics[0] : null;
            ?>
            <article class="post-card">
                <?php if (has_post_thumbnail()): ?>
                <a href="<?php the_permalink(); ?>" class="post-card__image" tabindex="-1" aria-hidden="true">
                    <?php the_post_thumbnail('blogtree-card'); ?>
                </a>
                <?php endif; ?>
                <div class="post-card__body">
                    <?php if ($first_topic): ?>
                    <a href="<?php echo esc_url(get_term_link($first_topic)); ?>" class="post-card__topic">
                        <?php echo esc_html($first_topic->name); ?>
                    </a>
                    <?php endif; ?>
                    <h3 class="post-card__title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>
                    <p class="post-card__excerpt"><?php echo wp_trim_words(get_the_excerpt(), 15, ''); ?></p>
                    <time class="post-card__date" datetime="<?php echo get_the_date('c'); ?>">
                        <?php echo get_the_date(); ?>
                    </time>
                </div>
            </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <?php endif; ?>
    </section>

    </div>
    <?php get_sidebar('front'); ?>
    </div>

<?php else: ?>

    <!-- ════════════════════════════════════════════════════════════════════════
         NY BESÖKARE
         ════════════════════════════════════════════════════════════════════ -->

    <!-- ── Hero ──────────────────────────────────────────────────────────── -->
    <section class="hero">
        <div class="hero__inner">

            <div class="hero__profile">
                <?php if (has_custom_logo()):
                    the_custom_logo();
                else: ?>
                    <img src="<?php echo esc_url(get_avatar_url($_admin_id, ['size' => 200])); ?>"
                         alt="<?php bloginfo('name'); ?>"
                         class="hero__avatar">
                <?php endif; ?>
            </div>

            <div class="hero__text">
                <p class="hero__label">Personlig blogg & community</p>
                <h1 class="hero__name"><?php bloginfo('name'); ?></h1>
                <?php if (get_bloginfo('description')): ?>
                    <p class="hero__bio"><?php bloginfo('description'); ?></p>
                <?php endif; ?>

                <div class="hero__actions">
                    <a href="#amnen" class="btn btn--primary">Utforska ämnen</a>
                    <a href="#senaste" class="btn btn--ghost">Senaste inlägg</a>
                </div>

                <?php
                $socials = [
                    'mastodon' => 'Mastodon',
                    'threads'  => 'Threads',
                    'x'        => 'X',
                    'github'   => 'GitHub',
                    'linkedin' => 'LinkedIn',
                ];
                $social_icons = [
                    'mastodon' => 'M21.327 8.566c0-4.339-2.843-5.61-2.843-5.61-1.433-.658-3.894-.935-6.451-.956h-.062c-2.557.021-5.016.298-6.45.956 0 0-2.843 1.271-2.843 5.61 0 .993-.019 2.181.012 3.441.103 4.243.778 8.425 4.701 9.463 1.809.479 3.362.579 4.612.51 2.268-.126 3.541-.809 3.541-.809l-.075-1.646s-1.621.511-3.441.449c-1.804-.062-3.707-.194-3.999-2.409a4.523 4.523 0 0 1-.04-.621s1.77.433 4.014.536c1.372.063 2.658-.08 3.965-.236 2.506-.299 4.688-1.843 4.962-3.254.434-2.223.398-5.424.398-5.424zm-3.353 5.59h-2.081V9.057c0-1.075-.452-1.62-1.357-1.62-1 0-1.501.647-1.501 1.927v2.791h-2.069V9.364c0-1.28-.501-1.927-1.502-1.927-.905 0-1.357.545-1.357 1.62v5.099H5.026V8.903c0-1.074.273-1.927.823-2.558.567-.631 1.307-.955 2.228-.955 1.065 0 1.872.409 2.405 1.228l.518.869.519-.869c.533-.819 1.34-1.228 2.4-1.228.92 0 1.66.324 2.233.955.549.631.822 1.484.822 2.558v5.253z',
                    'threads'  => 'M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.5 12.068V12c.024-6.75 4.834-11.5 10.686-11.5h.007c3.281.024 6.084 1.205 8.1 3.423 1.874 2.06 2.843 4.802 2.868 8.077v.067c-.025 3.275-.994 6.017-2.868 8.077-2.016 2.218-4.819 3.399-8.1 3.423l-.007-.567zm-.017-3.856c2.194 0 3.94-.656 5.19-1.951 1.203-1.246 1.831-2.991 1.863-5.186-.032-2.219-.66-3.964-1.863-5.21-1.25-1.295-2.996-1.951-5.19-1.951-1.922 0-3.55.572-4.842 1.7-1.266 1.105-1.961 2.618-2.066 4.497.105 1.879.8 3.392 2.066 4.497 1.292 1.128 2.92 1.7 4.842 1.7l-.007-.096z',
                    'x'        => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.736l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z',
                    'github'   => 'M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.042-1.416-4.042-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z',
                    'linkedin' => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
                ];
                $has_social = false;
                foreach ($socials as $key => $_) {
                    if (get_theme_mod('blogtree_' . $key)) { $has_social = true; break; }
                }
                if ($has_social): ?>
                <div class="hero__social">
                    <?php foreach ($socials as $key => $label):
                        $url = get_theme_mod('blogtree_' . $key);
                        if (!$url) continue; ?>
                        <a href="<?php echo esc_url($url); ?>"
                           class="social-link"
                           aria-label="<?php echo esc_attr($label); ?>"
                           target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true">
                                <path d="<?php echo esc_attr($social_icons[$key]); ?>"/>
                            </svg>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <!-- ── Vad är det här stället? ───────────────────────────────────────── -->
    <section class="about-strip">
        <div class="container">
            <div class="about-strip__grid">
                <div class="about-strip__item">
                    <span class="about-strip__icon">✍️</span>
                    <h3>Personlig blogg</h3>
                    <p>Jag skriver om saker jag bryr mig om – fackligt, teknik och politik.</p>
                </div>
                <div class="about-strip__item">
                    <span class="about-strip__icon">🤝</span>
                    <h3>Community</h3>
                    <p>Du kan kommentera, gilla och följa ämnen. Inloggade kan skicka in egna inlägg.</p>
                </div>
                <div class="about-strip__item">
                    <span class="about-strip__icon">🔒</span>
                    <h3>Öppen & ärlig</h3>
                    <p>Inga spårare. Ingen reklam. Bara innehåll.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="content-with-sidebar container">
    <div>

    <!-- ── Fokusområden ───────────────────────────────────────────────────── -->
    <?php
    $topics = get_terms([
        'taxonomy'   => 'topic',
        'parent'     => 0,
        'hide_empty' => true,
        'number'     => 8,
    ]);
    if ($topics && !is_wp_error($topics)): ?>
    <section class="topics-section" id="amnen">
        <h2 class="section-title">Fokusområden</h2>
        <p class="section-sub">Det här skriver jag om</p>
        <div class="topics-grid">
            <?php foreach ($topics as $topic):
                $color = get_term_meta($topic->term_id, 'wpblogtree_topic_color', true) ?: '#2c3e50';
            ?>
            <a href="<?php echo esc_url(get_term_link($topic)); ?>"
               class="topic-card"
               style="--topic-color: <?php echo esc_attr($color); ?>">
                <span class="topic-card__name"><?php echo esc_html($topic->name); ?></span>
                <span class="topic-card__count"><?php echo $topic->count; ?> inlägg</span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── Senaste inlägg ─────────────────────────────────────────────────── -->
    <section class="posts-section" id="senaste">
        <h2 class="section-title">Senaste inlägg</h2>
        <?php
        $posts_query = new WP_Query([
            'posts_per_page'      => 6,
            'ignore_sticky_posts' => true,
        ]);
        if ($posts_query->have_posts()): ?>
        <div class="post-grid">
            <?php while ($posts_query->have_posts()): $posts_query->the_post();
                $topics      = get_the_terms(get_the_ID(), 'topic');
                $first_topic = (!is_wp_error($topics) && !empty($topics)) ? $topics[0] : null;
            ?>
            <article class="post-card">
                <?php if (has_post_thumbnail()): ?>
                <a href="<?php the_permalink(); ?>" class="post-card__image" tabindex="-1" aria-hidden="true">
                    <?php the_post_thumbnail('blogtree-card'); ?>
                </a>
                <?php endif; ?>
                <div class="post-card__body">
                    <?php if ($first_topic): ?>
                    <a href="<?php echo esc_url(get_term_link($first_topic)); ?>" class="post-card__topic">
                        <?php echo esc_html($first_topic->name); ?>
                    </a>
                    <?php endif; ?>
                    <h3 class="post-card__title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>
                    <p class="post-card__excerpt"><?php echo wp_trim_words(get_the_excerpt(), 15, ''); ?></p>
                    <time class="post-card__date" datetime="<?php echo get_the_date('c'); ?>">
                        <?php echo get_the_date(); ?>
                    </time>
                </div>
            </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <?php endif; ?>
    </section>

    </div>
    <?php get_sidebar('front'); ?>
    </div>

<?php endif; ?>

<?php get_footer(); ?>
