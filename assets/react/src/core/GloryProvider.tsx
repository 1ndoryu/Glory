/*
 * GloryProvider — Context global del framework.
 * Centraliza los datos inyectados por PHP (contexto, contenido, opciones).
 * Envuelve automaticamente cada isla en hydration.ts.
 *
 * Los hooks useGloryContext y useGloryContent leen de este provider
 * cuando esta disponible, con fallback a window globals.
 */

import { createContext, useContext, useMemo, type ReactNode } from 'react';
import type { GloryContext, GloryContentMap } from '../types/glory';

const defaultContext: GloryContext = {
    siteUrl: '',
    themeUrl: '',
    restUrl: '/wp-json',
    nonce: '',
    isAdmin: false,
    locale: 'es',
};

export interface GloryProviderValue {
    context: GloryContext;
    content: GloryContentMap;
}

const GloryReactContext = createContext<GloryProviderValue | null>(null);

export function GloryProvider({ children }: { children: ReactNode }): JSX.Element {
    const value = useMemo<GloryProviderValue>(
        () => ({
            context: { ...defaultContext, ...window.GLORY_CONTEXT },
            content: window.__GLORY_CONTENT__ ?? {},
        }),
        [],
    );

    return <GloryReactContext.Provider value={value}>{children}</GloryReactContext.Provider>;
}

/*
 * Hook interno para acceder al valor del provider.
 * Retorna null si el componente no esta dentro de un GloryProvider.
 * Los hooks publicos usan esto para leer del provider con fallback a window.
 */
export function useGloryProvider(): GloryProviderValue | null {
    return useContext(GloryReactContext);
}
