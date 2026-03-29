<?php
/**
 * Template Name: Obesvarade kommentarer
 *
 * page-medlemmar-kommentarer-obesvarade.php
 * Slug: medlemmar/kommentarer/obesvarade
 *
 * Vy 1: inlägg med obesvarade kommentarer
 * Vy 2: ?post_id=X → kommentarstråd för ett inlägg
 */

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_safe_redirect(home_url('/konto/'));
    exit;
}

get_header();

global $wpdb;
$post_id   = (int) ($_GET['post_id'] ?? 0);
$nonce     = wp_create_nonce('blogtree_admin_comment');
$admin_ids = blogtree_admin_user_ids_sql();
?>

<div class="content-with-sidebar container">
<div class="konto-page">

    <div class="konto-page__header">
        <a href="<?php echo esc_url(home_url('/medlemmar/')); ?>" class="konto-back">← Medlemshantering</a>
        <h1 class="konto-page__title">Kommentarer</h1>
    </div>

    <?php blogtree_mod_tab_nav('obesvarade'); ?>

    <div class="members-notice" id="admin-notice" hidden></div>

    <?php if (!$post_id): ?>
    <!-- ── Vy 1: inläggslista ────────────────────────────────────────────── -->
    <?php
    $rows = $wpdb->get_results(
        "SELECT c.comment_post_ID AS post_id, COUNT(*) AS unread_count
         FROM {$wpdb->comments} c
         LEFT JOIN {$wpdb->commentmeta} cm
           ON c.comment_ID = cm.comment_id AND cm.meta_key = 'blogtree_admin_read'
         WHERE c.comment_approved = '1' AND cm.meta_id IS NULL
         GROUP BY c.comment_post_ID
         ORDER BY unread_count DESC"
    );
    ?>

    <?php if ($rows): ?>
    <div class="mod-post-list">
        <?php foreach ($rows as $row):
            $p_title = get_the_title($row->post_id);
            $p_url   = add_query_arg('post_id', $row->post_id, get_permalink(get_the_ID()));
        ?>
        <a href="<?php echo esc_url(add_query_arg('post_id', $row->post_id)); ?>"
           class="mod-post-row">
            <span class="mod-post-row__title"><?php echo esc_html($p_title); ?></span>
            <span class="mod-post-row__count">
                <?php echo (int) $row->unread_count; ?> obesvara<?php echo $row->unread_count === '1' ? 'd' : 'de'; ?>
            </span>
            <svg class="mod-post-row__arrow" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="konto-empty">Inga obesvarade kommentarer.</p>
    <?php endif; ?>

    <?php else: ?>
    <!-- ── Vy 2: kommentarstråd för ett inlägg ──────────────────────────── -->
    <?php
    $post_obj   = get_post($post_id);
    if (!$post_obj) {
        echo '<p class="konto-empty">Inlägget hittades inte.</p>';
    } else {
        $post_date = date_i18n('j F Y', strtotime($post_obj->post_date));

        // Hämta obesvarade toppnivå-kommentarer för inlägget
        $unread_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT c.comment_ID
             FROM {$wpdb->comments} c
             LEFT JOIN {$wpdb->commentmeta} cm
               ON c.comment_ID = cm.comment_id AND cm.meta_key = 'blogtree_admin_read'
             WHERE c.comment_post_ID = %d
               AND c.comment_approved = '1'
               AND c.comment_parent = 0
               AND cm.meta_id IS NULL
             ORDER BY c.comment_date ASC",
            $post_id
        ));

        $comments = $unread_ids ? get_comments([
            'comment__in' => $unread_ids,
            'orderby'     => 'comment_date',
            'order'       => 'ASC',
        ]) : [];
    ?>
    <div class="mod-thread-header">
        <a href="<?php echo esc_url(get_the_permalink($post_id)); ?>" target="_blank"
           class="mod-thread-header__title"><?php echo esc_html($post_obj->post_title); ?></a>
        <time class="mod-thread-header__date"><?php echo esc_html($post_date); ?></time>
        <a href="<?php echo esc_url(remove_query_arg('post_id')); ?>" class="konto-back" style="margin-left:auto">← Tillbaka</a>
    </div>

    <?php if ($comments): ?>
    <div class="mod-comments-list mod-comments-list--thread">
        <?php foreach ($comments as $comment):
            $content     = $comment->comment_content;
            $short       = mb_strlen($content) > 100;
            $preview     = $short ? mb_substr($content, 0, 100) : $content;
            $rest        = $short ? mb_substr($content, 100) : '';
            $is_read     = get_comment_meta($comment->comment_ID, 'blogtree_admin_read', true);
            $is_liked    = get_comment_meta($comment->comment_ID, 'blogtree_admin_liked', true);
            $replies     = get_comments([
                'post_id' => $post_id,
                'status'  => 'approve',
                'parent'  => $comment->comment_ID,
            ]);
        ?>
        <div class="mod-comment mod-comment--thread <?php echo $is_read ? 'mod-comment--read' : ''; ?>"
             id="mod-comment-<?php echo esc_attr($comment->comment_ID); ?>">

            <div class="mod-comment__header">
                <strong class="mod-comment__author"><?php echo esc_html($comment->comment_author); ?></strong>
                <time class="mod-comment__date">
                    <?php echo esc_html(date_i18n('j M Y H:i', strtotime($comment->comment_date))); ?>
                </time>
                <?php if ($is_liked): ?>
                <span class="mod-admin-liked-badge">❤ Admin gillar</span>
                <?php endif; ?>
            </div>

            <div class="mod-comment__text">
                <span class="comment-preview"><?php echo esc_html($preview); ?></span><?php if ($short): ?><span class="comment-rest" hidden><?php echo esc_html($rest); ?></span><button class="comment-expand-btn" type="button">… visa mer</button><?php endif; ?>
            </div>

            <?php if ($replies): ?>
            <div class="mod-thread__replies">
                <?php foreach ($replies as $reply): ?>
                <div class="mod-thread__reply">
                    <strong><?php echo esc_html($reply->comment_author); ?></strong>
                    <time><?php echo esc_html(date_i18n('j M Y H:i', strtotime($reply->comment_date))); ?></time>
                    <p><?php echo esc_html($reply->comment_content); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="mod-comment__actions">
                <button class="btn btn--ghost admin-action-btn admin-read-btn <?php echo $is_read ? 'admin-action-btn--active' : ''; ?>"
                        data-action="mark_read"
                        data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                        data-nonce="<?php echo esc_attr($nonce); ?>">
                    <?php echo $is_read ? 'Markerad som läst' : 'Markera som läst'; ?>
                </button>
                <button class="btn btn--ghost admin-action-btn admin-like-btn <?php echo $is_liked ? 'admin-action-btn--active' : ''; ?>"
                        data-action="like"
                        data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                        data-nonce="<?php echo esc_attr($nonce); ?>">
                    <?php echo $is_liked ? '❤ Gillar' : '♡ Gilla'; ?>
                </button>
                <button class="btn btn--ghost admin-action-btn admin-reply-toggle"
                        data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                    Svara
                </button>
            </div>

            <!-- Inline svar-formulär -->
            <div class="admin-reply-form" id="admin-reply-<?php echo esc_attr($comment->comment_ID); ?>" hidden>
                <form class="comment-form comment-form--admin"
                      data-post-id="<?php echo esc_attr($post_id); ?>"
                      data-parent-id="<?php echo esc_attr($comment->comment_ID); ?>">
                    <textarea class="comment-form__input" name="content"
                              placeholder="Ditt svar…" rows="3" required></textarea>
                    <div class="comment-form__footer">
                        <button type="submit" class="btn btn--primary btn--sm">Publicera svar</button>
                        <button type="button" class="btn btn--ghost btn--sm admin-reply-cancel"
                                data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">Avbryt</button>
                    </div>
                </form>
            </div>

        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="konto-empty">Alla kommentarer på det här inlägget är besvarade.</p>
    <?php endif; ?>

    <?php } ?>
    <?php endif; ?>

</div>
<?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>
