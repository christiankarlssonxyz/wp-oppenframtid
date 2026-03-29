<?php
/**
 * page-registrera.php – Registreringssida
 * Slug: registrera
 */

// Redan inloggad → till konto
if (is_user_logged_in()) {
    wp_safe_redirect(home_url('/konto/'));
    exit;
}

get_header();

$fel_param = $_GET['fel'] ?? '';
$felen     = $fel_param ? explode(',', $fel_param) : [];

$felmeddelanden = [
    'tom_anvandare'  => 'Ange ett användarnamn.',
    'ogiltig_epost'  => 'Ange en giltig e-postadress.',
    'kort_losenord'  => 'Lösenordet måste vara minst 8 tecken.',
    'anvandare_finns'=> 'Användarnamnet är redan taget.',
    'epost_finns'    => 'E-postadressen är redan registrerad.',
    'okant'          => 'Något gick fel, försök igen.',
];
?>

<div class="auth-page container">
    <div class="auth-card">

        <h1 class="auth-card__title">Skapa konto</h1>

        <?php if ($felen): ?>
        <div class="auth-card__error">
            <ul>
                <?php foreach ($felen as $fel): ?>
                    <?php if (isset($felmeddelanden[$fel])): ?>
                    <li><?php echo esc_html($felmeddelanden[$fel]); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form class="auth-form" method="post" action="">
            <?php wp_nonce_field('blogtree_register', 'blogtree_register_nonce'); ?>

            <div class="auth-form__field">
                <label for="user_login">Användarnamn</label>
                <input type="text" id="user_login" name="user_login"
                       value="<?php echo esc_attr($_GET['user_login'] ?? ''); ?>"
                       autocomplete="username" required>
            </div>

            <div class="auth-form__field">
                <label for="user_email">E-postadress</label>
                <input type="email" id="user_email" name="user_email"
                       value="<?php echo esc_attr($_GET['user_email'] ?? ''); ?>"
                       autocomplete="email" required>
            </div>

            <div class="auth-form__field">
                <label for="user_pass">Lösenord <span class="auth-form__hint">(minst 8 tecken)</span></label>
                <input type="password" id="user_pass" name="user_pass"
                       autocomplete="new-password" minlength="8" required>
            </div>

            <button type="submit" class="btn btn--primary auth-form__submit">Skapa konto</button>
        </form>

        <p class="auth-card__footer">
            Har du redan ett konto? <a href="<?php echo esc_url(wp_login_url()); ?>">Logga in</a>
        </p>

    </div>
</div>

<?php get_footer(); ?>
