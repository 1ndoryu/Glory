<?php

namespace Glory\Gbn\Config;

final class RoleConfig
{
    /**
     * @return array<string,array{config:array,schema:array}>
     */
    public static function all(): array
    {
        $roles = ContainerRegistry::rolePayload();
        if (function_exists('apply_filters')) {
            /** @var array<string,array{config:array,schema:array}> $roles */
            $roles = apply_filters('glory/gbn/roles/defaults', $roles);
        }
        return $roles;
    }

    /**
     * @return array{config:array,schema:array}
     */
    public static function get(string $role): array
    {
        $all = self::all();
        return $all[$role] ?? ['config' => [], 'schema' => []];
    }

    /**
     * @return array<mixed>
     */
    public static function config(string $role): array
    {
        $data = self::get($role);
        return $data['config'] ?? [];
    }

    /**
     * @return array<mixed>
     */
    public static function schema(string $role): array
    {
        $data = self::get($role);
        return $data['schema'] ?? [];
    }
}


