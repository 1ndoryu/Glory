<?php

namespace Glory\Integration\Avada\Options;

use Glory\Integration\Avada\AvadaOptionsBridge;
use Glory\Core\GloryFeatures;

final class Registrar
{
    public static function register(): void
    {
        // Si la integración con Avada está desactivada explícitamente, no registrar hooks
        if (GloryFeatures::isEnabled('avadaIntegration') === false) {
            return;
        }
        add_filter('avada_options_sections', [AvadaOptionsBridge::class, 'injectGlorySection']);

        // Invalidar caché de secciones del panel para reflejar cambios en claves/estructura (no afecta valores guardados).
        add_action('init', function() {
            if (function_exists('delete_transient')) {
                delete_transient('fusion_tos');
                delete_transient('fusion_fb_tos');
            }
        }, 1);
        add_action('init', [AvadaOptionsBridge::class, 'bootstrapOptionFilters'], 20);
        add_action('after_setup_theme', [AvadaOptionsBridge::class, 'bootstrapOptionFilters'], 20);
        add_action('wp_loaded', [AvadaOptionsBridge::class, 'bootstrapOptionFilters'], 20);
        add_action('glory_opcion_registered', [AvadaOptionsBridge::class, 'registerFiltersForOption'], 10, 2);

        // Hook seguro post-guardado para sincronizar solo claves glory_* hacia el repositorio sin alterar valores nativos.
        add_action('fusionredux/options/fusion_options/saved', [AvadaOptionsBridge::class, 'onFusionOptionsSaved'], 10, 2);
    }
}


