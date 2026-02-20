/*
 * Motor de hidratacion/montaje de islas React.
 * Extrae y mejora la logica que antes vivia en main.tsx.
 *
 * Soporta dos modos:
 *   A) Islas individuales: busca [data-island] y monta cada uno (legacy/fallback).
 *   B) SPA: si __GLORY_ROUTES__ existe, monta un PageRenderer unico
 *      que intercambia islas sin recarga al navegar.
 *
 * Flujo por isla (modo A):
 *   1. Busca contenedores [data-island] en el DOM
 *   2. Resuelve el componente via IslandRegistry
 *   3. Parsea props de data-props (JSON)
 *   4. Envuelve en: StrictMode > GloryProvider > AppProvider? > ErrorBoundary > Suspense? > DevOverlay?
 *   5. Monta con createRoot (CSR) o hydrateRoot (SSG)
 */

import { StrictMode, Suspense, type ComponentType, type ReactNode } from 'react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { islandRegistry } from './IslandRegistry';
import { IslandErrorBoundary } from './ErrorBoundary';
import { GloryProvider } from './GloryProvider';
import { DevOverlay } from './DevOverlay';
import { useNavigationStore } from './router/navigationStore';
import { PageRenderer } from './router/PageRenderer';
import type { GloryRoutesMap } from './router/navigationStore';

export interface InitOptions {
    appProvider?: ComponentType<{ children: ReactNode }>;
    suspenseFallback?: ReactNode;
}

const defaultSuspenseFallback = (
    <div style={{ padding: '12px', textAlign: 'center', color: '#9ca3af', fontSize: '14px' }}>
        Cargando...
    </div>
);

/*
 * Construye el arbol de wrappers alrededor de un elemento.
 * AppProvider es opcional y definido por el proyecto usuario en appIslands.tsx.
 */
function wrapWithProviders(
    element: JSX.Element,
    appProvider?: ComponentType<{ children: ReactNode }>,
): JSX.Element {
    const AppProv = appProvider;
    const wrapped = AppProv ? <AppProv>{element}</AppProv> : element;
    return <GloryProvider>{wrapped}</GloryProvider>;
}

/*
 * Monta una isla individual en su contenedor DOM.
 */
function mountIsland(
    container: HTMLElement,
    islandName: string,
    props: Record<string, unknown>,
    options: InitOptions,
): void {
    const resolved = islandRegistry.resolve(islandName);
    if (!resolved) {
        console.error(`[Glory] Componente "${islandName}" no registrado en IslandRegistry`);
        return;
    }

    const { component: Component, isLazy } = resolved;
    const shouldHydrate = container.dataset.hydrate === 'true';
    const hasContent =
        container.innerHTML.trim() !== '' &&
        !container.innerHTML.includes('<!-- react-island-loading -->');

    let islandContent: JSX.Element = <Component {...props} />;

    /* Lazy: envolver en Suspense para mostrar fallback mientras carga */
    if (isLazy) {
        islandContent = (
            <Suspense fallback={options.suspenseFallback ?? defaultSuspenseFallback}>
                {islandContent}
            </Suspense>
        );
    }

    /* DevOverlay solo en desarrollo */
    if (import.meta.env.DEV) {
        islandContent = (
            <DevOverlay islandName={islandName} props={props}>
                {islandContent}
            </DevOverlay>
        );
    }

    const element = (
        <StrictMode>
            {wrapWithProviders(
                <IslandErrorBoundary islandName={islandName}>
                    {islandContent}
                </IslandErrorBoundary>,
                options.appProvider,
            )}
        </StrictMode>
    );

    try {
        if (shouldHydrate && hasContent) {
            hydrateRoot(container, element);
            if (import.meta.env.DEV) {
                console.warn(`[Glory] Isla "${islandName}" hidratada (SSG)`);
            }
        } else {
            container.innerHTML = '';
            createRoot(container).render(element);
            if (import.meta.env.DEV) {
                console.warn(`[Glory] Isla "${islandName}" montada (CSR)`);
            }
        }
    } catch (error) {
        console.error(`[Glory] Error montando isla "${islandName}":`, error);

        /* Fallback: si la hidratacion falla, intentar CSR */
        if (shouldHydrate) {
            console.warn(`[Glory] Fallback a CSR para "${islandName}"`);
            try {
                container.innerHTML = '';
                createRoot(container).render(element);
            } catch (fallbackError) {
                console.error(`[Glory] Fallback CSR tambien fallo para "${islandName}":`, fallbackError);
            }
        }
    }
}

/*
 * Inicializa todas las islas React encontradas en el DOM.
 * Si __GLORY_ROUTES__ esta disponible, activa modo SPA con PageRenderer.
 * Si no, monta islas individualmente (modo clasico).
 */
