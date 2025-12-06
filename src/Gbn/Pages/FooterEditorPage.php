<?php

namespace Glory\Gbn\Pages;

use Glory\Gbn\Services\TemplateService;

/**
 * FooterEditorPage - Página de administración para editar el Footer con GBN.
 * 
 * Accesible desde: /wp-admin/admin.php?page=gbn-edit-footer
 * 
 * @package Glory\Gbn\Pages
 */
class FooterEditorPage
{
    /** @var string Slug de la página */
    public const PAGE_SLUG = 'gbn-edit-footer';

    /** @var string Capability requerida */
    public const CAPABILITY = 'edit_theme_options';

    /**
     * Registra la página de admin y los hooks necesarios.
     */
    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'addMenuPage']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);
    }

    /**
     * Añade la página al menú de administración.
     */
    public static function addMenuPage(): void
    {
        add_submenu_page(
            'themes.php', // Parent: Apariencia
            __('Editar Footer GBN', 'glory'),
            __('Footer GBN', 'glory'),
            self::CAPABILITY,
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    /**
     * Encola los assets necesarios para el editor.
     * 
     * @param string $hook Hook de la página actual
     */
    public static function enqueueAssets(string $hook): void
    {
        // Solo cargar en nuestra página
        if ($hook !== 'appearance_page_' . self::PAGE_SLUG) {
            return;
        }

        // Los estilos son los mismos que HeaderEditorPage
        wp_add_inline_style('glory-gbn-interactive', self::getEditorStyles());
    }

    /**
     * Obtiene los estilos CSS específicos del editor.
     * 
     * @return string
     */
    private static function getEditorStyles(): string
    {
        return '
            .gbn-template-editor-wrapper {
                max-width: 100%;
                margin: 20px 0;
            }
            .gbn-template-editor-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #ddd;
            }
            .gbn-template-editor-title {
                font-size: 23px;
                font-weight: 400;
                margin: 0;
            }
            .gbn-template-editor-actions {
                display: flex;
                gap: 10px;
            }
            .gbn-template-preview {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                min-height: 200px;
                position: relative;
            }
            .gbn-template-preview-content {
                padding: 0;
            }
            .gbn-template-notice {
                padding: 12px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            .gbn-template-notice.success {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
            }
            .gbn-template-notice.info {
                background: #cce5ff;
                border: 1px solid #b8daff;
                color: #004085;
            }
            .gbn-template-editor-footer {
                margin-top: 20px;
                padding-top: 15px;
                border-top: 1px solid #ddd;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .gbn-editor-hint {
                color: #666;
                font-size: 13px;
            }
        ';
    }

    /**
     * Renderiza la página del editor de Footer.
     */
    public static function renderPage(): void
    {
        // Verificar permisos
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'glory'));
        }

        // Obtener el template actual o el por defecto
        $footerData = TemplateService::getFooter();
        $hasExistingTemplate = TemplateService::hasFooter();
        $templateHtml = $hasExistingTemplate 
            ? $footerData['html'] 
            : TemplateService::getDefaultFooterTemplate();

        ?>
        <div class="wrap gbn-template-editor-wrapper">
            <div class="gbn-template-editor-header">
                <h1 class="gbn-template-editor-title">
                    <?php esc_html_e('Editor de Footer GBN', 'glory'); ?>
                </h1>
                <div class="gbn-template-editor-actions">
                    <button type="button" class="button" id="gbn-reset-footer">
                        <?php esc_html_e('Restaurar por defecto', 'glory'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="gbn-save-footer">
                        <?php esc_html_e('Guardar Footer', 'glory'); ?>
                    </button>
                </div>
            </div>

            <?php if (!$hasExistingTemplate): ?>
            <div class="gbn-template-notice info">
                <p>
                    <strong><?php esc_html_e('Template por defecto:', 'glory'); ?></strong>
                    <?php esc_html_e('Este es el template inicial. Edítalo y guarda para personalizarlo.', 'glory'); ?>
                </p>
            </div>
            <?php endif; ?>

            <div id="gbn-save-notice" class="gbn-template-notice success" style="display: none;">
                <p><?php esc_html_e('Footer guardado correctamente.', 'glory'); ?></p>
            </div>

            <div class="gbn-template-preview" id="gbn-footer-preview">
                <div class="gbn-template-preview-content" id="gbn-footer-content">
                    <?php 
                    // Renderizar el template HTML
                    echo $templateHtml; 
                    ?>
                </div>
            </div>

            <div class="gbn-template-editor-footer">
                <p class="gbn-editor-hint">
                    <?php esc_html_e('💡 Placeholders disponibles: {year}, {siteName}, {siteUrl}', 'glory'); ?>
                </p>
                <?php if ($hasExistingTemplate): ?>
                <button type="button" class="button button-link-delete" id="gbn-delete-footer">
                    <?php esc_html_e('Eliminar template personalizado', 'glory'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <script>
        (function() {
            'use strict';

            // Guardar footer
            document.getElementById('gbn-save-footer').addEventListener('click', function() {
                var content = document.getElementById('gbn-footer-content').innerHTML;
                var config = {};

                // Obtener configuración de GBN si está disponible
                if (window.Gbn && window.Gbn.state && window.Gbn.state.all) {
                    var blocks = window.Gbn.state.all();
                    blocks.forEach(function(block) {
                        if (block.role === 'footer' || block.role === 'logo' || block.role === 'menu') {
                            config[block.id] = block.config;
                        }
                    });
                }

                // Enviar AJAX
                jQuery.post(ajaxurl, {
                    action: 'gbn_save_footer_template',
                    nonce: '<?php echo wp_create_nonce('gbn_template_nonce'); ?>',
                    html: content,
                    config: JSON.stringify(config)
                }, function(response) {
                    if (response.success) {
                        var notice = document.getElementById('gbn-save-notice');
                        notice.style.display = 'block';
                        setTimeout(function() {
                            notice.style.display = 'none';
                        }, 3000);
                    } else {
                        alert('Error al guardar: ' + (response.data || 'Unknown error'));
                    }
                });
            });

            // Restaurar por defecto
            document.getElementById('gbn-reset-footer').addEventListener('click', function() {
                if (!confirm('<?php esc_html_e('¿Restaurar el footer al template por defecto?', 'glory'); ?>')) {
                    return;
                }
                location.reload();
            });

            // Eliminar template
            var deleteBtn = document.getElementById('gbn-delete-footer');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    if (!confirm('<?php esc_html_e('¿Eliminar el template personalizado? Se usará el footer por defecto del tema.', 'glory'); ?>')) {
                        return;
                    }

                    jQuery.post(ajaxurl, {
                        action: 'gbn_delete_footer_template',
                        nonce: '<?php echo wp_create_nonce('gbn_template_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error al eliminar: ' + (response.data || 'Unknown error'));
                        }
                    });
                });
            }
        })();
        </script>
        <?php
    }
}
