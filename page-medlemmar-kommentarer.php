<?php
/**
 * Template Name: Moderera kommentarer
 *
 * page-medlemmar-kommentarer.php
 * Slug: medlemmar/kommentarer
 */

if (!is_user_logged_in() || !blogtree_can_manage_members()) {
    wp_safe_redirect(home_url('/konto/'));
    exit;
}

get_header();

$status_filter = sanitize_key($_GET['status'] ?? 'flagged');
$page_num      = max(1, (int) ($_GET['paged'] ?? 1));
$per_page      = 20;

// Hämta kommentarer med rapporter
global $wpdb;

$offset    = ($page_num - 1) * $per_page;

// Kommentarer som har report-meta
$comment_ids = $wpdb->get_col($wpdb->prepare(
    "SELECT DISTINCT comment_id FROM {$wpdb->commentmeta}
     WHERE meta_key = %s
     ORDER BY comment_id DESC
     LIMIT %d OFFSET %d",
    BLOGTREE_REPORT_META_KEY,
    $per_page,
    $offset
));

$total_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT comment_id) FROM {$wpdb->commentmeta} WHERE meta_key = %s",
    BLOGTREE_REPORT_META_KEY
));

$comments = $comment_ids ? get_comments([
    'comment__in'  => $comment_ids,
    'status'       => 'any',
    'orderby'      => 'comment_ID',
    'order'        => 'DESC',
]) : [];

$pages    = ceil($total_count / $per_page);
$reasons  = blogtree_get_reasons();
?>

<div class="content-with-sidebar container">
<div class="konto-page">

    <div class="konto-page__header">
        <a href="<?php echo esc_url(home_url('/medlemmar/')); ?>" class="konto-back">← Medlemshantering</a>
        <h1 class="konto-page__title">Kommentarer</h1>
    </div>

    <?php blogtree_mod_tab_nav('rapporterade'); ?>

    <div class="members-notice" id="mod-notice" hidden></div>

    <?php if ($comments): ?>
    <div class="mod-comments-list">
        <?php foreach ($comments as $comment):
            $reports     = (array) get_comment_meta($comment->comment_ID, BLOGTREE_REPORT_META_KEY, true);
            $total       = array_sum($reports);
            $is_held     = ($comment->comment_approved === '0');
            $post_url    = get_permalink($comment->comment_post_ID);
            $post_title  = get_the_title($comment->comment_post_ID);
        ?>
        <div class="mod-comment" id="mod-comment-<?php echo esc_attr($comment->comment_ID); ?>">
            <div class="mod-comment__header">
                <span class="mod-comment__author"><?php echo esc_html($comment->comment_author); ?></span>
                <a href="<?php echo esc_url($post_url); ?>" class="mod-comment__post" target="_blank">
                    <?php echo esc_html($post_title); ?>
                </a>
                <time class="mod-comment__date">
                    <?php echo esc_html(date_i18n('j M Y H:i', strtotime($comment->comment_date))); ?>
                </time>
                <?php if ($is_held): ?>
                <span class="mod-comment__badge mod-comment__badge--held">Dold</span>
                <?php else: ?>
                <span class="mod-comment__badge mod-comment__badge--visible">Synlig</span>
                <?php endif; ?>
            </div>

            <div class="mod-comment__text">
                <?php echo esc_html($comment->comment_content); ?>
            </div>

            <div class="mod-comment__reports">
                <strong><?php echo (int) $total; ?> rapport<?php echo $total !== 1 ? 'er' : ''; ?>:</strong>
                <ul class="mod-comment__reasons">
                    <?php foreach ($reports as $key => $count):
                        $label = $reasons[$key] ?? $key;
                    ?>
                    <li><?php echo esc_html($label); ?> <span>(<?php echo (int) $count; ?>)</span></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="mod-comment__actions">
                <button class="btn btn--ghost mod-action-btn"
                        data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                        data-action="approve"
                        data-nonce="<?php echo esc_attr(wp_create_nonce('blogtree_moderate')); ?>">
                    Godkänn
                </button>
                <button class="btn btn--ghost mod-action-btn"
                        data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                        data-action="ignore"
                        data-nonce="<?php echo esc_attr(wp_create_nonce('blogtree_moderate')); ?>">
                    Ignorera rapport
                </button>
                <button class="btn mod-action-btn mod-action-btn--delete"
                        data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                        data-action="delete"
                        data-nonce="<?php echo esc_attr(wp_create_nonce('blogtree_moderate')); ?>">
                    Ta bort kommentar
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php echo paginate_links([
            'total'   => $pages,
            'current' => $page_num,
        ]); ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <p class="konto-empty">Inga rapporterade kommentarer just nu.</p>
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
        if (action === 'ignore' && !confirm('Ignorera rapporten? Räknaren återställs och rapportörerna meddelas.')) return;

        btn.disabled = true;
        var card = btn.closest('.mod-comment');

        var body = new URLSearchParams({
            action:      'blogtree_moderate_comment',
            comment_id:  btn.dataset.commentId,
            mod_action:  action,
            nonce:       btn.dataset.nonce,
        });

        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    showNotice(data.data.message, 'success');
                    if (action === 'delete' || action === 'ignore' || action === 'approve') {
                        if (card) card.remove();
                    }
                } else {
                    showNotice(data.data || 'Något gick fel.', 'error');
                    btn.disabled = false;
                }
            });
    });
}());
</script>

<?php get_footer(); ?>
