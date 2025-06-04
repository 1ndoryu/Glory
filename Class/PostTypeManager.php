<?
namespace Glory\Class;

use Glory\Class\GloryLogger;
use WP_Error;
use WP_Post;

class PostTypeManager
{
    private static $postTypes = [];

    public static function define(
        string $tipoEntradaSlug,
        array $argumentos,
        ?string $nombreSingular = null,
        ?string $nombrePlural   = null,
        array $metaDefault      = []
    ): void {
        if (empty($tipoEntradaSlug)) {
            GloryLogger::error('PostTypeManager: El slug del tipo de entrada no puede estar vacío.');
            return;
        }
        if (strlen($tipoEntradaSlug) > 20) {
            GloryLogger::error("PostTypeManager: Slug tipo entrada '{$tipoEntradaSlug}' excede 20 caracteres.");
            return;
        }
        if (!preg_match('/^[a-z0-9_]+$/', $tipoEntradaSlug)) {
            GloryLogger::error("PostTypeManager: Slug tipo entrada '{$tipoEntradaSlug}' inválido. Debe ser alfanumérico minúsculas con guiones bajos.");
            return;
        }

        $tiposReservados = ['post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'action', 'author', 'order', 'theme'];
        if (in_array($tipoEntradaSlug, $tiposReservados, true)) {
            GloryLogger::error("PostTypeManager: Slug tipo entrada '{$tipoEntradaSlug}' es reservado. Elija otro.");
            return;
        }
        if (isset(self::$postTypes[$tipoEntradaSlug])) {
            return;
        }

        if ($nombreSingular && $nombrePlural && !isset($argumentos['labels'])) {
            $argumentos['labels'] = self::generarEtiquetas($nombreSingular, $nombrePlural);
        } elseif (($nombreSingular || $nombrePlural) && !isset($argumentos['labels'])) {
            GloryLogger::error("PostTypeManager: Se requieren nombres singular y plural para generar etiquetas para '{$tipoEntradaSlug}' si no se proveen 'labels'.");
        }

        $argumentos['public'] = $argumentos['public'] ?? true;

        if (isset($argumentos['labels']) && !isset($argumentos['supports']) && $nombreSingular && $nombrePlural) {
            $argumentos['supports'] = ['title', 'editor', 'thumbnail'];
        }

        $argumentos['supports'] = $argumentos['supports'] ?? [];
        if (!is_array($argumentos['supports'])) {
            $argumentos['supports'] = [];
        }

        if (!empty($metaDefault) && !in_array('custom-fields', $argumentos['supports'], true)) {
            $argumentos['supports'][] = 'custom-fields';
        }

        self::$postTypes[$tipoEntradaSlug] = [
            'argumentos'  => $argumentos,
            'singular'    => $nombreSingular,
            'plural'      => $nombrePlural,
            'metaDefault' => $metaDefault,
        ];
    }

    private static function generarEtiquetas(string $singular, string $plural): array
    {
        $dominioText = 'glory';

        return [
            'name'                  => _x($plural, 'Post type general name', $dominioText),
            'singular_name'         => _x($singular, 'Post type singular name', $dominioText),
            'menu_name'             => _x($plural, 'Admin Menu text', $dominioText),
            'name_admin_bar'        => _x($singular, 'Add New on Toolbar', $dominioText),
            'add_new'               => __('Add New', $dominioText),
            'add_new_item'          => sprintf(__('Add New %s', $dominioText), $singular),
            'new_item'              => sprintf(__('New %s', $dominioText), $singular),
            'edit_item'             => sprintf(__('Edit %s', $dominioText), $singular),
            'view_item'             => sprintf(__('View %s', $dominioText), $singular),
            'view_items'            => sprintf(__('View %s', $dominioText), $plural),
            'all_items'             => sprintf(__('All %s', $dominioText), $plural),
            'search_items'          => sprintf(__('Search %s', $dominioText), $plural),
            'parent_item_colon'     => sprintf(__('Parent %s:', $dominioText), $singular),
            'not_found'             => sprintf(__('No %s found.', $dominioText), strtolower($plural)),
            'not_found_in_trash'    => sprintf(__('No %s found in Trash.', $dominioText), strtolower($plural)),
            'featured_image'        => _x('Featured Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', $dominioText),
            'set_featured_image'    => _x('Set featured image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', $dominioText),
            'remove_featured_image' => _x('Remove featured image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', $dominioText),
            'use_featured_image'    => _x('Use as featured image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', $dominioText),
            'archives'              => _x(sprintf('%s Archives', $singular), 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', $dominioText),
            'insert_into_item'      => _x(sprintf('Insert into %s', strtolower($singular)), 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', $dominioText),
            'uploaded_to_this_item' => _x(sprintf('Uploaded to this %s', strtolower($singular)), 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', $dominioText),
            'filter_items_list'     => _x(sprintf('Filter %s list', strtolower($plural)), 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', $dominioText),
            'items_list_navigation' => _x(sprintf('%s list navigation', $plural), 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', $dominioText),
            'items_list'            => _x(sprintf('%s list', $plural), 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', $dominioText),
        ];
    }

    public static function register(): void
    {
        add_action('init', [self::class, 'procesarTiposEntrada'], 10);
    }

    // ANTERIOR: processPostTypes
    public static function procesarTiposEntrada(): void
    {
        if (empty(self::$postTypes)) {
            return;
        }

        foreach (self::$postTypes as $tipoEntradaSlug => $definicion) {
            $resultado = register_post_type($tipoEntradaSlug, $definicion['argumentos']);

            if (is_wp_error($resultado)) {
                GloryLogger::error("PostTypeManager: Falló registro tipo entrada '{$tipoEntradaSlug}'.", [
                    'codigoError'      => $resultado->get_error_code(),
                    'mensajeError'     => $resultado->get_error_message(),
                    'argumentosUsados' => $definicion['argumentos']
                ]);
                continue;
            }

            if (!empty($definicion['metaDefault']) && is_array($definicion['metaDefault'])) {
                add_action('save_post_' . $tipoEntradaSlug, [self::class, 'agregarMetaDefault'], 10, 3);
            }
        }
    }

    // ANTERIOR: addDefaultMeta
    public static function agregarMetaDefault(
        int $idEntrada,
        WP_Post $entradaObjeto,
        bool $esActualizacion
    ): void {
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($idEntrada) || $esActualizacion) {
            return;
        }

        $tipoEntrada = $entradaObjeto->post_type;
        if (!isset(self::$postTypes[$tipoEntrada])) {
            GloryLogger::error("PostTypeManager agregarMetaDefault: Hook para ID entrada {$idEntrada}, tipo '{$tipoEntrada}' no definido internamente.", ['estadoEntrada' => $entradaObjeto->post_status]);
            return;
        }

        // ANTERIOR: $definition
        $definicion = self::$postTypes[$tipoEntrada];

        if (!empty($definicion['metaDefault']) && is_array($definicion['metaDefault'])) {
            $metaDefaultLocal = $definicion['metaDefault'];
            foreach ($metaDefaultLocal as $metaClave => $valorDefault) {
                $added = add_post_meta($idEntrada, $metaClave, $valorDefault, true);

                if ($added === false) {
                    $valorExistente = get_post_meta($idEntrada, $metaClave, true);
                    if ($valorExistente === '') {
                        GloryLogger::error("PostTypeManager agregarMetaDefault: Falló agregar meta '{$metaClave}' para ID entrada {$idEntrada}.");
                    }
                }
            }
        }
    }

    public static function limpiarReglasReescritura(): void
    {
        self::procesarTiposEntrada();
        flush_rewrite_rules();
    }
}
