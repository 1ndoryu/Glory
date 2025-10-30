<?php

namespace Glory\Gbn\Config;

use Glory\Components\ContentRender;

final class ContainerRegistry
{
    /**
     * @var array<string,array<string,mixed>>
     */
    private const BASE_DEFINITIONS = [
        'principal' => [
            'selector' => [
                'attribute' => 'gloryDiv',
                'dataAttribute' => 'data-gbnPrincipal',
            ],
            'defaults' => [
                'config' => [
                    'padding' => [
                        'superior' => null,
                        'derecha' => null,
                        'inferior' => null,
                        'izquierda' => null,
                    ],
                    'height' => 'auto',
                    'alineacion' => 'inherit',
                    'maxAncho' => null,
                    'fondo' => null,
                ],
                'schema' => [
                    [
                        'id' => 'height',
                        'tipo' => 'select',
                        'etiqueta' => 'Altura',
                        'opciones' => [
                            ['valor' => 'auto', 'etiqueta' => 'Automática'],
                            ['valor' => 'min-content', 'etiqueta' => 'Mínima'],
                            ['valor' => '100vh', 'etiqueta' => 'Altura completa'],
                        ],
                    ],
                    [
                        'id' => 'padding',
                        'tipo' => 'spacing',
                        'etiqueta' => 'Padding',
                        'unidades' => ['px', '%', 'rem'],
                        'paso' => 4,
                        'min' => 0,
                        'max' => 240,
                        'campos' => ['superior', 'derecha', 'inferior', 'izquierda'],
                    ],
                    [
                        'id' => 'alineacion',
                        'tipo' => 'select',
                        'etiqueta' => 'Alineación del contenido',
                        'opciones' => [
                            ['valor' => 'inherit', 'etiqueta' => 'Hereda'],
                            ['valor' => 'left', 'etiqueta' => 'Izquierda'],
                            ['valor' => 'center', 'etiqueta' => 'Centro'],
                            ['valor' => 'right', 'etiqueta' => 'Derecha'],
                        ],
                    ],
                    [
                        'id' => 'maxAncho',
                        'tipo' => 'slider',
                        'etiqueta' => 'Ancho máximo',
                        'unidad' => 'px',
                        'min' => 320,
                        'max' => 1600,
                        'paso' => 10,
                    ],
                    [
                        'id' => 'fondo',
                        'tipo' => 'color',
                        'etiqueta' => 'Color de fondo',
                        'permiteTransparencia' => true,
                    ],
                ],
            ],
        ],
        'secundario' => [
            'selector' => [
                'attribute' => 'gloryDivSecundario',
                'dataAttribute' => 'data-gbnSecundario',
            ],
            'defaults' => [
                'config' => [
                    'padding' => [
                        'superior' => null,
                        'derecha' => null,
                        'inferior' => null,
                        'izquierda' => null,
                    ],
                    'height' => 'auto',
                    'gap' => 20,
                    'layout' => 'block',
                    'flexDirection' => 'row',
                    'flexWrap' => 'nowrap',
                    'flexJustify' => 'flex-start',
                    'flexAlign' => 'stretch',
                    'gridColumns' => 3,
                    'gridRows' => 'auto',
                    'gridGap' => 20,
                ],
                'schema' => [
                    [
                        'id' => 'height',
                        'tipo' => 'select',
                        'etiqueta' => 'Altura',
                        'opciones' => [
                            ['valor' => 'auto', 'etiqueta' => 'Automática'],
                            ['valor' => 'min-content', 'etiqueta' => 'Mínima'],
                            ['valor' => '100vh', 'etiqueta' => 'Altura completa'],
                        ],
                    ],
                    [
                        'id' => 'layout',
                        'tipo' => 'select',
                        'etiqueta' => 'Layout',
                        'opciones' => [
                            ['valor' => 'block', 'etiqueta' => 'Bloques apilados'],
                            ['valor' => 'flex', 'etiqueta' => 'Flexbox'],
                            ['valor' => 'grid', 'etiqueta' => 'CSS Grid'],
                        ],
                    ],
                    [
                        'id' => 'padding',
                        'tipo' => 'spacing',
                        'etiqueta' => 'Padding interno',
                        'unidades' => ['px', '%', 'rem'],
                        'paso' => 4,
                        'min' => 0,
                        'max' => 160,
                        'campos' => ['superior', 'derecha', 'inferior', 'izquierda'],
                    ],
                    [
                        'id' => 'gap',
                        'tipo' => 'slider',
                        'etiqueta' => 'Separación entre elementos',
                        'unidad' => 'px',
                        'min' => 0,
                        'max' => 120,
                        'paso' => 2,
                    ],
                    [
                        'id' => 'flexDirection',
                        'tipo' => 'select',
                        'etiqueta' => 'Dirección Flex',
                        'opciones' => [
                            ['valor' => 'row', 'etiqueta' => 'Horizontal'],
                            ['valor' => 'column', 'etiqueta' => 'Vertical'],
                        ],
                        'condicion' => ['layout', 'flex'],
                    ],
                    [
                        'id' => 'flexWrap',
                        'tipo' => 'select',
                        'etiqueta' => 'Envoltura',
                        'opciones' => [
                            ['valor' => 'nowrap', 'etiqueta' => 'Sin envoltura'],
                            ['valor' => 'wrap', 'etiqueta' => 'Con envoltura'],
                        ],
                        'condicion' => ['layout', 'flex'],
                    ],
                    [
                        'id' => 'flexJustify',
                        'tipo' => 'select',
                        'etiqueta' => 'Justificación',
                        'opciones' => [
                            ['valor' => 'flex-start', 'etiqueta' => 'Inicio'],
                            ['valor' => 'center', 'etiqueta' => 'Centro'],
                            ['valor' => 'flex-end', 'etiqueta' => 'Fin'],
                            ['valor' => 'space-between', 'etiqueta' => 'Espacio entre'],
                            ['valor' => 'space-around', 'etiqueta' => 'Espacio alrededor'],
                        ],
                        'condicion' => ['layout', 'flex'],
                    ],
                    [
                        'id' => 'flexAlign',
                        'tipo' => 'select',
                        'etiqueta' => 'Alineación',
                        'opciones' => [
                            ['valor' => 'stretch', 'etiqueta' => 'Estirar'],
                            ['valor' => 'flex-start', 'etiqueta' => 'Inicio'],
                            ['valor' => 'center', 'etiqueta' => 'Centro'],
                            ['valor' => 'flex-end', 'etiqueta' => 'Fin'],
                        ],
                        'condicion' => ['layout', 'flex'],
                    ],
                    [
                        'id' => 'gridColumns',
                        'tipo' => 'slider',
                        'etiqueta' => 'Columnas Grid',
                        'unidad' => '',
                        'min' => 1,
                        'max' => 12,
                        'paso' => 1,
                        'condicion' => ['layout', 'grid'],
                    ],
                    [
                        'id' => 'gridGap',
                        'tipo' => 'slider',
                        'etiqueta' => 'Separación Grid',
                        'unidad' => 'px',
                        'min' => 0,
                        'max' => 120,
                        'paso' => 2,
                        'condicion' => ['layout', 'grid'],
                    ],
                ],
            ],
        ],
        'content' => [
            'selector' => [
                'attribute' => 'gloryContentRender',
                'dataAttribute' => 'data-gbnContent',
            ],
            'provider' => [ContentRender::class, 'gbnDefaults'],
        ],
    ];

