<aside class="sidebar">

    <!-- ── Om mig ─────────────────────────────────────────────────────────── -->
    <div class="sidebar-widget">
        <div class="sidebar-about">
            <?php echo get_avatar(1, 64, '', '', ['class' => 'sidebar-about__avatar']); ?>
            <div class="sidebar-about__text">
                <strong class="sidebar-about__name"><?php bloginfo('name'); ?></strong>
                <?php if (get_bloginfo('description')): ?>
                    <p class="sidebar-about__bio"><?php bloginfo('description'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Ämnen ──────────────────────────────────────────────────────────── -->
    <?php
    $topics = get_terms([
        'taxonomy'   => 'topic',
        'parent'     => 0,
        'hide_empty' => true,
        'number'     => 10,
    ]);
    if ($topics && !is_wp_error($topics)):
        $current_term = (is_tax('topic') || is_singular('post')) ? get_queried_object() : null;
        $current_term_id = ($current_term instanceof WP_Term && $current_term->taxonomy === 'topic')
            ? $current_term->term_id : 0;
    ?>
    <div class="sidebar-widget">
        <h3 class="sidebar-widget__title">Ämnen</h3>
        <ul class="sidebar-topics">
            <?php foreach ($topics as $topic):
                $color     = get_term_meta($topic->term_id, 'wpblogtree_topic_color', true) ?: '#2c3e50';
                $is_active = ($topic->term_id === $current_term_id);
            ?>
            <li>
                <a href="<?php echo esc_url(get_term_link($topic)); ?>"
                   class="sidebar-topics__link<?php echo $is_active ? ' is-active' : ''; ?>"
                   style="--topic-color: <?php echo esc_attr($color); ?>">
                    <?php echo esc_html($topic->name); ?>
                    <span class="sidebar-topics__count"><?php echo $topic->count; ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- ── Senaste inlägg ─────────────────────────────────────────────────── -->
    <?php
    $recent = new WP_Query([
        'posts_per_page'      => 5,
        'ignore_sticky_posts' => true,
    ]);
    if ($recent->have_posts()): ?>
    <div class="sidebar-widget">
        <h3 class="sidebar-widget__title">Senaste inlägg</h3>
        <ul class="sidebar-posts">
            <?php while ($recent->have_posts()): $recent->the_post(); ?>
            <li class="sidebar-posts__item">
                <a href="<?php the_permalink(); ?>" class="sidebar-posts__link">
                    <?php the_title(); ?>
                </a>
                <time class="sidebar-posts__date"><?php echo get_the_date(); ?></time>
            </li>
            <?php endwhile; wp_reset_postdata(); ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- ── Sociala medier ─────────────────────────────────────────────────── -->
    <?php
    $socials = [
        'mastodon' => ['label' => 'Mastodon',  'icon' => 'M21.327 8.566c0-4.339-2.843-5.61-2.843-5.61-1.433-.658-3.894-.935-6.451-.956h-.062c-2.557.021-5.016.298-6.45.956 0 0-2.843 1.271-2.843 5.61 0 .993-.019 2.181.012 3.441.103 4.243.778 8.425 4.701 9.463 1.809.479 3.362.579 4.612.51 2.268-.126 3.541-.809 3.541-.809l-.075-1.646s-1.621.511-3.441.449c-1.804-.062-3.707-.194-3.999-2.409a4.523 4.523 0 0 1-.04-.621s1.77.433 4.014.536c1.372.063 2.658-.08 3.965-.236 2.506-.299 4.688-1.843 4.962-3.254.434-2.223.398-5.424.398-5.424zm-3.353 5.59h-2.081V9.057c0-1.075-.452-1.62-1.357-1.62-1 0-1.501.647-1.501 1.927v2.791h-2.069V9.364c0-1.28-.501-1.927-1.502-1.927-.905 0-1.357.545-1.357 1.62v5.099H5.026V8.903c0-1.074.273-1.927.823-2.558.567-.631 1.307-.955 2.228-.955 1.065 0 1.872.409 2.405 1.228l.518.869.519-.869c.533-.819 1.34-1.228 2.4-1.228.92 0 1.66.324 2.233.955.549.631.822 1.484.822 2.558v5.253z'],
        'threads'  => ['label' => 'Threads',   'icon' => 'M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.5 12.068V12c.024-6.75 4.834-11.5 10.686-11.5h.007c3.281.024 6.084 1.205 8.1 3.423 1.874 2.06 2.843 4.802 2.868 8.077v.067c-.025 3.275-.994 6.017-2.868 8.077-2.016 2.218-4.819 3.399-8.1 3.423l-.007-.567zm-.017-3.856c2.194 0 3.94-.656 5.19-1.951 1.203-1.246 1.831-2.991 1.863-5.186-.032-2.219-.66-3.964-1.863-5.21-1.25-1.295-2.996-1.951-5.19-1.951-1.922 0-3.55.572-4.842 1.7-1.266 1.105-1.961 2.618-2.066 4.497.105 1.879.8 3.392 2.066 4.497 1.292 1.128 2.92 1.7 4.842 1.7l-.007-.096z'],
        'x'        => ['label' => 'X',         'icon' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.736l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z'],
        'github'   => ['label' => 'GitHub',    'icon' => 'M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.042-1.416-4.042-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z'],
        'linkedin' => ['label' => 'LinkedIn',  'icon' => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z'],
    ];
    $has_social = false;
    foreach ($socials as $key => $_) {
        if (get_theme_mod('blogtree_' . $key)) { $has_social = true; break; }
    }
    if ($has_social): ?>
    <div class="sidebar-widget">
        <h3 class="sidebar-widget__title">Följ mig</h3>
        <div class="sidebar-social">
            <?php foreach ($socials as $key => $data):
                $url = get_theme_mod('blogtree_' . $key);
                if (!$url) continue; ?>
                <a href="<?php echo esc_url($url); ?>"
                   class="sidebar-social__link"
                   aria-label="<?php echo esc_attr($data['label']); ?>"
                   target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true">
                        <path d="<?php echo esc_attr($data['icon']); ?>"/>
                    </svg>
                    <?php echo esc_html($data['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</aside>
