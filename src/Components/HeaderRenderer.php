<?php

namespace Glory\Components;

use Glory\Integration\Compatibility;
use Glory\Utility\AssetsUtility;
use Glory\Core\GloryFeatures;

class HeaderRenderer
{
    public static function render(array $config = []): void
    {
        // Values from config array
        $logoModo = $config['modoLogo'] ?? (Compatibility::avadaActivo() ? 'default' : 'image');
        $textoLogo = $config['textoLogo'] ?? get_bloginfo('name', 'display');
        $logoImageId = $config['logoImageId'] ?? null;
        $idMenu = $config['idMenu'] ?? 'mainMenu';

        $claseExtraHeader = '';
        if ($logoModo === 'text') {
            $claseExtraHeader = ' header-logo-text-mode';
        } elseif ($logoModo === 'none') {
            $claseExtraHeader = ' header-no-logo';
        }

        $menuActivo = GloryFeatures::isActive('menu', 'glory_componente_menu_activado');
        $claseHeader = 'siteMenuW ' . ($config['claseHeader'] ?? '') . $claseExtraHeader;
?>
        <?php if ($menuActivo): ?>
            <header class="<?php echo esc_attr(trim($claseHeader)); ?>" role="banner">
                <div class="siteMenuContainer">

                    <?php if ($logoModo !== 'none') : ?>
                        <div class="siteMenuLogo">
                            <?php
                            if ($logoModo === 'text') {
                            ?>
                                <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php echo esc_html($textoLogo); ?></a>
                            <?php
                            } else {
                                if (Compatibility::avadaActivo() && $logoModo === 'default') {
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
                                } elseif (!Compatibility::avadaActivo() && $logoModo === 'image') {
                                    if ($logoImageId && $image_url = wp_get_attachment_image_url($logoImageId, 'full')) {
                                        echo '<a href="' . esc_url(home_url('/')) . '" rel="home"><img src="' . esc_url($image_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '"></a>';
                                    } else {
                                        $default_logo_url = AssetsUtility::imagenUrl('glory::elements/blackExampleLogo.png');
                                        echo '<a href="' . esc_url(home_url('/')) . '" rel="home"><img src="' . esc_url($default_logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '"></a>';
                                    }
                                } else {
                                    if (function_exists('the_custom_logo') && has_custom_logo()) {
                                        the_custom_logo();
                                    } else {
                                        $default_logo_url = AssetsUtility::imagenUrl('glory::elements/blackExampleLogo.png');
                                        echo '<a href="' . esc_url(home_url('/')) . '" rel="home"><img src="' . esc_url($default_logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '"></a>';
                                    }
                                }
                            }
                            ?>
                        </div>



                        <nav class="siteMenuNav" role="navigation">
                            <div class="navTitle">Navigation</div>
                            <?php
                            if (has_nav_menu('main_navigation')) {
                                wp_nav_menu([
                                    'theme_location' => 'main_navigation',
                                    'container'      => false,
                                    'menu_id'        => esc_attr($idMenu),
                                    'menu_class'     => 'menu menu-level-1',
                                    'depth'          => 3,
                                    'walker'         => new \Glory\Components\MenuWalker(),
                                ]);
                            }
                            ?>
                        </nav>

                        <button aria-label="Toggle menu" class="burger" type="button">
                            <span></span>
                            <span></span>
                        </button>

                        <?php echo ThemeToggle::render(); ?>
                    <?php endif; ?>
                </div>


                <button class="background" aria-label="Close menu"></button>

            </header>
        <?php endif; ?>
<?php
    }
}
