<?php
// App/Glory/Class/GloryLogger.php

namespace Glory\Class;

class GloryLogger
{
    const tipoContenidoSlug        = 'glory_log';
    const nivelInfo                = 10;
    const nivelAdvertencia         = 20;
    const nivelError               = 30;
    const nivelCritico             = 50;
    const metaClaveFuncionCompleta = '_glory_log_function';
    const metaClaveNombreClase     = '_glory_log_class_name';
    const metaClaveNombreMetodo    = '_glory_log_method_name';
    const metaClaveEstado          = '_glory_log_status';
    const metaClaveNivel           = '_glory_log_level';
    const metaClaveMarcaTiempo     = '_glory_log_timestamp';
    const maxLogsInfo              = 300;
    const maxLogsError             = 100;

    private static $nivelMinimoGuardado       = self::nivelError;
    private static $bufferLogs                = [];
    private static $hookGuardarLogsRegistrado = false;

    public static function init(?int $nivel = null): void
    {
        if (!is_null($nivel) && in_array($nivel, [self::nivelInfo, self::nivelError], true)) {
            self::$nivelMinimoGuardado = $nivel;
        }
        add_action('init', [self::class, 'registrarTipoContenido'], 0);
        if (!wp_next_scheduled('limpiarLogsHook')) {
            wp_schedule_event(time(), 'daily', 'limpiarLogsHook');
        }
        add_action('limpiarLogsHook', [self::class, 'limpiarLogsAntiguos']);
    }

    public static function registrarTipoContenido(): void
    {
        if (post_type_exists(self::tipoContenidoSlug)) {
            return;
        }

        $etiquetas  = [
            'name'               => _x('Glory Logs', 'post type general name', 'glory'),
            'singular_name'      => _x('Glory Log', 'post type singular name', 'glory'),
            'menu_name'          => _x('Glory Logs', 'admin menu', 'glory'),
            'name_admin_bar'     => _x('Glory Log', 'add new on admin bar', 'glory'),
            'add_new'            => _x('Add New', 'log', 'glory'),
            'add_new_item'       => __('Add New Log', 'glory'),
            'new_item'           => __('New Log', 'glory'),
            'edit_item'          => __('Edit Log', 'glory'),
            'view_item'          => __('View Log', 'glory'),
            'all_items'          => __('All Glory Logs', 'glory'),
            'search_items'       => __('Search Logs', 'glory'),
            'parent_item_colon'  => __('Parent Logs:', 'glory'),
            'not_found'          => __('No logs found.', 'glory'),
            'not_found_in_trash' => __('No logs found in Trash.', 'glory')
        ];
        $argumentos = [
            'labels'             => $etiquetas,
            'description'        => __('Stores execution logs from the Glory Framework.', 'glory'),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'tools.php',
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 80,
            'supports'           => ['title', 'editor', 'custom-fields'],
            'show_in_rest'       => false,
        ];

        $resultado = register_post_type(self::tipoContenidoSlug, $argumentos);

        if (is_wp_error($resultado)) {
            error_log('[GloryLogger] registrarTipoContenido() - FAILED to register CPT! WP_Error: ' . $resultado->get_error_message());
        }
    }

    public static function setNivelMinimoGuardado(int $nivel): void
    {
        if (in_array($nivel, [self::nivelInfo, self::nivelError], true)) {
            self::$nivelMinimoGuardado = $nivel;
        }
    }

    public static function info(string $mensaje, array $contexto = []): void
    {
        self::registrar(self::nivelInfo, $mensaje, $contexto);
    }

    public static function error(string $mensaje, array $contexto = []): void
    {
        self::registrar(self::nivelError, $mensaje, $contexto);
    }

    public static function warning(string $mensaje, array $contexto = []): void
    {
        self::registrar(self::nivelAdvertencia, $mensaje, $contexto);
    }

    public static function critical(string $mensaje, array $contexto = []): void
    {
        self::registrar(self::nivelCritico, $mensaje, $contexto);
    }

    private static function registrar(int $nivel, string $mensaje, array $contexto = []): void
    {
        $nombreLlamador = self::getNombreLlamador();
        if (!$nombreLlamador) {
            return;
        }
        $huellaLog = md5($nivel . '|' . $mensaje . '|' . serialize($contexto));
        if (!isset(self::$bufferLogs[$nombreLlamador])) {
            self::$bufferLogs[$nombreLlamador] = [
                'mensajes'     => [],
                'nivelMaximo'  => self::nivelInfo,
                'hashesUnicos' => [],
            ];
        }
        if (isset(self::$bufferLogs[$nombreLlamador]['hashesUnicos'][$huellaLog])) {
            return;
        }
        self::$bufferLogs[$nombreLlamador]['hashesUnicos'][$huellaLog] = true;
        $marcaTiempo                                                   = time();
        self::$bufferLogs[$nombreLlamador]['mensajes'][]               = [
            'marcaTiempo' => $marcaTiempo,
            'nivel'       => $nivel,
            'mensaje'     => $mensaje,
            'contexto'    => $contexto
        ];
        if ($nivel > self::$bufferLogs[$nombreLlamador]['nivelMaximo']) {
            self::$bufferLogs[$nombreLlamador]['nivelMaximo'] = $nivel;
        }
        self::registrarHookGuardarLogs();
    }

