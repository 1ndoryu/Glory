/*
 * Store Zustand para navegacion SPA entre islas React.
 * Gestiona la ruta actual, transiciones y el historial de navegacion.
 *
 * El mapa de rutas se inyecta desde PHP via window.__GLORY_ROUTES__.
 * Cada ruta mapea un path a un island + props.
 */

import { create } from 'zustand';

export interface GloryRoute {
    island: string;
    props: Record<string, unknown>;
    title: string;
}

export type GloryRoutesMap = Record<string, GloryRoute>;

export interface NavigationState {
    /* Ruta actual activa */
    rutaActual: string;
    /* Isla activa actual */
    islaActual: string | null;
    /* Props de la isla actual */
    propsActuales: Record<string, unknown>;
    /* Titulo de la pagina actual */
    tituloActual: string;
    /* Si hay una navegacion en progreso */
    navegando: boolean;
    /* Mapa de rutas disponibles (inyectado por PHP) */
    rutas: GloryRoutesMap;
    /* Si el modo SPA esta activo (hay rutas disponibles) */
    modoSPA: boolean;
}

export interface NavigationActions {
    /* Inicializa el store con las rutas de PHP y la ruta actual */
    inicializar: (rutas: GloryRoutesMap, rutaInicial: string) => void;
    /* Navega a una nueva ruta sin recarga */
    navegar: (ruta: string) => void;
    /* Vuelve atras en el historial */
    volverAtras: () => void;
    /* Resuelve una ruta y devuelve su config (null si no es interna) */
    resolverRuta: (ruta: string) => GloryRoute | null;
    /* Marca el fin de la transicion */
    finalizarNavegacion: () => void;
}

function normalizarRuta(ruta: string): string {
    /* Elimina querystring y hash */
    const sinQuery = ruta.split('?')[0]?.split('#')[0] ?? ruta;

    /* Asegura slash al final excepto para '/' */
    if (sinQuery === '/' || sinQuery === '') return '/';
    return sinQuery.endsWith('/') ? sinQuery : sinQuery + '/';
}

/*
 * Busca una ruta en el mapa SPA por coincidencia exacta o por prefijo.
 * Rutas dinámicas como /perfil/john/ matchean con /perfil/ si no hay exacta.
 * Retorna la config de la ruta encontrada o null.
 */
function buscarRutaEnMapa(rutas: GloryRoutesMap, rutaNormalizada: string): GloryRoute | null {
    /* Primero búsqueda exacta */
    if (rutas[rutaNormalizada]) return rutas[rutaNormalizada];

    /* Búsqueda por prefijo: /perfil/john/ → /perfil/ */
    const segmentos = rutaNormalizada.split('/').filter(Boolean);
    for (let i = segmentos.length - 1; i >= 1; i--) {
        const prefijo = '/' + segmentos.slice(0, i).join('/') + '/';
        if (rutas[prefijo]) return rutas[prefijo];
    }

    return null;
}

export const useNavigationStore = create<NavigationState & NavigationActions>((set, get) => ({
    rutaActual: normalizarRuta(window.location.pathname),
    islaActual: null,
    propsActuales: {},
    tituloActual: document.title,
    navegando: false,
    rutas: {},
    modoSPA: false,

    inicializar: (rutas, rutaInicial) => {
        const ruta = normalizarRuta(rutaInicial);
        const config = buscarRutaEnMapa(rutas, ruta);

        set({
            rutas,
            modoSPA: Object.keys(rutas).length > 0,
            rutaActual: ruta,
            islaActual: config?.island ?? null,
            propsActuales: config?.props ?? {},
            tituloActual: config?.title ?? document.title,
        });

        /* Escuchar popstate para navegacion con historial (boton atras/adelante) */
        window.addEventListener('popstate', () => {
            const nuevaRuta = normalizarRuta(window.location.pathname);
            const nuevaConfig = buscarRutaEnMapa(get().rutas, nuevaRuta);

            if (nuevaConfig) {
                set({
                    rutaActual: nuevaRuta,
                    islaActual: nuevaConfig.island,
                    propsActuales: nuevaConfig.props,
                    tituloActual: nuevaConfig.title,
                    navegando: true,
                });

                if (nuevaConfig.title) document.title = nuevaConfig.title;
                window.scrollTo({ top: 0, behavior: 'instant' });
            }
        });
    },

    navegar: (ruta) => {
        const rutaNormalizada = normalizarRuta(ruta);
        const { rutaActual, rutas } = get();

        /* No navegar si ya estamos en esa ruta */
        if (rutaNormalizada === rutaActual) return;

        const config = buscarRutaEnMapa(rutas, rutaNormalizada);
        if (!config) {
            /* Ruta no encontrada en mapa SPA ni por prefijo, hacer navegacion tradicional */
            window.location.href = ruta;
            return;
        }

        /* Actualizar historial del navegador */
        window.history.pushState({ gloryRoute: rutaNormalizada }, '', rutaNormalizada);

        /* Actualizar estado */
        set({
            rutaActual: rutaNormalizada,
            islaActual: config.island,
            propsActuales: config.props,
            tituloActual: config.title,
            navegando: true,
        });

        if (config.title) document.title = config.title;
        window.scrollTo({ top: 0, behavior: 'instant' });
    },

    volverAtras: () => {
        window.history.back();
    },

    resolverRuta: (ruta) => {
        const rutaNormalizada = normalizarRuta(ruta);
        return buscarRutaEnMapa(get().rutas, rutaNormalizada);
    },

    finalizarNavegacion: () => {
        set({ navegando: false });
    },
}));
