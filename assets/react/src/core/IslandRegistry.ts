/*
 * Registry tipado de islas React.
 * Soporta registro directo e imports dinamicos (lazy loading).
 *
 * Uso:
 *   islandRegistry.register('MiIsla', MiIsla);
 *   islandRegistry.registerLazy('Pesada', () => import('./islands/Pesada'));
 */

import { lazy, type ComponentType } from 'react';

export type IslandComponent = ComponentType<Record<string, unknown>>;
export type IslandLoader = () => Promise<{ default: IslandComponent }>;

interface RegistryEntry {
    component?: IslandComponent;
    loader?: IslandLoader;
    lazyComponent?: IslandComponent;
}

export interface ResolvedIsland {
    component: IslandComponent;
    isLazy: boolean;
}

class IslandRegistryManager {
    private entries = new Map<string, RegistryEntry>();

    /*
     * Registra una isla con import estatico (cargado inmediatamente).
     */
    register(name: string, component: IslandComponent): void {
        if (this.entries.has(name) && import.meta.env.DEV) {
            console.warn(`[Glory Registry] Sobrescribiendo isla "${name}"`);
        }
        this.entries.set(name, { component });
    }

    /*
     * Registra una isla con import dinamico (lazy loading).
     * El componente se carga solo cuando la isla aparece en el DOM.
     */
    registerLazy(name: string, loader: IslandLoader): void {
        const lazyComponent = lazy(loader);
        this.entries.set(name, { loader, lazyComponent });
    }

    /*
     * Registra multiples islas de un mapa (ej: appIslands).
     */
    registerAll(map: Record<string, IslandComponent>): void {
        for (const [name, component] of Object.entries(map)) {
            this.register(name, component);
        }
    }

    /*
     * Resuelve una isla por nombre. Retorna null si no existe.
     */
    resolve(name: string): ResolvedIsland | null {
        const entry = this.entries.get(name);
        if (!entry) return null;

        if (entry.component) {
            return { component: entry.component, isLazy: false };
        }

        if (entry.lazyComponent) {
            return { component: entry.lazyComponent, isLazy: true };
        }

        return null;
    }

    has(name: string): boolean {
        return this.entries.has(name);
    }

    getNames(): string[] {
        return Array.from(this.entries.keys());
    }

    get size(): number {
        return this.entries.size;
    }

    clear(): void {
        this.entries.clear();
    }
}

/* Singleton global del registry */
export const islandRegistry = new IslandRegistryManager();
