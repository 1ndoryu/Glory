/*
 * Motor de hidratacion/montaje de islas React.
 * Extrae y mejora la logica que antes vivia en main.tsx.
 *
 * Flujo por isla:
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
                console.log(`[Glory] Isla "${islandName}" hidratada (SSG)`);
            }
        } else {
            container.innerHTML = '';
            createRoot(container).render(element);
            if (import.meta.env.DEV) {
                console.log(`[Glory] Isla "${islandName}" montada (CSR)`);
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
 * Llamar despues de registrar islas en IslandRegistry.
 */
export function initializeIslands(options: InitOptions = {}): void {
    const islands = document.querySelectorAll<HTMLElement>('[data-island]');

    if (islands.length === 0) {
        if (import.meta.env.DEV) {
            console.warn('[Glory] No se encontraron islas para montar');
        }
        return;
    }

    if (import.meta.env.DEV) {
        console.log(`[Glory] Montando ${islands.length} isla(s), registry: ${islandRegistry.getNames().join(', ')}`);
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