    private static function getNombreLlamador(): ?string
    {
        $traza = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        for ($i = 2; $i < count($traza); $i++) {
            if (isset($traza[$i]['class']) && $traza[$i]['class'] === self::class) {
                continue;
            }
            if (isset($traza[$i])) {
                $infoLlamador = $traza[$i];
                $funcion      = $infoLlamador['function'] ?? '[unknown_function]';
                if (isset($infoLlamador['class'])) {
                    $funcion = $infoLlamador['class'] . '::' . $funcion;
                }

                return $funcion;
            }
        }

        return '[unknown_caller]';
    }

    private static function registrarHookGuardarLogs(): void
    {
        if (!self::$hookGuardarLogsRegistrado) {
            add_action('shutdown', [self::class, 'guardarLogsEnBuffer'], PHP_INT_MAX);
            self::$hookGuardarLogsRegistrado = true;
        }
    }

    public static function guardarLogsEnBuffer(): void
    {
        if (empty(self::$bufferLogs)) {
            return;
        }
        foreach (self::$bufferLogs as $nombreFuncion => $datosLog) {
            $nivelMaximoEncontrado = $datosLog['nivelMaximo'];
            if (empty($datosLog['mensajes'])) {
                continue;
            }
            if ($nivelMaximoEncontrado >= self::$nivelMinimoGuardado) {
                self::crearEntradaLog($nombreFuncion, $datosLog);
            }
        }
        self::$bufferLogs                = [];
        self::$hookGuardarLogsRegistrado = false;
    }

    private static function crearEntradaLog(string $nombreFuncion, array $datosLog): void
    {
        if (empty($datosLog['mensajes'])) {
            return;
        }

        $tiempoInicio = $datosLog['mensajes'][0]['marcaTiempo'] ?? time();
        $nivelMaximo  = $datosLog['nivelMaximo'];

        $estado = 'INFO';
        if ($nivelMaximo === self::nivelError)
            $estado = 'ERROR';
        else if ($nivelMaximo === self::nivelAdvertencia)
            $estado = 'WARNING';
        else if ($nivelMaximo === self::nivelCritico)
            $estado = 'CRITICAL';

        $tituloEntrada = sprintf(
            '[%s] %s - %s',
            $estado,
            $nombreFuncion,
            wp_date('Y-m-d H:i:s', $tiempoInicio)
        );

        $contenidoEntrada  = "Log entries for function/method: {$nombreFuncion}\n";
        $contenidoEntrada .= "Overall Execution Status based on logs: {$estado}\n";
        $contenidoEntrada .= "--------------------------------------------------\n\n";

        foreach ($datosLog['mensajes'] as $registro) {
            $nivelTexto = 'INFO';
            if ($registro['nivel'] === self::nivelError)
                $nivelTexto = 'ERROR';
            else if ($registro['nivel'] === self::nivelAdvertencia)
                $nivelTexto = 'WARNING';
            else if ($registro['nivel'] === self::nivelCritico)
                $nivelTexto = 'CRITICAL';

            $contenidoEntrada .= sprintf(
                "[%s] [%s] %s\n",
                wp_date('H:i:s', $registro['marcaTiempo']) . '.' . substr(sprintf('%03d', ($registro['marcaTiempo'] - floor($registro['marcaTiempo'])) * 1000), 0, 3),
                $nivelTexto,
                esc_html($registro['mensaje'])
            );
            if (!empty($registro['contexto'])) {
                $contextoTexto     = print_r($registro['contexto'], true);
                $contenidoEntrada .= '  Context: ' . esc_html($contextoTexto) . "\n";
            }
            $contenidoEntrada .= "\n";
        }

        $datosEntrada = [
            'post_title'   => $tituloEntrada,
            'post_content' => $contenidoEntrada,
            'post_status'  => 'publish',
            'post_type'    => self::tipoContenidoSlug,
            'post_author'  => 0,
        ];

        $nivelFiltroActualDatos   = did_action('wp_insert_post_data');
        $nivelFiltroActualEntrada = did_action('wp_insert_post');

        if ($nivelFiltroActualDatos < 10)
            remove_all_filters('wp_insert_post_data');
        if ($nivelFiltroActualEntrada < 10)
            remove_all_filters('wp_insert_post');

        $idEntrada = wp_insert_post($datosEntrada, true);

        if (!is_wp_error($idEntrada) && $idEntrada > 0) {
            $nombreClase  = '';
            $nombreMetodo = $nombreFuncion;

            if (strpos($nombreFuncion, '::') !== false) {
                list($nombreClase, $nombreMetodo) = explode('::', $nombreFuncion, 2);
            }

            update_post_meta($idEntrada, self::metaClaveFuncionCompleta, $nombreFuncion);
            update_post_meta($idEntrada, self::metaClaveNombreClase, $nombreClase);
            update_post_meta($idEntrada, self::metaClaveNombreMetodo, $nombreMetodo);
            update_post_meta($idEntrada, self::metaClaveEstado, $estado);
            update_post_meta($idEntrada, self::metaClaveNivel, $nivelMaximo);
            update_post_meta($idEntrada, self::metaClaveMarcaTiempo, $tiempoInicio);
        } else {
            $mensajeError = is_wp_error($idEntrada) ? $idEntrada->get_error_message() : 'Unknown error (Invalid Post ID returned: ' . print_r($idEntrada, true) . ')';
            error_log('[GloryLogger] crearEntradaLog() - FAILED to create log post for ' . $nombreFuncion . '. Error: ' . $mensajeError);
        }
    }

