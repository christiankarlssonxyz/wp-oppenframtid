<?php
/**
 * single-mikroinlagg.php – Enskilt mikroinlägg
 */
get_header();
?>

<?php if (have_posts()): while (have_posts()): the_post();
    $post_id    = get_the_ID();
    $visibility = get_post_meta($post_id, '_mikro_visibility', true) ?: 'public';

    // Blockera members-inlägg för icke-inloggade
    if ($visibility === 'members' && !is_user_logged_in()):
        wp_safe_redirect(wp_login_url(get_permalink()));
        exit;
    endif;
?>

<div class="container">
    <div class="mikro-layout">
        <main class="mikro-main">

            <?php
            $banner_id = get_post_thumbnail_id($post_id);
            if ($banner_id):
                $banner_src = wp_get_attachment_image_src($banner_id, 'blogtree-mikro-banner');
                if ($banner_src):
            ?>
            <figure class="topic-banner">
                <img src="<?php echo esc_url($banner_src[0]); ?>"
                     width="<?php echo (int) $banner_src[1]; ?>"
                     height="<?php echo (int) $banner_src[2]; ?>"
                     alt=""
                     class="topic-banner__img"
                     loading="eager">
            </figure>
            <?php endif; endif; ?>

            <div class="mikro-single">
                <?php blogtree_mikro_card($post_id); ?>
            </div>

            <!-- Kommentarer -->
            <?php comments_template(); ?>

        </main>
        <?php get_sidebar(); ?>
    </div>
</div>

<?php endwhile; endif; ?>

<?php get_footer(); ?>
