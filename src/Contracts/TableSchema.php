<?php

/**
 * Contrato base para definir schemas de tablas custom (PostgreSQL, MySQL, etc).
 *
 * Cada tabla de la aplicación DEBE tener una clase que extienda TableSchema.
 * El Schema es la unica fuente de verdad para columnas, tipos y constraints.
 * Los archivos generados (Cols, DTO, TS) se derivan de esta declaración.
 *
 * @package Glory\Contracts
 */

namespace Glory\Contracts;

abstract class TableSchema
{
    /**
     * Nombre de la tabla en la base de datos.
     * Se define en la subclase.
     */
    abstract public function tabla(): string;

    /**
     * Definición de columnas.
     *
     * Cada entrada debe tener la estructura:
     * 'nombre_columna' => [
     *     'tipo'     => 'int'|'string'|'text'|'float'|'decimal'|'bool'|'datetime'|'json'|'array'|'vector',
     *     'pk'       => true|false (default: false),
     *     'nullable' => true|false (default: false),
     *     'default'  => valor por defecto (opcional),
     *     'unico'    => true|false (default: false),
     *     'max'      => longitud máxima para strings (opcional),
     *     'ref'      => 'tabla_referenciada(columna)' (opcional, FK),
     *     'check'    => ['valor1', 'valor2'] (opcional, CHECK constraint),
     * ]
     *
     * @return array<string, array<string, mixed>>
     */
    abstract public function columnas(): array;

    /**
     * Claves primarias compuestas (si aplica).
     * Retorna array de nombres de columna que forman la PK compuesta.
     * Si la tabla usa PK simple (SERIAL), retornar array vacío.
     *
     * @return string[]
     */
    public function pkCompuesta(): array
    {
        return [];
    }

    /**
     * Constraints UNIQUE compuestos (si aplica).
     * Retorna array de arrays de nombres de columna.
     *
     * @return array<string[]>
     */
    public function uniqueCompuestos(): array
    {
        return [];
    }

    /**
     * Obtiene la lista de nombres de columna.
     *
     * @return string[]
     */
    public function nombreColumnas(): array
    {
        return array_keys($this->columnas());
    }

    /**
     * Verifica si una columna existe en el schema.
     */
    public function columnaExiste(string $nombre): bool
    {
        return array_key_exists($nombre, $this->columnas());
    }

    /**
     * Obtiene la definición de una columna.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerColumna(string $nombre): ?array
    {
        return $this->columnas()[$nombre] ?? null;
    }

    /**
     * Obtiene el tipo PHP equivalente de una columna.
     */
    public function tipoPHP(string $nombre): ?string
    {
        $col = $this->obtenerColumna($nombre);
        if ($col === null) {
            return null;
        }

        $mapa = [
            'int'      => 'int',
            'float'    => 'float',
            'decimal'  => 'float',
            'string'   => 'string',
            'text'     => 'string',
            'bool'     => 'bool',
            'datetime' => 'string',
            'json'     => 'array',
            'array'    => 'array',
            'vector'   => 'string',
        ];

        return $mapa[$col['tipo']] ?? 'mixed';
    }

    /**
     * Obtiene el tipo TypeScript equivalente de una columna.
     */
    public function tipoTS(string $nombre): ?string
    {
        $col = $this->obtenerColumna($nombre);
        if ($col === null) {
            return null;
        }

        $mapa = [
            'int'      => 'number',
            'float'    => 'number',
            'decimal'  => 'number',
            'string'   => 'string',
            'text'     => 'string',
            'bool'     => 'boolean',
            'datetime' => 'string',
            'json'     => 'Record<string, unknown>',
            'array'    => 'string[]',
            'vector'   => 'number[]',
        ];

        return $mapa[$col['tipo']] ?? 'unknown';
    }

    /**
     * Valida que un array de datos contenga solo claves válidas del schema.
     * Retorna las claves inválidas encontradas.
     *
     * @return string[]
     */
    public function validarClaves(array $datos): array
    {
        $columnasValidas = $this->nombreColumnas();
        $clavesInvalidas = [];

        foreach (array_keys($datos) as $clave) {
            if (!in_array($clave, $columnasValidas, true)) {
                $clavesInvalidas[] = $clave;
            }
        }

        return $clavesInvalidas;
    }
}
