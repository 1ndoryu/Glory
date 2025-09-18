<?php

namespace Glory\Integration;

use Glory\Manager\OpcionManager;
use Glory\Integration\Avada\AvadaIntegration;

class IntegrationsManager
{
    /**
     * Registra el hook para agregar los scripts al header.
     */
    public function register(): void
    {
        add_action('wp_head', [$this, 'agregarScriptsAlHeader']);

        // Registrar integración con Avada Builder si está disponible.
        if ( function_exists('fusion_builder_map') || class_exists('Fusion_Element') ) {
            AvadaIntegration::register();
        } else {
            // Alternativamente, podemos enganchar tarde por si Avada carga después.
            add_action('after_setup_theme', function(){
                if ( function_exists('fusion_builder_map') || class_exists('Fusion_Element') ) {
                    AvadaIntegration::register();
                }
            }, 20);
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
