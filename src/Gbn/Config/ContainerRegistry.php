<?php

namespace Glory\Gbn\Config;



final class ContainerRegistry
{
    /**
     * @var array<string,array<string,mixed>>
     */
    private const BASE_DEFINITIONS = [
        // Componentes migrados a src/Gbn/Components/
    ];

    private static array $dynamicDefinitions = [];

    /**
     * @var array<string,array<string,mixed>>|null
     */
    private static ?array $resolved = null;

    /**
     * Registra un nuevo componente o sobrescribe uno existente.
     * @param string $role
     * @param array $definition
     */
    public static function register(string $role, array $definition): void
    {
        self::$dynamicDefinitions[$role] = $definition;
        self::$resolved = null; // Reset cache
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private static function resolveAll(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $result = [];
        // Merge base and dynamic. Dynamic overwrites base.
        $allDefinitions = array_merge(self::BASE_DEFINITIONS, self::$dynamicDefinitions);

        foreach ($allDefinitions as $role => $definition) {
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
            'label' => $definition['label'] ?? $role,
            'icon' => $definition['icon'] ?? '',
            'template' => $definition['template'] ?? '',
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
                'label' => $data['label'] ?? $role,
                'icon' => $data['icon'] ?? '',
                'config' => isset($data['config']) && is_array($data['config']) ? $data['config'] : [],
                'schema' => isset($data['schema']) && is_array($data['schema']) ? $data['schema'] : [],
            ];
        }
        return $payload;
    }
}
