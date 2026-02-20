/*
 * Hook para consumir la REST API de WordPress/Glory con tipado fuerte.
 * Maneja autenticacion (nonce), cache basico, y estados de carga/error.
 *
 * IMPORTANTE: Si se pasa `options`, el consumidor DEBE memoizarlo con useMemo
 * o definirlo fuera del componente. De lo contrario se creara un objeto nuevo
 * en cada render y el hook re-fetcheara en bucle infinito.
 *
 * Uso:
 * const { data, isLoading, error, refetch } = useWordPressApi<ImageListResponse>('/glory/v1/images');
 */

import { useState, useEffect, useCallback, useRef } from 'react';
import type { GloryApiResponse, ApiRequestOptions } from '../types/api';
import {
    apiCache, DEFAULT_CACHE_TTL, getCacheKey,
    limpiarCacheExpirado
} from '../utils/apiCache';
import { getNonce, getRestUrl } from '../utils/wpCredentials';

/* Re-exports para compatibilidad con consumidores existentes */
export { clearApiCache, invalidateApiCache } from '../utils/apiCache';
export { resetApiCredentials } from '../utils/wpCredentials';

interface UseWordPressApiResult<T> {
    data: T | null;
    isLoading: boolean;
    error: string | null;
    refetch: () => void;
}

export function useWordPressApi<T = unknown>(
    endpoint: string,
    options?: ApiRequestOptions,
): UseWordPressApiResult<T> {
    const [data, setData] = useState<T | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const abortRef = useRef<AbortController | null>(null);

    /*
     * Serializar options a string para estabilizar la referencia en useCallback.
     * Esto evita el bucle infinito: si el consumidor pasa un objeto literal como
     * options sin memoizarlo, el JSON string no cambia y useCallback no se recrea.
     */
    const optionsKey = options ? JSON.stringify(options) : '';
    const optionsRef = useRef(options);
    optionsRef.current = options;

    const fetchData = useCallback(async () => {
        const opts = optionsRef.current;
        const method = opts?.method ?? 'GET';
        const shouldCache = opts?.cache !== false && method === 'GET';
        const cacheTtl = opts?.cacheTtl ?? DEFAULT_CACHE_TTL;
        const cacheKey = getCacheKey(endpoint, opts);

        /* Stale-while-revalidate: devuelve cache si es valido */
        if (shouldCache) {
            const cached = apiCache.get(cacheKey);
            if (cached && Date.now() - cached.timestamp < cacheTtl) {
                setData(cached.data as T);
                setIsLoading(false);
                setError(null);
                return;
            }
        }

        /* Cancelar peticion anterior */
        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        setIsLoading(true);
        setError(null);

        try {
            const restUrl = getRestUrl();
            const url = endpoint.startsWith('http')
                ? endpoint
                : `${restUrl.replace(/\/$/, '')}/${endpoint.replace(/^\//, '')}`;

            const headers: Record<string, string> = {
                'Content-Type': 'application/json',
                ...opts?.headers,
            };

            const nonce = getNonce();
            if (nonce) {
                headers['X-WP-Nonce'] = nonce;
            }

            const fetchOptions: RequestInit = {
                method,
                headers,
                signal: opts?.signal ?? controller.signal,
            };

            if (opts?.body && method !== 'GET') {
                fetchOptions.body = JSON.stringify(opts.body);
            }

            const response = await fetch(url, fetchOptions);

            if (!response.ok) {
                const errorBody = (await response.json().catch(() => null)) as GloryApiResponse | null;
                throw new Error(
                    errorBody?.message ?? errorBody?.error ?? `HTTP ${response.status}: ${response.statusText}`,
                );
            }

            const result = (await response.json()) as T;
            setData(result);
            setError(null);

            /* Actualizar cache y limpiar entries expiradas */
            if (shouldCache) {
                apiCache.set(cacheKey, { data: result, timestamp: Date.now() });
                limpiarCacheExpirado(cacheTtl);
            }
        } catch (err) {
            if (err instanceof DOMException && err.name === 'AbortError') return;
            const message = err instanceof Error ? err.message : 'Error de red desconocido';
            setError(message);
            setData(null);
        } finally {
            setIsLoading(false);
        }
    /* eslint-disable-next-line react-hooks/exhaustive-deps -- optionsKey serializa options para estabilizar deps */
    }, [endpoint, optionsKey]);

    useEffect(() => {
        fetchData();
        return () => abortRef.current?.abort();
    }, [fetchData]);

    return { data, isLoading, error, refetch: fetchData };
}
