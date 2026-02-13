/**
 * Glory Page Builder - Block Registry
 *
 * Registro global de tipos de bloque disponibles.
 * Los proyectos registran sus bloques aquí para que el
 * PageBuilder sepa cómo renderizarlos.
 *
 * Uso desde App:
 * ```
 * import { BlockRegistry } from '@/pageBuilder';
 * import { HeroBlock, heroDefinition } from './blocks/HeroBlock';
 *
 * BlockRegistry.register(heroDefinition);
 * ```
 */

import type {BlockDefinition} from './types';

/**
 * Almacén interno de definiciones de bloque
 */
const registry: Map<string, BlockDefinition> = new Map();

/**
 * BlockRegistry - API pública
 */
export const BlockRegistry = {
    /**
     * Registra un nuevo tipo de bloque
     */
    register<T = Record<string, unknown>>(definition: BlockDefinition<T>): void {
        if (registry.has(definition.type)) {
            console.warn(`[BlockRegistry] Bloque "${definition.type}" ya registrado, sobrescribiendo.`);
        }
        registry.set(definition.type, definition as BlockDefinition);
    },

    /**
     * Registra múltiples bloques a la vez
     */
    registerAll(definitions: BlockDefinition<Record<string, unknown>>[]): void {
        definitions.forEach(def => this.register(def));
    },

    /**
     * Obtiene la definición de un tipo de bloque
     */
    get(type: string): BlockDefinition | undefined {
        return registry.get(type);
    },

    /**
     * Obtiene todas las definiciones registradas
     */
    getAll(): BlockDefinition[] {
        return Array.from(registry.values());
    },

    /**
     * Verifica si un tipo de bloque está registrado
     */
    has(type: string): boolean {
        return registry.has(type);
    },

    /**
     * Obtiene los props por defecto de un tipo de bloque
     */
    getDefaultProps(type: string): Record<string, unknown> {
        const definition = registry.get(type);
        return definition?.defaultProps || {};
    },

    /**
     * Obtiene el componente de un tipo de bloque
     */
    getComponent(type: string): BlockDefinition['component'] | undefined {
        const definition = registry.get(type);
        return definition?.component;
    },

    /**
     * Lista los tipos de bloque disponibles (para UI de agregar bloque)
     */
    getAvailableTypes(): Array<{type: string; label: string; icon?: string}> {
        return Array.from(registry.values()).map(def => ({
            type: def.type,
            label: def.label,
            icon: def.icon
        }));
    },

    /**
     * Limpia el registro (útil para testing)
     */
    clear(): void {
        registry.clear();
    }
};

export default BlockRegistry;
