<?php

namespace Glory\Components;

use Glory\Manager\OpcionManager;

class HeaderRenderer
{
    public static function render(array $opciones = []): void
    {
        $logoModo = OpcionManager::get('glory_logo_mode', 'default');
        
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
                        } else { // Modo 'default'
                            // Lógica original para asegurar compatibilidad
                            if (function_exists('the_custom_logo') && has_custom_logo()) {
                                the_custom_logo();
                            } elseif (function_exists('fusion_get_theme_option')) {
                                $logo = fusion_get_theme_option('sticky_header_logo', 'url');
                                if ($logo) {
                                    // Se envuelve en un enlace <a> para consistencia
                                    echo '<a href="' . esc_url(home_url('/')) . '" rel="home">';
                                    echo '<img src="' . esc_url($logo) . '" alt="' . esc_attr(get_bloginfo('name')) . '">';
                                    echo '</a>';
                                } else {
                                     ?>
                                    <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>
                                    <?php
                                }
                            } else {
                                ?>
                                <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>
                                <?php
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