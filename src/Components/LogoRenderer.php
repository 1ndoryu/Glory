<?php
/**
 * Renderizador de Logo
 *
 * Componente encargado de mostrar el logo del sitio, con soporte para lógica
 * específica de integraciones (como Avada) y modos de visualización configurables
 * (imagen, texto, SVG).
 *
 * @package Glory\Components
 */

namespace Glory\Components;

use Glory\Manager\OpcionManager;
use Glory\Integration\Compatibility;
use Glory\Utility\AssetsUtility;

/**
 * Clase LogoRenderer.
 *
 * Gestiona la visualización del logo mediante métodos directos y shortcodes.
 */
class LogoRenderer
{
    /**
     * Registra el shortcode `[theme_logo]`.
     */
    public static function register_shortcode(): void
    {
        add_shortcode('theme_logo', [self::class, 'render_shortcode']);
    }

    /**
     * Callback para el shortcode de logo.
     *
     * @param array|string $atts Atributos del shortcode (width, filter).
     * @return string HTML del logo.
     */
    public static function render_shortcode($atts): string
    {
        $atts = shortcode_atts(['width' => '', 'filter' => ''], $atts, 'theme_logo');
        return self::get_html([
            'width'  => $atts['width'],
            'filter' => $atts['filter'],
        ]);
    }

    /**
     * Genera el HTML del logo según la configuración actual.
     *
     * Soporta modos: 'default' (Avada), 'image' (Media Library), 'text' (Texto simple) y 'none'.
     *
     * @param array $args Argumentos de visualización:
     *                    - 'width': Ancho CSS opcional.
     *                    - 'filter': Filtro CSS opcional (alias 'white', 'black' o valor raw).
     * @return string HTML del logo.
     */
    public static function get_html(array $args = []): string
    {
        $defaultMode = Compatibility::avadaActivo() ? 'default' : 'image';
        $logoMode    = OpcionManager::get('glory_logo_mode', $defaultMode);
        $width       = $args['width'] ?? '';
        $filterInput = $args['filter'] ?? '';

        $filterCss   = '';
        $filterAlias = strtolower(trim($filterInput));

        switch ($filterAlias) {
            case 'white':
                $filterCss = 'brightness(0) invert(1)';
                break;
            case 'black':
                $filterCss = 'brightness(0)';
                break;
            case '':
                $filterCss = '';
                break;
            default:
                $filterCss = $filterInput;
        }

        if ($logoMode === 'none') {
            return '';
        }

        $style = '';
        if ($width !== '' || $filterCss !== '') {
            $styleContent = '';
            if (!empty($width)) {
                $styleContent .= 'width: ' . esc_attr($width) . '; height: auto;';
            }
            if ($filterCss !== '') {
                $styleContent .= ' filter: ' . esc_attr($filterCss) . ';';
            }
            $style = 'style="' . trim($styleContent) . '"';
        }
        $homeUrl  = esc_url(home_url('/'));
        $blogName = esc_attr(get_bloginfo('name', 'display'));
        $output   = '';

        if ($logoMode === 'text') {
            $logoText      = OpcionManager::get('glory_logo_text', $blogName);
            $textStyleAttr = '';
            if ($filterAlias === 'white') {
                $textStyleAttr = ' style="color: #ffffff;"';
            } elseif ($filterAlias === 'black') {
                $textStyleAttr = ' style="color: #000000;"';
            }
            $output = '<a href="' . $homeUrl . '" rel="home" class="glory-logo-text"' . $textStyleAttr . '>' . esc_html($logoText) . '</a>';
        } else {
            $logoHtml = '';
            if (Compatibility::avadaActivo() && $logoMode === 'default') {
                if (function_exists('fusion_get_theme_option')) {
                    $logoUrl = fusion_get_theme_option('sticky_header_logo', 'url') ?: fusion_get_theme_option('logo', 'url');
                    if ($logoUrl) {
                        $logoHtml = '<a href="' . $homeUrl . '" rel="home"><img src="' . esc_url($logoUrl) . '" alt="' . $blogName . '" ' . $style . '></a>';
                    }
                }
            } elseif (!Compatibility::avadaActivo() && $logoMode === 'image') {
                $imageId = OpcionManager::get('glory_logo_image');
                if ($imageId && $imageUrl = wp_get_attachment_image_url($imageId, 'full')) {
                    $logoHtml = '<a href="' . $homeUrl . '" rel="home"><img src="' . esc_url($imageUrl) . '" alt="' . $blogName . '" ' . $style . '></a>';
                }
            }

            if (empty($logoHtml)) {
                if (function_exists('get_custom_logo') && has_custom_logo()) {
                    $logoHtml = get_custom_logo();
                    if (!empty($width) || $filterCss !== '') {
                        $logoHtml = str_replace('<img ', '<img ' . $style . ' ', $logoHtml);
                    }
                } else {
                    // Fallback al logo por defecto de Glory
                    $defaultLogoUrl = AssetsUtility::imagenUrl('glory::elements/blackExampleLogo.png');
                    if ($defaultLogoUrl) {
                        $logoHtml = '<a href="' . $homeUrl . '" rel="home"><img src="' . esc_url($defaultLogoUrl) . '" alt="' . $blogName . '" ' . $style . '></a>';
                    } else {
                        $logoHtml = '<a href="' . $homeUrl . '" rel="home" class="glory-logo-text">' . esc_html($blogName) . '</a>';
                    }
                }
            }
            $output = $logoHtml;
        }

        return '<div class="glory-logo-shortcode-wrapper">' . $output . '</div>';
    }
}
