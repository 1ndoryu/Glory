<?php

namespace Glory\Integration;

use Glory\Manager\OpcionManager;
use Glory\Integration\Avada\AvadaIntegration;
use Glory\Core\GloryFeatures;
use Glory\Integration\Avada\AvadaOptionsBridge;

class IntegrationsManager
{
    /**
     * Registra el hook para agregar los scripts al header.
     */
    public function register(): void
    {
        add_action('wp_head', [$this, 'agregarScriptsAlHeader']);

        // Control centralizado de la integración con Avada.
        // Reglas:
        // - Si el flag está explícitamente en off => no cargar nunca.
        // - Si está explícitamente en on => intentar registrar (si Avada existe).
        // - Si no está definido en control.php => autodetectar Avada y registrar cuando exista.
        $flag = GloryFeatures::isEnabled('avadaIntegration');

        // Helper de detección de Avada (builder/elementos presentes).
        $avadaDisponible = function (): bool {
            return function_exists('fusion_builder_map') || class_exists('Fusion_Element') || function_exists('fusion_core') || class_exists('Fusion_Settings');
        };

        if ($flag === false) {
            // Integración desactivada explícitamente: no registrar nada relacionado a Avada.
            // Además, limpiar caché de secciones del panel de Avada para ocultar "Glory" si quedó cacheada.
            add_action('init', function() {
                try {
                    if (function_exists('delete_transient')) {
                        delete_transient('fusion_tos');
                        delete_transient('fusion_fb_tos');
                    }
                    // Intentar remover filtros en caso de que se hayan agregado antes en el ciclo actual.
                    remove_filter('avada_options_sections', [AvadaOptionsBridge::class, 'injectGlorySection']);
                } catch (\Throwable $t) {
                }
            }, 5);
            return;
        }

        $registrar = function () use ($avadaDisponible) {
            if ($avadaDisponible()) {
                AvadaIntegration::register();
            }
        };

        if ($flag === true) {
            // Forzar registro si Avada está presente ahora o más tarde.
            $registrar();
            add_action('after_setup_theme', $registrar, 20);
            add_action('init', $registrar, 20);
        } else {
            // Sin definición: autodetectar.
            if ($avadaDisponible()) {
                AvadaIntegration::register();
            } else {
                add_action('after_setup_theme', $registrar, 20);
                add_action('init', $registrar, 20);
            }
        }
    }

    /**
     * Imprime las metaetiquetas y scripts en el <head> de la página.
     */
    public function agregarScriptsAlHeader(): void
    {
        $this->agregarMetaGSC();
        $this->agregarScriptGA4();
        $this->agregarScriptsPersonalizados();
    }

    /**
     * Agrega la metaetiqueta de verificación de Google Search Console.
     */
    private function agregarMetaGSC(): void
    {
        $codigoVerificacion = OpcionManager::get('glory_gsc_verification_code');
        if (!empty($codigoVerificacion)) {
            printf(
                '<meta name="google-site-verification" content="%s" />' . "\n",
                esc_attr($codigoVerificacion)
            );
        }
    }

    /**
     * Agrega el script de seguimiento de Google Analytics 4 (gtag.js).
     */
    private function agregarScriptGA4(): void
    {
        $idMedicion = OpcionManager::get('glory_ga4_measurement_id');
        if (!empty($idMedicion)) {
            $scriptGA4 = "
<script async src=\"https://www.googletagmanager.com/gtag/js?id=%s\"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', '%s');
</script>
";
            printf($scriptGA4, esc_js($idMedicion), esc_js($idMedicion));
        }
    }

    /**
     * Agrega los scripts personalizados definidos en el panel de opciones.
     */
    private function agregarScriptsPersonalizados(): void
    {
        $scriptsPersonalizados = OpcionManager::get('glory_custom_header_scripts');
        if (!empty($scriptsPersonalizados)) {
            // Se imprime directamente, ya que se espera que el usuario inserte código HTML/JS válido.
            echo $scriptsPersonalizados . "\n";
        }
    }
}
