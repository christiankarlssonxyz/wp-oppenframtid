<?php
/**
 * comments.php – Kommentarsmall
 *
 * Anropas via comments_template() i single.php.
 * Visar kommentarer med svar-knapp och rapportera-knapp.
 */

if (post_password_required()) {
    echo '<p>Ange lösenordet för att se kommentarer.</p>';
    return;
}

$comments     = get_comments(['post_id' => get_the_ID(), 'status' => 'approve', 'parent' => 0]);
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
?>

<section class="comments-section" id="comments">

    <?php if ($comments): ?>
    <h2 class="comments-section__title">
        <?php echo count(get_comments(['post_id' => get_the_ID(), 'status' => 'approve'])); ?> kommentarer
    </h2>

    <ol class="comment-list" id="comment-list-<?php echo get_the_ID(); ?>">
        <?php blogtree_render_comments($comments, get_the_ID()); ?>
    </ol>
    <?php endif; ?>

    <!-- ── Skriv kommentar ──────────────────────────────────────────────── -->
    <?php if ($is_logged_in): ?>
    <div class="comment-form-wrap" id="comment-form-wrap">
        <h3 class="comment-form__title">Skriv en kommentar</h3>
        <form class="comment-form" id="main-comment-form"
              data-post-id="<?php echo get_the_ID(); ?>"
              data-parent-id="0">
            <textarea class="comment-form__input" name="content"
                      placeholder="Din kommentar…" rows="4" required></textarea>
            <div class="comment-form__footer">
                <button type="submit" class="btn btn--primary">Skicka</button>
            </div>
        </form>
    </div>
    <?php else: ?>
    <p class="comments-section__login">
        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">Logga in</a> för att kommentera
        &nbsp;·&nbsp;
        <button class="comments-section__guest-btn" data-post-id="<?php echo get_the_ID(); ?>">Kommentera som gäst</button>
    </p>
    <?php endif; ?>

    <!-- ── Gästkommentarsmodal ──────────────────────────────────────────── -->
    <div class="report-modal-overlay" id="guest-comment-overlay" hidden>
        <div class="report-modal" role="dialog" aria-modal="true" aria-labelledby="guest-comment-title">
            <h2 id="guest-comment-title" class="report-modal__title">Kommentera som gäst</h2>
            <div class="report-modal__field">
                <label for="guest-name">Namn</label>
                <input type="text" id="guest-name" placeholder="Ditt namn" autocomplete="name">
            </div>
            <div class="report-modal__field">
                <label for="guest-email">E-postadress</label>
                <input type="email" id="guest-email" placeholder="din@epost.se" autocomplete="email">
                <p class="report-modal__hint">Din e-postadress används bara för att verifiera kommentaren och raderas inom 24 timmar.</p>
            </div>
            <div class="report-modal__field">
                <label for="guest-content">Kommentar</label>
                <textarea id="guest-content" class="report-modal__textarea" rows="4" placeholder="Din kommentar…"></textarea>
            </div>
            <p class="report-modal__status" id="guest-comment-status" hidden></p>
            <div class="report-modal__footer">
                <button class="btn btn--primary" id="guest-comment-submit">Skicka</button>
                <button class="btn btn--ghost" id="guest-comment-cancel">Avbryt</button>
            </div>
        </div>
    </div>

</section>
<?php

function blogtree_render_comments(array $comments, int $post_id, int $depth = 0): void {
    foreach ($comments as $comment):
        $replies = get_comments(['post_id' => $post_id, 'status' => 'approve', 'parent' => $comment->comment_ID]);
    ?>
    <li class="comment-item" id="comment-<?php echo esc_attr($comment->comment_ID); ?>">
        <div class="comment-body">
            <div class="comment-meta">
                <?php echo get_avatar($comment->user_id ?: $comment->comment_author_email, 36); ?>
                <div>
                    <strong class="comment-author"><?php echo esc_html($comment->comment_author); ?></strong>
                    <time class="comment-date" datetime="<?php echo esc_attr($comment->comment_date); ?>">
                        <?php echo esc_html(date_i18n('j M Y H:i', strtotime($comment->comment_date))); ?>
                    </time>
                </div>
            </div>
            <div class="comment-content">
                <?php echo wpautop(esc_html($comment->comment_content)); ?>
            </div>
            <?php if (get_comment_meta($comment->comment_ID, 'blogtree_admin_liked', true)): ?>
            <div class="comment-admin-like">❤ Admin gillar den här kommentaren</div>
            <?php endif; ?>
            <div class="comment-actions">
                <?php if (is_user_logged_in() && $depth < 3): ?>
                <button class="comment-reply-btn" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                    Svara
                </button>
                <?php endif; ?>
                <button class="report-comment-btn"
                        data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                        aria-label="Rapportera kommentar">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                        <line x1="4" y1="22" x2="4" y2="15"/>
                    </svg>
                    Rapportera
                </button>
            </div>
            <!-- Svar-formulär (dolt) -->
            <div class="comment-reply-form" id="reply-form-<?php echo esc_attr($comment->comment_ID); ?>" hidden>
                <form class="comment-form comment-form--reply"
                      data-post-id="<?php echo esc_attr($post_id); ?>"
                      data-parent-id="<?php echo esc_attr($comment->comment_ID); ?>">
                    <textarea class="comment-form__input" name="content"
                              placeholder="Ditt svar…" rows="3" required></textarea>
                    <div class="comment-form__footer">
                        <button type="submit" class="btn btn--primary btn--sm">Skicka svar</button>
                        <button type="button" class="btn btn--ghost btn--sm reply-cancel-btn"
                                data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">Avbryt</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($replies): ?>
        <ol class="comment-list comment-list--replies">
            <?php blogtree_render_comments($replies, $post_id, $depth + 1); ?>
        </ol>
        <?php endif; ?>
    </li>
    <?php endforeach;
}
