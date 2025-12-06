<?php

namespace Glory\Gbn\Schema;

/**
 * Handles mapping between legacy field names and canonical ones.
 * Ensures backward compatibility for components migrating to new naming conventions.
 */
class FieldAliasMapper
{
    /**
     * Map of Legacy Name => Canonical Name
     */
    protected const ALIASES = SchemaConstants::LEGACY_KEY_MAP;

    /**
     * Returns the canonical name for a given field key.
     * If no alias exists, returns the original key.
     * 
     * @param string $key
     * @return string
     */
    public static function getCanonical(string $key): string
    {
        return self::ALIASES[$key] ?? $key;
    }

    /**
     * Returns all aliases for JS consumption.
     * 
     * @return array
     */
    public static function getAll(): array
    {
        return self::ALIASES;
    }

    /**
     * Migrates a configuration array to use canonical names.
     * Preserves legacy keys? Ideally no, we want to migrate.
     * 
     * @param array $config
     * @return array
     */
    public static function migrateConfig(array $config): array
    {
        $newConfig = $config;
        foreach (self::ALIASES as $legacy => $canonical) {
            if (isset($newConfig[$legacy]) && !isset($newConfig[$canonical])) {
                $newConfig[$canonical] = $newConfig[$legacy];
                // Optionally unset legacy? 
                // unset($newConfig[$legacy]); 
                // Better keep it for a while or remove it to enforce migration?
                // Plan says "Compatibilidad hacia atrás", usually implies reading both.
                // But for clean state, we should write canonical.
            }
        }
        return $newConfig;
    }
}