export function initializeIslands(options: InitOptions = {}): void {
    const routes = window.__GLORY_ROUTES__ as GloryRoutesMap | undefined;

    /* Modo SPA: hay rutas definidas, montar PageRenderer en el contenedor principal */
    if (routes && Object.keys(routes).length > 0) {
        initializeSPA(routes, options);
        return;
    }

    /* Modo clasico: montar islas individuales */
    initializeClassicIslands(options);
}

/*
 * Modo SPA: monta un unico PageRenderer que intercambia islas segun la ruta.
 * El contenedor [data-island] existente se reutiliza como root.
 */
function initializeSPA(routes: GloryRoutesMap, options: InitOptions): void {
    /*
     * Los callable props de PHP se omiten de __GLORY_ROUTES__ por diseño,
     * pero SÍ se evalúan y serializan en data-props del contenedor DOM.
     * Leer esos props evaluados y mergarlos en la ruta actual del mapa,
     * para que la isla inicial reciba datos como el usuario autenticado.
     */
    const contenedorInicial = document.querySelector<HTMLElement>('[data-island]');
    let rutasConPropsEvaluados = routes;

    if (contenedorInicial?.dataset.props) {
        try {
            const propsServidor = JSON.parse(contenedorInicial.dataset.props) as Record<string, unknown>;
            /* Normalización básica del path para hacer match con el mapa de rutas */
            const pathActual = window.location.pathname;
            const pathNorm = pathActual === '/' ? '/' : (pathActual.endsWith('/') ? pathActual : pathActual + '/');

            if (rutasConPropsEvaluados[pathNorm]) {
                rutasConPropsEvaluados = {
                    ...rutasConPropsEvaluados,
                    [pathNorm]: {
                        ...rutasConPropsEvaluados[pathNorm],
                        /* Props del servidor tienen prioridad: incluyen datos de usuario evaluados en tiempo de render */
                        props: { ...rutasConPropsEvaluados[pathNorm].props, ...propsServidor },
                    },
                };
            }
        } catch {
            /* JSON inválido en data-props: continuar sin merge, las props quedan vacías */
        }
    }

    /* Inicializar store de navegacion con las rutas (props evaluados incluidos) */
    useNavigationStore.getState().inicializar(rutasConPropsEvaluados, window.location.pathname);

    if (import.meta.env.DEV) {
        const rutasStr = Object.keys(routes).join(', ');
        console.warn(`[Glory SPA] Modo SPA activo con ${Object.keys(routes).length} rutas: ${rutasStr}`);
    }

    /* Encontrar el contenedor principal [data-island] para montar el PageRenderer */
    const container = document.querySelector<HTMLElement>('[data-island]');
    if (!container) {
        if (import.meta.env.DEV) {
            console.error('[Glory SPA] No se encontro contenedor [data-island] para PageRenderer');
        }
        return;
    }

    const AppProv = options.appProvider;

    const element = (
        <StrictMode>
            <GloryProvider>
                {AppProv ? (
                    <AppProv>
                        <PageRenderer suspenseFallback={options.suspenseFallback} />
                    </AppProv>
                ) : (
                    <PageRenderer suspenseFallback={options.suspenseFallback} />
                )}
            </GloryProvider>
        </StrictMode>
    );

    container.innerHTML = '';
    createRoot(container).render(element);

    if (import.meta.env.DEV) {
        const { islaActual } = useNavigationStore.getState();
        console.warn(`[Glory SPA] PageRenderer montado, isla inicial: ${islaActual}`);
    }
}

/*
 * Modo clasico: monta cada isla individualmente en su contenedor DOM.
 */
function initializeClassicIslands(options: InitOptions): void {
    const islands = document.querySelectorAll<HTMLElement>('[data-island]');

    if (islands.length === 0) {
        if (import.meta.env.DEV) {
            console.warn('[Glory] No se encontraron islas para montar');
        }
        return;
    }

    if (import.meta.env.DEV) {
        console.warn(`[Glory] Montando ${islands.length} isla(s), registry: ${islandRegistry.getNames().join(', ')}`);
    }

    islands.forEach((container) => {
        const islandName = container.dataset.island;

        if (!islandName) {
            console.error('[Glory] Contenedor sin nombre de isla:', container);
            return;
        }

        let props: Record<string, unknown> = {};
        const propsJson = container.dataset.props;

        if (propsJson) {
            try {
                props = JSON.parse(propsJson) as Record<string, unknown>;
            } catch (err) {
                console.error(`[Glory] Error parseando props para "${islandName}":`, err);
            }
        }

        mountIsland(container, islandName, props, options);
    });
}
