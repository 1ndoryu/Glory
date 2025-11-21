<?php
/**
 * Renderizador de Planificador (Scheduler)
 *
 * Componente visual para mostrar una cuadrícula de horarios y recursos (ej. barberos),
 * permitiendo visualizar eventos en una línea de tiempo.
 *
 * @package Glory\Components
 */

namespace Glory\Components;

use DateTime;
use DateInterval;
use DatePeriod;

/**
 * Clase SchedulerRenderer.
 *
 * Genera el markup base para el calendario de recursos.
 */
class SchedulerRenderer
{
    /** @var array Lista de eventos formateados. */
    private array $eventos;

    /** @var array Configuración del scheduler. */
    private array $config;

    /**
     * Constructor.
     *
     * @param array $eventos Lista de eventos.
     * @param array $config  Configuración.
     */
    public function __construct(array $eventos, array $config = [])
    {
        $this->eventos = $this->prepararEventos($eventos);
        $this->config  = $this->normalizarConfig($config);
    }

    /**
     * Renderiza el scheduler.
     *
     * @param array $eventos Lista de eventos crudos.
     * @param array $config  Configuración.
     */
    public static function render(array $eventos, array $config = []): void
    {
        $instancia = new self($eventos, $config);
        $instancia->renderizarCuadricula();
    }

    /**
     * Procesa los eventos para calcular duraciones y posiciones en minutos.
     *
     * @param array $eventos Eventos originales.
     * @return array Eventos procesados.
     */
    private function prepararEventos(array $eventos): array
    {
        $eventosPreparados = [];
        foreach ($eventos as $evento) {
            try {
                $inicio = new DateTime($evento['horaInicio']);
                $fin    = new DateTime($evento['horaFin']);
            } catch (\Exception $e) {
                continue; // Saltar evento con fecha inválida
            }
            $diferencia      = $fin->getTimestamp() - $inicio->getTimestamp();
            $duracionMinutos = $diferencia / 60;

            $evento['inicioMinutos']   = ((int)$inicio->format('H') * 60) + (int)$inicio->format('i');
            $evento['duracionMinutos'] = $duracionMinutos;
            $eventosPreparados[]       = $evento;
        }
        return $eventosPreparados;
    }

    /**
     * Normaliza la configuración con valores por defecto.
     *
     * @param array $config Configuración de entrada.
     * @return array Configuración completa.
     */
    private function normalizarConfig(array $config): array
    {
        return wp_parse_args($config, [
            'recursos'     => [], // ej. ['Barbero 1', 'Barbero 2']
            'horaInicio'   => '09:00',
            'horaFin'      => '21:00',
            'intervalo'    => 15, // en minutos
            'mapeoColores' => [],
        ]);
    }

    /**
     * Genera el HTML de la cuadrícula.
     */
    public function renderizarCuadricula(): void
    {
        $idContenedor = 'gloryScheduler-' . uniqid();
        $jsonEventos  = esc_attr((string)json_encode($this->eventos));
        $jsonConfig   = esc_attr((string)json_encode($this->config));

        echo "<div id='" . esc_attr($idContenedor) . "' class='glorySchedulerContenedor' data-eventos='" . $jsonEventos . "' data-config='" . $jsonConfig . "'>";
        $this->renderizarHtmlBase();
        echo "</div>";
    }

    /**
     * Genera la estructura HTML interna (encabezados, celdas de tiempo).
     */
    private function renderizarHtmlBase(): void
    {
        $recursos     = $this->config['recursos'];
        $columnasGrid = count($recursos) + 1;

        echo "<div class='glorySchedulerGrid' style='--columnas-grid: " . esc_attr($columnasGrid) . ";'>";

        // Encabezados de Recursos (Barberos)
        echo "<div class='celdaGrid celdaEncabezadoTiempo'>" . esc_html__('Hora', 'glory') . "</div>";
        foreach ($recursos as $recurso) {
            echo "<div class='celdaGrid celdaEncabezadoRecurso'>" . esc_html($recurso) . "</div>";
        }

        // Filas de Tiempo y Celdas vacías
        try {
            $inicio    = new DateTime($this->config['horaInicio']);
            $fin       = new DateTime($this->config['horaFin']);
            $intervalo = new DateInterval('PT' . (int)$this->config['intervalo'] . 'M');
            $periodo   = new DatePeriod($inicio, $intervalo, $fin);

            foreach ($periodo as $tiempo) {
                echo "<div class='celdaGrid celdaTiempo'>" . esc_html($tiempo->format('H:i')) . "</div>";
                for ($i = 0; $i < count($recursos); $i++) {
                    echo "<div class='celdaGrid celdaVacia'></div>";
                }
            }
        } catch (\Exception $e) {
            echo "<!-- Error generando grid de tiempo: " . esc_html($e->getMessage()) . " -->";
        }

        echo "</div>"; // Cierre de glorySchedulerGrid
        echo "<div class='capaEventos'></div>"; // Capa para renderizar eventos con JS
    }
}
