<?php
/**
 * page-konto.php – Kontodashboard
 * Slug: konto
 */

if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(home_url('/konto/')));
    exit;
}

get_header();

$user        = wp_get_current_user();
$saved_posts = (array) get_user_meta($user->ID, 'blogtree_saved_posts', true);
$followed    = (array) get_user_meta($user->ID, 'blogtree_followed_topics', true);
?>

<div class="content-with-sidebar container">
<div class="konto-page">

    <!-- ── Profilhuvud ─────────────────────────────────────────────────────── -->
    <div class="konto-profile">
        <div class="konto-profile__avatar">
            <?php echo get_avatar($user->ID, 80); ?>
        </div>
        <div class="konto-profile__info">
            <h1 class="konto-profile__name"><?php echo esc_html($user->display_name); ?></h1>
            <p class="konto-profile__role"><?php echo esc_html(blogtree_user_role_label($user)); ?></p>
            <p class="konto-profile__since">Medlem sedan <?php echo esc_html(date_i18n('j F Y', strtotime($user->user_registered))); ?></p>
        </div>
    </div>

    <!-- ── Snabblänkar ─────────────────────────────────────────────────────── -->
    <div class="konto-quicklinks">
        <a href="<?php echo esc_url(home_url('/konto/sparade/')); ?>" class="konto-quicklink">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
            </svg>
            <span class="konto-quicklink__count"><?php echo count(array_filter($saved_posts)); ?></span>
            <span class="konto-quicklink__label">Sparade inlägg</span>
        </a>
        <a href="<?php echo esc_url(home_url('/konto/foljer/')); ?>" class="konto-quicklink">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
            <span class="konto-quicklink__count"><?php echo count(array_filter($followed)); ?></span>
            <span class="konto-quicklink__label">Följer</span>
        </a>
        <a href="<?php echo esc_url(home_url('/konto/nyhetsbrev/')); ?>" class="konto-quicklink">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
            <span class="konto-quicklink__label">Nyhetsbrev</span>
        </a>
    </div>

    <!-- ── Redigera profil ─────────────────────────────────────────────────── -->
    <?php if (blogtree_can_manage_members()):
        $mod_notif = get_user_meta($user->ID, 'blogtree_mod_notifications', true);
    ?>
    <section class="konto-section">
        <h2 class="konto-section__title">Moderatorinställningar</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('blogtree_mod_settings', 'blogtree_mod_nonce'); ?>
            <input type="hidden" name="action" value="blogtree_save_mod_settings">
            <label class="konto-toggle">
                <input type="checkbox" name="mod_notifications" value="1" <?php checked($mod_notif, '1'); ?>>
                <span>Ta emot e-post när en kommentar flaggas automatiskt</span>
            </label>
            <button type="submit" class="btn btn--ghost" style="margin-top:var(--space-md)">Spara</button>
        </form>
    </section>
    <?php endif; ?>

    <section class="konto-section">
        <h2 class="konto-section__title">Profil</h2>

        <?php if (isset($_GET['uppdaterad'])): ?>
        <div class="konto-notice konto-notice--success">Profilen uppdaterad.</div>
        <?php endif; ?>
        <?php if (isset($_GET['fel'])): ?>
        <div class="konto-notice konto-notice--error">Något gick fel, försök igen.</div>
        <?php endif; ?>

        <form class="konto-form" method="post" enctype="multipart/form-data"
              action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('blogtree_update_profile', 'blogtree_profile_nonce'); ?>
            <input type="hidden" name="action" value="blogtree_update_profile">

            <div class="konto-form__avatar-upload">
                <?php echo get_avatar($user->ID, 72); ?>
                <div>
                    <label class="btn btn--ghost konto-form__avatar-btn" for="avatar_file">
                        Byt profilbild
                    </label>
                    <input type="file" id="avatar_file" name="avatar_file"
                           accept="image/jpeg,image/png,image/webp,image/gif"
                           class="konto-form__avatar-input"
                           style="display:none">
                    <p class="konto-form__hint">JPG, PNG, WebP · Max 1 MB · Sparas direkt</p>
                </div>
            </div>

            <div class="konto-form__row">
                <div class="konto-form__field">
                    <label for="display_name">Visningsnamn</label>
                    <input type="text" id="display_name" name="display_name"
                           value="<?php echo esc_attr($user->display_name); ?>">
                </div>
                <div class="konto-form__field">
                    <label for="user_email">E-postadress</label>
                    <input type="email" id="user_email" name="user_email"
                           value="<?php echo esc_attr($user->user_email); ?>">
                </div>
            </div>

            <div class="konto-form__password-section">
                <p class="konto-section__title">Byt lösenord <span class="konto-form__hint">(lämna tomt för att behålla nuvarande)</span></p>
                <div class="konto-form__row">
                    <div class="konto-form__field">
                        <label for="pass1">Nytt lösenord</label>
                        <input type="password" id="pass1" name="pass1" autocomplete="new-password">
                    </div>
                    <div class="konto-form__field">
                        <label for="pass2">Bekräfta lösenord</label>
                        <input type="password" id="pass2" name="pass2" autocomplete="new-password">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn--primary">Spara ändringar</button>
        </form>
    </section>

</div>
<?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>
