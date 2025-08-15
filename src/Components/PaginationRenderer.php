<?php

namespace Glory\Components;

use WP_Query;
use DOMDocument;

class PaginationRenderer
{
    public static function render(WP_Query $query): void
    {
        $big = 999999999;
        
        $current_page = $query->get('paged') ? absint($query->get('paged')) : 1;
        if ($current_page === 0) {
            $current_page = 1;
        }

        $args = [
            'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format'    => '?paged=%#%',
            'current'   => $current_page,
            'total'     => $query->max_num_pages,
            'type'      => 'array',
            'prev_text' => __('&laquo; Prev'),
            'next_text' => __('Next &raquo;'),
        ];

        $pages = paginate_links($args);

        if (is_array($pages)) {
            // Restauramos la clase original "gloryPaginacion noAjax" para que los estilos se apliquen.
            echo '<div class="gloryPaginacion noAjax">';
            foreach ($pages as $page_html) {
                if (strpos($page_html, '<a') === false) {
                    echo $page_html;
                    continue;
                }

                $dom = new DOMDocument();
                @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $page_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                
                $link = $dom->getElementsByTagName('a')->item(0);

                if ($link) {
                    $href = $link->getAttribute('href');
                    $page_num = 1;

                    if (preg_match('/\/page\/(\d+)/', $href, $matches) || preg_match('/paged=(\d+)/', $href, $matches)) {
                        $page_num = $matches[1];
                    } else {
                        if (strpos($link->nodeValue, 'Next') !== false) {
                            $page_num = $current_page + 1;
                        } elseif (strpos($link->nodeValue, 'Prev') !== false) {
                            $page_num = $current_page - 1;
                        }
                    }

                    $link->removeAttribute('href');
                    $link->setAttribute('data-page', (string)$page_num);
                    $existingClass = trim((string)$link->getAttribute('class'));
                    $link->setAttribute('class', trim($existingClass . ' noAjax'));
                    echo $dom->saveHTML($link);
                } else {
                    echo $page_html;
                }
            }
            echo '</div>';
        }
    }
}