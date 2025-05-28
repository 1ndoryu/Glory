<?php
# /Glory/Class/ContentManager.php

namespace Glory\Class;

// use Glory\Class\GloryLogger; // Ya está en el namespace
use DateTime;
use DateTimeZone;
use DateInterval;

class ContentManager
{
    public const OPTION_PREFIX = 'glory_content_';
    public const OPTION_META_CODE_HASH_SUFFIX = '_code_hash_on_save';
    public const OPTION_META_PANEL_SAVED_SUFFIX = '_is_panel_value';

    private static array $registered_content = [];
    private static $db_sentinel; 

    // Llamar a esto una vez, e.g., en un hook 'init' de tu plugin.
    public static function static_init() {
        if (self::$db_sentinel === null) {
            self::$db_sentinel = new \stdClass();
        }
    }

    public static function register(string $key, array $args = []): void
    {
        if (self::$db_sentinel === null) { self::static_init(); } // Asegurar inicialización

        $default_type = $args['type'] ?? 'text';
        $defaults = [
            'default'                   => '',
            'type'                      => $default_type,
            'label'                     => ucfirst(str_replace(['_', '-'], ' ', $key)),
            'section'                   => 'general',
            'section_label'             => ucfirst(str_replace(['_', '-'], ' ', $args['section'] ?? 'general')),
            'description'               => '',
            'escape'                    => ($default_type === 'text'),
            'force_default_on_register' => false,
        ];
        $parsed_args = wp_parse_args($args, $defaults);
        $code_default_for_hash = $parsed_args['default'];
        $parsed_args['code_version_hash'] = md5(is_scalar($code_default_for_hash) ? (string)$code_default_for_hash : serialize($code_default_for_hash));
        
        // No sobreescribir si ya existe y solo se está llamando de nuevo (e.g. desde registerOnTheFly)
        // Solo la primera llamada a register() para una clave debe establecer la configuración completa.
        // Las llamadas subsecuentes (ej: de get() -> registerOnTheFly()) no deberían alterar la config original.
        if (!isset(self::$registered_content[$key])) {
            self::$registered_content[$key] = $parsed_args;
        } else {
            // Si ya está registrado, solo actualizamos el hash del default del código por si acaso ha cambiado
            // entre la registración inicial y una llamada posterior a register() para la misma clave (poco común pero posible).
             self::$registered_content[$key]['code_version_hash'] = $parsed_args['code_version_hash'];
             // Y nos aseguramos que el default en memoria también esté actualizado con el último `register` call.
             // Esto es importante si `register` se llama múltiples veces con diferentes defaults para la misma clave ANTES de `get`.
             self::$registered_content[$key]['default'] = $parsed_args['default'];
        }


        // --- Lógica de Sincronización Código vs Panel ---
        // Esta lógica se ejecuta CADA VEZ que se registra un campo (típicamente una vez por carga de página por campo).
        $config = self::$registered_content[$key]; // Usar la config en memoria que podría haber sido actualizada
        $option_name = self::OPTION_PREFIX . $key;
        $code_default_value = $config['default'];
        $current_code_hash = $config['code_version_hash']; // Hash del default del código actual

        $db_value = get_option($option_name, self::$db_sentinel);
        $is_panel_value_flag = get_option($option_name . self::OPTION_META_PANEL_SAVED_SUFFIX, false); // false si no existe
        $hash_on_panel_save = get_option($option_name . self::OPTION_META_CODE_HASH_SUFFIX, self::$db_sentinel); // Sentinel si no existe

        // GloryLogger::info("SYNC [$key]: Initiating. Code default hash: '$current_code_hash'. force_default_on_register: " . ($config['force_default_on_register'] ? 'true':'false'));
        // GloryLogger::info("SYNC [$key]: DB state before sync: panel_flag=".($is_panel_value_flag?'true':'false').", hash_on_save=".($hash_on_panel_save === self::$db_sentinel ? 'NOT_SET' : $hash_on_panel_save).", db_value=".($db_value === self::$db_sentinel ? 'NOT_SET' : substr(print_r($db_value, true),0,100)."...") );


        if ($config['force_default_on_register']) {
            // GloryLogger::info("SYNC [$key]: 'force_default_on_register' is TRUE. Applying code default.");
            if ($db_value === self::$db_sentinel || $db_value !== $code_default_value) {
                update_option($option_name, $code_default_value);
                // GloryLogger::info("SYNC [$key]: Value updated to code default.");
            }
            if ($is_panel_value_flag) delete_option($option_name . self::OPTION_META_PANEL_SAVED_SUFFIX);
            if ($hash_on_panel_save !== self::$db_sentinel) delete_option($option_name . self::OPTION_META_CODE_HASH_SUFFIX);
            // GloryLogger::info("SYNC [$key]: Panel flags cleared due to force_default.");
        } else { // force_default_on_register is FALSE
            if ($is_panel_value_flag) { // Un valor fue guardado explícitamente desde el panel.
                // GloryLogger::info("SYNC [$key]: Panel flag is TRUE. Checking hash consistency.");
                if ($hash_on_panel_save === self::$db_sentinel) {
                    GloryLogger::error("SYNC ERROR [$key]: Panel value flag is TRUE, but NO HASH was stored from panel save. This is inconsistent. The code default for this key might have changed, or the hash was lost. To ensure data integrity, the current code default will be applied, and panel flags will be cleared. Please review the value in the panel. Current code default: " . print_r($code_default_value, true));
                    update_option($option_name, $code_default_value);
                    delete_option($option_name . self::OPTION_META_PANEL_SAVED_SUFFIX);
                    // No hay hash que borrar si era sentinel
                } elseif ($current_code_hash !== $hash_on_panel_save) {
                    GloryLogger::error("SYNC MISMATCH [$key]: Panel value OVERWRITTEN. The code's default value has changed since this content was last saved in the panel. Applying new code default and clearing panel flags. Old code hash (at panel save): '{$hash_on_panel_save}', New code hash (current): '{$current_code_hash}'. New code default: " . print_r($code_default_value, true));
                    update_option($option_name, $code_default_value);
                    delete_option($option_name . self::OPTION_META_PANEL_SAVED_SUFFIX);
                    delete_option($option_name . self::OPTION_META_CODE_HASH_SUFFIX);
                } else {
                    // GloryLogger::info("SYNC [$key]: Panel value RETAINED. Hashes match ('$current_code_hash').");
                    // El valor en $db_value (que es el del panel) se mantiene. No se hace nada.
                }
            } else { // Panel flag is FALSE (o no existe). El panel no ha guardado este valor, o fue invalidado.
                // GloryLogger::info("SYNC [$key]: Panel flag is FALSE. Code default logic applies.");
                if ($db_value === self::$db_sentinel) { // La opción principal no existe en la BD.
                    // GloryLogger::info("SYNC [$key]: Option does not exist in DB. Initializing with code default.");
                    update_option($option_name, $code_default_value);
                } else { // La opción existe, pero no tiene flag de panel. Pudo ser un default anterior.
                    // Si el valor actual en BD (sin flag de panel) es diferente al default del código actual,
                    // actualizamos para reflejar el default del código más reciente.
                    if ($db_value !== $code_default_value) {
                        // GloryLogger::info("SYNC [$key]: Option exists in DB without panel flag, but its value differs from current code default. Updating to current code default: " . print_r($code_default_value, true));
                        update_option($option_name, $code_default_value);
                    } else {
                        // GloryLogger::info("SYNC [$key]: Option exists in DB without panel flag, and its value matches current code default. No change needed.");
                    }
                }
                // En ambos casos (inicializado o actualizado a default del código sin flag de panel), los flags de panel (_is_panel_value y _code_hash_on_save)
                // no se establecen o se borran si existieran, porque el valor ahora es gobernado por el código.
                if ($is_panel_value_flag) { /* Esto no debería pasar si is_panel_value_flag ya es false */ delete_option($option_name . ContentManager::OPTION_META_PANEL_SAVED_SUFFIX); }
                if ($hash_on_panel_save !== self::$db_sentinel) { delete_option($option_name . self::OPTION_META_CODE_HASH_SUFFIX); }
            }
        }
        // GloryLogger::info("SYNC [$key]: Finished.");
    }


