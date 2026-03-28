<aside class="sidebar">

    <!-- ── Integritetsgranskning ─────────────────────────────────────────────── -->
    <div class="sidebar-widget">
        <h3 class="sidebar-widget__title">Integritet &amp; öppen källkod</h3>
        <div class="integrity-badge">
            <div class="integrity-badge__header">
                <svg class="integrity-badge__shield" viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M12 2L3 6v6c0 5.25 3.75 10.15 9 11.25C17.25 22.15 21 17.25 21 12V6L12 2z"/>
                    <path d="M9 12l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div>
                    <strong class="integrity-badge__title">Granskad webbplats</strong>
                    <span class="integrity-badge__domain">christiankarlsson.xyz</span>
                </div>
            </div>
            <ul class="integrity-badge__list">
                <li>Inga spårare eller analytics</li>
                <li>Ingen reklam</li>
                <li>Öppen källkod på GitHub</li>
                <li>Inga tredjepartstjänster</li>
            </ul>
            <?php
            $privacy_page = get_page_by_path('integritetspolicy') ?: get_page_by_path('integritet') ?: get_page_by_path('privacy');
            if ($privacy_page): ?>
            <a href="<?php echo esc_url(get_permalink($privacy_page)); ?>" class="integrity-badge__link">
                Läs integritetspolicyn
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Fästa inlägg ──────────────────────────────────────────────────────── -->
    <?php
    $sticky_ids = get_option('sticky_posts', []);
    if (!empty($sticky_ids)):
        $sticky = new WP_Query([
            'post__in'            => $sticky_ids,
            'posts_per_page'      => 5,
            'ignore_sticky_posts' => true,
            'orderby'             => 'post__in',
        ]);
        if ($sticky->have_posts()): ?>
    <div class="sidebar-widget">
        <h3 class="sidebar-widget__title">Utvalda inlägg</h3>
        <ul class="sidebar-posts">
            <?php while ($sticky->have_posts()): $sticky->the_post(); ?>
            <li class="sidebar-posts__item">
                <a href="<?php the_permalink(); ?>" class="sidebar-posts__link">
                    <?php the_title(); ?>
                </a>
                <time class="sidebar-posts__date"><?php echo get_the_date(); ?></time>
            </li>
            <?php endwhile; wp_reset_postdata(); ?>
        </ul>
    </div>
        <?php endif;
    endif; ?>

</aside>
