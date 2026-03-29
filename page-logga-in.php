<?php
/**
 * page-logga-in.php – Inloggningssida
 * Slug: logga-in
 */

// Redan inloggad → till konto
if (is_user_logged_in()) {
    wp_safe_redirect(home_url('/konto/'));
    exit;
}

get_header();

$redirect_to = esc_url($_GET['redirect_to'] ?? home_url('/konto/'));
$fel         = $_GET['fel'] ?? '';
?>

<div class="auth-page container">
    <div class="auth-card">

        <h1 class="auth-card__title">Logga in</h1>

        <?php if ($fel): ?>
        <div class="auth-card__error">
            <?php echo $fel === '1' ? 'Fel användarnamn eller lösenord.' : 'Något gick fel, försök igen.'; ?>
        </div>
        <?php endif; ?>

        <form class="auth-form" method="post" action="">
            <?php wp_nonce_field('blogtree_login', 'blogtree_login_nonce'); ?>
            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

            <div class="auth-form__field">
                <label for="log">Användarnamn eller e-post</label>
                <input type="text" id="log" name="log" autocomplete="username" required>
            </div>

            <div class="auth-form__field">
                <label for="pwd">Lösenord</label>
                <input type="password" id="pwd" name="pwd" autocomplete="current-password" required>
            </div>

            <div class="auth-form__remember">
                <label>
                    <input type="checkbox" name="rememberme" value="forever">
                    Kom ihåg mig
                </label>
            </div>

            <button type="submit" class="btn btn--primary auth-form__submit">Logga in</button>
        </form>

        <p class="auth-card__footer">
            Inget konto? <a href="<?php echo esc_url(home_url('/registrera/')); ?>">Registrera dig</a>
        </p>

    </div>
</div>

<?php get_footer(); ?>
