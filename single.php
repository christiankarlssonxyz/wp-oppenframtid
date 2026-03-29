<?php
/**
 * single.php – Enskilt inlägg
 */
get_header();
?>

<?php if (have_posts()): while (have_posts()): the_post(); ?>

<div class="content-with-sidebar container">
<article class="single-post">

    <header class="single-post__header">

        <?php
        $topics      = get_the_terms(get_the_ID(), 'topic');
        $first_topic = (!is_wp_error($topics) && !empty($topics)) ? $topics[0] : null;
        if ($first_topic): ?>
            <a href="<?php echo esc_url(get_term_link($first_topic)); ?>" class="post-card__topic">
                <?php echo esc_html($first_topic->name); ?>
            </a>
        <?php endif; ?>

        <h1 class="single-post__title"><?php the_title(); ?></h1>

        <div class="single-post__meta">
            <time datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date(); ?></time>
            <span>av <?php the_author(); ?></span>
            <?php
            $words   = str_word_count(strip_tags(get_the_content()));
            $minutes = max(1, ceil($words / 200));
            ?>
            <span>~<?php echo $minutes; ?> min läsning</span>
        </div>

        <!-- ── Åtgärdsrad ─────────────────────────────────────────────── -->
        <div class="post-actions">

            <?php
            // Gilla
            $likes    = (int) get_post_meta(get_the_ID(), 'blogtree_likes', true);
            $liked_by = (array) get_post_meta(get_the_ID(), 'blogtree_liked_by', true);
            $is_liked = is_user_logged_in() && in_array(get_current_user_id(), $liked_by);
            ?>
            <button class="post-action-btn like-btn <?php echo $is_liked ? 'is-liked' : ''; ?>"
                    data-post-id="<?php echo get_the_ID(); ?>"
                    <?php echo !is_user_logged_in() ? 'data-require-login="true"' : ''; ?>
                    aria-label="Gilla inlägget">
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                <span class="post-action-btn__label">Gilla<?php if ($likes > 0): ?> <span class="like-btn__count"><?php echo $likes; ?></span><?php endif; ?></span>
            </button>

            <?php if (is_user_logged_in()):
                $saved_posts = (array) get_user_meta(get_current_user_id(), 'blogtree_saved_posts', true);
                $is_saved    = in_array(get_the_ID(), array_map('intval', $saved_posts), true);
            ?>
            <!-- Spara -->
            <button class="post-action-btn save-btn <?php echo $is_saved ? 'is-saved' : ''; ?>"
                    data-post-id="<?php echo get_the_ID(); ?>"
                    aria-label="<?php echo $is_saved ? 'Ta bort från sparade' : 'Spara inlägg'; ?>">
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                </svg>
                <span class="post-action-btn__label save-btn__label"><?php echo $is_saved ? 'Sparad' : 'Spara'; ?></span>
            </button>
            <?php endif; ?>

            <!-- Kopiera länk -->
            <button class="post-action-btn copy-btn"
                    data-url="<?php echo esc_attr(get_permalink()); ?>"
                    aria-label="Kopiera länk till inlägget">
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                <span class="post-action-btn__label copy-btn__label">Kopiera länk</span>
            </button>

        </div>
    </header>

    <?php if (has_post_thumbnail()): ?>
    <div class="single-post__image">
        <?php the_post_thumbnail('large'); ?>
    </div>
    <?php endif; ?>

    <div class="single-post__content">
        <?php the_content(); ?>
    </div>

    <?php comments_template(); ?>

</article>

<?php endwhile; endif; ?>

<?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>
