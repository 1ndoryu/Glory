/*
 * Tipos del framework Glory.
 * Define las interfaces para el puente PHP→React: contenido, islas, contexto.
 */

import type { WPPost } from './wordpress';

/*
 * Contenido inyectado por ReactContentProvider en window.__GLORY_CONTENT__
 * Cada clave es un nombre registrado con register()/registerStatic()/registerFromDefaults()
 * y el valor es un array de posts procesados.
 */
export type GloryContentMap = Record<string, WPPost[]>;

/*
 * Contexto global inyectado por ReactIslands en window.GLORY_CONTEXT
 * Extendible via el filtro glory_react_context de WordPress.
 */
export interface GloryContext {
    siteUrl?: string;
    themeUrl?: string;
    restUrl?: string;
    nonce?: string;
    isAdmin?: boolean;
    userId?: number;
    locale?: string;
    options?: Record<string, unknown>;
    [key: string]: unknown;
}

/*
 * Props base que toda isla recibe automaticamente.
 * Las props custom se pasan via data-props en el HTML.
 */
export interface GloryIslandBaseProps {
    [key: string]: unknown;
}

/*
 * Configuracion de una pagina React registrada via PageManager::reactPage()
 */
export interface GloryPageConfig {
    slug: string;
    islandName: string;
    title: string;
    parentSlug?: string;
    roles?: string[];
    props?: Record<string, unknown>;
}

/*
 * Estructura de una opcion del sistema de opciones Glory.
 */
export interface GloryOption<T = unknown> {
    key: string;
    value: T;
    default: T;
    label?: string;
    type?: 'text' | 'number' | 'boolean' | 'select' | 'color' | 'image';
    group?: string;
}

/*
 * Registro de islas: mapea nombre de isla a su componente React.
 */
export type IslandRegistry = Record<string, React.ComponentType<Record<string, unknown>>>;

/*
 * Declaracion de variables globales inyectadas por PHP.
 */
declare global {
    interface Window {
        __GLORY_CONTENT__?: GloryContentMap;
        GLORY_CONTEXT?: GloryContext;
    }
}
