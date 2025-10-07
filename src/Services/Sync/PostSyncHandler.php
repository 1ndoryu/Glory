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
                return true;
            }
        }

        // Verifica si la imagen destacada está asignada.
        if (!empty($definition['imagenDestacadaAsset'])) {
            $currentThumbId = get_post_thumbnail_id($postDb->ID);
            if (empty($currentThumbId)) {
                // Solo marcar que necesita update si existe un adjunto válido para el asset
                $aid = \Glory\Utility\AssetsUtility::findExistingAttachmentIdForAsset((string) $definition['imagenDestacadaAsset']);
                if ($aid) {
                    return true;
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
}
