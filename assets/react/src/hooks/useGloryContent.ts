/*
 * Hook para acceder al contenido inyectado por ReactContentProvider.
 * Lee del GloryProvider si esta disponible, fallback a window.__GLORY_CONTENT__.
 * Incluye validacion runtime basica de los items.
 *
 * Uso: const { data, isLoading, error } = useGloryContent<WPPost>('blog');
 */

import { useState, useEffect } from 'react';
import type { WPPost } from '../types/wordpress';
import type { GloryContentMap } from '../types/glory';
import { useGloryProvider } from '../core/useGloryProvider';

interface UseGloryContentResult<T extends WPPost> {
    data: T[];
    isLoading: boolean;
    error: string | null;
}

export function useGloryContent<T extends WPPost = WPPost>(
    key: string,
): UseGloryContentResult<T> {
    const provider = useGloryProvider();
    const [data, setData] = useState<T[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        try {
            const content: GloryContentMap | undefined =
                provider?.content ?? window.__GLORY_CONTENT__;

            if (!content) {
                setError('Glory content no disponible: window.__GLORY_CONTENT__ no existe');
                setData([]);
                setIsLoading(false);
                return;
            }

            if (!(key in content)) {
                setError(`Clave "${key}" no encontrada en Glory content`);
                setData([]);
                setIsLoading(false);
                return;
            }

            const items = content[key];

            if (!Array.isArray(items)) {
                setError(`El contenido de "${key}" no es un array`);
                setData([]);
                setIsLoading(false);
                return;
            }

            /* Validacion runtime: comprobar campos minimos de WPPost */
            const validItems = items.filter((item): item is T => {
                if (typeof item !== 'object' || item === null) return false;
                if (typeof item.id !== 'number') return false;
                if (typeof item.slug !== 'string') return false;
                return true;
            });

            if (import.meta.env.DEV && validItems.length !== items.length) {
                console.warn(
                    `[Glory] ${items.length - validItems.length} items de "${key}" no pasaron validacion runtime`,
                );
            }

            setData(validItems);
            setError(null);
        } catch (err) {
            const message =
                err instanceof Error ? err.message : 'Error desconocido al leer Glory content';
            setError(message);
            setData([]);
        } finally {
            setIsLoading(false);
        }
    }, [key, provider]);

    return { data, isLoading, error };
}
