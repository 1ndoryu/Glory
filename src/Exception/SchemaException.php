<?php

/**
 * Excepción para errores de schema: columnas inexistentes, claves inválidas, etc.
 *
 * @package Glory\Exception
 */

namespace Glory\Exception;

class SchemaException extends \RuntimeException
{
    private string $tabla;
    private string $columna;

    public function __construct(string $mensaje, string $tabla = '', string $columna = '')
    {
        $this->tabla = $tabla;
        $this->columna = $columna;
        parent::__construct($mensaje);
    }

    public function getTabla(): string
    {
        return $this->tabla;
    }

    public function getColumna(): string
    {
        return $this->columna;
    }
}
