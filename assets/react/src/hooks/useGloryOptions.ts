/*
 * Hook para acceder a las opciones del tema Glory.
 * Lee de GLORY_CONTEXT.options (inyectadas por PHP) con fallback de provider.
 *
 * Uso:
 *   const { options, get } = useGloryOptions();
 *   const color = get('colorPrimario', '#3b82f6');
 */

import { useMemo } from 'react';
import { useGloryProvider } from '../core/useGloryProvider';

interface UseGloryOptionsResult<T> {
    options: T;
    get: <V = unknown>(key: string, defaultValue?: V) => V;
    has: (key: string) => boolean;
}

export function useGloryOptions<
    T extends Record<string, unknown> = Record<string, unknown>,
>(): UseGloryOptionsResult<T> {
    const provider = useGloryProvider();

    return useMemo(() => {
        const ctx = provider?.context ?? window.GLORY_CONTEXT;
        const raw = ((ctx?.options as T) ?? {}) as T;

        function get<V = unknown>(key: string, defaultValue?: V): V {
            const value = (raw as Record<string, unknown>)[key];
            if (value === undefined) return defaultValue as V;
            return value as V;
        }

        function has(key: string): boolean {
            return key in (raw as Record<string, unknown>);
        }

        return { options: raw, get, has };
    }, [provider]);
}
