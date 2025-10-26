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
?>
        <?php if ($menuActivo): ?>
            <header class="<?php echo esc_attr(trim($claseHeader)); ?>" role="banner">
                <div class="siteMenuContainer">

                    <?php if ($logoModo !== 'none') : ?>
                        <div class="siteMenuLogo">
                            <?php echo LogoRenderer::get_html(); ?>
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

                        <?php
                        // echo ThemeToggle::render();
                        ?>
                    <?php endif; ?>
                </div>


                <button class="background" aria-label="Close menu"></button>

            </header>
        <?php endif; ?>
<?php
    }
}
