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

    </header>

    <?php if (has_post_thumbnail()): ?>
    <div class="single-post__image">
        <?php the_post_thumbnail('large'); ?>
    </div>
    <?php endif; ?>

    <div class="single-post__content">
        <?php the_content(); ?>
    </div>

    <!-- ── Åtgärdsrad ─────────────────────────────────────────────────── -->
    <?php
    $likes    = (int) get_post_meta(get_the_ID(), 'blogtree_likes', true);
    $liked_by = (array) get_post_meta(get_the_ID(), 'blogtree_liked_by', true);
    $is_liked = is_user_logged_in() && in_array(get_current_user_id(), $liked_by);
    $comments_count = (int) get_comments_number();
    ?>
    <div class="post-actions-bar">
        <button class="post-actions-bar__btn like-btn <?php echo $is_liked ? 'is-liked' : ''; ?>"
                data-post-id="<?php echo get_the_ID(); ?>"
                <?php echo !is_user_logged_in() ? 'data-require-login="true"' : ''; ?>
                aria-label="Gilla inlägget">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="<?php echo $is_liked ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
            Gilla<?php if ($likes > 0): ?> <span class="like-btn__count"><?php echo $likes; ?></span><?php endif; ?>
        </button>

        <button class="post-actions-bar__btn" id="toggle-comments-btn" aria-expanded="false">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            Kommentera<?php if ($comments_count > 0): ?> <span><?php echo $comments_count; ?></span><?php endif; ?>
        </button>

        <button class="post-actions-bar__btn copy-btn"
                data-url="<?php echo esc_attr(get_permalink()); ?>"
                aria-label="Dela inlägget">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                <polyline points="16 6 12 2 8 6"/>
                <line x1="12" y1="2" x2="12" y2="15"/>
            </svg>
            <span class="copy-btn__label">Dela</span>
        </button>
    </div>

    <!-- ── Läs mer ────────────────────────────────────────────────────── -->
    <?php blogtree_render_read_more(get_the_ID()); ?>

    <!-- ── Kommentarer (dolda från start) ────────────────────────────── -->
    <div id="comments-wrapper" class="comments-wrapper" hidden>
        <?php comments_template(); ?>
    </div>

</article>

<?php endwhile; endif; ?>

<?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>
