<?php
/**
 * Template Name: Medlemshantering
 *
 * page-medlemmar.php – Frontend-admin för roller och konton
 * Åtkomst: Admin och Moderator
 */

if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(home_url('/medlemmar/')));
    exit;
}

if (!blogtree_can_manage_members()) {
    wp_safe_redirect(home_url('/konto/'));
    exit;
}

get_header();

$is_admin    = blogtree_is_admin();
$search      = sanitize_text_field($_GET['s'] ?? '');
$role_filter = sanitize_key($_GET['roll'] ?? '');
$page_num    = max(1, (int) ($_GET['paged'] ?? 1));
$per_page    = 20;

$args = [
    'number'  => $per_page,
    'offset'  => ($page_num - 1) * $per_page,
    'orderby' => 'registered',
    'order'   => 'DESC',
];

if ($search) {
    $args['search']         = '*' . $search . '*';
    $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
}

if ($role_filter) {
    $args['role'] = $role_filter;
}

$user_query = new WP_User_Query($args);
$users      = $user_query->get_results();
$total      = $user_query->get_total();
$pages      = ceil($total / $per_page);

$roles = [
    ''           => 'Alla roller',
    'subscriber' => 'Prenumerant',
    'author'     => 'Skribent',
    'moderator'  => 'Moderator',
];
if ($is_admin) {
    $roles['administrator'] = 'Administratör';
}
?>

<div class="content-with-sidebar container">
<div class="konto-page">

    <div class="konto-page__header">
        <h1 class="konto-page__title">Medlemshantering</h1>
        <p class="konto-profile__since"><?php echo (int) $total; ?> medlemmar totalt</p>
    </div>

    <!-- ── Filter ─────────────────────────────────────────────────────────── -->
    <form class="members-filter" method="get" action="">
        <input type="text" name="s" class="members-filter__search"
               placeholder="Sök namn, användarnamn eller e-post…"
               value="<?php echo esc_attr($search); ?>">
        <select name="roll" class="members-filter__role">
            <?php foreach ($roles as $val => $label): ?>
            <option value="<?php echo esc_attr($val); ?>" <?php selected($role_filter, $val); ?>>
                <?php echo esc_html($label); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn--ghost">Filtrera</button>
        <?php if ($search || $role_filter): ?>
        <a href="<?php echo esc_url(get_permalink()); ?>" class="btn btn--ghost">Rensa</a>
        <?php endif; ?>
    </form>

    <!-- ── Notis ─────────────────────────────────────────────────────────── -->
    <div class="members-notice" id="members-notice" hidden></div>

    <!-- ── Medlemslista ───────────────────────────────────────────────────── -->
    <?php if ($users): ?>
    <div class="members-table-wrap">
        <table class="members-table">
            <thead>
                <tr>
                    <th>Medlem</th>
                    <th>Roll</th>
                    <th>Registrerad</th>
                    <?php if ($is_admin): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $member):
                $member_roles  = (array) $member->roles;
                $current_role  = $member_roles[0] ?? 'subscriber';
                $is_self       = ($member->ID === get_current_user_id());
                $is_target_admin = in_array('administrator', $member_roles, true);

                // Moderatorer ser inte admins
                if (!$is_admin && $is_target_admin) continue;
            ?>
            <tr class="members-table__row" data-user-id="<?php echo esc_attr($member->ID); ?>">
                <td class="members-table__member">
                    <?php echo get_avatar($member->ID, 36); ?>
                    <div>
                        <strong><?php echo esc_html($member->display_name); ?></strong>
                        <span class="members-table__email"><?php echo esc_html($member->user_email); ?></span>
                    </div>
                </td>
                <td class="members-table__role">
                    <?php if ($is_self || $is_target_admin): ?>
                        <span class="members-role-badge members-role-badge--<?php echo esc_attr($current_role); ?>">
                            <?php echo esc_html(blogtree_role_label($current_role)); ?>
                        </span>
                    <?php else: ?>
                        <select class="members-role-select"
                                data-user-id="<?php echo esc_attr($member->ID); ?>"
                                data-nonce="<?php echo esc_attr(wp_create_nonce('blogtree_change_role')); ?>">
                            <?php
                            $selectable = ['subscriber' => 'Prenumerant', 'author' => 'Skribent', 'moderator' => 'Moderator'];
                            if ($is_admin) $selectable['administrator'] = 'Administratör';
                            foreach ($selectable as $val => $label):
                            ?>
                            <option value="<?php echo esc_attr($val); ?>" <?php selected($current_role, $val); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </td>
                <td class="members-table__date">
                    <?php echo esc_html(date_i18n('j M Y', strtotime($member->user_registered))); ?>
                </td>
                <?php if ($is_admin): ?>
                <td class="members-table__actions">
                    <?php if (!$is_self && !$is_target_admin): ?>
                    <button class="members-delete-btn"
                            data-user-id="<?php echo esc_attr($member->ID); ?>"
                            data-name="<?php echo esc_attr($member->display_name); ?>"
                            data-nonce="<?php echo esc_attr(wp_create_nonce('blogtree_delete_user')); ?>"
                            aria-label="Ta bort <?php echo esc_attr($member->display_name); ?>">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                            <path d="M10 11v6M14 11v6"/>
                            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ── Paginering ─────────────────────────────────────────────────────── -->
    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php echo paginate_links([
            'total'     => $pages,
            'current'   => $page_num,
            'prev_text' => '&larr;',
            'next_text' => '&rarr;',
            'add_args'  => array_filter(['s' => $search, 'roll' => $role_filter]),
        ]); ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <p class="konto-empty">Inga medlemmar hittades.</p>
    <?php endif; ?>

</div>
<?php get_sidebar(); ?>
</div>

<script>
(function () {
    var notice = document.getElementById('members-notice');

    function showNotice(msg, type) {
        notice.textContent = msg;
        notice.className = 'members-notice members-notice--' + type;
        notice.hidden = false;
        setTimeout(function () { notice.hidden = true; }, 4000);
    }

    // ── Rolländring ──────────────────────────────────────────────────────────
    document.querySelectorAll('.members-role-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var body = new URLSearchParams({
                action:  'blogtree_change_role',
                user_id: sel.dataset.userId,
                role:    sel.value,
                nonce:   sel.dataset.nonce,
            });
            sel.disabled = true;
            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', { method: 'POST', body: body })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        showNotice(data.data.message + ' (' + data.data.role_label + ')', 'success');
                    } else {
                        showNotice(data.data || 'Något gick fel.', 'error');
                        location.reload();
                    }
                })
                .finally(function () { sel.disabled = false; });
        });
    });

    // ── Ta bort användare ────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.members-delete-btn');
        if (!btn) return;

        if (!confirm('Ta bort ' + btn.dataset.name + '? Detta kan inte ångras.')) return;

        btn.disabled = true;
        var body = new URLSearchParams({
            action:  'blogtree_delete_user',
            user_id: btn.dataset.userId,
            nonce:   btn.dataset.nonce,
        });
        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    var row = btn.closest('.members-table__row');
                    if (row) row.remove();
                    showNotice('Användaren borttagen.', 'success');
                } else {
                    showNotice(data.data || 'Något gick fel.', 'error');
                    btn.disabled = false;
                }
            });
    });
}());
</script>

<?php get_footer(); ?>
