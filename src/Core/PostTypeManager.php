<?php
namespace Glory\Core;

use Glory\Core\GloryLogger;

use WP_Error;
use WP_Post;

/**
 * Gestiona la definición y registro de Tipos de Entrada Personalizados (Custom Post Types) en WordPress.
 * Permite definir CPTs con argumentos específicos, etiquetas personalizadas y metadatos por defecto.
 * @author @wandorius
 * // @tarea Jules: Evaluar si la generación de etiquetas o el manejo de metaDefault podrían extraerse
 * // a clases colaboradoras si la complejidad de PostTypeManager aumenta significativamente.
 */
class PostTypeManager
{
    /** @var array Almacena las definiciones de los tipos de entrada personalizados. */
    private static $postTypes = [];

    /**
     * Define un nuevo tipo de entrada personalizado.
     *
     * @param string $tipoEntradaSlug El slug para el tipo de entrada (máx. 20 caracteres, alfanumérico minúsculas y guiones bajos).
     * @param array $argumentos Argumentos para register_post_type().
     * @param string|null $nombreSingular Nombre singular para autogenerar etiquetas (ej. "Producto").
     * @param string|null $nombrePlural Nombre plural para autogenerar etiquetas (ej. "Productos").
     * @param array $metaDefault Metadatos a añadir por defecto al crear una nueva entrada de este tipo.
     */
    public static function define(
        string $tipoEntradaSlug,
        array $argumentos,
        ?string $nombreSingular = null,
        ?string $nombrePlural   = null,
        array $metaDefault      = []
    ): void {
        if (!self::_validarSlugTipoEntrada($tipoEntradaSlug)) {
            return;
        }
        if (isset(self::$postTypes[$tipoEntradaSlug])) {
            // Ya registrado, no hacer nada o loguear advertencia.
            GloryLogger::warning("PostTypeManager: El tipo de entrada '{$tipoEntradaSlug}' ya ha sido definido previamente. Se omite la nueva definición.");
            return;
        }

        self::_configurarArgumentosAdicionales($argumentos, $tipoEntradaSlug, $nombreSingular, $nombrePlural, $metaDefault);

        self::$postTypes[$tipoEntradaSlug] = [
            'argumentos'  => $argumentos, // Argumentos finales para register_post_type
            'singular'    => $nombreSingular, // Guardado para referencia, si es necesario
            'plural'      => $nombrePlural,   // Guardado para referencia
            'metaDefault' => $metaDefault,  // Metadatos por defecto
        ];
    }

