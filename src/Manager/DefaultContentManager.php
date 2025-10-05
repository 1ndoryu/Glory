<?php
namespace Glory\Manager;

use Glory\Core\DefaultContentRegistry;
use Glory\Services\DefaultContentSynchronizer;

/**
 * Fachada para el sistema de gestión de contenido por defecto.
 *
 * Proporciona una API pública y estática simple para definir contenido y registrar
 * los procesos de sincronización, delegando toda la lógica compleja a las clases
 * especializadas (Registry, Synchronizer).
 */
class DefaultContentManager
{
    private static ?DefaultContentSynchronizer $sincronizadorInstancia = null;

    /**
     * Define el contenido por defecto para un tipo de post.
     * Delega el almacenamiento de la definición a DefaultContentRegistry.
     *
     * @param string $tipoPost Slug del tipo de post.
     * @param array $postsDefault Array de definiciones de posts.
     * @param string $modoActualizacion 'none', 'force', o 'smart'.
     * @param bool $permitirEliminacion Si se deben eliminar posts obsoletos.
     */
    public static function define(string $tipoPost, array $postsDefault, string $modoActualizacion = 'smart', bool $permitirEliminacion = false): void
    {
        DefaultContentRegistry::define($tipoPost, $postsDefault, $modoActualizacion, $permitirEliminacion);
    }

    /**
     * Registra los hooks necesarios para la sincronización de contenido.
     */
    public static function register(): void
    {
        // Engancha el proceso principal al hook 'init' con prioridad 20.
        add_action('init', [self::class, 'procesarDefinicionesYRegistrarHooks'], 20);
    }

    /**
     * Instancia el sincronizador, ejecuta la sincronización y registra los hooks
     * para la detección de ediciones manuales.
     * Este método es el callback para el hook 'init'.
     */
    public static function procesarDefinicionesYRegistrarHooks(): void
    {
        // Asegura que solo haya una instancia del sincronizador por petición (patrón Singleton).
        if (self::$sincronizadorInstancia === null) {
            self::$sincronizadorInstancia = new DefaultContentSynchronizer();
        }

        // No ejecutar sincronización automáticamente aquí.
        // La sincronización se ejecuta manualmente desde SyncManager o CLI.

        // Después de la sincronización, registra los hooks 'save_post_{tipo}' para detectar
        // ediciones manuales, pero solo para los tipos de post que gestionamos.
        $definiciones = DefaultContentRegistry::getDefiniciones();
        foreach (array_keys($definiciones) as $tipoPost) {
            add_action('save_post_' . $tipoPost, [self::class, 'detectarEdicionManualHook'], 99, 3);
        }
    }
    
    /**
     * Método intermediario que llama al método de instancia en el sincronizador.
     * Este es el callback real para el hook 'save_post_*'.
     */
    public static function detectarEdicionManualHook(int $idPost, \WP_Post $objetoPost, bool $esActualizacion): void
    {
        if (self::$sincronizadorInstancia === null) {
            // Por si el hook se dispara en un contexto donde 'init' no se ha ejecutado.
            self::$sincronizadorInstancia = new DefaultContentSynchronizer();
        }
        self::$sincronizadorInstancia->detectarEdicionManual($idPost, $objetoPost, $esActualizacion);
    }
}