    private static ?array $resolved = null;

    /**
     * @return array<string,array<string,mixed>>
     */
    private static function resolveAll(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $result = [];
        foreach (self::BASE_DEFINITIONS as $role => $definition) {
            $result[$role] = self::resolveSingle($role, $definition);
        }

        if (function_exists('apply_filters')) {
            /** @var array<string,array<string,mixed>> $result */
            $result = apply_filters('glory/gbn/containers/all', $result);
        }

        self::$resolved = $result;
        return self::$resolved;
    }

    /**
     * @param array<string,mixed> $definition
     * @return array<string,mixed>
     */
    private static function resolveSingle(string $role, array $definition): array
    {
        $defaults = self::resolveDefaults($definition);
        $payload = [
            'role' => $role,
            'selector' => $definition['selector'] ?? [],
            'config' => $defaults['config'],
            'schema' => $defaults['schema'],
        ];

        if (function_exists('apply_filters')) {
            /** @var array<string,mixed> $payload */
            $payload = apply_filters('glory/gbn/container/' . $role, $payload);
        }

        return $payload;
    }

    /**
     * @param array<string,mixed> $definition
     * @return array{config:array,schema:array}
     */
    private static function resolveDefaults(array $definition): array
    {
        $config = [];
        $schema = [];

        if (isset($definition['provider']) && is_callable($definition['provider'])) {
            $provided = call_user_func($definition['provider']);
            if (is_array($provided)) {
                $config = $provided['config'] ?? [];
                $schema = $provided['schema'] ?? [];
            }
        } else {
            $defaults = $definition['defaults'] ?? [];
            $config = $defaults['config'] ?? [];
            $schema = $defaults['schema'] ?? [];
        }

        return [
            'config' => is_array($config) ? $config : [],
            'schema' => is_array($schema) ? $schema : [],
        ];
    }

    public static function reset(): void
    {
        self::$resolved = null;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function all(): array
    {
        return self::resolveAll();
    }

    /**
     * @return array<string,mixed>
     */
    public static function get(string $role): array
    {
        $all = self::resolveAll();
        return $all[$role] ?? [];
    }

    /**
     * @return array<string,array<string,string>>
     */
    public static function selectors(): array
    {
        $selectors = [];
        foreach (self::resolveAll() as $role => $data) {
            if (!empty($data['selector']) && is_array($data['selector'])) {
                $selectors[$role] = $data['selector'];
            }
        }
        return $selectors;
    }

    /**
     * @return array<string,array{config:array,schema:array}>
     */
    public static function rolePayload(): array
    {
        $payload = [];
        foreach (self::resolveAll() as $role => $data) {
            $payload[$role] = [
                'config' => isset($data['config']) && is_array($data['config']) ? $data['config'] : [],
                'schema' => isset($data['schema']) && is_array($data['schema']) ? $data['schema'] : [],
            ];
        }
        return $payload;
    }
}


