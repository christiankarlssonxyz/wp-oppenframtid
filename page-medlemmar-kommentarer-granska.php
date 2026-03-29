<?php
/**
 * Template Name: Granska kommentarer
 *
 * page-medlemmar-kommentarer-granska.php
 * Slug: medlemmar/kommentarer/granska
 *
 * Listar kommentarer från icke-inloggade som verifierat sin e-post
 * och väntar på moderatorns godkännande.
 */

if (!is_user_logged_in() || !blogtree_can_manage_members()) {
    wp_safe_redirect(home_url('/konto/'));
    exit;
}

get_header();

$page_num = max(1, (int) ($_GET['paged'] ?? 1));
$per_page = 20;
$offset   = ($page_num - 1) * $per_page;

// Kommentarer som väntar godkännande och har e-postmeta (=verifierade gäster)
$comments = get_comments([
    'status'     => 'hold',
    'number'     => $per_page,
    'offset'     => $offset,
    'meta_query' => [[
        'key'     => 'blogtree_pending_email',
        'compare' => 'EXISTS',
    ]],
]);

$total_count = get_comments([
    'status'     => 'hold',
    'count'      => true,
    'meta_query' => [[
        'key'     => 'blogtree_pending_email',
        'compare' => 'EXISTS',
    ]],
]);

$reported_count = get_comments([
    'status'  => 'any',
    'count'   => true,
    'meta_query' => [[
        'key'     => BLOGTREE_REPORT_META_KEY,
        'compare' => 'EXISTS',
    ]],
]);

$pages = ceil($total_count / $per_page);
?>

<div class="content-with-sidebar container">
<div class="konto-page">

    <div class="konto-page__header">
        <a href="<?php echo esc_url(home_url('/medlemmar/')); ?>" class="konto-back">← Medlemshantering</a>
        <h1 class="konto-page__title">Kommentarer</h1>
    </div>

    <?php blogtree_mod_tab_nav('granska'); ?>

    <div class="members-notice" id="mod-notice" hidden></div>

    <?php if ($comments): ?>
    <div class="mod-comments-list">
        <?php foreach ($comments as $comment):
            $email      = get_comment_meta($comment->comment_ID, 'blogtree_pending_email', true);
            $post_url   = get_permalink($comment->comment_post_ID);
            $post_title = get_the_title($comment->comment_post_ID);
        ?>
        <div class="mod-comment" id="mod-comment-<?php echo esc_attr($comment->comment_ID); ?>">
            <div class="mod-comment__header">
                <span class="mod-comment__author"><?php echo esc_html($comment->comment_author); ?></span>
                <?php if ($email): ?>
                <span class="mod-comment__email"><?php echo esc_html($email); ?></span>
                <?php endif; ?>
                <a href="<?php echo esc_url($post_url); ?>" class="mod-comment__post" target="_blank">
                    <?php echo esc_html($post_title); ?>
                </a>
                <time class="mod-comment__date">
                    <?php echo esc_html(date_i18n('j M Y H:i', strtotime($comment->comment_date))); ?>
                </time>
                <?php
                $verified = get_comment_meta($comment->comment_ID, 'blogtree_pending_verified', true);
                if ($verified === '1'): ?>
                <span class="mod-comment__badge mod-comment__badge--visible">Mejl verifierad</span>
                <?php else: ?>
                <span class="mod-comment__badge mod-comment__badge--held">Mejl ej verifierad</span>
                <?php endif; ?>
            </div>

            <div class="mod-comment__text">
                <?php echo esc_html($comment->comment_content); ?>
            </div>

            <div class="mod-comment__actions">
                <button class="btn btn--ghost mod-action-btn"
                        data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                        data-action="approve"
                        data-nonce="<?php echo esc_attr(wp_create_nonce('blogtree_moderate')); ?>">
                    Godkänn
                </button>
                <button class="btn mod-action-btn mod-action-btn--delete"
                        data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                        data-action="delete"
                        data-nonce="<?php echo esc_attr(wp_create_nonce('blogtree_moderate')); ?>">
                    Ta bort
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php echo paginate_links(['total' => $pages, 'current' => $page_num]); ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <p class="konto-empty">Inga kommentarer väntar på godkännande.</p>
    <?php endif; ?>

</div>
<?php get_sidebar(); ?>
</div>

<script>
(function () {
    var notice = document.getElementById('mod-notice');

    function showNotice(msg, type) {
        notice.textContent = msg;
        notice.className = 'members-notice members-notice--' + type;
        notice.hidden = false;
        setTimeout(function () { notice.hidden = true; }, 4000);
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.mod-action-btn');
        if (!btn) return;

        var action = btn.dataset.action;
        if (action === 'delete' && !confirm('Ta bort kommentaren permanent?')) return;

        btn.disabled = true;
        var card = btn.closest('.mod-comment');

        var body = new URLSearchParams({
            action:     'blogtree_moderate_comment',
            comment_id: btn.dataset.commentId,
            mod_action: action,
            nonce:      btn.dataset.nonce,
        });

        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    showNotice(data.data.message, 'success');
                    if (card) card.remove();
                } else {
                    showNotice(data.data || 'Något gick fel.', 'error');
                    btn.disabled = false;
                }
            });
    });
}());
</script>

<?php get_footer(); ?>
