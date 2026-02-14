/*
 * PageRenderer — Renderiza la isla correcta segun la ruta SPA actual.
 * Observa el navigationStore y monta/desmonta islas al navegar.
 *
 * Se monta como root en modo SPA (reemplaza el montaje individual de islas).
 * Transiciones suaves entre islas usando CSS transitions.
 */

import { useEffect, useRef, Suspense, type ReactNode } from 'react';
import { useNavigationStore } from './navigationStore';
import { islandRegistry } from '../IslandRegistry';
import { IslandErrorBoundary } from '../ErrorBoundary';

interface PageRendererProps {
    suspenseFallback?: ReactNode;
}

const defaultFallback = (
    <div style={{ padding: '12px', textAlign: 'center', color: '#9ca3af', fontSize: '14px' }}>
        Cargando...
    </div>
);

/*
 * Renderiza la isla correspondiente a la ruta actual.
 * Maneja transiciones y limpieza al cambiar de isla.
 */
export function PageRenderer({ suspenseFallback }: PageRendererProps): JSX.Element {
    const { islaActual, propsActuales, navegando, finalizarNavegacion } = useNavigationStore();
    const contenedorRef = useRef<HTMLDivElement>(null);

    /* Notificar fin de la transicion despues de render */
    useEffect(() => {
        if (navegando) {
            /* Micro-delay para permitir re-render antes de marcar como completo */
            const timer = requestAnimationFrame(() => {
                finalizarNavegacion();
            });
            return () => cancelAnimationFrame(timer);
        }
    }, [navegando, islaActual, finalizarNavegacion]);

    if (!islaActual) {
        return (
            <div style={{ padding: '40px', textAlign: 'center' }}>
                <h1>Pagina no encontrada</h1>
            </div>
        );
    }

    const resolved = islandRegistry.resolve(islaActual);
    if (!resolved) {
        if (import.meta.env.DEV) {
            console.error(`[Glory Router] Isla "${islaActual}" no encontrada en el registry`);
        }
        return (
            <div style={{ padding: '40px', textAlign: 'center' }}>
                <h1>Isla no registrada: {islaActual}</h1>
            </div>
        );
    }

    const { component: Component, isLazy } = resolved;

    let contenido: JSX.Element = <Component {...propsActuales} />;

    if (isLazy) {
        contenido = (
            <Suspense fallback={suspenseFallback ?? defaultFallback}>
                {contenido}
            </Suspense>
        );
    }

    return (
        <div ref={contenedorRef} data-glory-page={islaActual}>
            <IslandErrorBoundary islandName={islaActual}>
                {contenido}
            </IslandErrorBoundary>
        </div>
    );
}
