/**
 * Glory React Islands - Entry Point
 *
 * Punto de entrada para el sistema de islas React.
 * Registra islas en el IslandRegistry y delega el montaje al modulo de hidratacion.
 *
 * Para agregar islas al proyecto ver: App/React/appIslands.tsx
 * NO modificar este archivo para agregar islas del proyecto.
 */

import './index.css';
import { islandRegistry } from './core';
import { initializeIslands } from './core/hydration';

/* Islas del proyecto (importadas desde App/React) */
import appIslands, { AppProvider } from '@app/appIslands';

function marcarEntornoNativo(): void {
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return;
    }

    const capacitor = window.Capacitor;
    const esCapacitorNativo = (() => {
        if (!capacitor) return false;

        try {
            return !!capacitor.isNativePlatform?.() || typeof capacitor.getPlatform?.() === 'string';
        } catch {
            return false;
        }
    })();

    if (!esCapacitorNativo) {
        return;
    }

    const plataforma = (() => {
        try {
            return capacitor?.getPlatform?.() ?? null;
        } catch {
            return null;
        }
    })();

    window.__KAMPLES_MOBILE__ = true;
    document.body.classList.add('plataformaCapacitor');

    if (plataforma === 'android' || /android/i.test(navigator.userAgent)) {
        document.body.classList.add('plataformaAndroid');
    }
}

/* Registrar islas Glory de ejemplo solo en desarrollo */
if (import.meta.env.DEV) {
    import('./islands/ExampleIsland').then(({ ExampleIsland }) => {
        islandRegistry.register('ExampleIsland', ExampleIsland);
    }).catch(() => {
        /* Isla de ejemplo no disponible — ignorar en desarrollo */
    });
}

/* Registrar islas del proyecto */
islandRegistry.registerAll(appIslands);

/*
 * Ejemplo de isla lazy (cargada bajo demanda):
 * islandRegistry.registerLazy('Pesada', () => import('./islands/PesadaIsland'));
 */

/* Iniciar cuando el DOM este listo */
function init(): void {
    marcarEntornoNativo();
    initializeIslands({ appProvider: AppProvider });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
