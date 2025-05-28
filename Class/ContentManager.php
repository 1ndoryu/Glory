<?php
# /Glory/Class/ContentManager.php

namespace Glory\Class;

use Glory\Class\GloryLogger;
use DateTime;
use DateTimeZone;
use DateInterval;

class ContentManager
{
    /**
     * Prefijo usado para guardar las opciones en la base de datos.
     */
    public const OPTION_PREFIX = 'glory_content_'; // Cambiado a public const
    public const OPTION_META_CODE_HASH_SUFFIX = '_code_hash_on_save'; // Nuevo, public const
    public const OPTION_META_PANEL_SAVED_SUFFIX = '_is_panel_value';   // Nuevo, public const

    /**
     * Almacena la configuración de los campos de contenido registrados.
     * Estructura por clave:
     * 'key' => [
     *     'default'                   => mixed,  // Valor por defecto del código
     *     'type'                      => string, // 'text', 'richText', 'image', 'schedule', 'raw'
     *     'label'                     => string, // Título para el panel de administración
     *     'section'                   => string, // Sección en el panel de administración
     *     'description'               => string, // Descripción para el panel de administración
     *     'escape'                    => bool,   // Si el método get() debe escapar HTML por defecto para este tipo.
     *     'force_default_on_register' => bool,   // NUEVO: Si true, el default del código siempre sobreescribe la BD al registrar.
     *     'code_version_hash'         => string  // NUEVO (interno): Hash del valor por defecto del código.
     * ]
     */
    private static array $registered_content = [];

