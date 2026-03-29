<?php
/**
 * inc/linktree.php – Linktree-sida
 *
 * Registrerar posttypen "linktree_item" för att hantera länkarna.
 * Varje länk har: titel, URL, valfri undertitel.
 * Ordning styrs via drag-and-drop i admin (menu_order).
 */

// ── Registrera posttypen ───────────────────────────────────────────────────────
add_action('init', function () {
    register_post_type('linktree_item', [
        'label'               => 'Linktree-länkar',
        'labels'              => [
            'name'          => 'Linktree-länkar',
            'singular_name' => 'Länk',
            'add_new_item'  => 'Lägg till länk',
            'edit_item'     => 'Redigera länk',
            'new_item'      => 'Ny länk',
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_icon'           => 'dashicons-admin-links',
        'supports'            => ['title', 'page-attributes'],
        'hierarchical'        => false,
        'rewrite'             => false,
        'show_in_rest'        => false,
    ]);
});

// ── Meta-box: URL + undertitel ─────────────────────────────────────────────────
add_action('add_meta_boxes', function () {
    add_meta_box(
        'linktree_item_fields',
        'Länkinställningar',
        'blogtree_linktree_meta_box',
        'linktree_item',
        'normal',
        'high'
    );
});

function blogtree_linktree_meta_box($post) {
    wp_nonce_field('blogtree_linktree_save', 'blogtree_linktree_nonce');
    $url      = get_post_meta($post->ID, '_linktree_url', true);
    $subtitle = get_post_meta($post->ID, '_linktree_subtitle', true);
    ?>
    <table class="form-table" style="margin-top:0">
        <tr>
            <th style="width:120px"><label for="linktree_url">URL</label></th>
            <td>
                <input type="url" id="linktree_url" name="linktree_url"
                       value="<?php echo esc_attr($url); ?>"
                       class="large-text" placeholder="https://">
            </td>
        </tr>
        <tr>
            <th><label for="linktree_subtitle">Undertitel</label></th>
            <td>
                <input type="text" id="linktree_subtitle" name="linktree_subtitle"
                       value="<?php echo esc_attr($subtitle); ?>"
                       class="large-text" placeholder="Valfri kortbeskrivning">
            </td>
        </tr>
    </table>
    <p class="description" style="padding:0 0 8px 0">
        Ordning styrs via <strong>Sidordning</strong> i rutan "Sidattribut" till höger (lägre siffra = högre upp).
    </p>
    <?php
}

add_action('save_post_linktree_item', function ($post_id) {
    if (!isset($_POST['blogtree_linktree_nonce'])) return;
    if (!wp_verify_nonce($_POST['blogtree_linktree_nonce'], 'blogtree_linktree_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['linktree_url'])) {
        $url = esc_url_raw(trim($_POST['linktree_url']));
        if ($url) {
            update_post_meta($post_id, '_linktree_url', $url);
        } else {
            delete_post_meta($post_id, '_linktree_url');
        }
    }

    if (isset($_POST['linktree_subtitle'])) {
        $subtitle = sanitize_text_field($_POST['linktree_subtitle']);
        if ($subtitle) {
            update_post_meta($post_id, '_linktree_subtitle', $subtitle);
        } else {
            delete_post_meta($post_id, '_linktree_subtitle');
        }
    }
});
