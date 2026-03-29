<?php
/**
 * page-integritet.php – Integritetssida
 *
 * Visar integritets- och säkerhetsgranskning genererad från audit.json.
 * Används automatiskt för sidan med slug "integritet".
 */
get_header();
?>

<main class="site-main">
    <div class="container" style="padding-top: var(--space-xl); padding-bottom: var(--space-2xl)">
        <?php the_content(); ?>
    </div>
</main>

<?php get_footer(); ?>
