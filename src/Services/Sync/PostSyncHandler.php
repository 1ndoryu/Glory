<?php
// Glory/src/Services/Sync/PostSyncHandler.php

namespace Glory\Services\Sync;

use Glory\Core\GloryLogger;
use WP_Post;
use WP_Error;

/**
 * Maneja la lógica de creación, actualización y comparación para un post específico.
 */
class PostSyncHandler
{
    private const META_CLAVE_SLUG_DEFAULT = '_glory_default_content_slug';
    private const META_CLAVE_EDITADO_MANUALMENTE = '_glory_default_content_edited';

    private PostRelationHandler $relationHandler;
    private MediaIntegrityService $mediaIntegrity;

    /**
     * Crea un nuevo post a partir de su definición.
     */
    public function create(string $postType, array $definition): ?int
    {
        $insertData = $this->prepareCorePostData($postType, $definition);
        $postId = wp_insert_post($insertData, true);

        if (is_wp_error($postId)) {
            GloryLogger::error("PostSyncHandler: FALLÓ al insertar post '{$definition['slugDefault']}'.", ['error' => $postId->get_error_message()]);
            return null;
        }

        $this->relationHandler = new PostRelationHandler($postId);
        $this->relationHandler->setRelations($definition);
        $this->mediaIntegrity = new MediaIntegrityService();
        $this->mediaIntegrity->repairPostMedia($postId, $definition);

        GloryLogger::info("PostSyncHandler: Post '{$definition['slugDefault']}' (ID: {$postId}) creado.");
        return $postId;
    }

    /**
     * Actualiza un post existente.
     */
    public function update(int $postId, array $definition, bool $isForced): void
    {
        $updateData = $this->prepareCorePostData(get_post_type($postId), $definition);
        $updateData['ID'] = $postId;

        $result = wp_update_post($updateData, true);

        if (is_wp_error($result)) {
            GloryLogger::error("PostSyncHandler: FALLÓ al actualizar post ID {$postId}.", ['error' => $result->get_error_message()]);
            return;
        }

        $this->relationHandler = new PostRelationHandler($postId);
        $this->relationHandler->setRelations($definition);
        $this->mediaIntegrity = new MediaIntegrityService();
        $this->mediaIntegrity->repairPostMedia($postId, $definition);

        if ($isForced) {
            $this->cleanupMeta($postId, $definition);
            delete_post_meta($postId, self::META_CLAVE_EDITADO_MANUALMENTE);
            GloryLogger::info("PostSyncHandler: Post ID {$postId} actualizado (forzado).");
        } else {
            GloryLogger::info("PostSyncHandler: Post ID {$postId} actualizado.");
        }
    }

