<?php

namespace Infiny\Boutique;

defined('ABSPATH') || exit;

function category_labels(): array {
    $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
    if (is_wp_error($terms)) {
        return [];
    }

    $names = wp_list_pluck($terms, 'name', 'term_id');
    $labels = [];
    foreach ($terms as $term) {
        $ancestors = array_reverse(get_ancestors($term->term_id, 'product_cat', 'taxonomy'));
        $path = array_map(static fn ($ancestor) => $names[$ancestor] ?? '', $ancestors);
        $labels[$term->term_id] = implode(' › ', array_merge(array_filter($path), [$term->name]));
    }
    natcasesort($labels);
    return $labels;
}

function product_categories(\WC_Product $product): void {
    $selected = $product->get_category_ids('edit');
    ?>
    <fieldset>
        <legend>Catégories</legend>
        <input type="hidden" name="categories_presentes" value="1">
        <div class="ib-category-options">
            <?php foreach (category_labels() as $id => $label) : ?>
                <label><input type="checkbox" name="categories[]" value="<?php echo esc_attr($id); ?>"
                    <?php checked(in_array($id, $selected, true)); ?>> <?php echo esc_html($label); ?></label>
            <?php endforeach; ?>
        </div>
        <?php if (current_user_can('edit_product_terms')) : ?>
            <label>Nouvelle catégorie (facultatif) <input name="nouvelle_categorie" maxlength="200"></label>
        <?php endif; ?>
    </fieldset>
    <?php
}

function submitted_categories() {
    $labels = category_labels();
    $values = $_POST['categories'] ?? [];
    $ids = is_array($values) ? array_map('absint', array_filter($values, 'is_scalar')) : [];
    $ids = array_values(array_intersect($ids, array_keys($labels)));
    $new = input($_POST, 'nouvelle_categorie');
    if ($new === '' || !current_user_can('edit_product_terms')) {
        return $ids;
    }
    $result = wp_insert_term($new, 'product_cat');
    if (is_wp_error($result) && $result->get_error_code() !== 'term_exists') {
        return $result;
    }
    $ids[] = is_wp_error($result) ? (int) $result->get_error_data() : $result['term_id'];
    return array_values(array_unique($ids));
}

function categories(int $id): void {
    if (!current_user_can('manage_product_terms')) {
        echo '<p class="ib-empty" role="alert">Vous n’avez pas accès aux catégories.</p>';
        return;
    }
    $term = $id ? get_term($id, 'product_cat') : null;
    if ($id && !$term instanceof \WP_Term) {
        echo '<p class="ib-empty">Catégorie introuvable.</p>';
        button('Retour aux catégories', url('categories'));
        return;
    }

    $labels = category_labels();
    $default = (int) get_option('default_product_cat');
    if ($id) {
        button('Retour aux catégories', url('categories'));
    }
    if ($id && $id !== $default && input($_GET, 'supprimer') === '1' && current_user_can('delete_product_terms')) {
        ?>
        <form class="ib-panel" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <h3>Supprimer « <?php echo esc_html($term->name); ?> » ?</h3>
            <p>Les produits associés seront conservés.</p>
            <?php category_form_fields($id, 'supprimer'); ?>
            <button class="button">Confirmer la suppression</button>
        </form>
        <?php
        return;
    }

    if (current_user_can('edit_product_terms')) {
        ?>
        <form class="ib-panel" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <h3><?php echo $id ? 'Modifier la catégorie' : 'Ajouter une catégorie'; ?></h3>
            <?php category_form_fields($id, 'enregistrer'); ?>
            <div class="ib-fields">
                <label>Nom <input name="nom" maxlength="200" required value="<?php echo esc_attr($term->name ?? ''); ?>"></label>
                <label>Catégorie parente
                    <select name="parent">
                        <option value="0">Aucune</option>
                        <?php foreach ($labels as $candidate => $label) : ?>
                            <?php if ($id && ($candidate === $id || term_is_ancestor_of($id, $candidate, 'product_cat'))) continue; ?>
                            <option value="<?php echo esc_attr($candidate); ?>" <?php selected($term->parent ?? 0, $candidate); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <button class="button button-primary"><?php echo $id ? 'Enregistrer' : 'Ajouter'; ?></button>
        </form>
        <?php
    }
    if ($id || !$labels) {
        return;
    }

    echo '<div class="ib-table"><table><thead><tr><th>Catégorie</th><th>Actions</th></tr></thead><tbody>';
    foreach ($labels as $term_id => $label) {
        echo '<tr><td>' . esc_html($label) . '</td><td>';
        if (current_user_can('edit_product_terms')) {
            button('Modifier', url('categories', ['id' => $term_id]));
        }
        if ($term_id !== $default && current_user_can('delete_product_terms')) {
            button('Supprimer', url('categories', ['id' => $term_id, 'supprimer' => 1]));
        }
        echo '</td></tr>';
    }
    echo '</tbody></table></div>';
}

function category_form_fields(int $id, string $operation): void {
    wp_nonce_field('ib-category-' . $operation . '-' . $id);
    printf('<input type="hidden" name="action" value="infiny_boutique_category"><input type="hidden" name="id" value="%d"><input type="hidden" name="operation" value="%s">', $id, esc_attr($operation));
}

add_action('admin_post_infiny_boutique_category', function () {
    $id = absint(input($_POST, 'id'));
    $operation = input($_POST, 'operation');
    check_admin_referer('ib-category-' . $operation . '-' . $id);
    $delete = $operation === 'supprimer';
    if (!can_manage() || !taxonomy_exists('product_cat') ||
        !current_user_can($delete ? 'delete_product_terms' : 'edit_product_terms')) {
        wp_die('Accès indisponible.', '', ['response' => 403]);
    }
    $list = url('categories');
    $form = $id ? url('categories', ['id' => $id]) : $list;
    if ($delete) {
        $default = (int) get_option('default_product_cat');
        if (!$id || $id === $default || wp_delete_term($id, 'product_cat', ['default' => $default]) !== true) {
            finish($list, 'Cette catégorie n’a pas pu être supprimée.');
        }
        finish($list);
    }
    $name = input($_POST, 'nom');
    $parent = absint(input($_POST, 'parent'));
    if ($name === '' || ($parent && (!term_exists($parent, 'product_cat') || $parent === $id ||
        ($id && term_is_ancestor_of($id, $parent, 'product_cat'))))) {
        finish($form, 'Vérifiez le nom et la catégorie parente.');
    }
    $result = $id
        ? wp_update_term($id, 'product_cat', ['name' => $name, 'parent' => $parent])
        : wp_insert_term($name, 'product_cat', ['parent' => $parent]);
    if (is_wp_error($result)) {
        finish($form, $result->get_error_message());
    }
    finish($list);
});
