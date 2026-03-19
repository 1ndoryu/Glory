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
    /* Patrón de params para rutas dinámicas (ej: ':id/:slug?') */
    params?: string;
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
    /* Inicializa el store con las rutas de PHP y la ruta actual.
     * propsEvaluados: props del servidor (data-props del DOM) que sobreescriben los del mapa.
     * Necesario para rutas dinámicas (/cancion/slug) donde el mapa tiene /cancion/ sin slug. */
    inicializar: (rutas: GloryRoutesMap, rutaInicial: string, propsEvaluados?: Record<string, unknown>) => void;
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

/*
 * Extrae parámetros nombrados de los segmentos dinámicos de la URL.
 * Patrón ':id/:slug?' → segmentos '168/big-daddy-kane' → { id: '168', slug: 'big-daddy-kane' }.
 * Segmentos con sufijo ? son opcionales.
 */
function extraerParamsDeUrl(patron: string, segmentoDinamico: string): Record<string, string> {
    const definiciones = patron.split('/').filter(Boolean);
    const segmentos = segmentoDinamico.split('/').filter(Boolean);
    const resultado: Record<string, string> = {};

    for (let i = 0; i < definiciones.length; i++) {
        const def = definiciones[i];
        const esOpcional = def.endsWith('?');
        const nombre = def.replace(/^:/, '').replace(/\?$/, '');

        if (i < segmentos.length) {
            resultado[nombre] = segmentos[i];
        } else if (!esOpcional) {
            resultado[nombre] = '';
        }
    }

    return resultado;
}

/*
 * Resuelve los props para una ruta.
 * En coincidencia exacta retorna los props estáticos del mapa.
 * En coincidencia por prefijo:
 *   - Si la ruta tiene patrón de params, extrae params nombrados.
 *   - Si no, inyecta todo como slug (retrocompatibilidad).
 */
function resolverPropsParaRuta(rutas: GloryRoutesMap, rutaNormalizada: string): Record<string, unknown> {
    if (rutas[rutaNormalizada]) return rutas[rutaNormalizada].props;

    const segmentos = rutaNormalizada.split('/').filter(Boolean);
    for (let i = segmentos.length - 1; i >= 1; i--) {
        const prefijo = '/' + segmentos.slice(0, i).join('/') + '/';
        if (rutas[prefijo]) {
            const segmentoDinamico = rutaNormalizada.slice(prefijo.length).replace(/\/$/, '');
            if (!segmentoDinamico) return rutas[prefijo].props;

            const rutaConfig = rutas[prefijo];
            if (rutaConfig.params) {
                return { ...rutaConfig.props, ...extraerParamsDeUrl(rutaConfig.params, segmentoDinamico) };
            }

            return { ...rutas[prefijo].props, slug: segmentoDinamico };
        }
    }

    return {};
}

export const useNavigationStore = create<NavigationState & NavigationActions>((set, get) => ({
    rutaActual: normalizarRuta(window.location.pathname),
    islaActual: null,
    propsActuales: {},
    tituloActual: document.title,
    navegando: false,
    rutas: {},
    modoSPA: false,

    inicializar: (rutas, rutaInicial, propsEvaluados) => {
        const ruta = normalizarRuta(rutaInicial);
        const config = buscarRutaEnMapa(rutas, ruta);
        /* resolverPropsParaRuta extrae el slug de la URL para rutas dinámicas
         * (ej: /cancion/mi-cancion/ → slug='mi-cancion') como fallback base.
         * propsEvaluados del servidor toman prioridad (mismos valores, más confiables). */
        const propsDeRuta = resolverPropsParaRuta(rutas, ruta);
        const propsActuales = propsEvaluados && Object.keys(propsEvaluados).length > 0
            ? { ...propsDeRuta, ...propsEvaluados }
            : propsDeRuta;

        set({
            rutas,
            modoSPA: Object.keys(rutas).length > 0,
            rutaActual: ruta,
            islaActual: config?.island ?? null,
            propsActuales,
            tituloActual: config?.title ?? document.title,
        });

        /* Escuchar popstate para navegacion con historial (boton atras/adelante) */
        window.addEventListener('popstate', () => {
            const nuevaRuta = normalizarRuta(window.location.pathname);
            const rutasActuales = get().rutas;
            const nuevaConfig = buscarRutaEnMapa(rutasActuales, nuevaRuta);

            if (nuevaConfig) {
                set({
                    rutaActual: nuevaRuta,
                    islaActual: nuevaConfig.island,
                    /* Extraer slug de la URL para rutas dinámicas (ej: /cancion/slug/) */
                    propsActuales: resolverPropsParaRuta(rutasActuales, nuevaRuta),
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

        /* [183A-77] Preservar query string y hash en el historial.
         * normalizarRuta() los elimina para matching, pero pushState necesita la URL completa
         * para que filtros de búsqueda (?buscar=) persistan en la barra de direcciones. */
        const idxQuery = ruta.indexOf('?');
        const idxHash = ruta.indexOf('#');
        const idxSufijo = idxQuery >= 0 ? idxQuery : (idxHash >= 0 ? idxHash : -1);
        const sufijo = idxSufijo >= 0 ? ruta.substring(idxSufijo) : '';
        const urlCompleta = rutaNormalizada + sufijo;

        /* Actualizar historial del navegador */
        window.history.pushState({ gloryRoute: rutaNormalizada }, '', urlCompleta);

        /* Actualizar estado.
         * resolverPropsParaRuta extrae slug de la URL cuando es prefijo match
         * (ej: navegar('/cancion/mi-cancion/') → { slug: 'mi-cancion' }). */
        set({
            rutaActual: rutaNormalizada,
            islaActual: config.island,
            propsActuales: resolverPropsParaRuta(rutas, rutaNormalizada),
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
