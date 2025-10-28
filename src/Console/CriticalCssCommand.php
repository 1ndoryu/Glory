<?php

namespace Glory\Console;

use WP_CLI; // phpcs:ignore
use Glory\Services\LocalCriticalCss;
use Glory\Services\GestorCssCritico;

if (defined('WP_CLI') && WP_CLI) {
    /**
     * Genera y cachea CSS crítico localmente.
     */
    class CriticalCssCommand
    {
        /**
         * Generar CSS crítico para URLs específicas o para todo el contenido público.
         *
         * ## OPCIONES
         * [--url=<url>]
         * : URL específica para generar crítico.
         *
         * [--all]
         * : Genera para todo el contenido publicado (páginas y posts) + página de inicio.
         *
         * [--limit=<n>]
         * : Límite de elementos cuando se usa --all (por defecto 200).
         *
         * ## EJEMPLOS
         * wp glory critical-css generate --url=https://padel.wandori.us/
         * wp glory critical-css generate --all --limit=100
         */
        public function generate($args, $assoc_args)
        {
            $urls = [];
            if (!empty($assoc_args['url'])) {
                $urls[] = (string) $assoc_args['url'];
            }
            if (!empty($assoc_args['all'])) {
                $limit = isset($assoc_args['limit']) ? (int) $assoc_args['limit'] : 200;
                $urls[] = home_url('/');
                $q = new \WP_Query([
                    'post_type'      => ['page', 'post'],
                    'post_status'    => 'publish',
                    'posts_per_page' => $limit,
                    'fields'         => 'ids',
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                ]);
                foreach ($q->posts as $pid) {
                    $urls[] = get_permalink($pid);
                }
            }

            if (!$urls) {
                WP_CLI::error('Proporciona --url o --all');
                return;
            }

            $countOk = 0; $countFail = 0;
            foreach (array_unique($urls) as $u) {
                if (!$u) continue;
                WP_CLI::log("Generando crítico: $u");
                $css = LocalCriticalCss::generate($u);
                if ($css) {
                    GestorCssCritico::guardarCssParaUrl($u, $css);
                    $countOk++;
                } else {
                    $countFail++;
                    WP_CLI::warning("Fallo generando crítico: $u");
                }
            }
            WP_CLI::success("Listo. OK=$countOk, FALLIDOS=$countFail");
        }
    }

    WP_CLI::add_command('glory critical-css', CriticalCssCommand::class);
}


