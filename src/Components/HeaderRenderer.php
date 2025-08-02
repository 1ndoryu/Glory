<?php

namespace Glory\Components;

use Glory\Manager\OpcionManager;
use Glory\Core\Compatibility;
use Glory\Utility\AssetsUtility;

class HeaderRenderer
{
    public static function render(array $opciones = []): void
    {
        $default_mode = Compatibility::is_avada_active() ? 'default' : 'image';
        $logoModo = OpcionManager::get('glory_logo_mode', $default_mode);

        $claseExtraHeader = '';
        if ($logoModo === 'text') {
            $claseExtraHeader = ' header-logo-text-mode';
        } elseif ($logoModo === 'none') {
            $claseExtraHeader = ' header-no-logo';
        }

        $claseHeader = 'siteMenuW ' . ($opciones['claseHeader'] ?? '') . $claseExtraHeader;
        $idMenu = $opciones['idMenu'] ?? 'main-menu';
?>
        <header class="<?php echo esc_attr(trim($claseHeader)); ?>" role="banner">
            <div class="siteMenuContainer">

                <?php if ($logoModo !== 'none') : ?>
                    <div class="siteMenuLogo">
                        <?php
                        if ($logoModo === 'text') {
                            $textoLogo = OpcionManager::get('glory_logo_text', get_bloginfo('name', 'display'));
                        ?>
                            <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php echo esc_html($textoLogo); ?></a>
                        <?php
                        } else {
                            if (Compatibility::is_avada_active() && $logoModo === 'default') {
                                if (function_exists('the_custom_logo') && has_custom_logo()) {
                                    the_custom_logo();
                                } elseif (function_exists('fusion_get_theme_option')) {
                                    $logo = fusion_get_theme_option('sticky_header_logo', 'url');
                                    if ($logo) {
                                        echo '<a href="' . esc_url(home_url('/')) . '" rel="home">';
                                        echo '<img src="' . esc_url($logo) . '" alt="' . esc_attr(get_bloginfo('name')) . '">';
                                        echo '</a>';
                                    } else {
                                        echo '<a href="' . esc_url(home_url('/')) . '" rel="home">' . get_bloginfo('name') . '</a>';
                                    }
                                } else {
                                    echo '<a href="' . esc_url(home_url('/')) . '" rel="home">' . get_bloginfo('name') . '</a>';
                                }
                            } elseif (!Compatibility::is_avada_active() && $logoModo === 'image') {
                                $image_id = OpcionManager::get('glory_logo_image');
                                if ($image_id && $image_url = wp_get_attachment_image_url($image_id, 'full')) {
                                    echo '<a href="' . esc_url(home_url('/')) . '" rel="home"><img src="' . esc_url($image_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '"></a>';
                                } else {
                                    // Usar logo por defecto de Glory
                                    $default_logo_url = AssetsUtility::getImagenUrl('glory::elements/whiteExampleLogo.png');
                                    echo '<a href="' . esc_url(home_url('/')) . '" rel="home"><img src="' . esc_url($default_logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '"></a>';
                                }
                            } else {
                                if (function_exists('the_custom_logo') && has_custom_logo()) {
                                    the_custom_logo();
                                } else {
                                    // Usar logo por defecto de Glory
                                    $default_logo_url = AssetsUtility::getImagenUrl('glory::elements/whiteExampleLogo.png');
                                    echo '<a href="' . esc_url(home_url('/')) . '" rel="home"><img src="' . esc_url($default_logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '"></a>';
                                }
                            }
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <nav class="siteMenuNav" role="navigation">
                    <div class="nav-title">Navigation</div>
                    <?php
                    if (has_nav_menu('main_navigation')) {
                        wp_nav_menu([
                            'theme_location' => 'main_navigation',
                            'container'      => false,
                            'menu_id'        => esc_attr($idMenu),
                        ]);
                    }
                    ?>
                </nav>

                <button aria-label="Toggle menu" class="burger" type="button">
                    <span></span>
                    <span></span>
                </button>
            </div>

            <button class="background" aria-label="Close menu"></button>
        </header>
<?php
    }
}
