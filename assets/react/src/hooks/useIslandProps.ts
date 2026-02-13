/*
 * Hook para obtener las props tipadas de la isla actual.
 * Util dentro de un componente de isla para acceder a sus props con tipado fuerte.
 *
 * Uso:
 * interface MiIslaProps { titulo: string; items: Item[]; }
 * const props = useIslandProps<MiIslaProps>();
 */

import { useMemo } from 'react';

export function useIslandProps<T extends Record<string, unknown> = Record<string, unknown>>(
    rawProps: Record<string, unknown>,
): T {
    return useMemo(() => rawProps as T, [rawProps]);
}
