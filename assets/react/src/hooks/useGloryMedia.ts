/*
 * Hook para acceder a imagenes via la REST API de Glory.
 * Usa el sistema de alias definidos en AssetsUtility de PHP.
 *
 * Uso:
 *   const { url, alt, isLoading } = useGloryMedia('logo');
 *   const { aliases } = useGloryMediaAliases();
 */

import { useWordPressApi } from './useWordPressApi';
import type { ImageUrlResponse, ImageAliasesResponse } from '../types/api';

interface UseGloryMediaResult {
    url: string | null;
    alt: string | null;
    isLoading: boolean;
    error: string | null;
}

/*
 * Obtiene la URL de una imagen por alias.
 */
export function useGloryMedia(alias: string): UseGloryMediaResult {
    const { data, isLoading, error } = useWordPressApi<ImageUrlResponse>(
        `/glory/v1/images/url?alias=${encodeURIComponent(alias)}`,
    );

    return {
        url: data?.url ?? null,
        alt: data?.alt ?? null,
        isLoading,
        error,
    };
}

/*
 * Obtiene todos los alias de imagenes disponibles.
 */
export function useGloryMediaAliases(): {
    aliases: Record<string, string>;
    isLoading: boolean;
    error: string | null;
} {
    const { data, isLoading, error } = useWordPressApi<ImageAliasesResponse>(
        '/glory/v1/images/aliases',
    );

    return {
        aliases: data?.aliases ?? {},
        isLoading,
        error,
    };
}