    /**
     * Registra un campo de contenido editable con sus metadatos y valor por defecto.
     *
     * @param string $key Identificador único para este contenido.
     * @param array $args Argumentos para configurar el campo:
     *                    'default'     => (mixed) Valor por defecto.
     *                    'type'        => (string) Tipo de contenido. Default: 'text'.
     *                    'label'       => (string) Título descriptivo. Default: se genera desde $key.
     *                    'section'     => (string) Sección del panel. Default: 'general'.
     *                    'description' => (string) Descripción para el panel. Default: ''.
     *                    'escape'      => (bool) Si el valor debe ser escapado.
     *                    'force_default_on_register' => (bool) Si true, el default del código se impone en cada carga. Default: false.
     */
    public static function register(string $key, array $args = []): void
    {
        $default_type = $args['type'] ?? 'text';
        $default_escape = ($default_type === 'text');

        $defaults = [
            'default'                   => '',
            'type'                      => $default_type,
            'label'                     => ucfirst(str_replace(['_', '-'], ' ', $key)),
            'section'                   => 'general',
            'description'               => '',
            'escape'                    => $default_escape,
            'force_default_on_register' => false, // Nuevo default
        ];

        $parsed_args = wp_parse_args($args, $defaults);

        // Calcular hash del default del código
        $code_default_for_hash = $parsed_args['default'];
        $parsed_args['code_version_hash'] = md5(is_scalar($code_default_for_hash) ? (string)$code_default_for_hash : serialize($code_default_for_hash));

        self::$registered_content[$key] = $parsed_args;

        // --- Lógica de Sincronización Código vs Panel ---
        $config = self::$registered_content[$key];
        $option_name = self::OPTION_PREFIX . $key;
        $code_default_value = $config['default'];
        $current_code_hash = $config['code_version_hash'];

        GloryLogger::info("--- SYNC CHECK FOR KEY: $key ---");
        $db_value_before_sync = get_option($option_name, '--DB_VALUE_NOT_SET--');
        $is_panel_flag_before_sync = get_option($option_name . self::OPTION_META_PANEL_SAVED_SUFFIX, false);
        $hash_on_save_before_sync = get_option($option_name . self::OPTION_META_CODE_HASH_SUFFIX, '--HASH_NOT_SET--');
        $sentinel_for_db_check = new \stdClass(); 


        if ($db_value_before_sync === $sentinel_for_db_check) {
            GloryLogger::info("SYNC [$key]: DB value for '{$option_name}': --DOES_NOT_EXIST_IN_DB--");
        } else {
            GloryLogger::info("SYNC [$key]: DB value for '{$option_name}': " . print_r($db_value_before_sync, true));
        }
        GloryLogger::info("SYNC [$key]: Code default value (from PHP register call): " . print_r($code_default_value, true));
        GloryLogger::info("SYNC [$key]: Current code_version_hash (md5 of code default): " . print_r($current_code_hash, true));
        GloryLogger::info("SYNC [$key]: force_default_on_register (from PHP register call): " . ($config['force_default_on_register'] ? 'true' : 'false'));
        GloryLogger::info("SYNC [$key]: _is_panel_value flag from DB ('{$option_name}" . self::OPTION_META_PANEL_SAVED_SUFFIX . "'): " . ($is_panel_flag_before_sync ? 'true' : 'false'));
        GloryLogger::info("SYNC [$key]: _code_hash_on_save from DB ('{$option_name}" . self::OPTION_META_CODE_HASH_SUFFIX . "'): " . print_r($hash_on_save_before_sync, true));

        if ($config['force_default_on_register'] === true) {
            GloryLogger::info("SYNC [$key]: Path taken: 'force_default_on_register' is TRUE.");
            // $current_db_value = get_option($option_name, null); // Ya tenemos $db_value_before_sync

            // Forzar si el valor de la BD es diferente o no existe
            // Si $db_value_before_sync es el sentinel, significa que no existe, por lo tanto es diferente al code_default_value (a menos que code_default_value sea también el sentinel, lo cual es improbable)
            if ($db_value_before_sync === $sentinel_for_db_check || $db_value_before_sync !== $code_default_value) {
                GloryLogger::info("SYNC [$key]: force_default: DB value differs from code default OR DB value does not exist. OVERWRITING '{$option_name}' with code default.");
                update_option($option_name, $code_default_value);
            } else {
                GloryLogger::info("SYNC [$key]: force_default: DB value is SAME as code default. No update needed for '{$option_name}'.");
            }
            // Limpiar flags del panel ya que el código manda
            if ($is_panel_flag_before_sync) { // Solo borrar si existe
                GloryLogger::info("SYNC [$key]: force_default: Deleting panel flag '{$option_name}" . self::OPTION_META_PANEL_SAVED_SUFFIX . "'.");
                delete_option($option_name . self::OPTION_META_PANEL_SAVED_SUFFIX);
            }
            if ($hash_on_save_before_sync !== '--HASH_NOT_SET_IN_DB--') { // Solo borrar si existe
                GloryLogger::info("SYNC [$key]: force_default: Deleting panel hash '{$option_name}" . self::OPTION_META_CODE_HASH_SUFFIX . "'.");
                delete_option($option_name . self::OPTION_META_CODE_HASH_SUFFIX);
            }
            // El GloryLogger::info original ya estaba bien aquí:
            GloryLogger::info("Content '{$key}' was (or would be if different) force-updated to code default due to 'force_default_on_register' flag. Panel flags cleared.");
        } else { // force_default_on_register es false
            GloryLogger::info("SYNC [$key]: Path taken: 'force_default_on_register' is FALSE.");
            // $is_panel_value_flag = get_option($option_name . self::OPTION_META_PANEL_SAVED_SUFFIX, false); // Ya la tenemos como $is_panel_flag_before_sync

            if ($is_panel_flag_before_sync) {
                GloryLogger::info("SYNC [$key]: Sub-path: _is_panel_value_flag from DB IS TRUE.");
                // $hash_on_panel_save = get_option($option_name . self::OPTION_META_CODE_HASH_SUFFIX); // Ya la tenemos como $hash_on_save_before_sync

                if ($current_code_hash !== $hash_on_save_before_sync) {
                    GloryLogger::info("SYNC [$key]: Panel flag is TRUE, BUT current_code_hash ('{$current_code_hash}') !== hash_on_panel_save ('{$hash_on_save_before_sync}'). Code default has changed since panel save. OVERWRITING '{$option_name}' with new code default and clearing panel flags.");
                    update_option($option_name, $code_default_value);
                    delete_option($option_name . self::OPTION_META_PANEL_SAVED_SUFFIX);
                    delete_option($option_name . self::OPTION_META_CODE_HASH_SUFFIX);
                    // El GloryLogger::info original ya estaba bien aquí:
                    // GloryLogger::info("Code for '{$key}' changed since last panel save. Code default has been applied. Old hash: {$hash_on_panel_save}, new hash: {$current_code_hash}.");
                } else {
                    GloryLogger::info("SYNC [$key]: Panel flag is TRUE, and hashes MATCH (current_code_hash: '{$current_code_hash}', hash_on_panel_save: '{$hash_on_save_before_sync}'). Panel value for '{$option_name}' should be RETAINED. No changes made by sync logic.");
                }
            } else { // _is_panel_value_flag is false (según lo leído de la BD)
                GloryLogger::info("SYNC [$key]: Sub-path: _is_panel_value_flag from DB IS FALSE.");
                // El flag del panel no está activo.
                // Si la opción NO existe en la BD, la inicializamos con el default del código.
                if ($db_value_before_sync === $sentinel_for_db_check) { // Si la opción no existe en la BD
                    GloryLogger::info("SYNC [$key]: Panel flag FALSE, and option '{$option_name}' does NOT exist in DB. INITIALIZING '{$option_name}' with code default.");
                    update_option($option_name, $code_default_value);
                    // El GloryLogger::info original ya estaba bien aquí:
                    // GloryLogger::info("Content '{$key}' initialized with code default as no DB value or panel flag existed.");
                } else {
                    // La opción SÍ existe en la BD pero sin el flag del panel.
                    // Esto significa que es un valor ya establecido (quizás por una ejecución anterior de esta lógica de inicialización,
                    // o por 'force_default_on_register', o un valor antiguo antes de los flags). Se respeta.
                    GloryLogger::info("SYNC [$key]: Panel flag FALSE, but option '{$option_name}' EXISTS in DB. Value: " . print_r($db_value_before_sync, true) . ". Assuming it's a code-driven value or old value. RETAINING. No changes made by sync logic.");
                }
            }
        }
        // --- Fin Lógica de Sincronización ---

        GloryLogger::info("Content explicitly registered for key: $key with args: " . json_encode(self::$registered_content[$key]));
    }

