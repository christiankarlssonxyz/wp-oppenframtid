<?php
/**
 * Template Name: Konto – Sparade inlägg
 *
 * page-konto-sparade.php – Sparade inlägg
 */

if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(home_url('/konto/sparade/')));
    exit;
}

get_header();

$saved_ids = array_filter(array_map('intval',
    (array) get_user_meta(get_current_user_id(), 'blogtree_saved_posts', true)
));
?>

<div class="content-with-sidebar container">
<div class="konto-page">

    <div class="konto-page__header">
        <a href="<?php echo esc_url(home_url('/konto/')); ?>" class="konto-back">← Mitt konto</a>
        <h1 class="konto-page__title">Sparade inlägg</h1>
    </div>

    <?php if ($saved_ids):
        $query = new WP_Query([
            'post__in'            => $saved_ids,
            'posts_per_page'      => -1,
            'orderby'             => 'post__in',
            'ignore_sticky_posts' => true,
        ]);
        if ($query->have_posts()): ?>
        <div class="post-grid">
            <?php while ($query->have_posts()): $query->the_post();
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
                    <div class="post-card__footer">
                        <time class="post-card__date" datetime="<?php echo get_the_date('c'); ?>">
                            <?php echo get_the_date(); ?>
                        </time>
                        <button class="konto-unsave-btn" data-post-id="<?php echo get_the_ID(); ?>">
                            Ta bort
                        </button>
                    </div>
                </div>
            </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <?php endif;
    else: ?>
    <p class="konto-empty">Du har inga sparade inlägg ännu. Klicka på "Spara" inne på ett inlägg för att spara det här.</p>
    <?php endif; ?>

</div>
<?php get_sidebar(); ?>
</div>

<script>
(function () {
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.konto-unsave-btn');
        if (!btn || !window.blogtreeSaved) return;
        btn.disabled = true;
        var card = btn.closest('.post-card');
        var body = new URLSearchParams({
            action:  'blogtree_save_post',
            post_id: btn.dataset.postId,
            nonce:   blogtreeSaved.nonce,
        });
        fetch(blogtreeSaved.ajaxurl, { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && !data.data.saved && card) {
                    card.remove();
                }
            });
    });
}());
</script>

<?php get_footer(); ?>
