/*
 * Hook para acceder al contexto global de Glory.
 * Lee window.GLORY_CONTEXT con tipo seguro.
 *
 * Uso: const { siteUrl, nonce, isAdmin } = useGloryContext();
 */

import { useMemo } from 'react';
import type { GloryContext } from '../types/glory';

const defaultContext: GloryContext = {
    siteUrl: '',
    themeUrl: '',
    restUrl: '/wp-json',
    nonce: '',
    isAdmin: false,
    locale: 'es',
};

export function useGloryContext(): GloryContext {
    return useMemo(() => {
        const ctx = window.GLORY_CONTEXT;
        if (!ctx) {
            console.warn('Glory: window.GLORY_CONTEXT no disponible, usando valores por defecto');
            return defaultContext;
        }
        return { ...defaultContext, ...ctx };
    }, []);
}
