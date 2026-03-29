<?php
/**
 * taxonomy-topic.php – Ämnessida
 *
 * Visar alla inlägg inom ett ämne.
 * URL-format: /topic/amnesnamn/
 */
$term = get_queried_object();

if ( ! $term || ! isset( $term->term_id ) ) {
    global $wp_query;
    $wp_query->set_404();
    status_header( 404 );
    nocache_headers();
    require get_404_template();
    exit;
}

get_header();

$color = get_term_meta($term->term_id, 'wpblogtree_topic_color', true) ?: '#2c3e50';
$paged = get_query_var('paged') ?: 1;
?>

<!-- ── ÄMNESRUBRIK ────────────────────────────────────────────────────────────── -->
<section class="topic-header" style="--topic-color: <?php echo esc_attr($color); ?>">
    <div class="container">

        <?php if ($term->parent):
            $parent = get_term($term->parent, 'topic'); ?>
            <nav class="breadcrumb">
                <a href="<?php echo esc_url(get_term_link($parent)); ?>"><?php echo esc_html($parent->name); ?></a>
                <span aria-hidden="true">&rsaquo;</span>
                <span><?php echo esc_html($term->name); ?></span>
            </nav>
        <?php endif; ?>

        <p class="topic-header__label">ÄMNE</p>
        <h1 class="topic-header__title"><?php echo esc_html($term->name); ?></h1>

        <?php if ($term->description): ?>
            <p class="topic-header__desc"><?php echo esc_html($term->description); ?></p>
        <?php endif; ?>

        <?php if (is_user_logged_in()):
            $user_id   = get_current_user_id();
            $followed  = (array) get_user_meta($user_id, 'blogtree_followed_topics', true);
            $following = in_array($term->term_id, $followed);
        ?>
        <button class="follow-btn <?php echo $following ? 'is-following' : ''; ?>"
                data-term-id="<?php echo esc_attr($term->term_id); ?>">
            <?php echo $following ? 'Följer' : 'Följ'; ?>
        </button>
        <?php endif; ?>

    </div>
</section>

<!-- ── INLÄGG + SIDEBAR ───────────────────────────────────────────────────────── -->
<div class="content-with-sidebar container" style="padding-top: var(--space-xl)">

    <!-- Huvudinnehåll -->
    <div>

        <?php
        $banner_id      = (int) get_term_meta($term->term_id, 'wpblogtree_topic_banner_id', true);
        $banner_src     = $banner_id ? wp_get_attachment_image_src($banner_id, 'blogtree-topic-banner') : false;
        $banner_caption = get_term_meta($term->term_id, 'wpblogtree_topic_banner_caption', true);
        if ($banner_src): ?>
        <figure class="topic-banner">
            <img src="<?php echo esc_url($banner_src[0]); ?>"
                 width="<?php echo (int) $banner_src[1]; ?>"
                 height="<?php echo (int) $banner_src[2]; ?>"
                 alt="<?php echo esc_attr(wp_strip_all_tags($banner_caption)); ?>"
                 class="topic-banner__img"
                 loading="lazy">
            <?php if ($banner_caption): ?>
            <figcaption class="banner-caption"><?php echo wp_kses_post($banner_caption); ?></figcaption>
            <?php endif; ?>
        </figure>
        <?php endif; ?>

        <?php
        $loop = new WP_Query([
            'post_type'      => 'post',
            'tax_query'      => [[
                'taxonomy'         => 'topic',
                'field'            => 'term_id',
                'terms'            => [$term->term_id],
                'include_children' => false,
            ]],
            'paged'          => $paged,
            'posts_per_page' => (int) get_option('posts_per_page', 10),
        ]);

        if ($loop->have_posts()): ?>
        <div class="post-grid">
            <?php while ($loop->have_posts()): $loop->the_post(); ?>
            <article class="post-card">
                <?php if (has_post_thumbnail()): ?>
                <a href="<?php the_permalink(); ?>" class="post-card__image" tabindex="-1" aria-hidden="true">
                    <?php the_post_thumbnail('medium'); ?>
                </a>
                <?php endif; ?>
                <div class="post-card__body">
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

        <div class="pagination">
            <?php echo paginate_links([
                'total'     => $loop->max_num_pages,
                'current'   => $paged,
                'prev_text' => '&larr;',
                'next_text' => '&rarr;',
            ]); ?>
        </div>

        <?php else: ?>
        <p>Inga inlägg hittades.</p>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <?php get_sidebar(); ?>

</div>

<?php get_footer(); ?>