    public static function getCodeDefaultHash(string $key): ?string
    {
        if (isset(self::$registered_content[$key]['code_version_hash'])) {
            return self::$registered_content[$key]['code_version_hash'];
        }
        // Fallback si se llama antes de que 'code_version_hash' esté poblado (poco probable con el flujo actual si register siempre se llama primero)
        // o si el campo fue registrado "on-the-fly" y por alguna razón no se calculó el hash en ese momento (se arregló).
        if (isset(self::$registered_content[$key]['default'])) {
            $default_value = self::$registered_content[$key]['default'];
            // GloryLogger::info("getCodeDefaultHash: Calculating hash on-the-fly for key '{$key}' as 'code_version_hash' was not pre-set in memory (should be rare).");
            return md5(is_scalar($default_value) ? (string)$default_value : serialize($default_value));
        }
        GloryLogger::error("getCodeDefaultHash: CRITICAL - No default value found for key '{$key}' in registered content to calculate hash. This indicates an issue with registration flow.");
        return null;
    }


    private static function registerOnTheFly(string $key, $default_value, string $type, ?string $label, ?string $section, ?string $description, bool $escape_behavior): void
    {
        if (!isset(self::$registered_content[$key])) {
            // GloryLogger::info("ContentManager: Registering on-the-fly for key: $key. This will trigger full sync logic.");
            // Los campos "on-the-fly" se registran con `force_default_on_register = false` por defecto
            // y su 'default' es el $default_param pasado a get().
            self::register($key, [
                'default'     => $default_value, // El default para on-the-fly es el que se pasó a get()
                'type'        => $type,
                'label'       => $label, // Puede ser null, register() lo manejará
                'section'     => $section, // Puede ser null
                'description' => $description, // Puede ser null
                'escape'      => $escape_behavior,
                // 'force_default_on_register' se quedará en false por defecto en register().
            ]);
        } else {
            // Si ya está registrado, nos aseguramos que su 'default' en memoria y 'code_version_hash'
            // reflejen la llamada MÁS RECIENTE a register() o registerOnTheFly() para esa clave,
            // ya que el $default_param de get() podría ser diferente al $default del register() original.
            // Esto es sutil: el 'default' para la lógica de SINCRONIZACIÓN (en register()) es el del PRIMER register().
            // Pero el 'default' para fallback en GET() si la opción no existe (después de sync) podría ser el de registerOnTheFly.
            // La forma en que está ahora, register() actualizará el 'default' y 'code_version_hash' en self::$registered_content[$key]
            // con los valores de la última llamada. Esto significa que la lógica de sincronización usará
            // el default de la llamada más reciente a register/registerOnTheFly.
            // Esto es generalmente lo que se quiere: la última definición de "default del código" es la que cuenta.
        }
    }

