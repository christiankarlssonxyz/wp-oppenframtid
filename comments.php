<?php
/**
 * comments.php – Kommentarsmall
 */

if (post_password_required()) {
    echo '<p>Ange lösenordet för att se kommentarer.</p>';
    return;
}

$comments     = get_comments(['post_id' => get_the_ID(), 'status' => 'approve', 'parent' => 0]);
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();

$emojis = ['😀','😂','😍','🤔','😎','🥳','😢','😡','🙏','👍',
           '👎','❤️','🔥','✅','⚡','🎉','💡','📢','🚀','🌍',
           '📰','🗳️','⚖️','🏛️','📊','💬','🤝','✊','👏','🌱',
           '⚠️','❌','💰','🏆','📝','🔍','📌','🕐','💪','🧵'];
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
            <input type="hidden" name="content" class="comment-content-input">

            <!-- Toolbar -->
            <div class="comment-toolbar">
                <button type="button" class="comment-toolbar__btn" data-cmd="bold" title="Fetstil"><b>B</b></button>
                <button type="button" class="comment-toolbar__btn" data-cmd="italic" title="Kursiv"><i>I</i></button>
                <button type="button" class="comment-toolbar__btn" data-cmd="underline" title="Understrykning"><u>U</u></button>
                <div class="comment-toolbar__sep"></div>
                <button type="button" class="comment-toolbar__btn" data-cmd="insertUnorderedList" title="Punktlista">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/></svg>
                </button>
                <button type="button" class="comment-toolbar__btn" data-cmd="insertOrderedList" title="Numrerad lista">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"/></svg>
                </button>
                <button type="button" class="comment-toolbar__btn" data-cmd="createLink" title="Lägg till länk">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                </button>
                <div class="comment-toolbar__sep"></div>
                <button type="button" class="comment-toolbar__btn comment-emoji-toggle" title="Emoji">😊</button>
            </div>

            <!-- Emoji-picker -->
            <div class="comment-emoji-picker" hidden>
                <div class="mikro-emoji-picker__grid">
                    <?php foreach ($emojis as $e): ?>
                    <button type="button" class="mikro-emoji-btn comment-emoji-insert" data-emoji="<?php echo $e; ?>"><?php echo $e; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Editor -->
            <div class="comment-editor comment-form__input"
                 contenteditable="true"
                 data-placeholder="Din kommentar…"
                 role="textbox"
                 aria-multiline="true"
                 aria-label="Kommentar"></div>

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
                <?php echo wp_kses_post($comment->comment_content); ?>
            </div>
            <?php if (get_comment_meta($comment->comment_ID, 'blogtree_admin_liked', true)): ?>
            <div class="comment-admin-like">❤ Admin gillar den här kommentaren</div>
            <?php endif; ?>
            <div class="comment-actions">
                <?php
                $liked_by     = (array) get_comment_meta($comment->comment_ID, 'blogtree_liked_by', true);
                $like_count   = count($liked_by);
                $user_liked   = is_user_logged_in() && in_array(get_current_user_id(), $liked_by, true);
                ?>
                <button class="comment-like-btn <?php echo $user_liked ? 'is-liked' : ''; ?>"
                        data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                        <?php echo !is_user_logged_in() ? 'disabled title="Logga in för att gilla"' : ''; ?>"
                        aria-label="Gilla kommentar">
                    <svg viewBox="0 0 24 24" width="13" height="13"
                         fill="<?php echo $user_liked ? 'currentColor' : 'none'; ?>"
                         stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    <span class="comment-like-btn__count"><?php echo $like_count > 0 ? $like_count : ''; ?></span>
                </button>

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
                    <input type="hidden" name="content" class="comment-content-input">
                    <div class="comment-editor comment-form__input"
                         contenteditable="true"
                         data-placeholder="Ditt svar…"
                         role="textbox"
                         aria-multiline="true"></div>
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
