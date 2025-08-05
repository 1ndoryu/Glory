<?php
// Glory/src/Services/AnalyticsEngine.php

namespace Glory\Services;

use Exception;

class AnalyticsEngine
{
    private array $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function calcular(array $configuracionCalculos): array
    {
        $resultados = [];
        foreach ($configuracionCalculos as $claveResultado => $calculo) {
            try {
                preg_match('/(\w+)\((.+)\)/', $calculo, $matches);
                if (count($matches) !== 3) {
                    throw new Exception("Formato de cálculo inválido: {$calculo}");
                }

                $funcion = strtolower($matches[1]);
                $parametro = trim($matches[2]);
                $columna = ($parametro !== '*') ? $parametro : null;

                switch ($funcion) {
                    case 'sum':
                        $resultados[$claveResultado] = $this->sumar($columna);
                        break;
                    case 'count':
                        $resultados[$claveResultado] = $this->contar($columna);
                        break;
                    case 'avg':
                        $resultados[$claveResultado] = $this->promedio($columna);
                        break;
                    default:
                        throw new Exception("Función de cálculo desconocida: {$funcion}");
                }
            } catch (Exception $e) {
                $resultados[$claveResultado] = ['error' => $e->getMessage()];
            }
        }
        return $resultados;
    }

    private function extraerColumna(?string $clave): array
    {
        if ($clave === null) {
            return $this->datos;
        }

        if (empty($this->datos)) {
            return [];
        }

        // Verifica si los datos son objetos o arrays asociativos
        $esObjeto = is_object($this->datos[0]);

        if ($esObjeto && !property_exists($this->datos[0], $clave)) {
            throw new Exception("La columna '{$clave}' no existe en los objetos de datos.");
        }
        if (!$esObjeto && !array_key_exists($clave, $this->datos[0])) {
            throw new Exception("La clave '{$clave}' no existe en los arrays de datos.");
        }

        return array_column($this->datos, $esObjeto ? null : $clave, $esObjeto ? $clave : null);
    }

    private function sumar(?string $columna): float
    {
        if ($columna === null) {
            throw new Exception("La función SUM requiere el nombre de una columna.");
        }
        $valores = $this->extraerColumna($columna);
        return array_sum($valores);
    }

    private function contar(?string $columna): int
    {
        if ($columna === null) {
            return count($this->datos);
        }
        $valores = $this->extraerColumna($columna);
        return count(array_filter($valores)); // Cuenta solo valores no vacíos
    }

    private function promedio(?string $columna): float
    {
        if ($columna === null) {
            throw new Exception("La función AVG requiere el nombre de una columna.");
        }
        $valores = $this->extraerColumna($columna);
        $conteo = count($valores);
        return $conteo > 0 ? array_sum($valores) / $conteo : 0;
    }
}