    public static function get(
        string $key,
        $default_param = '', // Default de último recurso si la opción no existe después de la sincronización
        bool $escape_output = true,
        ?string $panel_title = null,
        ?string $panel_section = null,
        ?string $panel_description = null,
        string $content_type = 'text'
    ) {
        if (self::$db_sentinel === null) { self::static_init(); }

        // GloryLogger::info("GET [$key]: Called. Default param value: " . substr(print_r($default_param, true),0,100)."..." );

        // PASO 1: Asegurar que el campo esté "registrado" y la lógica de sincronización se ejecute.
        // Si $key no está en self::$registered_content, se registrará aquí.
        // Si ya está, registerOnTheFly no hará nada más que actualizar el hash/default en memoria si es necesario.
        // El `default_value` para `registerOnTheFly` es `$default_param` de `get()`.
        self::registerOnTheFly($key, $default_param, $content_type, $panel_title, $panel_section, $panel_description, $escape_output);

        // PASO 2: Obtener el valor de la opción de la BD.
        // En este punto, la lógica de `register()` (incluida la sincronización) ya se ha ejecutado.
        // El valor en la opción `$option_name` DEBERÍA ser el valor final y correcto.
        $option_name = self::OPTION_PREFIX . $key;
        $final_value = get_option($option_name, self::$db_sentinel);

        if ($final_value === self::$db_sentinel) {
            // Esto NO debería suceder si `register()` funcionó correctamente, ya que siempre inicializaría la opción.
            // Indica un problema en la lógica de `register()` o un estado inesperado.
            GloryLogger::error("GET ERROR [$key]: Option '$option_name' NOT FOUND in DB even after sync logic in register() should have run. This is unexpected. Fallback to in-memory registered default for '$key'.");
            // Como fallback, usar el default del código que está en memoria (que fue establecido por la última llamada a register/registerOnTheFly).
            $final_value = self::$registered_content[$key]['default'] ?? $default_param; // Doble fallback
        } else {
            // GloryLogger::info("GET [$key]: Retrieved final value from DB option '$option_name' (post-sync): " . substr(print_r($final_value, true),0,100)."..." );
        }
        
        if (is_string($final_value) && $escape_output) {
            return esc_html($final_value);
        }
        return $final_value;
    }

