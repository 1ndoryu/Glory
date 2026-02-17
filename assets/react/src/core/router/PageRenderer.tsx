/*
 * PageRenderer — Renderiza la isla correcta segun la ruta SPA actual.
 * Observa el navigationStore y mantiene islas visitadas montadas (keep-alive).
 *
 * C133: Las páginas visitadas se mantienen en DOM con display:none para
 * preservar estado local (scroll, datos cargados, refs). Solo la isla
 * activa tiene display:block. Máximo MAX_CACHE_PAGES islas en cache.
 *
 * C167: Refactorizado para cumplir con React Compiler — sin setState
 * dentro de useEffect. Usa patrón "adjusting state when a prop changes"
 * recomendado por React docs.
 */

import { useEffect, useRef, useState, Suspense, type ReactNode } from 'react';
import { useNavigationStore } from './navigationStore';
import { islandRegistry } from '../IslandRegistry';
import { IslandErrorBoundary } from '../ErrorBoundary';

interface PageRendererProps {
    suspenseFallback?: ReactNode;
}

/* Máximo de páginas cacheadas en DOM simultáneamente */
const MAX_CACHE_PAGES = 5;

interface PaginaCacheada {
    islaId: string;
    props: Record<string, unknown>;
    orden: number;
}

/* Contador module-level para ordenar páginas por antigüedad (evicción LRU) */
let contadorOrden = 0;

const defaultFallback = (
    <div style={{ padding: '12px', textAlign: 'center', color: '#9ca3af', fontSize: '14px' }}>
        Cargando...
    </div>
);

/*
 * Renderiza TODAS las islas visitadas recientemente (keep-alive).
 * Solo la isla activa es visible (display:block), el resto permanece
 * oculto en DOM (display:none) preservando su estado React completo.
 */
export function PageRenderer({ suspenseFallback }: PageRendererProps): JSX.Element {
    const { islaActual, propsActuales, navegando, finalizarNavegacion } = useNavigationStore();
    const [paginasCache, setPaginasCache] = useState<PaginaCacheada[]>([]);
    const [islaAnterior, setIslaAnterior] = useState<string | null>(null);
    const islaActualRef = useRef(islaActual);

    /*
     * Actualizar cache durante render (NO en effect).
     * Patrón: https://react.dev/learn/you-might-not-need-an-effect#adjusting-some-state-when-a-prop-changes
     * Cuando islaActual cambia, React re-renderiza inmediatamente con el estado actualizado.
     */
    if (islaActual && islaActual !== islaAnterior) {
        setIslaAnterior(islaActual);
        islaActualRef.current = islaActual;
        contadorOrden++;

        const existe = paginasCache.find(p => p.islaId === islaActual);

        if (existe) {
            /* Actualizar props y orden */
            setPaginasCache(paginasCache.map(p =>
                p.islaId === islaActual
                    ? { ...p, props: propsActuales ?? {}, orden: contadorOrden }
                    : p
            ));
        } else {
            /* Agregar nueva isla */
            const nueva: PaginaCacheada = {
                islaId: islaActual,
                props: propsActuales ?? {},
                orden: contadorOrden,
            };

            const nuevaLista = [...paginasCache, nueva];

            if (nuevaLista.length > MAX_CACHE_PAGES) {
                /* Descartar la mas antigua que NO sea la activa */
                const ordenadas = nuevaLista
                    .filter(p => p.islaId !== islaActual)
                    .sort((a, b) => a.orden - b.orden);
                const aDescartar = ordenadas[0];
                setPaginasCache(nuevaLista.filter(p => p.islaId !== aDescartar.islaId));
            } else {
                setPaginasCache(nuevaLista);
            }
        }
    }

    /* Notificar fin de la transicion despues de render */
    useEffect(() => {
        if (navegando) {
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

    return (
        <>
            {paginasCache.map(pagina => {
                const resolved = islandRegistry.resolve(pagina.islaId);
                if (!resolved) return null;

                const { component: Component, isLazy } = resolved;
                const esActiva = pagina.islaId === islaActual;

                let contenido: JSX.Element = <Component {...pagina.props} />;

                if (isLazy) {
                    contenido = (
                        <Suspense fallback={suspenseFallback ?? defaultFallback}>
                            {contenido}
                        </Suspense>
                    );
                }

                return (
                    <div
                        key={pagina.islaId}
                        data-glory-page={pagina.islaId}
                        style={{ display: esActiva ? 'block' : 'none' }}
                    >
                        <IslandErrorBoundary islandName={pagina.islaId}>
                            {contenido}
                        </IslandErrorBoundary>
                    </div>
                );
            })}
        </>
    );
}
