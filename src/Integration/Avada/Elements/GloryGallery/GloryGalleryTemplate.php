<?php

namespace Glory\Integration\Avada\Elements\GloryGallery;

use \Glory\Components\ContentRender;
use \Glory\Utility\ImageUtility;

class GloryGalleryTemplate
{
    /**
     * Template principal para renderizar cada item de la galería
     *
     * @param \WP_Post $post Post de la imagen
     * @param string $itemClass Clase del item
     */
    public static function renderItem(\WP_Post $post, string $itemClass): void
    {
        $link_enabled = ContentRender::getCurrentOption('linkEnabled', true);
        $title_show = ContentRender::getCurrentOption('titleShow', true);
        $title_position = ContentRender::getCurrentOption('titlePosition', 'bottom');
        $img_show = ContentRender::getCurrentOption('imgShow', true);
        $size = ContentRender::getCurrentOption('imgSize', 'medium');
        $optimize = ContentRender::getCurrentOption('imgOptimize', true);
        $quality = ContentRender::getCurrentOption('imgQuality', 60);

        $alt = get_post_meta($post->ID, '_wp_attachment_image_alt', true) ?: get_the_title($post);
        $full_url = wp_get_attachment_url($post->ID);
        ?>
        <div id="post-<?php echo esc_attr($post->ID); ?>" class="<?php echo esc_attr($itemClass); ?>">
            <?php if ($link_enabled): ?>
                <a class="glory-cr__link" href="<?php echo esc_url($full_url); ?>">
            <?php endif; ?>

                <div class="glory-cr__stack">
                    <?php if ($title_show && $title_position === 'top'): ?>
                        <?php self::renderTitle($post); ?>
                    <?php endif; ?>

                    <?php if ($img_show): ?>
                        <?php self::renderImage($post, $size, $optimize, $quality, $alt); ?>
                    <?php endif; ?>

                    <?php if ($title_show && $title_position === 'bottom'): ?>
                        <?php self::renderTitle($post); ?>
                    <?php endif; ?>
                </div>

            <?php if ($link_enabled): ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el título del post
     *
     * @param \WP_Post $post Post de la imagen
     */
    private static function renderTitle(\WP_Post $post): void
    {
        ?>
        <h2 class="glory-cr__title"><?php echo esc_html(get_the_title($post)); ?></h2>
        <?php
    }

    /**
     * Renderiza la imagen del post
     *
     * @param \WP_Post $post Post de la imagen
     * @param string $size Tamaño de imagen
     * @param bool $optimize Si optimizar la imagen
     * @param int $quality Calidad de optimización
     * @param string $alt Texto alternativo
     */
    private static function renderImage(\WP_Post $post, string $size, bool $optimize, int $quality, string $alt): void
    {
        if ($optimize) {
            $imgHtml = ImageUtility::optimizar($post, $size, $quality);
            if (is_string($imgHtml) && $imgHtml !== '') {
                $imgHtml = preg_replace('/^<img\s+/i', '<img class="glory-cr__image" alt="' . esc_attr($alt) . '" ', $imgHtml);
                echo $imgHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            } else {
                echo wp_get_attachment_image($post->ID, $size, false, ['class' => 'glory-cr__image', 'alt' => $alt]);
            }
        } else {
            echo wp_get_attachment_image($post->ID, $size, false, ['class' => 'glory-cr__image', 'alt' => $alt]);
        }
    }

    /**
     * Callback compatible con ContentRender para usar como plantilla
     *
     * @param \WP_Post $post Post de la imagen
     * @param string $itemClass Clase del item
     */
    public static function galleryItemTemplate(\WP_Post $post, string $itemClass): void
    {
        self::renderItem($post, $itemClass);
    }
}
