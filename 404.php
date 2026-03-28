<?php
/**
 * 404.php – Sidan hittades inte
 */
get_header();
?>

<main class="site-main">
    <div class="container">
        <div class="error-page">
            <p class="error-page__code">404</p>
            <h1 class="error-page__title">Sidan hittades inte</h1>
            <p class="error-page__text">
                Adressen du angav finns inte eller har flyttats.
            </p>
            <div class="error-page__actions">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn--primary">Till startsidan</a>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>
