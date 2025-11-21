<?php
/**
 * Renderizador de Paginación
 *
 * Gestiona la renderización de controles de paginación para listados de posts,
 * adaptando la salida para funcionar con navegación AJAX si es necesario.
 *
 * @package Glory\Components
 */

namespace Glory\Components;

use WP_Query;
use DOMDocument;

/**
 * Clase PaginationRenderer.
 *
 * Genera links de paginación compatibles con el sistema AJAX de Glory.
 */
class PaginationRenderer
{
    /**
     * Renderiza la paginación basada en una consulta WP_Query.
     *
     * @param WP_Query $query La consulta de WordPress a paginar.
     */
    public static function render(WP_Query $query): void
    {
        $big = 999999999;

        $currentPage = $query->get('paged') ? absint($query->get('paged')) : 1;
        if ($currentPage === 0) {
            $currentPage = 1;
        }

        $args = [
            'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format'    => '?paged=%#%',
            'current'   => $currentPage,
            'total'     => $query->max_num_pages,
            'type'      => 'array',
            'prev_text' => __('&laquo; Prev'),
            'next_text' => __('Next &raquo;'),
        ];

        $pages = paginate_links($args);

        if (is_array($pages)) {
            // Restauramos la clase original "gloryPaginacion noAjax" para que los estilos se apliquen.
            echo '<div class="gloryPaginacion noAjax">';
            foreach ($pages as $pageHtml) {
                if (strpos($pageHtml, '<a') === false) {
                    // Omitir spans de prev/next cuando no hay página previa/siguiente
                    if (preg_match('/class=("|\')(?:[^"\']*)\b(prev|next)\b/i', $pageHtml)) {
                        continue;
                    }
                    echo $pageHtml;
                    continue;
                }

                $dom = new DOMDocument();
                // Suprimir errores de parsing HTML mal formado
                @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $pageHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

                $link = $dom->getElementsByTagName('a')->item(0);

                if ($link) {
                    $href    = $link->getAttribute('href');
                    $pageNum = 1;

                    if (preg_match('/\/page\/(\d+)/', $href, $matches) || preg_match('/paged=(\d+)/', $href, $matches)) {
                        $pageNum = $matches[1];
                    } else {
                        if (strpos($link->nodeValue, 'Next') !== false) {
                            $pageNum = $currentPage + 1;
                        } elseif (strpos($link->nodeValue, 'Prev') !== false) {
                            $pageNum = $currentPage - 1;
                        }
                    }

                    $link->removeAttribute('href');
                    $link->setAttribute('data-page', (string)$pageNum);
                    $existingClass = trim((string)$link->getAttribute('class'));
                    $link->setAttribute('class', trim($existingClass . ' noAjax'));
                    echo $dom->saveHTML($link);
                } else {
                    echo $pageHtml;
                }
            }
            echo '</div>';
        }
    }
}
