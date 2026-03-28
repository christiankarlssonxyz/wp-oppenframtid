<?php
/**
 * page.php – Statisk sida (t.ex. Om mig, Kontakt)
 */
get_header();
?>

<?php if (have_posts()): while (have_posts()): the_post(); ?>

<article class="page-content container">
    <h1 class="page-content__title"><?php the_title(); ?></h1>
    <div class="page-content__body">
        <?php the_content(); ?>
    </div>
</article>

<?php endwhile; endif; ?>

<?php get_footer(); ?>
