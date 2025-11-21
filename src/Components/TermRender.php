<?php
/**
 * Renderizador de Términos
 *
 * Componente encargado de consultar y visualizar listas de términos de taxonomía
 * (categorías, etiquetas, etc.) de forma flexible y personalizable.
 *
 * @package Glory\Components
 */

namespace Glory\Components;

use Glory\Core\GloryLogger;
use WP_Term_Query;
use WP_Term;

/**
 * Clase TermRender.
 *
 * Permite renderizar colecciones de términos con una plantilla personalizada.
 */
class TermRender
{
    /**
     * Imprime una lista de términos.
     *
     * @param string $taxonomy Taxonomía a consultar (ej. 'category').
     * @param array  $opciones Opciones de configuración:
     *                         - 'numero' (int)                : Número máximo de términos, 0 = sin límite.
     *                         - 'claseContenedor' (string)    : Clase CSS para el contenedor principal.
     *                         - 'claseItem' (string)          : Clase CSS para cada término.
     *                         - 'plantillaCallback' (callable): Función que renderiza cada término.
     *                         - 'argumentosConsulta' (array)  : Parámetros extra para WP_Term_Query.
     *                         - 'ordenRandom' (bool)          : Si true, se devuelve en orden aleatorio.
     */
    public static function print(string $taxonomy, array $opciones = []): void
    {
        $defaults = [
            'numero'             => 0,
            'claseContenedor'    => 'glory-term-list',
            'claseItem'          => 'glory-term-item',
            'plantillaCallback'  => [self::class, 'defaultTemplate'],
            'argumentosConsulta' => [],
            'ordenRandom'        => false,
        ];
        $config   = wp_parse_args($opciones, $defaults);

        $args = array_merge([
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
        ], $config['argumentosConsulta']);

        if ((int) $config['numero'] > 0) {
            $args['number'] = (int) $config['numero'];
        }
        if ($config['ordenRandom']) {
            $args['orderby'] = 'rand';
        }

        // GloryLogger::info('TermRender: ejecutando consulta', ['args' => $args]);

        $consulta = new WP_Term_Query($args);

        $terms = method_exists($consulta, 'get_terms') ? $consulta->get_terms() : ($consulta->terms ?? []);

        if (is_wp_error($terms)) {
            GloryLogger::error('TermRender: WP_Term_Query devolvió error', ['error' => $terms->get_error_message()]);
            echo '<p>' . esc_html__('Error al obtener términos.', 'glory') . '</p>';
            return;
        }

        // GloryLogger::info('TermRender: número de términos encontrados', ['count' => is_countable($terms) ? count($terms) : 'no-countable']);

        if (empty($terms)) {
            echo '<p>' . esc_html__('No se encontraron términos.', 'glory') . '</p>';
            return;
        }

        $contenedorClass = trim($config['claseContenedor'] . ' ' . sanitize_html_class($taxonomy));
        $itemClass       = trim($config['claseItem'] . ' ' . sanitize_html_class($taxonomy) . '-item');

        echo '<div class="' . esc_attr($contenedorClass) . '">';
        foreach ($terms as $term) {
            if ($term instanceof WP_Term) {
                call_user_func($config['plantillaCallback'], $term, $itemClass);
            }
        }
        echo '</div>';
    }

    /**
     * Plantilla por defecto para renderizar un término.
     *
     * @param WP_Term $term      El objeto del término.
     * @param string  $itemClass Clase CSS para el ítem.
     */
    public static function defaultTemplate(WP_Term $term, string $itemClass): void
    {
        ?>
        <div class="<?php echo esc_attr($itemClass); ?>">
            <a href="<?php echo esc_url(get_term_link($term)); ?>">
                <?php echo esc_html($term->name); ?> (<?php echo intval($term->count); ?>)
            </a>
        </div>
        <?php
    }
}
