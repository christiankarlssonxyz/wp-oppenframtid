<?php
/**
 * inc/topics.php – Taxonomi: Ämnen
 *
 * Registrerar "topic" som en anpassad taxonomi.
 * Ämnen fungerar som fokusområden – berättar för besökaren
 * vad sidan handlar om.
 *
 * URL-format: /topic/amnesnamn/
 */

// ── Registrera taxonomin ───────────────────────────────────────────────────────
add_action('init', function () {
    register_taxonomy('topic', 'post', [
        'label'             => 'Ämnen',
        'labels'            => [
            'name'          => 'Ämnen',
            'singular_name' => 'Ämne',
            'add_new_item'  => 'Lägg till ämne',
            'edit_item'     => 'Redigera ämne',
        ],
        'hierarchical'      => true,   // Fungerar som kategorier (kan ha underämnen)
        'show_ui'           => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'topic'],
        'show_in_rest'      => true,   // Stöd för blockredigeraren
    ]);
});

// ── Ladda mediepicker på ämnes-admin-sidor ─────────────────────────────────────
add_action('admin_enqueue_scripts', function ($hook) {
    if (in_array($hook, ['edit-tags.php', 'term.php'], true)) {
        wp_enqueue_media();
        wp_add_inline_script('media-editor', "
(function($){
    function makePicker(btnId, removeId, inputId, previewId, title){
        var picker;
        $(document).on('click', '#' + btnId, function(e){
            e.preventDefault();
            if(picker){ picker.open(); return; }
            picker = wp.media({ title: title, button: { text: 'Välj bild' }, multiple: false, library: { type: 'image' } });
            picker.on('select', function(){
                var att = picker.state().get('selection').first().toJSON();
                $('#' + inputId).val(att.id);
                var thumb = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
                $('#' + previewId).attr('src', thumb).show();
                $('#' + removeId).show();
            });
            picker.open();
        });
        $(document).on('click', '#' + removeId, function(e){
            e.preventDefault();
            $('#' + inputId).val('');
            $('#' + previewId).hide().attr('src','');
            $(this).hide();
        });
    }
    makePicker('topic-banner-btn',   'topic-banner-remove',   'topic-banner-id',   'topic-banner-preview',   'Välj bannerbild');
    makePicker('topic-og-image-btn', 'topic-og-image-remove', 'topic-og-image-id', 'topic-og-image-preview', 'Välj delningsbild');
})(jQuery);
        ");
    }
});

// ── Färgval per ämne i admin ───────────────────────────────────────────────────
add_action('topic_edit_form_fields', function ($term) {
    $color     = get_term_meta($term->term_id, 'wpblogtree_topic_color', true) ?: '#2c3e50';
    $banner_id = (int) get_term_meta($term->term_id, 'wpblogtree_topic_banner_id', true);
    $preview   = $banner_id ? wp_get_attachment_image_src($banner_id, 'medium') : false;
    ?>
    <tr class="form-field">
        <th><label for="topic-header-label">Övre rubrik (label)</label></th>
        <td>
            <?php $header_label = get_term_meta($term->term_id, 'wpblogtree_topic_header_label', true); ?>
            <input type="text" id="topic-header-label" name="topic_header_label"
                   value="<?php echo esc_attr($header_label); ?>"
                   placeholder="ÄMNE" class="regular-text">
            <p class="description">Liten text ovanför rubriken på ämnessidan. Lämna tomt för standardtexten "ÄMNE".</p>
        </td>
    </tr>
    <tr class="form-field">
        <th><label for="topic-color">Färg</label></th>
        <td>
            <input type="color" id="topic-color" name="topic_color" value="<?php echo esc_attr($color); ?>">
            <p class="description">Visas på ämnessidan och i ämneskorten på startsidan.</p>
        </td>
    </tr>
    <tr class="form-field">
        <th><label for="topic-gradient-color">Gradientfärg (hero)</label></th>
        <td>
            <?php $gradient_color = get_term_meta($term->term_id, 'wpblogtree_topic_gradient_color', true) ?: ''; ?>
            <input type="color" id="topic-gradient-color" name="topic_gradient_color"
                   value="<?php echo esc_attr($gradient_color ?: $color); ?>">
            <p class="description">Andra färgen i gradientbakgrunden i hero. Lämna som ämnesfärgen för ingen gradient.</p>
        </td>
    </tr>
    <tr class="form-field">
        <th><label>Bannerbild</label></th>
        <td>
            <input type="hidden" id="topic-banner-id" name="topic_banner_id" value="<?php echo esc_attr($banner_id ?: ''); ?>">
            <?php if ($preview): ?>
            <img id="topic-banner-preview"
                 src="<?php echo esc_url($preview[0]); ?>"
                 style="max-width:300px;height:auto;display:block;margin-bottom:8px;border-radius:4px;">
            <?php else: ?>
            <img id="topic-banner-preview" src="" style="max-width:300px;height:auto;display:none;margin-bottom:8px;border-radius:4px;">
            <?php endif; ?>
            <button type="button" id="topic-banner-btn" class="button">Välj bild</button>
            <button type="button" id="topic-banner-remove" class="button" style="<?php echo $banner_id ? '' : 'display:none;'; ?>margin-left:4px;">Ta bort bild</button>
            <p class="description">
                Rekommenderat format: <strong>1 200 × 400 px</strong> (3:1).<br>
                Ladda upp minst 1 200 px bred för bäst kvalitet på alla skärmar.
            </p>
        </td>
    </tr>
    <tr class="form-field">
        <th><label>Delningsbild (OG-bild)</label></th>
        <td>
            <?php
            $og_id      = (int) get_term_meta($term->term_id, 'wpblogtree_topic_og_image_id', true);
            $og_preview = $og_id ? wp_get_attachment_image_src($og_id, 'medium') : false;
            ?>
            <input type="hidden" id="topic-og-image-id" name="topic_og_image_id" value="<?php echo esc_attr($og_id ?: ''); ?>">
            <?php if ($og_preview): ?>
            <img id="topic-og-image-preview"
                 src="<?php echo esc_url($og_preview[0]); ?>"
                 style="max-width:300px;height:auto;display:block;margin-bottom:8px;border-radius:4px;">
            <?php else: ?>
            <img id="topic-og-image-preview" src="" style="max-width:300px;height:auto;display:none;margin-bottom:8px;border-radius:4px;">
            <?php endif; ?>
            <button type="button" id="topic-og-image-btn" class="button">Välj bild</button>
            <button type="button" id="topic-og-image-remove" class="button" style="<?php echo $og_id ? '' : 'display:none;'; ?>margin-left:4px;">Ta bort bild</button>
            <p class="description">
                Rekommenderat format: <strong>1 200 × 630 px</strong> (1,91:1).<br>
                Visas när ett inlägg i det här ämnet delas på sociala medier (Facebook, LinkedIn, iMessage m.fl.).<br>
                Om inlägget har en featured image används den istället.
            </p>
        </td>
    </tr>
    <tr class="form-field">
        <th><label for="topic-banner-caption">Bildtext</label></th>
        <td>
            <?php
            $caption = get_term_meta($term->term_id, 'wpblogtree_topic_banner_caption', true);
            wp_editor($caption, 'topic-banner-caption', [
                'textarea_name' => 'topic_banner_caption',
                'textarea_rows' => 4,
                'media_buttons' => false,
                'teeny'         => true,
                'tinymce'       => [
                    'toolbar1' => 'bold,italic,link,removeformat',
                    'toolbar2' => '',
                ],
                'quicktags'     => ['buttons' => 'strong,em,link,br'],
            ]);
            ?>
            <p class="description">Visas under bannerbilden. Stödjer fetstil, kursiv, länkar och radbrytning.</p>
        </td>
    </tr>
    <?php
});

add_action('edited_topic', function ($term_id) {
    if (isset($_POST['topic_header_label'])) {
        $label = sanitize_text_field($_POST['topic_header_label']);
        if ($label) {
            update_term_meta($term_id, 'wpblogtree_topic_header_label', $label);
        } else {
            delete_term_meta($term_id, 'wpblogtree_topic_header_label');
        }
    }
    if (isset($_POST['topic_color'])) {
        update_term_meta($term_id, 'wpblogtree_topic_color', sanitize_hex_color($_POST['topic_color']));
    }
    if (isset($_POST['topic_gradient_color'])) {
        $grad = sanitize_hex_color($_POST['topic_gradient_color']);
        if ($grad) {
            update_term_meta($term_id, 'wpblogtree_topic_gradient_color', $grad);
        } else {
            delete_term_meta($term_id, 'wpblogtree_topic_gradient_color');
        }
    }
    if (isset($_POST['topic_banner_id'])) {
        $banner_id = absint($_POST['topic_banner_id']);
        if ($banner_id) {
            update_term_meta($term_id, 'wpblogtree_topic_banner_id', $banner_id);
        } else {
            delete_term_meta($term_id, 'wpblogtree_topic_banner_id');
        }
    }
    if (isset($_POST['topic_og_image_id'])) {
        $og_id = absint($_POST['topic_og_image_id']);
        if ($og_id) {
            update_term_meta($term_id, 'wpblogtree_topic_og_image_id', $og_id);
        } else {
            delete_term_meta($term_id, 'wpblogtree_topic_og_image_id');
        }
    }
    if (isset($_POST['topic_banner_caption'])) {
        $caption = wp_kses_post($_POST['topic_banner_caption']);
        if ($caption) {
            update_term_meta($term_id, 'wpblogtree_topic_banner_caption', $caption);
        } else {
            delete_term_meta($term_id, 'wpblogtree_topic_banner_caption');
        }
    }
});