    /**
     * Obtiene el hash MD5 del valor por defecto actual definido en el código para una clave.
     * Utilizado por el panel de administración al guardar.
     *
     * @param string $key La clave del contenido.
     * @return string|null El hash MD5 o null si la clave no está registrada o no tiene default.
     */
    public static function getCodeDefaultHash(string $key): ?string
    {
        if (isset(self::$registered_content[$key]['code_version_hash'])) {
            return self::$registered_content[$key]['code_version_hash'];
        }
        // Fallback si se llama antes de que 'code_version_hash' esté poblado (poco probable con el flujo actual)
        // o si el campo fue registrado "on-the-fly" sin este hash.
        if (isset(self::$registered_content[$key]['default'])) {
            $default_value = self::$registered_content[$key]['default'];
            return md5(is_scalar($default_value) ? (string)$default_value : serialize($default_value));
        }
        return null;
    }


    private static function registerOnTheFly(string $key, $default_value, string $type, ?string $label, ?string $section, ?string $description, bool $escape_behavior): void
    {
        if (!isset(self::$registered_content[$key])) {
            // Los campos "on-the-fly" no participan en la lógica compleja de `force_default_on_register`
            // ni en la comparación de hashes a menos que se modifique este método.
            // Se registran con `force_default_on_register = false` por defecto.
            self::register($key, [
                'default'     => $default_value,
                'type'        => $type,
                'label'       => $label,
                'section'     => $section,
                'description' => $description,
                'escape'      => $escape_behavior,
                // 'force_default_on_register' se quedará en false por defecto.
            ]);
            GloryLogger::info("Content registered on-the-fly for key: $key");
        }
    }

