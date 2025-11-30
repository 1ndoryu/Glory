<?php
/**
 * Componente de Imagen Inteligente
 *
 * Renderiza imágenes optimizadas con soporte para CDN (Jetpack Photon),
 * atributos de estilo avanzados (aspect-ratio, object-fit) y clases únicas
 * para evitar conflictos de estilo.
 *
 * @package Glory\Components
 */

namespace Glory\Components;

use Glory\Utility\ImageUtility;

/**
 * Clase GloryImage.
 *
 * Genera etiquetas de imagen HTML altamente configurables y optimizadas.
 */
class GloryImage
{
    /**
     * Renderiza el HTML de una imagen.
     *
     * @param array $args Argumentos de configuración:
     *                    - 'attachment_id': ID del adjunto WP.
     *                    - 'image_url': URL directa (fallback si no hay ID).
     *                    - 'image_size': Tamaño WP (default 'full').
     *                    - 'quality': Calidad JPEG (0-100).
     *                    - 'aspect_ratio': CSS aspect-ratio.
     *                    - 'object_fit': CSS object-fit.
     * @return string HTML de la imagen.
     */
    /**
     * Devuelve la configuración predeterminada y el esquema para GBN.
     *
     * @return array
     */
    public static function gbnDefaults(): array
    {
        return [
            'config' => [
                'aspect_ratio' => '',
                'object_fit'   => 'cover',
                'align'        => 'none',
                'full_width'   => 'no',
                'height'       => '',
            ],
            'schema' => [
                [
                    'id'       => 'aspect_ratio',
                    'tipo'     => 'text',
                    'etiqueta' => 'Aspect Ratio (ej: 16/9)',
                ],
                [
                    'id'       => 'object_fit',
                    'tipo'     => 'select',
                    'etiqueta' => 'Ajuste de imagen',
                    'opciones' => [
                        'cover'   => 'Cubrir (Cover)',
                        'contain' => 'Contener (Contain)',
                        'fill'    => 'Llenar (Fill)',
                        'none'    => 'Ninguno',
                    ],
                ],
                [
                    'id'       => 'align',
                    'tipo'     => 'select',
                    'etiqueta' => 'Alineación',
                    'opciones' => [
                        'none'   => 'Ninguna',
                        'left'   => 'Izquierda',
                        'center' => 'Centro',
                        'right'  => 'Derecha',
                    ],
                ],
                [
                    'id'       => 'full_width',
                    'tipo'     => 'toggle',
                    'etiqueta' => 'Ancho completo',
                ],
                [
                    'id'       => 'height',
                    'tipo'     => 'text',
                    'etiqueta' => 'Altura (ej: 300px)',
                ],
            ],
        ];
    }

