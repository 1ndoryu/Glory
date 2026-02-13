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

interface UseWordPressApiResult<T> {
    data: T | null;
    isLoading: boolean;
    error: string | null;
    refetch: () => void;
}

/*
 * Cache en memoria con TTL y limite maximo de entries.
 * Evita crecimiento ilimitado limpiando entries expiradas periodicamente.
 */
const apiCache = new Map<string, { data: unknown; timestamp: number }>();
const DEFAULT_CACHE_TTL = 30_000; /* 30 segundos */
const MAX_CACHE_ENTRIES = 100;

/* Limpia entries expiradas del cache para evitar memory leak */
function limpiarCacheExpirado(ttl: number): void {
    if (apiCache.size <= MAX_CACHE_ENTRIES) return;
    const ahora = Date.now();
    for (const [key, entry] of apiCache) {
        if (ahora - entry.timestamp > ttl) {
            apiCache.delete(key);
        }
    }
    /* Si sigue excediendo, eliminar las mas antiguas */
    if (apiCache.size > MAX_CACHE_ENTRIES) {
        const entries = [...apiCache.entries()].sort((a, b) => a[1].timestamp - b[1].timestamp);
        const sobran = entries.length - MAX_CACHE_ENTRIES;
        for (let i = 0; i < sobran; i++) {
            apiCache.delete(entries[i][0]);
        }
    }
}

function getCacheKey(endpoint: string, options?: ApiRequestOptions): string {
    const method = options?.method ?? 'GET';
    const body = options?.body ? JSON.stringify(options.body) : '';
    return `${method}:${endpoint}:${body}`;
}

/* Lectura de nonce y restUrl cacheadas como singleton. Solo se leen una vez de window. */
let cachedNonce: string | null = null;
let cachedRestUrl: string | null = null;

function getNonce(): string {
    if (cachedNonce !== null) return cachedNonce;

    const context = window.GLORY_CONTEXT;
    if (context?.nonce) {
        cachedNonce = context.nonce;
        return cachedNonce;
    }

    /* Fallback: WP Core pone el nonce en wpApiSettings */
    const wpSettings = (window as unknown as Record<string, unknown>).wpApiSettings as
        | { nonce?: string }
        | undefined;
    cachedNonce = wpSettings?.nonce ?? '';
    return cachedNonce;
}

function getRestUrl(): string {
    if (cachedRestUrl !== null) return cachedRestUrl;

    const context = window.GLORY_CONTEXT;
    if (context?.restUrl) {
        cachedRestUrl = context.restUrl;
        return cachedRestUrl;
    }

    /* Fallback: WP Core */
    const wpSettings = (window as unknown as Record<string, unknown>).wpApiSettings as
        | { root?: string }
        | undefined;
    cachedRestUrl = wpSettings?.root ?? '/wp-json';
    return cachedRestUrl;
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

/*
 * Limpia toda la cache de la API.
 * Util al invalidar datos (ej: despues de un POST).
 */
export function clearApiCache(): void {
    apiCache.clear();
}

/*
 * Invalida una entrada especifica de la cache.
 */
export function invalidateApiCache(endpoint: string, options?: ApiRequestOptions): void {
    const key = getCacheKey(endpoint, options);
    apiCache.delete(key);
}

/*
 * Resetea los singletons de nonce/restUrl.
 * Util si el nonce se renueva sin recargar pagina.
 */
export function resetApiCredentials(): void {
    cachedNonce = null;
    cachedRestUrl = null;
}
