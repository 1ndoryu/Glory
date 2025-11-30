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
                    'layout' => 'flex',
                    'flexDirection' => 'row',
                    'flexWrap' => 'wrap',
                    'flexJustify' => 'flex-start',
                    'flexAlign' => 'stretch',
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
                    [
                        'id' => 'gap',
                        'tipo' => 'slider',
                        'etiqueta' => 'Separación (Gap)',
                        'unidad' => 'px',
                        'min' => 0,
                        'max' => 120,
                        'paso' => 2,
                        'condicion' => ['layout', 'flex'],
                    ],
                    [
                        'id' => 'layout',
                        'tipo' => 'icon_group',
                        'etiqueta' => 'Layout',
                        'opciones' => [
                            ['valor' => 'block', 'etiqueta' => 'Bloque', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>'],
                            ['valor' => 'flex', 'etiqueta' => 'Flexbox', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 3v18"/></svg>'],
                            ['valor' => 'grid', 'etiqueta' => 'Grid', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>'],
                        ],
                    ],
                    [
                        'id' => 'flexDirection',
                        'tipo' => 'icon_group',
                        'etiqueta' => 'Dirección',
                        'opciones' => [
                            ['valor' => 'row', 'etiqueta' => 'Horizontal', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><path d="M16 8l4 4-4 4"/></svg>'],
                            ['valor' => 'column', 'etiqueta' => 'Vertical', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16"/><path d="M8 16l4 4 4-4"/></svg>'],
                        ],
                        'condicion' => ['layout', 'flex'],
                    ],
                    [
                        'id' => 'flexWrap',
                        'tipo' => 'icon_group',
                        'etiqueta' => 'Envoltura',
                        'opciones' => [
                            ['valor' => 'nowrap', 'etiqueta' => 'No envolver', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/></svg>'],
                            ['valor' => 'wrap', 'etiqueta' => 'Envolver', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 8h10a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4"/><path d="M8 12l-4 4 4 4"/></svg>'],
                        ],
                        'condicion' => ['layout', 'flex'],
                    ],
                    [
                        'id' => 'flexJustify',
                        'tipo' => 'icon_group',
                        'etiqueta' => 'Justificación',
                        'opciones' => [
                            ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="6" height="18" rx="1"/></svg>'],
                            ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="3" width="6" height="18" rx="1"/></svg>'],
                            ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="15" y="3" width="6" height="18" rx="1"/></svg>'],
                            ['valor' => 'space-between', 'etiqueta' => 'Espacio entre', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="4" height="18" rx="1"/><rect x="17" y="3" width="4" height="18" rx="1"/></svg>'],
                            ['valor' => 'space-around', 'etiqueta' => 'Espacio alrededor', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="3" width="4" height="18" rx="1"/><rect x="15" y="3" width="4" height="18" rx="1"/></svg>'],
                        ],
                        'condicion' => ['layout', 'flex'],
                    ],
                    [
                        'id' => 'flexAlign',
                        'tipo' => 'icon_group',
                        'etiqueta' => 'Alineación',
                        'opciones' => [
                            ['valor' => 'stretch', 'etiqueta' => 'Estirar', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3v18"/><path d="M20 3v18"/><rect x="8" y="6" width="8" height="12" rx="1"/></svg>'],
                            ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3h16"/><rect x="8" y="7" width="8" height="8" rx="1"/></svg>'],
                            ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>'],
                            ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21h16"/><rect x="8" y="9" width="8" height="8" rx="1"/></svg>'],
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
                    'width' => '1/1',
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
                        'id' => 'width',
                        'tipo' => 'fraction',
                        'etiqueta' => 'Ancho',
                        'opciones' => [
                            ['valor' => '1/1', 'etiqueta' => '1/1'],
                            ['valor' => '5/6', 'etiqueta' => '5/6'],
                            ['valor' => '4/5', 'etiqueta' => '4/5'],
                            ['valor' => '3/4', 'etiqueta' => '3/4'],
                            ['valor' => '2/3', 'etiqueta' => '2/3'],
                            ['valor' => '3/5', 'etiqueta' => '3/5'],
                            ['valor' => '1/2', 'etiqueta' => '1/2'],
                            ['valor' => '2/5', 'etiqueta' => '2/5'],
                            ['valor' => '1/3', 'etiqueta' => '1/3'],
                            ['valor' => '1/4', 'etiqueta' => '1/4'],
                            ['valor' => '1/5', 'etiqueta' => '1/5'],
                            ['valor' => '1/6', 'etiqueta' => '1/6'],
                        ],
                    ],
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
                        'tipo' => 'icon_group',
                        'etiqueta' => 'Layout',
                        'opciones' => [
                            ['valor' => 'block', 'etiqueta' => 'Bloque', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>'],
                            ['valor' => 'flex', 'etiqueta' => 'Flexbox', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 3v18"/></svg>'],
                            ['valor' => 'grid', 'etiqueta' => 'Grid', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>'],
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
                        'tipo' => 'icon_group',
                        'etiqueta' => 'Dirección Flex',
                        'opciones' => [
                            ['valor' => 'row', 'etiqueta' => 'Horizontal', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><path d="M16 8l4 4-4 4"/></svg>'],
                            ['valor' => 'column', 'etiqueta' => 'Vertical', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16"/><path d="M8 16l4 4 4-4"/></svg>'],
                        ],
                        'condicion' => ['layout', 'flex'],
                    ],
                    [
                        'id' => 'flexWrap',
                        'tipo' => 'icon_group',
                        'etiqueta' => 'Envoltura',
                        'opciones' => [
                            ['valor' => 'nowrap', 'etiqueta' => 'Sin envoltura', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/></svg>'],
                            ['valor' => 'wrap', 'etiqueta' => 'Con envoltura', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 8h10a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4"/><path d="M8 12l-4 4 4 4"/></svg>'],
                        ],
                        'condicion' => ['layout', 'flex'],
                    ],
                    [
                        'id' => 'flexJustify',
                        'tipo' => 'icon_group',
                        'etiqueta' => 'Justificación',
                        'opciones' => [
                            ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="6" height="18" rx="1"/></svg>'],
                            ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="3" width="6" height="18" rx="1"/></svg>'],
                            ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="15" y="3" width="6" height="18" rx="1"/></svg>'],
                            ['valor' => 'space-between', 'etiqueta' => 'Espacio entre', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="4" height="18" rx="1"/><rect x="17" y="3" width="4" height="18" rx="1"/></svg>'],
                            ['valor' => 'space-around', 'etiqueta' => 'Espacio alrededor', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="3" width="4" height="18" rx="1"/><rect x="15" y="3" width="4" height="18" rx="1"/></svg>'],
                        ],
                        'condicion' => ['layout', 'flex'],
                    ],
                    [
                        'id' => 'flexAlign',
                        'tipo' => 'icon_group',
                        'etiqueta' => 'Alineación',
                        'opciones' => [
                            ['valor' => 'stretch', 'etiqueta' => 'Estirar', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3v18"/><path d="M20 3v18"/><rect x="8" y="6" width="8" height="12" rx="1"/></svg>'],
                            ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3h16"/><rect x="8" y="7" width="8" height="8" rx="1"/></svg>'],
                            ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>'],
                            ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21h16"/><rect x="8" y="9" width="8" height="8" rx="1"/></svg>'],
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


