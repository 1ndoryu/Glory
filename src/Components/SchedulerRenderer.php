<?php
// Glory/src/Components/SchedulerRenderer.php

namespace Glory\Components;

use DateTime;

class SchedulerRenderer
{
    private array $eventos;
    private array $config;

    public function __construct(array $eventos, array $config = [])
    {
        $this->eventos = $this->prepararEventos($eventos);
        $this->config = $this->normalizarConfig($config);
    }

    public static function render(array $eventos, array $config = []): void
    {
        $instancia = new self($eventos, $config);
        $instancia->renderizarCuadricula();
    }

    private function prepararEventos(array $eventos): array
    {
        $eventosPreparados = [];
        foreach ($eventos as $evento) {
            $inicio = new DateTime($evento['horaInicio']);
            $fin = new DateTime($evento['horaFin']);
            $diferencia = $fin->getTimestamp() - $inicio->getTimestamp();
            $duracionMinutos = $diferencia / 60;

            $evento['inicioMinutos'] = ($inicio->format('H') * 60) + $inicio->format('i');
            $evento['duracionMinutos'] = $duracionMinutos;
            $eventosPreparados[] = $evento;
        }
        return $eventosPreparados;
    }

    private function normalizarConfig(array $config): array
    {
        return wp_parse_args($config, [
            'recursos' => [], // ej. ['Barbero 1', 'Barbero 2']
            'horaInicio' => '09:00',
            'horaFin' => '21:00',
            'intervalo' => 15, // en minutos
            'mapeoColores' => []
        ]);
    }

    public function renderizarCuadricula(): void
    {
        $idContenedor = 'gloryScheduler-' . uniqid();
        $jsonEventos = esc_attr(json_encode($this->eventos));
        $jsonConfig = esc_attr(json_encode($this->config));

        echo "<div id='{$idContenedor}' class='glorySchedulerContenedor' data-eventos='{$jsonEventos}' data-config='{$jsonConfig}'>";
        $this->renderizarHtmlBase();
        echo "</div>";
    }

    private function renderizarHtmlBase(): void
    {
        $recursos = $this->config['recursos'];
        $columnasGrid = count($recursos) + 1;

        echo "<div class='glorySchedulerGrid' style='--columnas-grid: {$columnasGrid};'>";

        // Encabezados de Recursos (Barberos)
        echo "<div class='celdaGrid celdaEncabezadoTiempo'>Hora</div>";
        foreach ($recursos as $recurso) {
            echo "<div class='celdaGrid celdaEncabezadoRecurso'>" . esc_html($recurso) . "</div>";
        }

        // Filas de Tiempo y Celdas vacías
        $inicio = new DateTime($this->config['horaInicio']);
        $fin = new DateTime($this->config['horaFin']);
        $intervalo = new \DateInterval('PT' . $this->config['intervalo'] . 'M');

        $periodo = new \DatePeriod($inicio, $intervalo, $fin);

        foreach ($periodo as $tiempo) {
            echo "<div class='celdaGrid celdaTiempo'>" . $tiempo->format('H:i') . "</div>";
            for ($i = 0; $i < count($recursos); $i++) {
                echo "<div class='celdaGrid celdaVacia'></div>";
            }
        }

        echo "</div>"; // Cierre de glorySchedulerGrid
        echo "<div class='capaEventos'></div>"; // Capa para renderizar eventos con JS
    }
}
