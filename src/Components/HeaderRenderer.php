<?php

namespace Glory\Components;

class HeaderRenderer
{
    /**
     * Renderiza el header del sitio utilizando las clases y estructura solicitadas.
     *
     * @param array $opciones Opciones para personalizar el header.
     * 'claseHeader' (string) Clases CSS adicionales para la etiqueta <header>.
     * 'idMenu' (string) El ID que se asignará al menú.
     */
    public static function render(array $opciones = []): void
    {
        $claseHeader = 'siteMenuW ' . ($opciones['claseHeader'] ?? '');
        $idMenu = $opciones['idMenu'] ?? 'main-menu';
?>
        <header class="<?php echo esc_attr(trim($claseHeader)); ?>" role="banner">
            <div class="siteMenuContainer">
                <div class="siteMenuLogo">
                    <?php
                    if (function_exists('the_custom_logo') && has_custom_logo()) {
                        the_custom_logo();
                    } elseif (function_exists('fusion_get_theme_option')) {
                        //
                        $logo = fusion_get_theme_option('sticky_header_logo', 'url');
                        if ($logo) {
                            echo '<img src="' . esc_url($logo) . '" alt="' . esc_attr(get_bloginfo('name')) . '">';
                        }
                    } else {
                    ?>
                        <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>
                    <?php
                    }
                    ?>
                </div>

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
