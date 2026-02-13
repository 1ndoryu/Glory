import { useContext } from 'react';
import { GloryReactContext } from './gloryContext';
import type { GloryProviderValue } from './gloryContext';

/*
 * Hook interno para acceder al valor del provider.
 * Retorna null si el componente no esta dentro de un GloryProvider.
 */
export function useGloryProvider(): GloryProviderValue | null {
    return useContext(GloryReactContext);
}
