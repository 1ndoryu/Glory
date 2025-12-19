<?php

/**
 * Renderizador de Cabecera (Header)
 *
 * Orquesta la visualización de la cabecera del sitio, integrando el logo,
 * el menú de navegación (propio o de Avada) y controles de UI adicionales.
 *
 * @package Glory\Components
 */

namespace Glory\Components;

use Glory\Integration\Compatibility;
use Glory\Core\GloryFeatures;
use Glory\Components\LogoRenderer;

/**
 * Clase HeaderRenderer.
 *
 * Gestiona la estructura principal del <header>.
 */
class HeaderRenderer
{
    /**
     * Renderiza el header del sitio.
     *
     * @param array $config Configuración del header:
     *                      - 'modoLogo': 'default', 'image', 'text', 'none'.
     *                      - 'textoLogo': Texto a mostrar si el modo es texto.
     *                      - 'logoImageId': ID de la imagen si el modo es image.
     *                      - 'idMenu': ID HTML para el menú.
     *                      - 'claseHeader': Clases extra para el contenedor.
     */
    public static function render(array $config = []): void
    {
        // Verificar exclusión explícita por configuración
        if (!empty($config['exclude_on']) && function_exists('is_page') && is_page($config['exclude_on'])) {
            return;
        }

        // Exclusión forzada para el panel de control de GBN
        if (function_exists('is_page') && is_page('gbn-control-panel')) {
            return;
        }

        // Valores desde el array de config
        $logoModo    = $config['modoLogo'] ?? (Compatibility::avadaActivo() ? 'default' : 'image');
        $idMenu      = $config['idMenu'] ?? 'mainMenu';

        $claseExtraHeader = '';
        if ($logoModo === 'text') {
            $claseExtraHeader = ' header-logo-text-mode';
        } elseif ($logoModo === 'none') {
            $claseExtraHeader = ' header-no-logo';
        }

        $menuActivo  = GloryFeatures::isActive('menu', 'glory_componente_menu_activado');
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
        <?php if ($menuActivo) : ?>
            <header class="<?php echo esc_attr(trim($claseHeader)); ?>" role="banner">
                <div class="siteMenuContainer">

                    <?php if ($logoModo !== 'none') : ?>
                        <div class="siteMenuLogo">
                            <?php echo LogoRenderer::get_html(); ?>
                        </div>

                        <?php if ($usarMenuAvada) : ?>
                            <?php
                            // Cuando el tema Avada está activo y la constante lo permite, usamos su menú principal nativo.
                            avada_main_menu();
                            ?>
                        <?php else : ?>
                            <nav class="siteMenuNav" role="navigation">
                                <div class="navTitle"><?php esc_html_e('Navigation', 'glory'); ?></div>
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

                        <button aria-label="<?php esc_attr_e('Toggle menu', 'glory'); ?>" class="burger" type="button">
                            <span></span>
                            <span></span>
                        </button>

                        <?php
                        // echo ThemeToggle::render();
                        ?>
                    <?php endif; ?>
                    <button class="background" aria-label="<?php esc_attr_e('Close menu', 'glory'); ?>"></button>
                    <div class="buscadorMenu">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </div>

            </header>
        <?php endif; ?>
<?php
    }
}
