<?php

/**
 * Registro central de schemas del proyecto.
 *
 * Carga y valida todos los TableSchema y PostTypeSchema definidos en App/Config/Schema/.
 * En modo estricto (WP_DEBUG), rechaza accesos a tablas/columnas sin schema registrado.
 *
 * @package Glory\Core
 */

namespace Glory\Core;

use Glory\Contracts\TableSchema;
use Glory\Contracts\PostTypeSchema;
use Glory\Exception\SchemaException;

class SchemaRegistry
{
    /** @var array<string, TableSchema> tabla => instancia */
    private static array $tablas = [];

    /** @var array<string, PostTypeSchema> postType => instancia */
    private static array $postTypes = [];

    private static bool $inicializado = false;

    /**
     * Inicializa el registro cargando schemas desde App/Config/Schema/.
     * Se llama una vez en el bootstrap de Glory.
     */
    public static function init(): void
    {
        if (self::$inicializado) {
            return;
        }

        self::$inicializado = true;

        $schemaDir = get_stylesheet_directory() . '/App/Config/Schema';
        if (!is_dir($schemaDir)) {
            return;
        }

        $archivos = glob($schemaDir . '/*Schema.php');
        if ($archivos === false) {
            return;
        }

        foreach ($archivos as $archivo) {
            $nombreClase = pathinfo($archivo, PATHINFO_FILENAME);
            $fqcn = 'App\\Config\\Schema\\' . $nombreClase;

            /* Cargar si no está autoloaded */
            if (!class_exists($fqcn, false)) {
                require_once $archivo;
            }

            if (!class_exists($fqcn)) {
                continue;
            }

            $instancia = new $fqcn();

            if ($instancia instanceof TableSchema) {
                self::registrarTabla($instancia);
            } elseif ($instancia instanceof PostTypeSchema) {
                self::registrarPostType($instancia);
            }
        }
    }

    /**
     * Registra un TableSchema manualmente (para tests o módulos externos).
     */
    public static function registrarTabla(TableSchema $schema): void
    {
        self::$tablas[$schema->tabla()] = $schema;
    }

    /**
     * Registra un PostTypeSchema manualmente.
     */
    public static function registrarPostType(PostTypeSchema $schema): void
    {
        self::$postTypes[$schema->postType()] = $schema;
    }

    /**
     * Obtiene el schema de una tabla.
     */
    public static function tabla(string $nombre): ?TableSchema
    {
        self::init();
        return self::$tablas[$nombre] ?? null;
    }

    /**
     * Obtiene el schema de un post type.
     */
    public static function postType(string $nombre): ?PostTypeSchema
    {
        self::init();
        return self::$postTypes[$nombre] ?? null;
    }

    /**
     * Verifica si una columna existe en una tabla registrada.
     */
    public static function columnaExiste(string $tabla, string $columna): bool
    {
        self::init();
        $schema = self::$tablas[$tabla] ?? null;
        if ($schema === null) {
            return false;
        }

        return $schema->columnaExiste($columna);
    }

    /**
     * Verifica si un meta key existe para un post type registrado.
     */
    public static function metaExiste(string $postType, string $metaKey): bool
    {
        self::init();
        $schema = self::$postTypes[$postType] ?? null;
        if ($schema === null) {
            return false;
        }

        return $schema->metaExiste($metaKey);
    }

    /**
     * Determina si el modo estricto está activo.
     * En modo estricto, las validaciones lanzan excepciones en vez de warnings.
     */
    public static function modoEstricto(): bool
    {
        return defined('WP_DEBUG') && WP_DEBUG;
    }

    /**
     * Valida que una tabla tenga schema registrado.
     * En modo estricto, lanza SchemaException si no tiene.
     */
    public static function exigirTabla(string $tabla): void
    {
        self::init();

        if (isset(self::$tablas[$tabla])) {
            return;
        }

        $msg = "SchemaRegistry: tabla '{$tabla}' no tiene schema registrado. "
             . "Crea App/Config/Schema/{$tabla}Schema.php o usa 'npx glory create table {$tabla}'.";

        if (self::modoEstricto()) {
            throw new SchemaException($msg, $tabla);
        }

        if (function_exists('error_log')) {
            error_log("[Glory Schema Warning] {$msg}");
        }
    }

    /**
     * Valida que una columna exista en el schema de una tabla.
     */
    public static function exigirColumna(string $tabla, string $columna): void
    {
        /* Primero verificar que la tabla tiene schema */
        self::exigirTabla($tabla);

        $schema = self::$tablas[$tabla] ?? null;
        if ($schema === null) {
            return; /* Ya se reportó en exigirTabla */
        }

        if ($schema->columnaExiste($columna)) {
            return;
        }

        $similares = self::sugerirColumna($schema, $columna);
        $sugerencia = $similares ? " Quizás quisiste: " . implode(', ', $similares) : '';

        $msg = "SchemaRegistry: columna '{$columna}' no existe en tabla '{$tabla}'.{$sugerencia}";

        if (self::modoEstricto()) {
            throw new SchemaException($msg, $tabla, $columna);
        }

        if (function_exists('error_log')) {
            error_log("[Glory Schema Warning] {$msg}");
        }
    }

    /**
     * Sugiere columnas similares (para mensajes de error útiles).
     *
     * @return string[]
     */
    private static function sugerirColumna(TableSchema $schema, string $columna): array
    {
        $columnas = $schema->nombreColumnas();
        $sugerencias = [];

        foreach ($columnas as $existente) {
            $distancia = levenshtein($columna, $existente);
            if ($distancia <= 3) {
                $sugerencias[] = $existente;
            }
        }

        return $sugerencias;
    }

    /**
     * Obtiene todas las tablas registradas.
     *
     * @return array<string, TableSchema>
     */
    public static function todasTablas(): array
    {
        return self::$tablas;
    }

    /**
     * Obtiene todos los post types registrados.
     *
     * @return array<string, PostTypeSchema>
     */
    public static function todosPostTypes(): array
    {
        return self::$postTypes;
    }

    /**
     * Resetea el registro (para tests).
     */
    public static function reset(): void
    {
        self::$tablas = [];
        self::$postTypes = [];
        self::$inicializado = false;
    }
}
