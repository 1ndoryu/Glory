<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

use Glory\Plugins\AmazonProduct\i18n\Labels;

/**
 * Tab para importar productos manualmente desde archivos HTML de Amazon.
 * 
 * Responsabilidad unica: Renderizar la interfaz de usuario.
 * La logica AJAX esta en ManualImportAjaxController.
 */
class ManualImportTab implements TabInterface
{
    public function getSlug(): string
    {
        return 'manual-import';
    }

    public function getLabel(): string
    {
        return Labels::get('manual_import_tab');
    }

    public function render(): void
    {
        $this->enqueueAssets();
        $this->renderInterface();
    }

    /**
     * Encola CSS y JavaScript necesarios.
     */
    private function enqueueAssets(): void
    {
        $baseUrl = get_template_directory_uri() . '/Glory/src/Plugins/AmazonProduct/assets';

        wp_enqueue_style(
            'amazon-manual-import',
            $baseUrl . '/css/manual-import.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'amazon-manual-import',
            $baseUrl . '/js/manual-import.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('amazon-manual-import', 'manualImportConfig', [
            'nonce' => wp_create_nonce('amazon_manual_import_ajax')
        ]);
    }

    /**
     * Renderiza la interfaz principal.
     */
    private function renderInterface(): void
    {
        require __DIR__ . '/../Views/manual-import-interface.php';
    }
}