    /**
     * Valida el slug del tipo de entrada.
     *
     * @param string $tipoEntradaSlug El slug a validar.
     * @return bool True si es válido, false en caso contrario.
     */
    private static function _validarSlugTipoEntrada(string $tipoEntradaSlug): bool
    {
        if (empty($tipoEntradaSlug)) {
            GloryLogger::error('PostTypeManager: El slug del tipo de entrada no puede estar vacío.');
            return false;
        }
        if (strlen($tipoEntradaSlug) > 20) {
            GloryLogger::error("PostTypeManager: El slug del tipo de entrada '{$tipoEntradaSlug}' excede los 20 caracteres permitidos.");
            return false;
        }
        if (!preg_match('/^[a-z0-9_]+$/', $tipoEntradaSlug)) {
            GloryLogger::error("PostTypeManager: El slug del tipo de entrada '{$tipoEntradaSlug}' contiene caracteres inválidos. Solo se permiten letras minúsculas, números y guiones bajos.");
            return false;
        }
        $tiposReservados = ['post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'action', 'author', 'order', 'theme'];
        if (in_array($tipoEntradaSlug, $tiposReservados, true)) {
            GloryLogger::error("PostTypeManager: El slug '{$tipoEntradaSlug}' está reservado por WordPress y no puede ser utilizado para un tipo de entrada personalizado.");
            return false;
        }
        return true;
    }

    /**
     * Configura los argumentos 'labels' y 'supports' si no están definidos explícitamente.
     *
     * @param array &$argumentos Array de argumentos pasado por referencia.
     * @param string $tipoEntradaSlug Slug del tipo de entrada.
     * @param string|null $nombreSingular Nombre singular.
     * @param string|null $nombrePlural Nombre plural.
     * @param array $metaDefault Array de metadatos por defecto.
     */
    private static function _configurarArgumentosAdicionales(
        array &$argumentos,
        string $tipoEntradaSlug,
        ?string $nombreSingular,
        ?string $nombrePlural,
        array $metaDefault
    ): void {
        // Generar etiquetas automáticamente si se proporcionan nombres singular/plural y no hay etiquetas definidas.
        if ($nombreSingular && $nombrePlural && !isset($argumentos['labels'])) {
            $argumentos['labels'] = self::generarEtiquetas($nombreSingular, $nombrePlural);
        } elseif (($nombreSingular || $nombrePlural) && !isset($argumentos['labels'])) {
            // Advertir si solo se proporciona uno de los nombres, ya que ambos son necesarios para generar etiquetas.
            GloryLogger::warning("PostTypeManager: Para la generación automática de etiquetas del tipo de entrada '{$tipoEntradaSlug}', se requieren tanto el nombre singular como el plural. No se generaron etiquetas automáticamente.");
        }

        // Establecer 'public' a true por defecto si no se especifica.
        $argumentos['public'] = $argumentos['public'] ?? true;

        // Establecer 'supports' por defecto si hay etiquetas y nombres, pero no 'supports'.
        if (isset($argumentos['labels']) && $nombreSingular && $nombrePlural && !isset($argumentos['supports'])) {
            $argumentos['supports'] = ['title', 'editor', 'thumbnail'];
        }

        // Asegurar que 'supports' sea un array.
        $argumentos['supports'] = $argumentos['supports'] ?? [];
        if (!is_array($argumentos['supports'])) {
            $argumentos['supports'] = []; // Forzar a array si es un tipo incorrecto.
        }

        // Añadir soporte para 'custom-fields' si se definen metadatos por defecto y no está ya en 'supports'.
        if (!empty($metaDefault) && !in_array('custom-fields', $argumentos['supports'], true)) {
            $argumentos['supports'][] = 'custom-fields';
        }
    }

    /**
     * Genera un array de etiquetas para un tipo de entrada personalizado.
     *
     * @param string $singular Nombre singular del tipo de entrada (ej. "Libro").
     * @param string $plural Nombre plural del tipo de entrada (ej. "Libros").
     * @return array Array de etiquetas listas para usar en `register_post_type`.
     */
    private static function generarEtiquetas(string $singular, string $plural): array
    {
        $dominioText = 'glory'; // Dominio de texto para traducciones.

        return [
            'name'                  => _x($plural, 'nombre general del tipo de entrada', $dominioText),
            'singular_name'         => _x($singular, 'nombre singular del tipo de entrada', $dominioText),
            'menu_name'             => _x($plural, 'texto del menú de administración', $dominioText),
            'name_admin_bar'        => _x($singular, 'Añadir nuevo en la barra de herramientas', $dominioText),
            'add_new'               => __('Añadir nuevo', $dominioText), // Cambiado de 'Add New' a 'Añadir nuevo'
            'add_new_item'          => sprintf(__('Añadir nuevo %s', $dominioText), $singular),
            'new_item'              => sprintf(__('Nuevo %s', $dominioText), $singular),
            'edit_item'             => sprintf(__('Editar %s', $dominioText), $singular),
            'view_item'             => sprintf(__('Ver %s', $dominioText), $singular),
            'view_items'            => sprintf(__('Ver %s', $dominioText), $plural),
            'all_items'             => sprintf(__('Todos los %s', $dominioText), $plural), // Cambiado 'All %s' a 'Todos los %s'
            'search_items'          => sprintf(__('Buscar %s', $dominioText), $plural),
            'parent_item_colon'     => sprintf(__('Superior %s:', $dominioText), $singular), // Cambiado 'Parent %s:' a 'Superior %s:'
            'not_found'             => sprintf(__('No se han encontrado %s.', $dominioText), strtolower($plural)), // Cambiado 'No %s found.' a 'No se han encontrado %s.'
            'not_found_in_trash'    => sprintf(__('No se han encontrado %s en la papelera.', $dominioText), strtolower($plural)), // Cambiado 'No %s found in Trash.' a 'No se han encontrado %s en la papelera.'
            'featured_image'        => _x('Imagen Destacada', 'Sobrescribe la frase “Imagen Destacada” para este tipo de entrada. Añadido en 4.3', $dominioText),
            'set_featured_image'    => _x('Establecer imagen destacada', 'Sobrescribe la frase “Establecer imagen destacada” para este tipo de entrada. Añadido en 4.3', $dominioText),
            'remove_featured_image' => _x('Eliminar imagen destacada', 'Sobrescribe la frase “Eliminar imagen destacada” para este tipo de entrada. Añadido en 4.3', $dominioText),
            'use_featured_image'    => _x('Usar como imagen destacada', 'Sobrescribe la frase “Usar como imagen destacada” para este tipo de entrada. Añadido en 4.3', $dominioText),
            'archives'              => _x(sprintf('Archivos de %s', $singular), 'La etiqueta del archivo del tipo de entrada utilizada en los menús de navegación. Por defecto “Archivos de entradas”. Añadido en 4.4', $dominioText), // Cambiado '%s Archives' a 'Archivos de %s'
            'insert_into_item'      => _x(sprintf('Insertar en %s', strtolower($singular)), 'Sobrescribe la frase “Insertar en la entrada”/”Insertar en la página” (usada al insertar medios en una entrada). Añadido en 4.4', $dominioText),
            'uploaded_to_this_item' => _x(sprintf('Subido a este %s', strtolower($singular)), 'Sobrescribe la frase “Subido a esta entrada”/”Subido a esta página” (usada al ver medios adjuntos a una entrada). Añadido en 4.4', $dominioText),
            'filter_items_list'     => _x(sprintf('Filtrar lista de %s', strtolower($plural)), 'Texto para lectores de pantalla para el encabezado de los enlaces de filtro en la pantalla de listado del tipo de entrada. Por defecto “Filtrar lista de entradas”/”Filtrar lista de páginas”. Añadido en 4.4', $dominioText),
            'items_list_navigation' => _x(sprintf('Navegación de lista de %s', $plural), 'Texto para lectores de pantalla para el encabezado de paginación en la pantalla de listado del tipo de entrada. Por defecto “Navegación de la lista de entradas”/”Navegación de la lista de páginas”. Añadido en 4.4', $dominioText), // Cambiado '%s list navigation' a 'Navegación de lista de %s'
            'items_list'            => _x(sprintf('Lista de %s', $plural), 'Texto para lectores de pantalla para el encabezado de la lista de elementos en la pantalla de listado del tipo de entrada. Por defecto “Lista de entradas”/”Lista de páginas”. Añadido en 4.4', $dominioText), // Cambiado '%s list' a 'Lista de %s'
        ];
    }

    /**
     * Registra el hook 'init' para procesar los tipos de entrada definidos.
     */
    public static function register(): void
    {
        add_action('init', [self::class, 'procesarTiposEntrada'], 10);
    }

    /**
     * Procesa y registra todos los tipos de entrada personalizados definidos.
     * Se ejecuta en el hook 'init'.
     */
    public static function procesarTiposEntrada(): void
    {
        if (empty(self::$postTypes)) {
            return;
        }

        foreach (self::$postTypes as $tipoEntradaSlug => $definicion) {
            $resultado = register_post_type($tipoEntradaSlug, $definicion['argumentos']);

            if (is_wp_error($resultado)) {
                GloryLogger::error("PostTypeManager: Falló el registro del tipo de entrada '{$tipoEntradaSlug}'.", [
                    'codigoError'      => $resultado->get_error_code(),
                    'mensajeError'     => $resultado->get_error_message(),
                    'argumentosUsados' => $definicion['argumentos']
                ]);
                continue;
            }

            // Si se definieron metadatos por defecto, se engancha la función para agregarlos.
            if (!empty($definicion['metaDefault']) && is_array($definicion['metaDefault'])) {
                add_action('save_post_' . $tipoEntradaSlug, [self::class, 'agregarMetaDefault'], 10, 3);
            }
        }
    }

    /**
     * Agrega metadatos por defecto a una entrada cuando se crea por primera vez.
     * Este método se engancha a 'save_post_{$tipoEntradaSlug}'.
     *
     * @param int     $idEntrada        ID de la entrada que se está guardando.
     * @param WP_Post $entradaObjeto    Objeto de la entrada que se está guardando.
     * @param bool    $esActualizacion  True si es una actualización de una entrada existente, false si es nueva.
     */
    public static function agregarMetaDefault(
        int $idEntrada,
        WP_Post $entradaObjeto,
        bool $esActualizacion
    ): void {
        // Evitar ejecución durante autoguardado, revisiones o si es una actualización.
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($idEntrada) || $esActualizacion) {
            return;
        }

        $tipoEntrada = $entradaObjeto->post_type;
        if (!isset(self::$postTypes[$tipoEntrada])) {
            GloryLogger::error("PostTypeManager (agregarMetaDefault): Hook llamado para el ID de entrada {$idEntrada} (tipo '{$tipoEntrada}'), pero este tipo no está definido internamente.", ['estadoEntrada' => $entradaObjeto->post_status]);
            return;
        }

        $definicion = self::$postTypes[$tipoEntrada];

        if (!empty($definicion['metaDefault']) && is_array($definicion['metaDefault'])) {
            $metaDefaultLocal = $definicion['metaDefault'];
            foreach ($metaDefaultLocal as $metaClave => $valorDefault) {
                // add_post_meta con $unique = true no añadirá el meta si ya existe.
                $added = add_post_meta($idEntrada, $metaClave, $valorDefault, true);

                // Si add_post_meta devuelve false, significa que el meta ya existía o falló.
                // Se verifica si el valor existente es una cadena vacía, lo que podría indicar un problema si se esperaba un valor.
                if ($added === false) {
                    $valorExistente = get_post_meta($idEntrada, $metaClave, true);
                    if ($valorExistente === '') { // Solo loguear si el meta existente está vacío, ya que podría ser intencional que ya exista.
                        GloryLogger::error("PostTypeManager (agregarMetaDefault): Falló al agregar el metadato '{$metaClave}' para el ID de entrada {$idEntrada} (el metadato ya existía pero estaba vacío).");
                    }
                }
            }
        }
    }

    /**
     * Limpia y regenera las reglas de reescritura de WordPress.
     * Es útil llamar a esto después de registrar o modificar tipos de entrada o taxonomías.
     * Generalmente se usa durante la activación/desactivación de un plugin/tema.
     */
    public static function limpiarReglasReescritura(): void
    {
        // Asegura que los CPTs estén registrados antes de limpiar las reglas.
        self::procesarTiposEntrada();
        flush_rewrite_rules();
    }
}
