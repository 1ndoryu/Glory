/*
 * GloryProvider — Context global del framework.
 * Centraliza los datos inyectados por PHP (contexto, contenido, opciones).
 * Envuelve automaticamente cada isla en hydration.ts.
 *
 * Los hooks useGloryContext y useGloryContent leen de este provider
 * cuando esta disponible, con fallback a window globals.
 */

import { useMemo, type ReactNode } from 'react';
import { GloryReactContext, getGloryProviderValue } from './gloryContext';

export function GloryProvider({ children }: { children: ReactNode }): JSX.Element {
    const value = useMemo(() => getGloryProviderValue(), []);

    return <GloryReactContext.Provider value={value}>{children}</GloryReactContext.Provider>;
}