    public static function limpiarLogsAntiguos(): void
    {
        error_log('[GloryLogger Cron] limpiarLogsAntiguos() - Iniciando limpieza de logs antiguos.');
        self::recortarLogsPorEstado('INFO', self::maxLogsInfo);
        self::recortarLogsPorEstado('ERROR', self::maxLogsError);
        // Podríamos añadir limpieza para WARNING y CRITICAL si se desea, o agruparlos con ERROR.
        // Por ahora, solo INFO y ERROR tienen límites explícitos.
        error_log('[GloryLogger Cron] limpiarLogsAntiguos() - Limpieza completada.');
    }

    private static function recortarLogsPorEstado(string $estadoRecortar, int $limite): void
    {
        $consultaConteo = new \WP_Query([
            'post_type'      => self::tipoContenidoSlug,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'   => self::metaClaveEstado,
                    'value' => $estadoRecortar,
                ]
            ],
            'fields'         => 'ids',
        ]);

        $totalLogsPorEstado = $consultaConteo->found_posts;
        error_log("[GloryLogger Cron] recortarLogsPorEstado() - Estado: {$estadoRecortar}. Total actual: {$totalLogsPorEstado}. Límite: {$limite}.");

        if ($totalLogsPorEstado > $limite) {
            $cantidadEntradasAEliminar = $totalLogsPorEstado - $limite;
            error_log("[GloryLogger Cron] recortarLogsPorEstado() - Estado: {$estadoRecortar}. Necesita eliminar {$cantidadEntradasAEliminar} logs.");

            $consultaMasAntiguos = new \WP_Query([
                'post_type'      => self::tipoContenidoSlug,
                'post_status'    => 'publish',
                'posts_per_page' => $cantidadEntradasAEliminar,
                'orderby'        => 'date',
                'order'          => 'ASC',
                'meta_query'     => [
                    [
                        'key'   => self::metaClaveEstado,
                        'value' => $estadoRecortar,
                    ]
                ],
                'fields'         => 'ids',
            ]);

            if (!empty($consultaMasAntiguos->posts)) {
                $contadorEliminados = 0;
                foreach ($consultaMasAntiguos->posts as $idEntradaAEliminar) {
                    $eliminado = wp_delete_post($idEntradaAEliminar, true);
                    if ($eliminado) {
                        $contadorEliminados++;
                    } else {
                        error_log("[GloryLogger Cron] recortarLogsPorEstado() - Estado: {$estadoRecortar}. No se pudo eliminar el post ID: {$idEntradaAEliminar}.");
                    }
                }
                error_log("[GloryLogger Cron] recortarLogsPorEstado() - Estado: {$estadoRecortar}. Se eliminaron {$contadorEliminados} de {$cantidadEntradasAEliminar} logs.");
            } else {
                error_log("[GloryLogger Cron] recortarLogsPorEstado() - Estado: {$estadoRecortar}. No se encontraron posts para eliminar (consultaMasAntiguos vacía), aunque el conteo inicial era mayor al límite.");
            }
        } else {
            error_log("[GloryLogger Cron] recortarLogsPorEstado() - Estado: {$estadoRecortar}. No se necesita eliminar logs.");
        }
    }
}
