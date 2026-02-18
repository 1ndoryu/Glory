<?php

/**
 * Contrato base para definir schemas de Custom Post Types de WordPress.
 *
 * Cada post type que use meta fields DEBE tener una clase que extienda PostTypeSchema.
 * Los meta fields declarados aquí generan getters tipados y tipos TypeScript.
 *
 * @package Glory\Contracts
 */

namespace Glory\Contracts;

abstract class PostTypeSchema
{
    /**
     * Nombre del post type en WordPress.
     */
    abstract public function postType(): string;

    /**
     * Definición de meta fields.
     *
     * Cada entrada debe tener la estructura:
     * 'meta_key' => [
     *     'tipo'     => 'int'|'string'|'text'|'float'|'bool'|'array'|'json',
     *     'required' => true|false (default: false),
     *     'default'  => valor por defecto (opcional),
     *     'unico'    => true|false (default: false),
     *     'max'      => longitud máxima (opcional),
     * ]
     *
     * @return array<string, array<string, mixed>>
     */
    abstract public function meta(): array;

    /**
     * Definición de taxonomías asociadas.
     *
     * 'nombre_taxonomia' => [
     *     'jerarquica' => true|false,
     * ]
     *
     * @return array<string, array<string, mixed>>
     */
    public function taxonomias(): array
    {
        return [];
    }

    /**
     * Obtiene la lista de meta keys registradas.
     *
     * @return string[]
     */
    public function nombreMetas(): array
    {
        return array_keys($this->meta());
    }

    /**
     * Verifica si un meta key existe en el schema.
     */
    public function metaExiste(string $key): bool
    {
        return array_key_exists($key, $this->meta());
    }

    /**
     * Obtiene la definición de un meta field.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerMeta(string $key): ?array
    {
        return $this->meta()[$key] ?? null;
    }

    /**
     * Obtiene el tipo PHP equivalente de un meta field.
     */
    public function tipoPHPMeta(string $key): ?string
    {
        $metaInfo = $this->obtenerMeta($key);
        if ($metaInfo === null) {
            return null;
        }

        $mapa = [
            'int'    => 'int',
            'float'  => 'float',
            'string' => 'string',
            'text'   => 'string',
            'bool'   => 'bool',
            'array'  => 'array',
            'json'   => 'array',
        ];

        return $mapa[$metaInfo['tipo']] ?? 'mixed';
    }

    /**
     * Obtiene el tipo TypeScript equivalente de un meta field.
     */
    public function tipoTSMeta(string $key): ?string
    {
        $metaInfo = $this->obtenerMeta($key);
        if ($metaInfo === null) {
            return null;
        }

        $mapa = [
            'int'    => 'number',
            'float'  => 'number',
            'string' => 'string',
            'text'   => 'string',
            'bool'   => 'boolean',
            'array'  => 'unknown[]',
            'json'   => 'Record<string, unknown>',
        ];

        return $mapa[$metaInfo['tipo']] ?? 'unknown';
    }

    /**
     * Valida que un array de meta keys contenga solo claves registradas.
     *
     * @return string[]
     */
    public function validarClaves(array $datos): array
    {
        $metasValidas = $this->nombreMetas();
        $clavesInvalidas = [];

        foreach (array_keys($datos) as $clave) {
            if (!in_array($clave, $metasValidas, true)) {
                $clavesInvalidas[] = $clave;
            }
        }

        return $clavesInvalidas;
    }
}
