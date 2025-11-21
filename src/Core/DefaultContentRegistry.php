<?php
/**
 * Registro de Contenido por Defecto
 *
 * Mantiene un registro estático de las definiciones de contenido (posts) que
 * el framework debe sincronizar o crear automáticamente.
 *
 * @package Glory\Core
 */

namespace Glory\Core;

/**
 * Registro central para las definiciones del contenido por defecto.
 *
 * Esta clase actúa como un almacén estático para la configuración de todos los
 * posts por defecto que el sistema debe gestionar. Su única responsabilidad es
 * mantener las definiciones en memoria para que otros servicios, como el
 * DefaultContentSynchronizer, puedan consultarlas. No interactúa con la base de
 * datos ni contiene lógica de negocio compleja.
 */
class DefaultContentRegistry
{
    /**
     * @var array Almacén estático para las definiciones.
     * La estructura es: ['post_type' => ['definicionesPost' => [...], 'modoActualizacion' => 'smart', ...]]
     */
    private static array $definiciones = [];

    /**
     * Define el contenido por defecto para un tipo de post específico.
     *
     * @param string $tipoPost El slug del tipo de post.
     * @param array  $postsDefault Array de definiciones de posts.
     * @param string $modoActualizacion 'none', 'force', 'smart'.
     * @param bool   $permitirEliminacion Si se deben eliminar posts de la BD que ya no están definidos en el código.
     */
    public static function define(string $tipoPost, array $postsDefault, string $modoActualizacion = 'smart', bool $permitirEliminacion = false): void
    {
        if (isset(self::$definiciones[$tipoPost])) {
            GloryLogger::warning("DefaultContentRegistry: Las definiciones para el tipo de post '{$tipoPost}' ya existen. Se sobreescribirán.");
        }

        self::$definiciones[$tipoPost] = [
            'definicionesPost'    => $postsDefault,
            'modoActualizacion'   => $modoActualizacion,
            'permitirEliminacion' => $permitirEliminacion,
        ];
    }

    /**
     * Obtiene todas las definiciones registradas.
     *
     * @return array Un array con todas las definiciones.
     */
    public static function getDefiniciones(): array
    {
        return self::$definiciones;
    }

    /**
     * Obtiene la definición para un tipo de post específico.
     *
     * @param string $tipoPost El slug del tipo de post.
     * @return array|null La configuración o null si no se encuentra.
     */
    public static function getDefinicion(string $tipoPost): ?array
    {
        return self::$definiciones[$tipoPost] ?? null;
    }
}
