<?php

namespace Glory\Components;

use Glory\Manager\OpcionManager;
use Glory\Core\Compatibility;
use Glory\Utility\AssetsUtility;

class LogoRenderer
{
    public static function register_shortcode(): void
    {
        add_shortcode('theme_logo', [self::class, 'render_shortcode']);
    }

    public static function render_shortcode($atts): string
    {
        $atts = shortcode_atts(['width' => '', 'filter' => ''], $atts, 'theme_logo');
        return self::get_html([
            'width'  => $atts['width'],
            'filter' => $atts['filter'],
        ]);
    }

    public static function get_html(array $args = []): string
    {
        $default_mode = Compatibility::is_avada_active() ? 'default' : 'image';
        $logo_mode = OpcionManager::get('glory_logo_mode', $default_mode);
        $width  = $args['width'] ?? '';
        $filter_input = $args['filter'] ?? '';

        $filter_css = '';
        $filter_alias = strtolower(trim($filter_input));
        switch ($filter_alias) {
            case 'white':
                $filter_css = 'brightness(0) invert(1)';
                break;
            case 'black':
                $filter_css = 'brightness(0)';
                break;
            case '':
                $filter_css = '';
                break;
            default:
                $filter_css = $filter_input;
        }

        if ($logo_mode === 'none') {
            return '';
        }

        $style = '';
        if ($width !== '' || $filter_css !== '') {
            $style_content = '';
            if (!empty($width)) {
                $style_content .= 'width: ' . esc_attr($width) . '; height: auto;';
            }
            if ($filter_css !== '') {
                $style_content .= ' filter: ' . esc_attr($filter_css) . ';';
            }
            $style = 'style="' . trim($style_content) . '"';
        }
        $home_url = esc_url(home_url('/'));
        $blog_name = esc_attr(get_bloginfo('name', 'display'));
        $output = '';

        if ($logo_mode === 'text') {
            $logo_text = OpcionManager::get('glory_logo_text', $blog_name);
            $text_style_attr = '';
            if ($filter_alias === 'white') {
                $text_style_attr = ' style="color: #ffffff;"';
            } elseif ($filter_alias === 'black') {
                $text_style_attr = ' style="color: #000000;"';
            }
            $output = '<a href="' . $home_url . '" rel="home" class="glory-logo-text"' . $text_style_attr . '>' . esc_html($logo_text) . '</a>';
        } else {
            $logo_html = '';
            if (Compatibility::is_avada_active() && $logo_mode === 'default') {
                if (function_exists('fusion_get_theme_option')) {
                    $logo_url = fusion_get_theme_option('sticky_header_logo', 'url') ?: fusion_get_theme_option('logo', 'url');
                    if ($logo_url) {
                        $logo_html = '<a href="' . $home_url . '" rel="home"><img src="' . esc_url($logo_url) . '" alt="' . $blog_name . '" ' . $style . '></a>';
                    }
                }
            } elseif (!Compatibility::is_avada_active() && $logo_mode === 'image') {
                $image_id = OpcionManager::get('glory_logo_image');
                if ($image_id && $image_url = wp_get_attachment_image_url($image_id, 'full')) {
                    $logo_html = '<a href="' . $home_url . '" rel="home"><img src="' . esc_url($image_url) . '" alt="' . $blog_name . '" ' . $style . '></a>';
                }
            }

            if (empty($logo_html)) {
                if (function_exists('get_custom_logo') && has_custom_logo()) {
                    $logo_html = get_custom_logo();
                    if (!empty($width) || $filter_css !== '') {
                        $logo_html = str_replace('<img ', '<img ' . $style . ' ', $logo_html);
                    }
                } else {
                    // Fallback al logo por defecto de Glory
                    $default_logo_url = AssetsUtility::getImagenUrl('glory::elements/whiteExampleLogo.png');
                    if ($default_logo_url) {
                         $logo_html = '<a href="' . $home_url . '" rel="home"><img src="' . esc_url($default_logo_url) . '" alt="' . $blog_name . '" ' . $style . '></a>';
                    } else {
                        $logo_html = '<a href="' . $home_url . '" rel="home" class="glory-logo-text">' . esc_html($blog_name) . '</a>';
                    }
                }
            }
            $output = $logo_html;
        }

        return '<div class="glory-logo-shortcode-wrapper">' . $output . '</div>';
    }
}