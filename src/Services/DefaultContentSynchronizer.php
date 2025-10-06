<?php

namespace Glory\Services;

use Glory\Core\DefaultContentRegistry;
use Glory\Repository\DefaultContentRepository;
use Glory\Core\GloryLogger;
use Glory\Services\Sync\PostSyncHandler;
use Glory\Services\Sync\TermSyncHandler;
use WP_Post;


class DefaultContentSynchronizer
{
    private const META_CLAVE_EDITADO_MANUALMENTE = '_glory_default_content_edited';

    private DefaultContentRepository $repository;
    private PostSyncHandler $postHandler;
    private TermSyncHandler $termHandler;
    private bool $isProcessing = false;

    public function __construct()
    {
        $this->repository = new DefaultContentRepository();
        $this->postHandler = new PostSyncHandler();
        $this->termHandler = new TermSyncHandler();
    }


    public function sincronizar(): void
    {
        if ($this->isProcessing) return;
        $this->isProcessing = true;
        // GloryLogger::info('DCS: Iniciando sincronización de contenido por defecto.');

        $this->termHandler->sync();

        $definitionsByType = DefaultContentRegistry::getDefiniciones();
        if (empty($definitionsByType)) {
            // GloryLogger::info('DCS: No hay definiciones de posts para sincronizar.');
            $this->isProcessing = false;
            return;
        }

        foreach ($definitionsByType as $postType => $config) {
            // LÍNEA DE DEPURACIÓN AÑADIDA
            // GloryLogger::info("DCS: Procesando tipo '{$postType}'. ¿Existe? " . (post_type_exists($postType) ? 'Sí' : 'No'));

            if (!post_type_exists($postType)) continue;

            $this->sincronizarPostsParaTipo($postType, $config);
            $this->eliminarPostsObsoletosParaTipo($postType, $config);
        }

        $this->isProcessing = false;
    }


    public function restablecer(): void
    {
        if ($this->isProcessing) return;
        $this->isProcessing = true;

        foreach (DefaultContentRegistry::getDefiniciones() as $postType => $config) {
            if (!post_type_exists($postType)) continue;

            foreach ($config['definicionesPost'] as $definition) {
                $slugDefault = trim($definition['slugDefault']);
                $post = $this->repository->findPorSlug($postType, $slugDefault);
                if ($post) {
                    $this->postHandler->update($post->ID, $definition, true);
                    GloryLogger::info("DCS: Post '{$slugDefault}' (ID: {$post->ID}) restablecido a default.");
                }
            }
        }
        $this->isProcessing = false;
    }


    public function detectarEdicionManual(int $postId, WP_Post $post, bool $isUpdate): void
    {
        if ($this->isProcessing || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($postId) || !$isUpdate) {
            return;
        }
        if (!is_admin() || !current_user_can('edit_post', $postId)) {
            return;
        }

        $slugDefault = get_post_meta($postId, '_glory_default_content_slug', true);
        if (!empty($slugDefault) && !$this->repository->haSidoEditadoManualmente($postId)) {
            update_post_meta($postId, self::META_CLAVE_EDITADO_MANUALMENTE, '1');
        }
    }


    private function sincronizarPostsParaTipo(string $postType, array $config): void
    {
        foreach ($config['definicionesPost'] as $definition) {
            $slugDefault = trim($definition['slugDefault']);
            $existingPost = $this->repository->findPorSlug($postType, $slugDefault);

            if ($existingPost) {
                $this->gestionarPostExistente($existingPost, $definition, $config['modoActualizacion']);
            } else {
                $this->postHandler->create($postType, $definition);
            }
        }
    }


    private function gestionarPostExistente(WP_Post $post, array $definition, string $updateMode): void
    {
        if ($updateMode === 'none') return;

        $isEdited = $this->repository->haSidoEditadoManualmente($post->ID);

        if ($updateMode === 'force') {
            $this->postHandler->update($post->ID, $definition, true);
        } elseif ($updateMode === 'smart' && !$isEdited) {
            if ($this->postHandler->needsUpdate($post, $definition)) {
                $this->postHandler->update($post->ID, $definition, false);
            }
        }
    }


    private function eliminarPostsObsoletosParaTipo(string $postType, array $config): void
    {
        if (empty($config['permitirEliminacion'])) return;

        $definedSlugs = array_column($config['definicionesPost'], 'slugDefault');
        $obsoleteIds = $this->repository->findObsoletos($postType, $definedSlugs);

        foreach ($obsoleteIds as $postId) {
            if (!$this->repository->haSidoEditadoManualmente($postId)) {
                if (!wp_delete_post($postId, true)) {
                    GloryLogger::error("DCS: FALLÓ al eliminar post obsoleto ID {$postId}.");
                }
            }
        }
    }
}