    /**
     * Renderiza el HTML de una imagen.
     *
     * @param array $args Argumentos de configuración:
     *                    - 'attachment_id': ID del adjunto WP.
     *                    - 'image_url': URL directa (fallback si no hay ID).
     *                    - 'image_size': Tamaño WP (default 'full').
     *                    - 'quality': Calidad JPEG (0-100).
     *                    - 'aspect_ratio': CSS aspect-ratio.
     *                    - 'object_fit': CSS object-fit.
     * @return string HTML de la imagen.
     */
    public static function render(array $args = []): string
    {
        $attachmentId = isset($args['attachment_id']) ? (int) $args['attachment_id'] : 0;
        $imageUrl     = isset($args['image_url']) ? (string) $args['image_url'] : '';
        $imageSize    = isset($args['image_size']) ? (string) $args['image_size'] : 'full';

        $alt    = isset($args['alt']) ? (string) $args['alt'] : '';
        $width  = '';
        $height = '';

        if ($attachmentId > 0 && function_exists('wp_get_attachment_image_src')) {
            $srcData = wp_get_attachment_image_src($attachmentId, $imageSize);
            if (is_array($srcData) && !empty($srcData[0])) {
                $imageUrl = $srcData[0];
                $width    = isset($srcData[1]) ? (int) $srcData[1] : '';
                $height   = isset($srcData[2]) ? (int) $srcData[2] : '';
            }
            if ($alt === '' && function_exists('get_post_meta')) {
                $alt = (string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
            }
            if ($alt === '' && function_exists('get_the_title')) {
                $alt = (string) get_the_title($attachmentId);
            }
        }

        if ($imageUrl === '') {
            return '';
        }

        // Optimización básica vía CDN de Jetpack si está disponible.
        try {
            $resize   = ($width && $height) ? $width . ',' . $height : null;
            $imageUrl = ImageUtility::jetpack_photon_url($imageUrl, [
                'quality' => isset($args['quality']) ? (int) $args['quality'] : 70,
                'strip'   => 'all',
                'resize'  => $resize,
            ]);
        } catch (\Throwable $t) {
            // Silenciar cualquier error de optimización para no romper la visualización.
        }

        $aspectRatio = isset($args['aspect_ratio']) ? trim((string) $args['aspect_ratio']) : '';
        $objectFit   = isset($args['object_fit']) ? (string) $args['object_fit'] : 'cover';
        $minWidth    = isset($args['min_width']) ? (string) $args['min_width'] : '';
        $cssHeight   = isset($args['height']) ? (string) $args['height'] : '';
        $fullWidth   = isset($args['full_width']) && 'yes' === (string) $args['full_width'];
        $align       = isset($args['align']) ? (string) $args['align'] : 'none';

        $titleText = isset($args['title_text']) ? (string) $args['title_text'] : '';
        $showTitle = isset($args['show_title']) && 'yes' === (string) $args['show_title'];

        // Generar clase única para estilos específicos de instancia si fuera necesario
        $instanceClass = isset($args['instance_class']) ? (string) $args['instance_class'] : 'glory-image-' . substr(md5(uniqid('', true)), 0, 8);

        $containerStyles = '';
        if (in_array($align, ['left', 'right', 'center'], true)) {
            $containerStyles .= 'text-align:' . $align . ';';
        }

        $imgStyles = 'max-width:100%;';
        if ($aspectRatio !== '') {
            $imgStyles .= 'aspect-ratio:' . esc_attr($aspectRatio) . ';';
        }
        $imgStyles .= 'object-fit:' . esc_attr($objectFit) . ';';
        if (!$fullWidth && $minWidth !== '') {
            $imgStyles .= 'min-width:' . esc_attr($minWidth) . ';';
        }
        if ($fullWidth) {
            $imgStyles .= 'width:100%;';
        }
        if ($cssHeight !== '') {
            $imgStyles .= 'height:' . esc_attr($cssHeight) . ';';
        } else {
            $imgStyles .= 'height:auto;';
        }

        $gbnAttrs = '';
        if (class_exists(\Glory\Core\GloryFeatures::class) && \Glory\Core\GloryFeatures::isActive('gbn', 'glory_gbn_activado') !== false) {
            $gbnRole    = self::gbnDefaults();
            $configAttr = esc_attr(wp_json_encode($gbnRole['config'] ?? []));
            $schemaAttr = esc_attr(wp_json_encode($gbnRole['schema'] ?? []));
            $gbnAttrs   = ' data-gbn-image="1" data-gbn-role="image"'
                . ' data-gbn-config="' . $configAttr . '"'
                . ' data-gbn-schema="' . $schemaAttr . '"';
        }

        $html  = '<div class="glory-image ' . esc_attr($instanceClass) . '" style="' . esc_attr($containerStyles) . '"' . $gbnAttrs . '>';
        $html .= '<img class="glory-image__image" src="' . esc_url($imageUrl) . '" alt="' . esc_attr($alt) . '" loading="lazy" style="' . esc_attr($imgStyles) . '" />';
        if ($showTitle && $titleText !== '') {
            $html .= '<div class="glory-image__title">' . esc_html($titleText) . '</div>';
        }
        $html .= '</div>';

        return $html;
    }
}
