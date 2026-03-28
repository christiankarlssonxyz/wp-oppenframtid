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

// ── Färgval per ämne i admin ───────────────────────────────────────────────────
add_action('topic_edit_form_fields', function ($term) {
    $color = get_term_meta($term->term_id, 'wpblogtree_topic_color', true) ?: '#2c3e50';
    ?>
    <tr class="form-field">
        <th><label for="topic-color">Färg</label></th>
        <td>
            <input type="color" id="topic-color" name="topic_color" value="<?php echo esc_attr($color); ?>">
            <p class="description">Visas på ämnessidan och i ämneskorten på startsidan.</p>
        </td>
    </tr>
    <?php
});

add_action('edited_topic', function ($term_id) {
    if (isset($_POST['topic_color'])) {
        update_term_meta($term_id, 'wpblogtree_topic_color', sanitize_hex_color($_POST['topic_color']));
    }
});
