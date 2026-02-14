/*
 * Hook publico para navegacion SPA entre islas Glory.
 *
 * Uso:
 *   const { navegar, volverAtras, rutaActual, navegando } = useNavigation();
 *   navegar('/servicios/');
 */

import { useCallback } from 'react';
import { useNavigationStore } from '../core/router/navigationStore';

export interface UseNavigationReturn {
    /* Navega a una ruta sin recarga (si esta en el mapa SPA) */
    navegar: (ruta: string) => void;
    /* Vuelve atras en el historial */
    volverAtras: () => void;
    /* Ruta actual activa */
    rutaActual: string;
    /* Si hay una navegacion en progreso */
    navegando: boolean;
    /* Si el modo SPA esta activo */
    modoSPA: boolean;
    /* Isla actual renderizada */
    islaActual: string | null;
    /* Verifica si una ruta es la activa */
    esRutaActiva: (ruta: string) => boolean;
}

export function useNavigation(): UseNavigationReturn {
    const store = useNavigationStore();

    const esRutaActiva = useCallback(
        (ruta: string): boolean => {
            const normalizada = ruta.endsWith('/') || ruta === '/'
                ? ruta
                : ruta + '/';
            return store.rutaActual === normalizada;
        },
        [store.rutaActual],
    );

    return {
        navegar: store.navegar,
        volverAtras: store.volverAtras,
        rutaActual: store.rutaActual,
        navegando: store.navegando,
        modoSPA: store.modoSPA,
        islaActual: store.islaActual,
        esRutaActiva,
    };
}