    public static function text(string $key, string $default = '', ?string $panel_title = null, ?string $panel_section = null, ?string $panel_description = null): string
    {
        return (string) self::get($key, $default, true, $panel_title, $panel_section, $panel_description, 'text');
    }

    public static function richText(string $key, string $default = '', ?string $panel_title = null, ?string $panel_section = null, ?string $panel_description = null): string
    {
        $value = self::get($key, $default, false, $panel_title, $panel_section, $panel_description, 'richText');
        return wp_kses_post((string)$value);
    }

    public static function image(string $key, string $default = '', ?string $panel_title = null, ?string $panel_section = null, ?string $panel_description = null): string
    {
        return (string) self::get($key, $default, false, $panel_title, $panel_section, $panel_description, 'image');
    }

    public static function schedule(string $key, array $defaultSchedule = [], ?string $panel_title = null, ?string $panel_section = null, ?string $panel_description = null): array
    {
        $schedule_data = self::get($key, $defaultSchedule, false, $panel_title, $panel_section, $panel_description, 'schedule');

        if (!is_array($schedule_data)) { 
            GloryLogger::error("SCHEDULE ERROR [$key]: Retrieved data is not an array. Value: " . print_r($schedule_data, true) . ". Falling back to default schedule parameter.");
            $schedule_data = $defaultSchedule; 
        }
        
        // Si después del fallback (o si era un array vacío originalmente) y tenemos un defaultSchedule con datos, lo usamos para normalizar.
        // Esto previene intentar normalizar un array vacío si el default tiene estructura.
        if (empty($schedule_data) && !empty($defaultSchedule)) {
            // GloryLogger::info("SCHEDULE INFO [$key]: Retrieved data is empty or invalid, using provided default schedule for normalization: " . print_r($defaultSchedule, true));
            $schedule_data = $defaultSchedule;
        }

        $normalized_schedule = [];
        if (is_array($schedule_data)) { // Proceder solo si es un array
            foreach ($schedule_data as $entry) {
                if (!is_array($entry) || !isset($entry['day'])) {
                    // GloryLogger::info("SCHEDULE INFO [$key]: Invalid or incomplete entry in schedule data. Entry: " . print_r($entry, true) . ". Skipping.");
                    continue;
                }
                
                $n_day = sanitize_text_field($entry['day']);
                $n_status = sanitize_text_field($entry['status'] ?? 'closed');
                $n_open = ($n_status === 'open') ? sanitize_text_field($entry['open'] ?? '') : '';
                $n_close = ($n_status === 'open') ? sanitize_text_field($entry['close'] ?? '') : '';
                $n_hours = '';

                if ($n_status === 'open' && !empty($n_open) && !empty($n_close)) {
                    $n_hours = $n_open . '-' . $n_close;
                } elseif ($n_status === 'closed') {
                    $n_hours = 'Cerrado';
                } else if ($n_status === 'open' && empty($n_open) && empty($n_close) && !empty($entry['hours']) && strtolower($entry['hours']) !== 'cerrado') {
                     // Intentar reconstruir open/close desde hours si status es open pero faltan tiempos
                    $parts = explode('-', $entry['hours']);
                    if (count($parts) === 2) {
                        $n_open = trim($parts[0]);
                        $n_close = trim($parts[1]);
                        // Validar formato H:i, si no, descartar.
                        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $n_open) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $n_close)) {
                            $n_open = ''; $n_close = ''; $n_status = 'closed'; $n_hours = 'Cerrado';
                            GloryLogger::info("SCHEDULE INFO [$key]: Invalid time format in 'hours' field '{$entry['hours']}' for day '{$n_day}'. Setting to closed.");
                        } else {
                           $n_hours = $n_open . '-' . $n_close; // Re-asignar por si trim cambió algo
                        }
                    } else {
                         $n_status = 'closed'; $n_hours = 'Cerrado'; // hours no tiene formato esperado
                         GloryLogger::info("SCHEDULE INFO [$key]: 'hours' field '{$entry['hours']}' for day '{$n_day}' is not in 'HH:MM-HH:MM' format. Setting to closed.");
                    }
                }


