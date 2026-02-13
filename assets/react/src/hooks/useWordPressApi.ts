/*
 * Hook para consumir la REST API de WordPress/Glory con tipado fuerte.
 * Maneja autenticacion (nonce), cache basico, y estados de carga/error.
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

/* Cache en memoria simple con TTL */
const apiCache = new Map<string, { data: unknown; timestamp: number }>();
const DEFAULT_CACHE_TTL = 30_000; /* 30 segundos */

function getCacheKey(endpoint: string, options?: ApiRequestOptions): string {
    const method = options?.method ?? 'GET';
    const body = options?.body ? JSON.stringify(options.body) : '';
    return `${method}:${endpoint}:${body}`;
}

function getNonce(): string {
    const context = window.GLORY_CONTEXT;
    if (context?.nonce) return context.nonce;

    /* Fallback: WP Core pone el nonce en wpApiSettings */
    const wpSettings = (window as unknown as Record<string, unknown>).wpApiSettings as
        | { nonce?: string }
        | undefined;
    return wpSettings?.nonce ?? '';
}

function getRestUrl(): string {
    const context = window.GLORY_CONTEXT;
    if (context?.restUrl) return context.restUrl;

    /* Fallback: WP Core */
    const wpSettings = (window as unknown as Record<string, unknown>).wpApiSettings as
        | { root?: string }
        | undefined;
    return wpSettings?.root ?? '/wp-json';
}

export function useWordPressApi<T = unknown>(
    endpoint: string,
    options?: ApiRequestOptions,
): UseWordPressApiResult<T> {
    const [data, setData] = useState<T | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const abortRef = useRef<AbortController | null>(null);

    const fetchData = useCallback(async () => {
        const method = options?.method ?? 'GET';
        const shouldCache = options?.cache !== false && method === 'GET';
        const cacheTtl = options?.cacheTtl ?? DEFAULT_CACHE_TTL;
        const cacheKey = getCacheKey(endpoint, options);

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
                ...options?.headers,
            };

            const nonce = getNonce();
            if (nonce) {
                headers['X-WP-Nonce'] = nonce;
            }

            const fetchOptions: RequestInit = {
                method,
                headers,
                signal: options?.signal ?? controller.signal,
            };

            if (options?.body && method !== 'GET') {
                fetchOptions.body = JSON.stringify(options.body);
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

            /* Actualizar cache */
            if (shouldCache) {
                apiCache.set(cacheKey, { data: result, timestamp: Date.now() });
            }
        } catch (err) {
            if (err instanceof DOMException && err.name === 'AbortError') return;
            const message = err instanceof Error ? err.message : 'Error de red desconocido';
            setError(message);
            setData(null);
        } finally {
            setIsLoading(false);
        }
    }, [endpoint, options?.method, options?.body, options?.cache, options?.cacheTtl, options?.signal, options?.headers]);

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
