/**
 * Glory React Islands - Entry Point
 *
 * Este archivo es el punto de entrada para todas las islas React.
 * Solo se carga cuando hay islas registradas en la pagina (opcional).
 *
 * Sistema de renderizado:
 * 1. PHP renderiza el contenedor con data-island="NombreComponente"
 * 2. Este script busca esos contenedores y monta los componentes React
 * 3. Los props se pasan via data-props (JSON)
 */

import {StrictMode} from 'react';
import {createRoot} from 'react-dom/client';

// Importar estilos de Tailwind CSS
import './index.css';

// ===============================================
// COMPONENTES GLORY (Genericos, reutilizables)
// ===============================================
import {ExampleIsland} from './islands/ExampleIsland';

// ===============================================
// COMPONENTES APP (Especificos de este proyecto)
// Importados desde App/React/ via alias @app
// ===============================================
import {HomeIsland} from '@app/islands/HomeIsland';
import {ServicesIsland} from '@app/islands/ServicesIsland';
import {PricingIsland} from '@app/islands/PricingIsland';
import {DemosIsland} from '@app/islands/DemosIsland';
import {AboutIsland} from '@app/islands/AboutIsland';
import {MainAppIsland} from '@app/islands/MainAppIsland';

// Mapa de componentes disponibles
// La clave es el valor de data-island, el valor es el componente React
const islandComponents: Record<string, React.ComponentType<Record<string, unknown>>> = {
    // Componentes Glory (genericos)
    ExampleIsland: ExampleIsland,

    // Componentes App (especificos del proyecto)
    HomeIsland: HomeIsland,
    ServicesIsland: ServicesIsland,
    PricingIsland: PricingIsland,
    DemosIsland: DemosIsland,
    AboutIsland: AboutIsland,

    // SPA Router - Usar esto para navegacion sin recarga
    MainAppIsland: MainAppIsland

    // Agregar nuevas islas aqui
};

/**
 * Inicializa todas las islas React encontradas en el DOM
 * Busca elementos con data-island y monta el componente correspondiente
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

        try {
            // Limpiar el contenedor antes de montar (elimina placeholders/comentarios)
            container.innerHTML = '';

            // Usar createRoot para renderizar el componente
            const root = createRoot(container);
            root.render(
                <StrictMode>
                    <Component {...props} />
                </StrictMode>
            );

            console.log(`[Glory React] Isla "${islandName}" montada correctamente`);
        } catch (error) {
            console.error(`[Glory React] Error montando isla "${islandName}":`, error);
        }
    });
}

// Ejecutar cuando el DOM este listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeIslands);
} else {
    initializeIslands();
}
