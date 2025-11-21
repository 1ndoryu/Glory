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
                </div>

            </header>
        <?php endif; ?>
        <?php
    }
}
