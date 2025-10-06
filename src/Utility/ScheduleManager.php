<?php

namespace Glory\Utility;

use DateTime;
use DateTimeZone;
use DateInterval;
use Glory\Core\GloryLogger;
use Glory\Manager\OpcionManager;

/**
 * Clase de utilidad para gestionar y normalizar datos de horarios.
 * Ubicada en Utility para reflejar su propósito como helper con estado.
 */
class ScheduleManager
{
    public static function getScheduleData(string $key, array $defaultSchedule = []): array
    {
        // La llamada a OpcionManager se simplifica para coincidir con su firma real.
        // Los parámetros extra se eliminaron ya que OpcionManager no los utiliza.
        $schedule_data = OpcionManager::get($key, $defaultSchedule);

        if (!is_array($schedule_data)) {
            GloryLogger::error("SCHEDULE ERROR [$key]: Retrieved data is not an array. Value: " . print_r($schedule_data, true) . ". Falling back to default schedule parameter.");
            $schedule_data = $defaultSchedule;
        }

        if (empty($schedule_data) && !empty($defaultSchedule)) {
            GloryLogger::info("SCHEDULE INFO [$key]: Retrieved data is empty or invalid, using provided default schedule for normalization: " . print_r($defaultSchedule, true));
            $schedule_data = $defaultSchedule;
        }

        $normalized_schedule = [];
        if (is_array($schedule_data)) {
            foreach ($schedule_data as $entry) {
                if (!is_array($entry) || !isset($entry['day'])) {
                    GloryLogger::info("SCHEDULE INFO [$key]: Invalid or incomplete entry in schedule data. Entry: " . print_r($entry, true) . ". Skipping.");
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
                    $parts = explode('-', $entry['hours']);
                    if (count($parts) === 2) {
                        $n_open = trim($parts[0]);
                        $n_close = trim($parts[1]);
                        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $n_open) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $n_close)) {
                            $n_open = '';
                            $n_close = '';
                            $n_status = 'closed';
                            $n_hours = 'Cerrado';
                            GloryLogger::info("SCHEDULE INFO [$key]: Invalid time format in 'hours' field '{$entry['hours']}' for day '{$n_day}'. Setting to closed.");
                        } else {
                            $n_hours = $n_open . '-' . $n_close;
                        }
                    } else {
                        $n_status = 'closed';
                        $n_hours = 'Cerrado';
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

    public static function getCurrentScheduleStatus(string $schedule_key, array $default_schedule, string $timezone_string = 'Europe/Madrid'): array
    {
        $schedule = self::getScheduleData($schedule_key, $default_schedule);

        if (empty($schedule)) {
            return ['status_class' => 'closed', 'message' => 'Horario no disponible.'];
        }

        try {
            $timezone = new DateTimeZone($timezone_string);
            $now = new DateTime('now', $timezone);
        } catch (\Exception $e) {
            GloryLogger::error("getCurrentStatus [$schedule_key]: Invalid timezone string: '$timezone_string'. Error: " . $e->getMessage() . ". Falling back to UTC.");
            $timezone = new DateTimeZone('UTC');
            $now = new DateTime('now', $timezone);
        }

        $current_day_english = $now->format('l');
        $day_map_es_en = ['Lunes' => 'Monday', 'Martes' => 'Tuesday', 'Miércoles' => 'Wednesday', 'Jueves' => 'Thursday', 'Viernes' => 'Friday', 'Sábado' => 'Saturday', 'Domingo' => 'Sunday'];
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
        if ($today_schedule_entry && ($today_schedule_entry['status'] ?? 'closed') === 'open' && !empty($today_schedule_entry['open']) && !empty($today_schedule_entry['close'])) {
            try {
                $open_time_str = $today_schedule_entry['open'];
                $close_time_str = $today_schedule_entry['close'];

                $open_datetime = DateTime::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $open_time_str, $timezone);
                $close_datetime = DateTime::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $close_time_str, $timezone);

                if (!$open_datetime || !$close_datetime) {
                    GloryLogger::error("getCurrentStatus [$schedule_key]: Could not parse open/close times for today: '$open_time_str', '$close_time_str'. Date used: " . $now->format('Y-m-d'));
                } else {
                    if ($close_datetime < $open_datetime) {
                        $close_datetime->add(new DateInterval('P1D'));
                    }
                    if ($now >= $open_datetime && $now < $close_datetime) {
                        $is_open_now = true;
                    }
                }
            } catch (\Exception $e) {
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
        } else {
            $next_opening_day_info = '';
            $days_ordered = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
            $current_day_index = array_search($current_day_name_es, $days_ordered);

            if ($current_day_index !== false) {
                if ($today_schedule_entry && ($today_schedule_entry['status'] ?? 'closed') === 'open' && !empty($today_schedule_entry['open'])) {
                    try {
                        $open_datetime_today = DateTime::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $today_schedule_entry['open'], $timezone);
                        if ($open_datetime_today && $now < $open_datetime_today) {
                            $next_opening_day_info = 'hoy a las ' . esc_html($today_schedule_entry['open']);
                        }
                    } catch (\Exception $e) { /* Error already logged */
                    }
                }

                if (empty($next_opening_day_info)) {
                    for ($i = 1; $i <= 7; $i++) {
                        $next_day_idx = ($current_day_index + $i) % 7;
                        $next_day_name_candidate = $days_ordered[$next_day_idx];
                        foreach ($schedule as $s_entry) {
                            if (($s_entry['day'] ?? '') === $next_day_name_candidate && ($s_entry['status'] ?? 'closed') === 'open' && !empty($s_entry['open'])) {
                                $day_prefix = ($i === 1) ? 'mañana' : 'el ' . lcfirst($next_day_name_candidate);
                                $next_opening_day_info = $day_prefix . ' a las ' . esc_html($s_entry['open']);
                                goto found_next_opening_schedule_manager;
                            }
                        }
                    }
                    found_next_opening_schedule_manager:;
                }
            }
            $message = 'Cerrado.';
            if (!empty($next_opening_day_info)) {
                $message .= ' Abrimos ' . $next_opening_day_info . '.';
            }
            return ['status_class' => 'closed', 'message' => $message];
        }
    }
}