    /**
     * Compara una definición con el estado del post en la BD.
     */
    public function needsUpdate(\WP_Post $postDb, array $definition): bool
    {
        $this->relationHandler = new PostRelationHandler($postDb->ID);

        // Compara campos principales
        if (
            $postDb->post_title !== ($definition['titulo'] ?? '') ||
            $postDb->post_content !== ($definition['contenido'] ?? '') ||
            $postDb->post_status !== ($definition['estado'] ?? 'publish') ||
            $postDb->post_excerpt !== ($definition['extracto'] ?? '')
        ) {
            \Glory\Core\GloryLogger::info('NeedsUpdate: cambio en campos principales', [
                'ID' => (int) $postDb->ID,
                'title_changed' => $postDb->post_title !== ($definition['titulo'] ?? ''),
                'content_changed' => $postDb->post_content !== ($definition['contenido'] ?? ''),
                'status_changed' => $postDb->post_status !== ($definition['estado'] ?? 'publish'),
                'excerpt_changed' => $postDb->post_excerpt !== ($definition['extracto'] ?? ''),
            ]);
            return true;
        }

        // Compara metadatos (excluyendo claves que pertenecen a taxonomías)
        $taxonomies = get_object_taxonomies($postDb->post_type);
        $metaEntrada = $definition['metaEntrada'] ?? [];
        foreach ($metaEntrada as $key => $value) {
            if (in_array($key, $taxonomies, true)) {
                // Es una taxonomía; su asignación se maneja en relaciones, no como meta
                continue;
            }
            if (get_post_meta($postDb->ID, $key, true) != $value) {
                \Glory\Core\GloryLogger::info('NeedsUpdate: diferencia en meta', [
                    'ID' => (int) $postDb->ID,
                    'meta_key' => (string) $key,
                    'db_value' => (string) get_post_meta($postDb->ID, $key, true),
                ]);
                return true;
            }
        }

        // Verifica si la imagen destacada cambio (no solo si falta).
        if (!empty($definition['imagenDestacadaAsset'])) {
            $currentThumbId = get_post_thumbnail_id($postDb->ID);
            $definedAsset = (string) $definition['imagenDestacadaAsset'];

            if (empty($currentThumbId)) {
                // No hay imagen - necesita update si el asset existe
                $aid = \Glory\Utility\AssetsUtility::findExistingAttachmentIdForAsset($definedAsset);
                if ($aid) {
                    \Glory\Core\GloryLogger::info('NeedsUpdate: falta imagen destacada asignada', [
                        'ID' => (int) $postDb->ID,
                        'asset' => $definedAsset,
                    ]);
                    return true;
                }
            } else {
                // Hay imagen - verificar si coincide con la definicion
                // Obtener el asset guardado en el attachment actual
                $currentAssetRequested = get_post_meta($currentThumbId, '_glory_asset_requested', true);
                $currentAssetSource = get_post_meta($currentThumbId, '_glory_asset_source', true);
                $currentAsset = is_string($currentAssetRequested) && $currentAssetRequested !== ''
                    ? $currentAssetRequested
                    : (is_string($currentAssetSource) && $currentAssetSource !== '' ? $currentAssetSource : '');

                // Comparar assets (normalizando el formato)
                // Normalizar ambos assets al formato de ruta expandida para comparacion consistente
                // El asset actual viene como ruta (ej: Glory/assets/images/elements/libros/48leyesdelpoder.png)
                // El asset definido viene como alias::nombre (ej: elements::libros/48leyesdelpoder.png)
                $definedAssetExpanded = $this->expandAssetReference($definedAsset);

                if ($currentAsset !== $definedAssetExpanded) {
                    // El asset cambio en la definicion - forzar actualizacion
                    // Solo si el nuevo asset existe o puede importarse
                    if (\Glory\Utility\AssetsUtility::assetExists($definedAsset)) {
                        \Glory\Core\GloryLogger::info('NeedsUpdate: imagen destacada cambio en definicion', [
                            'ID' => (int) $postDb->ID,
                            'asset_actual' => $currentAsset,
                            'asset_definido' => $definedAssetExpanded,
                            'asset_definido_original' => $definedAsset,
                        ]);
                        return true;
                    }
                }
            }
        }

        // Compara relaciones (simplificado, puedes expandir esto si es necesario)
        // Esta lógica podría volverse compleja, por ahora se mantiene simple.
        // Una comparación exhaustiva de relaciones podría requerir más consultas.

        return false;
    }

    /**
     * Prepara el array de datos para wp_insert_post o wp_update_post.
     */
    private function prepareCorePostData(string $postType, array $definition): array
    {
        $data = [
            'post_type'    => $postType,
            'post_title'   => $definition['titulo'],
            'post_content' => $definition['contenido'] ?? '',
            'post_status'  => $definition['estado'] ?? 'publish',
            'post_excerpt' => $definition['extracto'] ?? '',
            'meta_input'   => $definition['metaEntrada'] ?? [],
        ];
        $data['meta_input'][self::META_CLAVE_SLUG_DEFAULT] = trim($definition['slugDefault']);
        return $data;
    }

    /**
     * Limpia metadatos que ya no están en la definición durante una actualización forzada.
     */
    private function cleanupMeta(int $postId, array $definition): void
    {
        $definedMeta = $definition['metaEntrada'] ?? [];
        $existingMeta = get_post_meta($postId);

        foreach (array_keys($existingMeta) as $existingKey) {
            if (str_starts_with($existingKey, '_') || array_key_exists($existingKey, $definedMeta)) {
                continue;
            }
            delete_post_meta($postId, $existingKey);
        }
    }

    /**
     * Expande una referencia de asset al formato de ruta completa.
     * Convierte "alias::archivo" a "ruta/completa/archivo".
     */
    private function expandAssetReference(string $assetReference): string
    {
        // Si no tiene el separador ::, ya esta en formato expandido
        if (strpos($assetReference, '::') === false) {
            return $assetReference;
        }

        // Usar AssetsUtility para parsear la referencia y obtener la ruta
        $parsed = \Glory\Utility\AssetsUtility::parseAssetReference($assetReference);
        if (!is_array($parsed) || count($parsed) !== 2) {
            return $assetReference;
        }

        list($alias, $nombreArchivo) = $parsed;

        // Mapa de alias a rutas (debe coincidir con AssetsUtility::init)
        $aliasMap = [
            'glory' => 'Glory/assets/images',
            'elements' => 'Glory/assets/images/elements',
            'colors' => 'Glory/assets/images/colors',
            'logos' => 'Glory/assets/images/logos',
            'tema' => 'App/Assets/images',
        ];

        $basePath = $aliasMap[$alias] ?? null;
        if ($basePath === null) {
            return $assetReference;
        }

        return $basePath . '/' . ltrim($nombreArchivo, '/\\');
    }
}
