/**
 * Glory React Islands - Entry Point
 *
 * Este archivo es el punto de entrada para todas las islas React.
 * Solo se carga cuando hay islas registradas en la pagina (opcional).
 *
 * Sistema de renderizado:
 * 1. PHP renderiza el contenedor con data-island="NombreComponente"
 * 2. Si existe HTML pre-renderizado (SSG), se usa data-hydrate="true"
 * 3. Este script busca esos contenedores y monta/hidrata los componentes React
 * 4. Los props se pasan via data-props (JSON)
 *
 * Modos de montaje:
 * - hydrateRoot: Cuando data-hydrate="true" (preserva HTML existente)
 * - createRoot: Cuando no hay SSG (reemplaza contenido)
 */

import {StrictMode} from 'react';
import {createRoot, hydrateRoot} from 'react-dom/client';

// Importar estilos de Tailwind CSS
import './index.css';

/*
 * COMPONENTES GLORY (Genericos, reutilizables)
 * Estos componentes vienen con Glory y sirven como ejemplos.
 * NO agregar componentes especificos del proyecto aqui.
 */
import {ExampleIsland} from './islands/ExampleIsland';

/*
 * COMPONENTES APP (Especificos del proyecto)
 *
 * IMPORTANTE: Glory debe mantenerse AGNOSTICO del proyecto.
 * Los componentes del proyecto se importan desde App/React/appIslands.tsx
 *
 * Para agregar nuevas islas al proyecto:
 * 1. Crear componente en App/React/islands/NombreIsla.tsx
 * 2. Registrar en App/React/appIslands.tsx
 * 3. Crear funcion PHP en App/Templates/pages/nombre-isla.php
 * 4. Definir pagina en App/Config/pages.php
 *
 * NO modificar este archivo para agregar islas del proyecto.
 */
import appIslands, {AppProvider} from '@app/appIslands';

/*
 * Mapa de componentes disponibles
 * Combina islas de Glory (ejemplos) con islas del proyecto (App)
 */
const islandComponents: Record<string, React.ComponentType<Record<string, unknown>>> = {
    // Componentes Glory (genericos - NO MODIFICAR)
    ExampleIsland: ExampleIsland,

    // Componentes del proyecto (importados desde App/React/appIslands.tsx)
    ...appIslands
};

/*
 * Envuelve un elemento con el AppProvider si está definido
 */
function wrapWithProvider(element: JSX.Element): JSX.Element {
    if (AppProvider) {
        return <AppProvider>{element}</AppProvider>;
    }
    return element;
}

/**
 * Inicializa todas las islas React encontradas en el DOM
 * Busca elementos con data-island y monta/hidrata el componente correspondiente
 */
function initializeIslands(): void {
    const islands = document.querySelectorAll<HTMLElement>('[data-island]');

    if (islands.length === 0) {
        console.warn('[Glory React] No se encontraron islas para hidratar');
        return;
    }

    islands.forEach(container => {
        const islandName = container.dataset.island;

        if (!islandName) {
            console.error('[Glory React] Contenedor sin nombre de isla:', container);
            return;
        }

        const Component = islandComponents[islandName];

        if (!Component) {
            console.error(`[Glory React] Componente "${islandName}" no registrado en islandComponents`);
            return;
        }

        // Obtener props del atributo data-props
        let props: Record<string, unknown> = {};
        const propsJson = container.dataset.props;

        if (propsJson) {
            try {
                props = JSON.parse(propsJson);
            } catch (error) {
                console.error(`[Glory React] Error parseando props para "${islandName}":`, error);
            }
        }

        // Determinar si hay contenido SSG para hidratar
        const shouldHydrate = container.dataset.hydrate === 'true';
        const hasContent = container.innerHTML.trim() !== '' && !container.innerHTML.includes('<!-- react-island-loading -->');

        try {
            const element = <StrictMode>{wrapWithProvider(<Component {...props} />)}</StrictMode>;

            if (shouldHydrate && hasContent) {
                /*
                 * Modo SSG: Hidratar preservando el HTML existente
                 * React se "adhiere" al DOM sin recrearlo
                 */
                hydrateRoot(container, element);
                console.log(`[Glory React] Isla "${islandName}" hidratada (SSG)`);
            } else {
                /*
                 * Modo CSR: Crear raiz nueva y renderizar
                 * Limpia el contenedor y monta React desde cero
                 */
                container.innerHTML = '';
                const root = createRoot(container);
                root.render(element);
                console.log(`[Glory React] Isla "${islandName}" montada (CSR)`);
            }
        } catch (error) {
            console.error(`[Glory React] Error montando isla "${islandName}":`, error);

            /*
             * Fallback: Si la hidratacion falla, intentar CSR
             * Esto puede pasar si el HTML SSG no coincide con el componente actual
             */
            if (shouldHydrate) {
                console.warn(`[Glory React] Fallback a CSR para "${islandName}"`);
                try {
                    container.innerHTML = '';
                    const root = createRoot(container);
                    root.render(<StrictMode>{wrapWithProvider(<Component {...props} />)}</StrictMode>);
                } catch (fallbackError) {
                    console.error(`[Glory React] Fallback CSR tambien fallo:`, fallbackError);
                }
            }
        }
    });
}

// Ejecutar cuando el DOM este listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeIslands);
} else {
    initializeIslands();
}
