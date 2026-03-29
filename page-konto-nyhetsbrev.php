<?php
/**
 * Template Name: Konto – Nyhetsbrev
 *
 * page-konto-nyhetsbrev.php – Nyhetsbrev
 */

if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(home_url('/konto/nyhetsbrev/')));
    exit;
}

get_header();
?>

<div class="content-with-sidebar container">
<div class="konto-page">

    <div class="konto-page__header">
        <a href="<?php echo esc_url(home_url('/konto/')); ?>" class="konto-back">← Mitt konto</a>
        <h1 class="konto-page__title">Nyhetsbrev</h1>
    </div>

    <div class="konto-coming-soon">
        <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
        </svg>
        <h2>Tjänsten kommer senare</h2>
        <p>Vi jobbar på ett nyhetsbrev där du kan välja vilka ämnen du vill få uppdateringar om. Håll utkik!</p>
    </div>

</div>
<?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>
