<?php

namespace Glory\Integration\Avada;

final class AvadaBuilderCptSupport
{
    public static function register(): void
    {
        if ( ! function_exists('add_action') ) {
            return;
        }

        // Asegurar que todos los CPT públicos tengan soporte del editor.
        add_action('init', [self::class, 'ensureEditorSupportForPublicCpts'], 20);

        // Añadir automáticamente todos los CPT públicos (con editor) a la whitelist del builder.
        add_filter('fusion_builder_allowed_post_types', [self::class, 'filterAddPublicCptsToBuilder'], 20, 1);
    }

    public static function ensureEditorSupportForPublicCpts(): void
    {
        if ( ! function_exists('get_post_types') || ! function_exists('post_type_supports') ) {
            return;
        }
        $objects = get_post_types(['public' => true], 'objects');
        if ( ! is_array($objects) ) {
            return;
        }

        foreach ($objects as $slug => $obj) {
            // Excluir tipos básicos o especiales.
            if ( in_array($slug, ['attachment','revision','nav_menu_item','custom_css','customize_changeset'], true) ) {
                continue;
            }
            if ( ! post_type_supports($slug, 'editor') ) {
                add_post_type_support($slug, 'editor');
            }
        }
    }

    /**
     * Agrega todos los CPT públicos con soporte de editor a la lista permitida del Avada Builder.
     *
     * @param array $types
     * @return array
     */
    public static function filterAddPublicCptsToBuilder(array $types): array
    {
        if ( ! function_exists('get_post_types') || ! function_exists('post_type_supports') ) {
            return $types;
        }

        $objects = get_post_types(['public' => true], 'objects');
        if ( ! is_array($objects) ) {
            return $types;
        }

        foreach ($objects as $slug => $obj) {
            if ( in_array($slug, ['attachment','revision','nav_menu_item','custom_css','customize_changeset'], true) ) {
                continue;
            }
            if ( post_type_supports($slug, 'editor') ) {
                $types[] = $slug;
            }
        }

        return array_values(array_unique($types));
    }
}


