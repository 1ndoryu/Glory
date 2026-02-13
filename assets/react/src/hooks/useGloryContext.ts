/*
 * Hook para acceder al contexto global de Glory.
 * Lee del GloryProvider si esta disponible, fallback a window.GLORY_CONTEXT.
 *
 * Uso: const { siteUrl, nonce, isAdmin } = useGloryContext();
 */

import { useMemo } from 'react';
import type { GloryContext } from '../types/glory';
import { useGloryProvider } from '../core/GloryProvider';

const defaultContext: GloryContext = {
    siteUrl: '',
    themeUrl: '',
    restUrl: '/wp-json',
    nonce: '',
    isAdmin: false,
    locale: 'es',
};

export function useGloryContext(): GloryContext {
    const provider = useGloryProvider();

    return useMemo(() => {
        /* Si hay GloryProvider, usar datos centralizados */
        if (provider) return provider.context;

        /* Fallback: leer directo de window (compatibilidad sin provider) */
        const ctx = window.GLORY_CONTEXT;
        if (!ctx) {
            console.warn('Glory: window.GLORY_CONTEXT no disponible, usando valores por defecto');
            return defaultContext;
        }
        return { ...defaultContext, ...ctx };
    }, [provider]);
}