    public static function get(
        string $key,
        $default_param = '',
        bool $escape_output = true,
        ?string $panel_title = null,
        ?string $panel_section = null,
        ?string $panel_description = null,
        string $content_type = 'text'
    ) {
        GloryLogger::info("get() called for key: $key");
        $option_name = self::OPTION_PREFIX . $key;

        // Obtener el valor de la opción directamente.
        // Si la opción no existe, get_option() devuelve false por defecto.
        // Si la opción existe pero su valor es false (booleano), también devolverá false.
        // Para distinguir "no existe" de "existe y es false", necesitamos una comprobación más robusta
        // o confiar en nuestros flags _is_panel_value o en que no guardaremos booleanos false directamente.
        $db_value = get_option($option_name);

        $final_value = null;
        $option_actually_exists_in_db = true; // Asumimos que existe si get_option no devuelve el valor por defecto (que es `false`)
        // o si nuestro flag del panel está activo.

        // Si get_option devuelve false, podría ser que no existe, o que su valor es false.
        // Para nuestro caso, si el panel guardó algo (incluso un string vacío), queremos ese valor.
        // Si la opción no existe Y el panel no ha guardado nada, entonces recurrimos a los defaults.

        // Primero, verificamos si get_option devolvió el valor por defecto indicando que NO existe.
        // (get_option() devuelve el segundo parámetro si la opción no se encuentra, o false si no se da un segundo parámetro)
        $test_non_existence_default = new \stdClass(); // Objeto único para testear
        $value_if_not_exists = get_option($option_name, $test_non_existence_default);

        if ($value_if_not_exists === $test_non_existence_default) {
            // La opción $option_name NO existe en la base de datos
            $option_actually_exists_in_db = false;
        }
        // Si $value_if_not_exists NO ES $test_non_existence_default, significa que la opción SÍ existe.
        // En este caso, $db_value (obtenido antes sin default) contiene el valor real de la BD.

        // Prioridad 1: Valor de la base de datos (si el panel lo guardó o si la opción simplemente existe)
        if (get_option($option_name . self::OPTION_META_PANEL_SAVED_SUFFIX, false) || $option_actually_exists_in_db) {
            // Usamos $db_value que se obtuvo sin un default, por lo que es el valor real de la BD si existe.
            $final_value = $db_value;
            GloryLogger::info("Value for key '$key' taken from DB (panel_saved or option exists). DB value: " . print_r($db_value, true));
        } else {
            // Prioridad 2: Valor por defecto de un campo registrado explícitamente
            if (isset(self::$registered_content[$key]) && array_key_exists('default', self::$registered_content[$key])) {
                $final_value = self::$registered_content[$key]['default'];
                GloryLogger::info("No value in DB for key: $key. Using registered default.");
            }
            // Prioridad 3: Valor por defecto pasado como parámetro a esta función
            else {
                $final_value = $default_param;
                GloryLogger::info("No value in DB for key: $key. No registered default. Using parameter default.");
            }
        }

        // Registrar "al vuelo" si se proporcionaron metadatos y no había registro explícito
        self::registerOnTheFly($key, $default_param, $content_type, $panel_title, $panel_section, $panel_description, $escape_output);

        if (is_string($final_value) && $escape_output) {
            return esc_html($final_value);
        }

        return $final_value;
    }

    public static function text(string $key, string $default = '', ?string $panel_title = null, ?string $panel_section = null, ?string $panel_description = null): string
    {
        GloryLogger::info("text called for key: $key");
        // El último true es para escape_output
        return self::get($key, $default, true, $panel_title, $panel_section, $panel_description, 'text');
    }

    public static function richText(string $key, string $default = '', ?string $panel_title = null, ?string $panel_section = null, ?string $panel_description = null): string
    {
        GloryLogger::info("richText called for key: $key");
        // El false es para escape_output
        $value = self::get($key, $default, false, $panel_title, $panel_section, $panel_description, 'richText');
        return wp_kses_post($value);
    }

    public static function image(string $key, string $default = '', ?string $panel_title = null, ?string $panel_section = null, ?string $panel_description = null): string
    {
        GloryLogger::info("image called for key: $key");
        return self::get($key, $default, false, $panel_title, $panel_section, $panel_description, 'image');
    }

    public static function schedule(string $key, array $defaultSchedule = [], ?string $panel_title = null, ?string $panel_section = null, ?string $panel_description = null): array
    {
        GloryLogger::info("schedule called for key: $key");
        $schedule_data = self::get($key, $defaultSchedule, false, $panel_title, $panel_section, $panel_description, 'schedule');

        if (!is_array($schedule_data) || empty($schedule_data)) {
            GloryLogger::info("No schedule data found or invalid for key: $key. Using provided default schedule for normalization.");
            $schedule_data = $defaultSchedule;
        }

        $normalized_schedule = [];
        if (is_array($schedule_data)) {
            foreach ($schedule_data as $entry) {
                if (!is_array($entry)) {
                    GloryLogger::info("Invalid entry in schedule data for key: $key. Entry: " . print_r($entry, true));
                    continue;
                }
                $normalized_entry = [
                    'day'    => $entry['day'] ?? 'Día Desconocido',
                    'open'   => $entry['open'] ?? '',
                    'close'  => $entry['close'] ?? '',
                    'status' => $entry['status'] ?? 'closed',
                    'hours'  => $entry['hours'] ?? ''
                ];

                // Lógica de normalización de horas (sin cambios)
                if (!empty($normalized_entry['hours']) && (empty($normalized_entry['open']) || empty($normalized_entry['close']))) {
                    if (strtolower($normalized_entry['hours']) === 'cerrado') {
                        $normalized_entry['status'] = 'closed';
                        $normalized_entry['open'] = '';
                        $normalized_entry['close'] = '';
                    } else {
                        $parts = explode('-', $normalized_entry['hours']);
                        if (count($parts) === 2) {
                            $normalized_entry['open'] = trim($parts[0]);
                            $normalized_entry['close'] = trim($parts[1]);
                            $normalized_entry['status'] = 'open';
                        }
                    }
                } elseif ($normalized_entry['status'] === 'open' && empty($normalized_entry['hours']) && !empty($normalized_entry['open']) && !empty($normalized_entry['close'])) {
                    $normalized_entry['hours'] = $normalized_entry['open'] . '-' . $normalized_entry['close'];
                } elseif ($normalized_entry['status'] === 'closed') {
                    $normalized_entry['hours'] = 'Cerrado';
                    $normalized_entry['open'] = '';
                    $normalized_entry['close'] = '';
                }

                $normalized_schedule[] = $normalized_entry;
            }
        } else {
            GloryLogger::error("Final schedule_data for key '$key' is not an array after get(). Returning empty normalized schedule.");
        }
        return $normalized_schedule;
    }

