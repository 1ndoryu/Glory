<?php

namespace Glory\Components;

use Glory\Integration\Compatibility;
use Glory\Core\GloryFeatures;
use Glory\Components\LogoRenderer;

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

        // Permitir controlar por constante si el header de Glory debe usar el menú nativo de Avada.
        // Reglas:
        // - Si GLORY_USE_FULL_HEADER está en true => siempre usamos el HTML de menú de Glory
        //   (nav.siteMenuNav + ul.menu-level-1), ideal para reutilizar los estilos de header.css.
        // - Si GLORY_USE_FULL_HEADER no está activo y Avada está disponible:
        //     - Por defecto (constante GLORY_HEADER_USE_AVADA_MENU no definida) se usa avada_main_menu().
        //     - Si defines GLORY_HEADER_USE_AVADA_MENU en false, se fuerza el menú HTML de Glory.
        $usarMenuAvada = Compatibility::avadaActivo()
            && function_exists('avada_main_menu')
            && (!defined('GLORY_USE_FULL_HEADER') || !GLORY_USE_FULL_HEADER)
            && (!defined('GLORY_HEADER_USE_AVADA_MENU') || GLORY_HEADER_USE_AVADA_MENU);
        ?>
        <?php if ($menuActivo): ?>
            <header class="<?php echo esc_attr(trim($claseHeader)); ?>" role="banner">
                <div class="siteMenuContainer">

                    <?php if ($logoModo !== 'none') : ?>
                        <div class="siteMenuLogo">
                            <?php echo LogoRenderer::get_html(); ?>
                        </div>

                        <?php if ($usarMenuAvada): ?>
                            <?php
                            // Cuando el tema Avada está activo y la constante lo permite, usamos su menú principal nativo.
                            avada_main_menu();
                            ?>
                        <?php else : ?>
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
                        <?php endif; ?>

                        <button aria-label="Toggle menu" class="burger" type="button">
                            <span></span>
                            <span></span>
                        </button>

                        <?php
                        // echo ThemeToggle::render();
                        ?>
                    <?php endif; ?>
                    <button class="background" aria-label="Close menu"></button>
                </div>



            </header>
        <?php endif; ?>
        <?php
    }
}
