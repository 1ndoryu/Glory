<?php
# /Glory/ContentManager.php

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
    private const OPTION_PREFIX = 'glory_content_';

    /**
     * Almacena la configuración de los campos de contenido registrados.
     * Estructura por clave:
     * 'key' => [
     *     'default'     => mixed, // Valor por defecto del código
     *     'type'        => string, // 'text', 'richText', 'image', 'schedule', 'raw'
     *     'label'       => string, // Título para el panel de administración
     *     'section'     => string, // Sección en el panel de administración
     *     'description' => string, // Descripción para el panel de administración
     *     'escape'      => bool,   // Si el método get() debe escapar HTML por defecto para este tipo.
     * ]
     */
    private static array $registered_content = [];

    /**
     * Registra un campo de contenido editable con sus metadatos y valor por defecto.
     * Este método es la forma preferida de definir los contenidos y sus propiedades.
     *
     * @param string $key Identificador único para este contenido.
     * @param array $args Argumentos para configurar el campo:
     *                    'default'     => (mixed) Valor por defecto.
     *                    'type'        => (string) Tipo de contenido (e.g., 'text', 'richText', 'image', 'schedule'). Ayuda a determinar el control en el panel. Default: 'text'.
     *                    'label'       => (string) Título descriptivo para el panel. Default: se genera desde $key.
     *                    'section'     => (string) Sección del panel a la que pertenece. Default: 'general'.
     *                    'description' => (string) Descripción para el panel. Default: ''.
     *                    'escape'      => (bool) Si el valor debe ser escapado por esc_html() por defecto al usar get(). Default: true para 'text', false para otros.
     */
    public static function register(string $key, array $args = []): void
    {
        $default_type = $args['type'] ?? 'text';
        $default_escape = ($default_type === 'text'); // Escapar por defecto para 'text', no para otros como 'richText', 'image', 'schedule'

        $defaults = [
            'default'     => '',
            'type'        => $default_type,
            'label'       => ucfirst(str_replace(['_', '-'], ' ', $key)),
            'section'     => 'general',
            'description' => '',
            'escape'      => $default_escape,
        ];

        self::$registered_content[$key] = wp_parse_args($args, $defaults);
        GloryLogger::info("Content explicitly registered for key: $key with args: " . json_encode(self::$registered_content[$key]));
    }

    /**
     * Registra información de un campo "al vuelo" si no fue registrado explícitamente.
     * Esto ocurre cuando se llama a get(), text(), etc., con metadatos.
     */
    private static function registerOnTheFly(string $key, $default_value, string $type, ?string $label, ?string $section, ?string $description, bool $escape_behavior): void
    {
        if (!isset(self::$registered_content[$key])) {
            self::register($key, [
                'default'     => $default_value,
                'type'        => $type,
                'label'       => $label, // register() se encargará del default si es null
                'section'     => $section,
                'description' => $description,
                'escape'      => $escape_behavior,
            ]);
            GloryLogger::info("Content registered on-the-fly for key: $key");
        }
    }

    /**
     * Obtiene el valor de un contenido editable.
     *
     * 1. Busca una opción en la base de datos.
     * 2. Si no se encuentra o está vacío, usa el valor por defecto definido (priorizando el registrado, luego el parámetro).
     * 3. Registra metadatos "al vuelo" si se proporcionan y no existe un registro explícito.
     *
     * @param string $key Identificador único para este contenido.
     * @param mixed $default_param Valor por defecto a usar si no hay valor en BD y no hay default registrado.
     * @param bool $escape_output Si es true y el valor es string, escapa el valor para salida segura en HTML.
     * @param string|null $panel_title (Opcional) Título para el panel de administración.
     * @param string|null $panel_section (Opcional) Sección para el panel de administración.
     * @param string|null $panel_description (Opcional) Descripción para el panel de administración.
     * @param string $content_type (Interno) Usado para determinar el tipo al registrar "al vuelo".
     *
     * @return mixed El valor del contenido (escapado según $escape_output si es string) o el valor por defecto.
     */
    public static function get(
        string $key,
        $default_param = '',
        bool $escape_output = true,
        ?string $panel_title = null,
        ?string $panel_section = null,
        ?string $panel_description = null,
        string $content_type = 'text' // Ayuda a 'registerOnTheFly' a saber el tipo
    ) {
        GloryLogger::info("get() called for key: $key");
        $option_name = self::OPTION_PREFIX . $key;
        $db_value = get_option($option_name);

        $final_value = null;

        // Prioridad 1: Valor de la base de datos (si existe y no es una cadena vacía)
        if ($db_value !== false && (!is_string($db_value) || $db_value !== '')) {
            $final_value = $db_value;
            GloryLogger::info("Value found in DB for key: $key.");
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
        // Se usa el $default_param como el default para el registro on-the-fly
        // La $escape_output también informa el comportamiento de escape por defecto del registro.
        self::registerOnTheFly($key, $default_param, $content_type, $panel_title, $panel_section, $panel_description, $escape_output);

        if (is_string($final_value) && $escape_output) {
            return esc_html($final_value);
        }

        return $final_value;
    }

    /**
     * Obtiene texto simple (siempre escapado por defecto).
     *
     * @param string $key Identificador único.
     * @param string $default Valor por defecto.
     * @param string|null $panel_title (Opcional) Título para el panel de administración.
     * @param string|null $panel_section (Opcional) Sección para el panel de administración.
     * @param string|null $panel_description (Opcional) Descripción para el panel de administración.
     * @return string Texto escapado.
     */
    public static function text(string $key, string $default = '', ?string $panel_title = null, ?string $panel_section = null, ?string $panel_description = null): string
    {
        GloryLogger::info("text called for key: $key");
        return self::get($key, $default, true, $panel_title, $panel_section, $panel_description, 'text');
    }

    /**
     * Obtiene texto que podría contener HTML simple y seguro (sanitizado con wp_kses_post).
     *
     * @param string $key Identificador único.
     * @param string $default Valor por defecto.
     * @param string|null $panel_title (Opcional) Título para el panel de administración.
     * @param string|null $panel_section (Opcional) Sección para el panel de administración.
     * @param string|null $panel_description (Opcional) Descripción para el panel de administración.
     * @return string Texto sanitizado.
     */
    public static function richText(string $key, string $default = '', ?string $panel_title = null, ?string $panel_section = null, ?string $panel_description = null): string
    {
        GloryLogger::info("richText called for key: $key");
        $value = self::get($key, $default, false, $panel_title, $panel_section, $panel_description, 'richText');
        return wp_kses_post($value);
    }

    /**
     * Obtiene la URL de una imagen.
     *
     * @param string $key Identificador único.
     * @param string $default URL por defecto.
     * @param string|null $panel_title (Opcional) Título para el panel de administración.
     * @param string|null $panel_section (Opcional) Sección para el panel de administración.
     * @param string|null $panel_description (Opcional) Descripción para el panel de administración.
     * @return string URL de la imagen.
     */
    public static function image(string $key, string $default = '', ?string $panel_title = null, ?string $panel_section = null, ?string $panel_description = null): string
    {
        GloryLogger::info("image called for key: $key");
        return self::get($key, $default, false, $panel_title, $panel_section, $panel_description, 'image');
    }

    /**
     * Obtiene un horario guardado como un array de días.
     *
     * @param string $key Identificador único.
     * @param array $defaultSchedule Array por defecto para el horario.
     * @param string|null $panel_title (Opcional) Título para el panel de administración.
     * @param string|null $panel_section (Opcional) Sección para el panel de administración.
     * @param string|null $panel_description (Opcional) Descripción para el panel de administración.
     * @return array El horario recuperado o el array por defecto, normalizado.
     */
    public static function schedule(string $key, array $defaultSchedule = [], ?string $panel_title = null, ?string $panel_section = null, ?string $panel_description = null): array
    {
        GloryLogger::info("schedule called for key: $key");
        $schedule_data = self::get($key, $defaultSchedule, false, $panel_title, $panel_section, $panel_description, 'schedule');

        if (!is_array($schedule_data) || empty($schedule_data)) {
            GloryLogger::info("No schedule data found or invalid for key: $key. Using provided default schedule for normalization.");
            // Si get() devuelve $defaultSchedule porque no hay nada en BD ni registro,
            // o si el valor de la BD/registro no es un array válido, usamos $defaultSchedule para la normalización.
            $schedule_data = $defaultSchedule; 
        }
        
        $normalized_schedule = [];
        if (is_array($schedule_data)) { // Asegurar que seguimos teniendo un array
            foreach ($schedule_data as $entry) {
                if (!is_array($entry)) { // Saltar entradas no válidas dentro del array
                    GloryLogger::warning("Invalid entry in schedule data for key: $key. Entry: " . print_r($entry, true));
                    continue;
                }
                $normalized_entry = [
                    'day'    => $entry['day'] ?? 'Día Desconocido',
                    'open'   => $entry['open'] ?? '',
                    'close'  => $entry['close'] ?? '',
                    'status' => $entry['status'] ?? 'closed',
                    'hours'  => $entry['hours'] ?? ''
                ];

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

    /**
     * Obtiene y muestra el estado actual del restaurante (abierto/cerrado) y el próximo horario.
     * (Esta función no necesita cambios significativos, ya que depende de self::schedule)
     *
     * @param string $schedule_key La clave utilizada para guardar el horario (usada con self::schedule).
     * @param array $default_schedule El horario por defecto si no se encuentra nada.
     * @param string $timezone_string La zona horaria para las comparaciones, ej: 'Europe/Madrid'.
     * @return array Un array con 'status_class' ('opened' o 'closed') y 'message'.
     */
    public static function getCurrentStatus(string $schedule_key, array $default_schedule, string $timezone_string = 'Europe/Madrid'): array
    {
        GloryLogger::info("getCurrentStatus called for schedule_key: $schedule_key");
        // No es necesario pasar metadatos del panel aquí, ya que schedule() se encarga de su propio registro.
        $schedule = self::schedule($schedule_key, $default_schedule);
        
        if (empty($schedule)) {
            return ['status_class' => 'closed', 'message' => 'Horario no disponible.'];
        }

        try {
            $now = new DateTime('now', new DateTimeZone($timezone_string));
        } catch (\Exception $e) {
            GloryLogger::error("Invalid timezone string: $timezone_string. Error: " . $e->getMessage());
            $now = new DateTime('now', new DateTimeZone('UTC')); // Fallback
        }
        
        $current_day_english = $now->format('l');
        $day_map_es_en = [
            'Lunes' => 'Monday', 'Martes' => 'Tuesday', 'Miércoles' => 'Wednesday',
            'Jueves' => 'Thursday', 'Viernes' => 'Friday', 'Sábado' => 'Saturday', 'Domingo' => 'Sunday'
        ];
        $day_map_en_es = array_flip($day_map_es_en);
        $current_day_name_es = $day_map_en_es[$current_day_english] ?? $current_day_english;

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
                
                // Si createFromFormat falla (ej. formato incorrecto), $open_datetime o $close_datetime serán false.
                if (!$open_datetime || !$close_datetime) {
                    GloryLogger::error("Could not parse open/close times for today: $open_time_str, $close_time_str. Key: $schedule_key");
                } else {
                    // Ajustar fechas al día actual para la comparación
                    $open_datetime->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
                    $close_datetime->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));

                    // Manejar horarios que cruzan la medianoche (ej: 22:00 - 02:00)
                    if ($close_datetime < $open_datetime) {
                        // Si la hora de cierre es anterior a la de apertura, asumimos que es del día siguiente.
                        // Verificamos si 'now' está entre la apertura de hoy y la medianoche O
                        // si 'now' está entre la medianoche y la hora de cierre (que ya sería del día siguiente).
                        $end_of_today_for_open_range = (clone $open_datetime)->setTime(23, 59, 59);
                        $start_of_next_day_for_close_range = (clone $close_datetime)->setTime(0, 0, 0); // $close_datetime ya es del día "siguiente" lógicamente

                        if (($now >= $open_datetime && $now <= $end_of_today_for_open_range) || // Hoy, después de abrir y antes de medianoche
                            ($now >= $start_of_next_day_for_close_range && $now < $close_datetime->add(new DateInterval('P1D'))) // Mañana, antes de cerrar (close_datetime se ajusta al día siguiente)
                           ) {
                            $is_open_now = true;
                        }
                         // Este caso es más complejo si el día actual ($now) ya es el "día de cierre".
                         // La lógica actual asume que $today_schedule_entry es para el día de $now.
                         // Si hoy es Sábado 01:00, y el horario del Viernes era 22:00-02:00,
                         // necesitaríamos una lógica para mirar el cierre del día *anterior* si $now es temprano en el día.
                         // Una forma más simple es que el admin ponga: Vie: 22:00-23:59, Sab: 00:00-02:00.
                         // Con la lógica actual: si son las 01:00 del Sábado y el horario del Sábado es 00:00-02:00, funcionará.
                         // Si el horario cruza la medianoche (ej. 22:00-02:00)
                        // Si la hora actual es mayor o igual a la hora de apertura (estamos en el primer día del rango)
                        // O si la hora actual es menor que la hora de cierre (estamos en el segundo día del rango)
                        // Esta es la lógica más simple para un solo rango que cruza medianoche:
                        if ($now >= $open_datetime || $now < $close_datetime) { // Asumiendo que $close_datetime ya es para el día siguiente lógicamente.
                            // Pero $open_datetime y $close_datetime se crean con la fecha de $now.
                            // Si $close_datetime < $open_datetime, $close_datetime se refiere al día siguiente.
                            $close_datetime_adjusted = clone $close_datetime;
                            $close_datetime_adjusted->add(new DateInterval('P1D'));
                            if ($now >= $open_datetime && $now < $close_datetime_adjusted) {
                                $is_open_now = true;
                            }
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
                $close_display = ($today_schedule_entry['close'] === '00:00' || $today_schedule_entry['close'] === '24:00') ? 'medianoche' : 'las ' . esc_html($today_schedule_entry['close']);
                $message .= " Cerramos a {$close_display}.";
            }
            return ['status_class' => 'opened', 'message' => $message];
        } else {
            $next_opening_day_info = '';
            $days_ordered = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
            $current_day_index = array_search($current_day_name_es, $days_ordered);

            if ($current_day_index !== false) {
                if ($today_schedule_entry && $today_schedule_entry['status'] === 'open' && !empty($today_schedule_entry['open'])) {
                    try {
                        $open_datetime_today = DateTime::createFromFormat('H:i', $today_schedule_entry['open'], new DateTimeZone($timezone_string));
                        if ($open_datetime_today) {
                            $open_datetime_today->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
                            if ($now < $open_datetime_today) {
                                $next_opening_day_info = 'hoy a las ' . esc_html($today_schedule_entry['open']);
                            }
                        }
                    } catch (\Exception $e) {
                         GloryLogger::error("Error parsing today's opening time for next status: " . $e->getMessage());
                    }
                }

                if (empty($next_opening_day_info)) {
                    for ($i = 1; $i <= 7; $i++) {
                        $next_day_idx = ($current_day_index + $i) % 7;
                        $next_day_name_candidate = $days_ordered[$next_day_idx];
                        
                        foreach($schedule as $s_entry){
                            if($s_entry['day'] === $next_day_name_candidate && $s_entry['status'] === 'open' && !empty($s_entry['open'])){
                                $day_prefix = ($i === 1) ? 'mañana' : 'el ' . lcfirst($next_day_name_candidate); // lcfirst para "el lunes"
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

    /**
     * Devuelve todos los campos de contenido registrados con sus metadatos.
     * Útil para construir el panel de administración.
     *
     * @return array
     */
    public static function getRegisteredContentFields(): array
    {
        // Opcionalmente, enriquecer con el valor actual de la base de datos
        $fields_with_current_values = [];
        foreach (self::$registered_content as $key => $config) {
            $option_name = self::OPTION_PREFIX . $key;
            $db_value = get_option($option_name);

            if ($db_value !== false && (!is_string($db_value) || $db_value !== '')) {
                $config['current_value'] = $db_value;
            } else {
                 // Si no hay valor en la DB, el current_value es el default del registro
                $config['current_value'] = $config['default'] ?? null;
            }
            $fields_with_current_values[$key] = $config;
        }
        return $fields_with_current_values;
        // O simplemente: return self::$registered_content; si el panel se encarga de obtener los valores.
    }
}