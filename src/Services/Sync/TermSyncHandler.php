<?php
// Glory/src/Services/Sync/TermSyncHandler.php

namespace Glory\Services\Sync;

use Glory\Core\GloryLogger;
use Glory\Utility\AssetsUtility;

/**
 * Gestiona la sincronización de definiciones de taxonomías (términos).
 */
class TermSyncHandler
{
    private const META_CLAVE_CATEGORIA_GESTIONADA = '_glory_managed_category';

    /**
     * Sincroniza todas las definiciones de categorías globales.
     */
    public function sync(): void
    {
        $categoriasDef = $GLOBALS['glory_categorias_definidas'] ?? [];
        if (empty($categoriasDef)) {
            // GloryLogger::info('TermSyncHandler: No hay definiciones de categorías para sincronizar.');
            return;
        }

        $this->deleteObsoleteTerms($categoriasDef);
        $this->createOrUpdateTerms($categoriasDef);
    }

    /**
     * Elimina términos gestionados que ya no están en la definición.
     */
    private function deleteObsoleteTerms(array $definitions): void
    {
        $definedNames = array_filter(array_column($definitions, 'nombre'));
        $managedTerms = get_terms([
            'taxonomy' => 'category',
            'hide_empty' => false,
            'meta_key' => self::META_CLAVE_CATEGORIA_GESTIONADA,
            'meta_value' => '1'
        ]);

        if (is_wp_error($managedTerms)) {
            return;
        }

        foreach ($managedTerms as $term) {
            if (!in_array($term->name, $definedNames, true)) {
                wp_delete_term($term->term_id, 'category');
                // GloryLogger::info("TermSyncHandler: Categoría obsoleta '{$term->name}' eliminada.");
            }
        }
    }

    /**
     * Crea o actualiza los términos basados en las definiciones.
     */
    private function createOrUpdateTerms(array $definitions): void
    {
        foreach ($definitions as $def) {
            $nombre = $def['nombre'] ?? null;
            if (!$nombre) continue;

            $term = get_term_by('name', $nombre, 'category');

            if (!$term) {
                $result = wp_insert_term($nombre, 'category', ['description' => $def['descripcion'] ?? '']);
                if (!is_wp_error($result)) {
                    $term = get_term($result['term_id']);
                }
            }

            if ($term instanceof \WP_Term) {
                // Marcar como gestionado y actualizar datos
                update_term_meta($term->term_id, self::META_CLAVE_CATEGORIA_GESTIONADA, '1');

                if ($term->description !== ($def['descripcion'] ?? '')) {
                    wp_update_term($term->term_id, 'category', ['description' => $def['descripcion'] ?? '']);
                }

                if (!empty($def['imagenAsset']) && !get_term_meta($term->term_id, 'glory_category_image_id', true)) {
                    $attachmentId = AssetsUtility::get_attachment_id_from_asset($def['imagenAsset']);
                    if ($attachmentId) {
                        update_term_meta($term->term_id, 'glory_category_image_id', $attachmentId);
                    }
                }
            }
        }
    }
}
