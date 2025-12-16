<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

use Glory\Plugins\AmazonProduct\i18n\Labels;
use Glory\Plugins\AmazonProduct\Admin\AdminAssetLoader;

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
        AdminAssetLoader::enqueueManualImport();
        $this->renderInterface();
    }

    /**
     * Renderiza la interfaz principal.
     */
    private function renderInterface(): void
    {
        require __DIR__ . '/../Views/manual-import-interface.php';
    }
}
