<?php
/**
 * inc/card-thumb.php – Dedikerad kortbild
 *
 * Låter redaktörer ladda upp en separat bild (800×450 px, 16:9)
 * som visas i inläggskort på ämnessidor och startsidan.
 * Faller tillbaka på utvald bild (featured image).
 */

// ── Metabox ───────────────────────────────────────────────────────────────────

add_action('add_meta_boxes', function () {
    add_meta_box(
        'blogtree_card_thumb',
        'Kortbild (800 × 450 px)',
        'blogtree_card_thumb_metabox',
        'post',
        'side',
        'low'
    );
});

function blogtree_card_thumb_metabox($post) {
    wp_nonce_field('blogtree_card_thumb_save', 'blogtree_card_thumb_nonce');

    $image_id = (int) get_post_meta($post->ID, 'blogtree_card_thumb_id', true);
    $src      = $image_id ? wp_get_attachment_image_url($image_id, 'blogtree-card-thumb') : '';
    ?>
    <div>
        <img id="blogtree-card-thumb-preview"
             src="<?php echo esc_url($src); ?>"
             style="width:100%;height:auto;margin-bottom:8px;border-radius:4px;<?php echo $src ? '' : 'display:none;'; ?>">
    </div>
    <input type="hidden" id="blogtree_card_thumb_id" name="blogtree_card_thumb_id"
           value="<?php echo esc_attr($image_id ?: ''); ?>">
    <button type="button" class="button" id="blogtree-card-thumb-btn">
        <?php echo $image_id ? 'Byt kortbild' : 'Välj kortbild'; ?>
    </button>
    <button type="button" class="button" id="blogtree-card-thumb-remove"
            style="margin-left:4px;<?php echo $image_id ? '' : 'display:none;'; ?>">
        Ta bort
    </button>
    <p class="description" style="margin-top:8px">
        Rekommenderat format: 800 × 450 px (16:9).<br>
        Lämnas tom används utvald bild istället.
    </p>
    <script>
    (function($) {
        var frame;
        $('#blogtree-card-thumb-btn').on('click', function(e) {
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({
                title:    'Välj kortbild',
                button:   { text: 'Använd som kortbild' },
                multiple: false,
                library:  { type: 'image' }
            });
            frame.on('select', function() {
                var att = frame.state().get('selection').first().toJSON();
                var url = att.sizes && att.sizes['blogtree-card-thumb']
                    ? att.sizes['blogtree-card-thumb'].url
                    : att.url;
                $('#blogtree_card_thumb_id').val(att.id);
                $('#blogtree-card-thumb-preview').attr('src', url).show();
                $('#blogtree-card-thumb-btn').text('Byt kortbild');
                $('#blogtree-card-thumb-remove').show();
            });
            frame.open();
        });

        $('#blogtree-card-thumb-remove').on('click', function(e) {
            e.preventDefault();
            $('#blogtree_card_thumb_id').val('');
            $('#blogtree-card-thumb-preview').attr('src', '').hide();
            $('#blogtree-card-thumb-btn').text('Välj kortbild');
            $(this).hide();
        });
    })(jQuery);
    </script>
    <?php
}

// ── Spara ─────────────────────────────────────────────────────────────────────

add_action('save_post_post', function ($post_id) {
    if (
        ! isset($_POST['blogtree_card_thumb_nonce']) ||
        ! wp_verify_nonce($_POST['blogtree_card_thumb_nonce'], 'blogtree_card_thumb_save') ||
        ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) ||
        ! current_user_can('edit_post', $post_id)
    ) {
        return;
    }

    if ( isset($_POST['blogtree_card_thumb_id']) ) {
        $id = absint($_POST['blogtree_card_thumb_id']);
        if ($id) {
            update_post_meta($post_id, 'blogtree_card_thumb_id', $id);
        } else {
            delete_post_meta($post_id, 'blogtree_card_thumb_id');
        }
    }
});

// ── Hjälpfunktion ─────────────────────────────────────────────────────────────

/**
 * Returnerar bild-ID för kortbilden.
 * Faller tillbaka på utvald bild (featured image).
 */
function blogtree_card_thumb_id(int $post_id): int {
    $id = (int) get_post_meta($post_id, 'blogtree_card_thumb_id', true);
    if ($id) return $id;
    return (int) get_post_thumbnail_id($post_id);
}