                $normalized_schedule[] = [
                    'day'    => $n_day,
                    'open'   => $n_open,
                    'close'  => $n_close,
                    'status' => $n_status,
                    'hours'  => $n_hours
                ];
            }
        }
        return $normalized_schedule;
    }


    public static function getCurrentStatus(string $schedule_key, array $default_schedule, string $timezone_string = 'Europe/Madrid'): array
    {
        $schedule = self::schedule($schedule_key, $default_schedule); 

        if (empty($schedule)) {
            return ['status_class' => 'closed', 'message' => 'Horario no disponible.'];
        }

        try {
            $timezone = new DateTimeZone($timezone_string);
            $now = new DateTime('now', $timezone);
        } catch (\Exception $e) {
            GloryLogger::error("getCurrentStatus [$schedule_key]: Invalid timezone string: '$timezone_string'. Error: " . $e->getMessage() . ". Falling back to UTC.");
            $timezone = new DateTimeZone('UTC'); // Fallback a UTC
            $now = new DateTime('now', $timezone);
        }

        $current_day_english = $now->format('l'); // Monday, Tuesday...
        $day_map_es_en = ['Lunes' => 'Monday', 'Martes' => 'Tuesday', 'Miércoles' => 'Wednesday', 'Jueves' => 'Thursday', 'Viernes' => 'Friday', 'Sábado' => 'Saturday', 'Domingo' => 'Sunday'];
        $day_map_en_es = array_flip($day_map_es_en);
        $current_day_name_es = $day_map_en_es[$current_day_english] ?? $current_day_english; // Fallback si no está en el mapa

        $today_schedule_entry = null;
        foreach ($schedule as $entry) { // Buscar la entrada del día actual
            if (isset($entry['day']) && $entry['day'] === $current_day_name_es) {
                $today_schedule_entry = $entry;
                break;
            }
        }

        $is_open_now = false;
        if ($today_schedule_entry && ($today_schedule_entry['status'] ?? 'closed') === 'open' && !empty($today_schedule_entry['open']) && !empty($today_schedule_entry['close'])) {
            try {
                // Crear objetos DateTime para hoy con las horas de apertura/cierre
                $open_time_str = $today_schedule_entry['open'];
                $close_time_str = $today_schedule_entry['close'];

                $open_datetime = DateTime::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $open_time_str, $timezone);
                $close_datetime = DateTime::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $close_time_str, $timezone);

                if (!$open_datetime || !$close_datetime) {
                    GloryLogger::error("getCurrentStatus [$schedule_key]: Could not parse open/close times for today: '$open_time_str', '$close_time_str'. Date used: ".$now->format('Y-m-d'));
                } else {
                    // Si la hora de cierre es anterior a la de apertura (ej: 22:00 - 02:00), el cierre es al día siguiente.
                    if ($close_datetime < $open_datetime) {
                        $close_datetime->add(new DateInterval('P1D'));
                    }
                    if ($now >= $open_datetime && $now < $close_datetime) {
                        $is_open_now = true;
                    }
                }
            } catch (\Exception $e) { // Capturar errores de DateTime
                GloryLogger::error("getCurrentStatus [$schedule_key]: Error processing schedule times: " . $e->getMessage());
            }
        }

        if ($is_open_now) {
            $message = 'Abierto.';
            if ($today_schedule_entry && !empty($today_schedule_entry['close'])) {
                $close_display_time = ($today_schedule_entry['close'] === '00:00' || $today_schedule_entry['close'] === '24:00') ? 'medianoche' : 'las ' . esc_html($today_schedule_entry['close']);
                $message .= " Cerramos a {$close_display_time}.";
            }
            return ['status_class' => 'opened', 'message' => $message];
        } else { // Está cerrado ahora, buscar próxima apertura
            $next_opening_day_info = '';
            $days_ordered = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
            $current_day_index = array_search($current_day_name_es, $days_ordered);

            if ($current_day_index !== false) { // Si el día actual es válido
                // Comprobar si abre más tarde hoy
                if ($today_schedule_entry && ($today_schedule_entry['status'] ?? 'closed') === 'open' && !empty($today_schedule_entry['open'])) {
                    try {
                        $open_datetime_today = DateTime::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $today_schedule_entry['open'], $timezone);
                        if ($open_datetime_today && $now < $open_datetime_today) { // Si la hora actual es antes de la hora de apertura de hoy
                            $next_opening_day_info = 'hoy a las ' . esc_html($today_schedule_entry['open']);
                        }
                    } catch (\Exception $e) { /* Error ya logueado si el formato es malo */ }
                }

                // Si no abre más tarde hoy, buscar el próximo día de apertura en la semana
                if (empty($next_opening_day_info)) {
                    for ($i = 1; $i <= 7; $i++) { // Iterar los próximos 7 días
                        $next_day_idx = ($current_day_index + $i) % 7;
                        $next_day_name_candidate = $days_ordered[$next_day_idx];
                        foreach ($schedule as $s_entry) {
                            if (($s_entry['day'] ?? '') === $next_day_name_candidate && ($s_entry['status'] ?? 'closed') === 'open' && !empty($s_entry['open'])) {
                                $day_prefix = ($i === 1) ? 'mañana' : 'el ' . lcfirst($next_day_name_candidate); // "mañana" o "el lunes"
                                $next_opening_day_info = $day_prefix . ' a las ' . esc_html($s_entry['open']);
                                goto found_next_opening; // Salir de ambos bucles
                            }
                        }
                    }
                    found_next_opening:;
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
        if (self::$db_sentinel === null) { self::static_init(); }
        // GloryLogger::info("getRegisteredContentFields: Preparing fields for admin panel.");
        $fields_with_current_values = [];

        // Asegurarse de que todos los campos definidos en el código estén en self::$registered_content
        // Esto es importante si se llaman directamente a getRegisteredContentFields() antes que a todos los `get()` o `register()` individuales.
        // Sin embargo, el flujo normal es que `register()` se llama para cada campo durante la carga del plugin/tema.

        foreach (self::$registered_content as $key => $config) {
            // La lógica de `register()` ya se ha ejecutado para este $key (o se ejecutará si es un `registerOnTheFly` indirecto).
            // El valor en la BD ya debería estar sincronizado.
            $option_name = self::OPTION_PREFIX . $key;
            $current_db_value = get_option($option_name, self::$db_sentinel);

            $new_config = $config; // Copiar la configuración base
            if ($current_db_value !== self::$db_sentinel) {
                $new_config['current_value'] = $current_db_value;
                // GloryLogger::info("getRegisteredContentFields [$key]: Value for panel display (from DB after sync): " . substr(print_r($new_config['current_value'], true),0,100)."..." );
            } else {
                // Esto sería un error grave si register() no inicializó la opción.
                $new_config['current_value'] = $config['default'] ?? null; // Fallback al default en memoria
                GloryLogger::error("getRegisteredContentFields ERROR [$key]: Option '$option_name' NOT FOUND in DB for panel. Using code default: " . substr(print_r($new_config['current_value'],true),0,100)."..." );
            }
            
            // // Añadir metadatos de depuración si es necesario
            // $new_config['debug_is_panel_value'] = get_option($option_name . self::OPTION_META_PANEL_SAVED_SUFFIX, false);
            // $new_config['debug_code_hash_on_save'] = get_option($option_name . self::OPTION_META_CODE_HASH_SUFFIX, '--NOT SET--');
            // $new_config['debug_current_code_hash'] = $config['code_version_hash'] ?? '--UNKNOWN--';
            // $new_config['debug_option_exists_in_db'] = ($current_db_value !== self::$db_sentinel);

            $fields_with_current_values[$key] = $new_config;
        }
        return $fields_with_current_values;
    }
}

// IMPORTANTE: Llama a ContentManager::static_init(); en un hook 'init' de tu plugin principal.
// Ejemplo (en el archivo principal de tu plugin):
// add_action('init', ['Glory\Class\ContentManager', 'static_init'], 1);
// Esto asegura que $db_sentinel se inicialice temprano.