    public static function getCurrentStatus(string $schedule_key, array $default_schedule, string $timezone_string = 'Europe/Madrid'): array
    {
        // Sin cambios en esta función, ya que depende de self::schedule que ya tiene la lógica
        GloryLogger::info("getCurrentStatus called for schedule_key: $schedule_key");
        $schedule = self::schedule($schedule_key, $default_schedule);

        if (empty($schedule)) {
            return ['status_class' => 'closed', 'message' => 'Horario no disponible.'];
        }

        try {
            $now = new DateTime('now', new DateTimeZone($timezone_string));
        } catch (\Exception $e) {
            GloryLogger::error("Invalid timezone string: $timezone_string. Error: " . $e->getMessage());
            // Fallback a UTC si la zona horaria es inválida
            $now = new DateTime('now', new DateTimeZone('UTC'));
        }

        $current_day_english = $now->format('l'); // e.g., Monday
        $day_map_es_en = [
            'Lunes' => 'Monday',
            'Martes' => 'Tuesday',
            'Miércoles' => 'Wednesday',
            'Jueves' => 'Thursday',
            'Viernes' => 'Friday',
            'Sábado' => 'Saturday',
            'Domingo' => 'Sunday'
        ];
        $day_map_en_es = array_flip($day_map_es_en);
        $current_day_name_es = $day_map_en_es[$current_day_english] ?? $current_day_english; // Fallback al nombre en inglés si no está en el mapa

        $today_schedule_entry = null;
        foreach ($schedule as $entry) {
            if (isset($entry['day']) && $entry['day'] === $current_day_name_es) {
                $today_schedule_entry = $entry;
                break;
            }
        }

        $is_open_now = false;

        if ($today_schedule_entry && isset($today_schedule_entry['status']) && $today_schedule_entry['status'] === 'open' && !empty($today_schedule_entry['open']) && !empty($today_schedule_entry['close'])) {
            $open_time_str = $today_schedule_entry['open'];
            $close_time_str = $today_schedule_entry['close'];

            try {
                $open_datetime = DateTime::createFromFormat('H:i', $open_time_str, new DateTimeZone($timezone_string));
                $close_datetime = DateTime::createFromFormat('H:i', $close_time_str, new DateTimeZone($timezone_string));

                if (!$open_datetime || !$close_datetime) {
                    GloryLogger::error("Could not parse open/close times for today: $open_time_str, $close_time_str. Key: $schedule_key");
                } else {
                    // Ajustar fechas al día actual para la comparación
                    $open_datetime->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
                    $close_datetime->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));

                    if ($close_datetime < $open_datetime) { // Horario cruza medianoche
                        $close_datetime_adjusted = clone $close_datetime;
                        $close_datetime_adjusted->add(new DateInterval('P1D')); // El cierre es al día siguiente
                        if ($now >= $open_datetime && $now < $close_datetime_adjusted) {
                            $is_open_now = true;
                        }
                    } else { // Cierre el mismo día
                        if ($now >= $open_datetime && $now < $close_datetime) {
                            $is_open_now = true;
                        }
                    }
                }
            } catch (\Exception $e) {
                GloryLogger::error("Error processing schedule times for key $schedule_key: " . $e->getMessage());
            }
        }

        if ($is_open_now) {
            $message = 'Abierto.';
            if ($today_schedule_entry && !empty($today_schedule_entry['close'])) {
                $close_display_time = ($today_schedule_entry['close'] === '00:00' || $today_schedule_entry['close'] === '24:00') ? 'medianoche' : 'las ' . esc_html($today_schedule_entry['close']);
                $message .= " Cerramos a {$close_display_time}.";
            }
            return ['status_class' => 'opened', 'message' => $message];
        } else {
            $next_opening_day_info = '';
            $days_ordered = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
            $current_day_index = array_search($current_day_name_es, $days_ordered);

            if ($current_day_index !== false) {
                // Comprobar si abre más tarde hoy
                if ($today_schedule_entry && $today_schedule_entry['status'] === 'open' && !empty($today_schedule_entry['open'])) {
                    try {
                        $open_datetime_today = DateTime::createFromFormat('H:i', $today_schedule_entry['open'], new DateTimeZone($timezone_string));
                        if ($open_datetime_today) {
                            $open_datetime_today->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
                            if ($now < $open_datetime_today) { // Si la hora actual es antes de la hora de apertura de hoy
                                $next_opening_day_info = 'hoy a las ' . esc_html($today_schedule_entry['open']);
                            }
                        }
                    } catch (\Exception $e) {
                        GloryLogger::error("Error parsing today's opening time for next status: " . $e->getMessage());
                    }
                }

                // Si no abre más tarde hoy, buscar el próximo día
                if (empty($next_opening_day_info)) {
                    for ($i = 1; $i <= 7; $i++) {
                        $next_day_idx = ($current_day_index + $i) % 7;
                        $next_day_name_candidate = $days_ordered[$next_day_idx];

                        foreach ($schedule as $s_entry) { // Iterar sobre el array de horario completo
                            if ($s_entry['day'] === $next_day_name_candidate && $s_entry['status'] === 'open' && !empty($s_entry['open'])) {
                                $day_prefix = ($i === 1) ? 'mañana' : 'el ' . lcfirst($next_day_name_candidate);
                                $next_opening_day_info = $day_prefix . ' a las ' . esc_html($s_entry['open']);
                                goto found_next_opening; // Salir de ambos bucles
                            }
                        }
                    }
                    found_next_opening:; // Etiqueta para el goto
                }
            }
            $message = 'Cerrado.';
            if (!empty($next_opening_day_info)) {
                $message .= ' Abrimos ' . $next_opening_day_info . '.';
            }
            return ['status_class' => 'closed', 'message' => $message];
        }
    }


    public static function getRegisteredContentFields(): array
    {
        $fields_with_current_values = [];
        foreach (self::$registered_content as $key => $config) {
            $option_name = self::OPTION_PREFIX . $key;

            $is_panel_value_flag = get_option($option_name . self::OPTION_META_PANEL_SAVED_SUFFIX, false);

            // Usamos un centinela para distinguir "no existe" de "existe y es false/null/""
            $sentinel = new \stdClass();
            $db_value_or_sentinel = get_option($option_name, $sentinel);
            $option_actually_exists_in_db = ($db_value_or_sentinel !== $sentinel);

            if ($is_panel_value_flag) {
                // Si el flag del panel está activo, el valor en la BD es el valor del panel.
                // $db_value_or_sentinel es el valor real de la BD.
                $config['current_value'] = $db_value_or_sentinel;
                GloryLogger::info("getRegisteredContentFields: Key '$key' uses panel value due to flag. Value: " . print_r($config['current_value'], true));
            } elseif ($option_actually_exists_in_db) {
                // El flag del panel no está, pero la opción EXISTE en la BD.
                // Esto significa que es un valor por defecto del código que fue sincronizado por register()
                // o un valor antiguo antes de que existiera el flag. Se considera el valor actual.
                $config['current_value'] = $db_value_or_sentinel;
                GloryLogger::info("getRegisteredContentFields: Key '$key' uses existing DB value (no panel flag). Value: " . print_r($config['current_value'], true));
            } else {
                // Ni flag del panel, ni opción existe en la BD. Usar el default del código.
                $config['current_value'] = $config['default'] ?? null;
                GloryLogger::info("getRegisteredContentFields: Key '$key' uses code default (no panel flag, no DB value). Value: " . print_r($config['current_value'], true));
            }

            // Para depuración en el panel si lo necesitas:
            // $config['debug_is_panel_value'] = $is_panel_value_flag;
            // $config['debug_code_hash_on_save'] = get_option($option_name . self::OPTION_META_CODE_HASH_SUFFIX, null);
            // $config['debug_current_code_hash'] = $config['code_version_hash'] ?? null;
            // $config['debug_option_actually_exists'] = $option_actually_exists_in_db;

            $fields_with_current_values[$key] = $config;
        }
        return $fields_with_current_values;
    }
}
