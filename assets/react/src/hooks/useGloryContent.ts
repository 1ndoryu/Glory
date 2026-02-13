/*
 * Hook para acceder al contenido inyectado por ReactContentProvider.
 * Lee window.__GLORY_CONTENT__ con tipo seguro.
 *
 * Uso: const posts = useGloryContent<WPPost>('blog');
 */

import { useState, useEffect } from 'react';
import type { WPPost } from '../types/wordpress';
import type { GloryContentMap } from '../types/glory';

interface UseGloryContentResult<T extends WPPost> {
    data: T[];
    isLoading: boolean;
    error: string | null;
}

export function useGloryContent<T extends WPPost = WPPost>(
    key: string,
): UseGloryContentResult<T> {
    const [data, setData] = useState<T[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        try {
            const content: GloryContentMap | undefined = window.__GLORY_CONTENT__;

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

            const items = content[key] as T[];

            if (!Array.isArray(items)) {
                setError(`El contenido de "${key}" no es un array`);
                setData([]);
                setIsLoading(false);
                return;
            }

            setData(items);
            setError(null);
        } catch (err) {
            const message = err instanceof Error ? err.message : 'Error desconocido al leer Glory content';
            setError(message);
            setData([]);
        } finally {
            setIsLoading(false);
        }
    }, [key]);

    return { data, isLoading, error };
}
