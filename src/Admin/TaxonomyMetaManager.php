<?php
// Glory/src/Admin/TaxonomyMetaManager.php

namespace Glory\Admin;

class TaxonomyMetaManager
{
    private const META_KEY = 'glory_category_image_id';

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueMediaScripts']);
        add_action('category_add_form_fields', [$this, 'addCategoryField'], 10, 2);
        add_action('category_edit_form_fields', [$this, 'editCategoryField'], 10, 2);
        add_action('create_category', [$this, 'saveCategoryMeta'], 10, 2);
        add_action('edited_category', [$this, 'saveCategoryMeta'], 10, 2);
    }

    public function enqueueMediaScripts($hook): void
    {
        if ('term.php' !== $hook && 'edit-tags.php' !== $hook) {
            return;
        }
        wp_enqueue_media();
        add_action('admin_footer', [$this, 'mediaUploaderScript']);
    }

    public function addCategoryField(): void
    {
?>
        <div class="form-field term-group">
            <label for="<?php echo self::META_KEY; ?>"><?php _e('Imagen de la Categoría', 'glory'); ?></label>
            <input type="hidden" id="<?php echo self::META_KEY; ?>" name="<?php echo self::META_KEY; ?>" value="">
            <div id="category-image-wrapper"></div>
            <p>
                <button type="button" class="button button-secondary glory-upload-image-button"><?php _e('Subir/Añadir Imagen', 'glory'); ?></button>
                <button type="button" class="button button-secondary glory-remove-image-button" style="display:none;"><?php _e('Eliminar Imagen', 'glory'); ?></button>
            </p>
        </div>
    <?php
    }

    public function editCategoryField(\WP_Term $term): void
    {
        $imageId = get_term_meta($term->term_id, self::META_KEY, true);
        $imageUrl = $imageId ? wp_get_attachment_image_url($imageId, 'medium') : '';
    ?>
        <tr class="form-field term-group-wrap">
            <th scope="row"><label for="<?php echo self::META_KEY; ?>"><?php _e('Imagen de la Categoría', 'glory'); ?></label></th>
            <td>
                <input type="hidden" id="<?php echo self::META_KEY; ?>" name="<?php echo self::META_KEY; ?>" value="<?php echo esc_attr($imageId); ?>">
                <div id="category-image-wrapper">
                    <?php if ($imageUrl) : ?>
                        <img src="<?php echo esc_url($imageUrl); ?>" style="max-width:200px;height:auto;">
                    <?php endif; ?>
                </div>
                <p>
                    <button type="button" class="button button-secondary glory-upload-image-button"><?php _e('Subir/Añadir Imagen', 'glory'); ?></button>
                    <button type="button" class="button button-secondary glory-remove-image-button" style="<?php echo $imageId ? '' : 'display:none;'; ?>"><?php _e('Eliminar Imagen', 'glory'); ?></button>
                </p>
            </td>
        </tr>
    <?php
    }

    public function saveCategoryMeta(int $term_id): void
    {
        if (isset($_POST[self::META_KEY]) && is_numeric($_POST[self::META_KEY])) {
            update_term_meta($term_id, self::META_KEY, (int)$_POST[self::META_KEY]);
        } else {
            delete_term_meta($term_id, self::META_KEY);
        }
    }

    public function mediaUploaderScript(): void
    {
    ?>
        <script>
            jQuery(document).ready(function($) {
                var mediaUploader;
                $('body').on('click', '.glory-upload-image-button', function(e) {
                    e.preventDefault();
                    var button = $(this);
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    mediaUploader = wp.media.frames.file_frame = wp.media({
                        title: 'Elegir Imagen',
                        button: {
                            text: 'Elegir Imagen'
                        },
                        multiple: false
                    });
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#<?php echo self::META_KEY; ?>').val(attachment.id);
                        $('#category-image-wrapper').html('<img src="' + attachment.sizes.medium.url + '" style="max-width:200px;height:auto;">');
                        button.siblings('.glory-remove-image-button').show();
                    });
                    mediaUploader.open();
                });
                $('body').on('click', '.glory-remove-image-button', function(e) {
                    e.preventDefault();
                    $('#<?php echo self::META_KEY; ?>').val('');
                    $('#category-image-wrapper').html('');
                    $(this).hide();
                });
            });
        </script>
<?php
    }
}
