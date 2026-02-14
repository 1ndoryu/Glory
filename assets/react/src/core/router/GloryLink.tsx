/*
 * GloryLink — Componente para navegacion SPA entre islas.
 * Intercepta clicks en enlaces internos y navega sin recarga.
 * Si la ruta no esta en el mapa SPA, hace navegacion tradicional.
 *
 * Uso: <GloryLink href="/servicios/">Ver servicios</GloryLink>
 */

import { useCallback, type MouseEvent, type AnchorHTMLAttributes, type ReactNode } from 'react';
import { useNavigationStore } from './navigationStore';

export interface GloryLinkProps extends Omit<AnchorHTMLAttributes<HTMLAnchorElement>, 'href'> {
    href: string;
    children: ReactNode;
    /* Forzar recarga completa incluso para rutas SPA */
    forceReload?: boolean;
}

/*
 * Determina si una URL es interna al sitio (mismo origen).
 */
function esEnlaceInterno(href: string): boolean {
    if (href.startsWith('/') && !href.startsWith('//')) return true;
    if (href.startsWith('#')) return false;

    try {
        const url = new URL(href, window.location.origin);
        return url.origin === window.location.origin;
    } catch {
        return false;
    }
}

/*
 * Extrae el pathname de un href (relativo o absoluto).
 */
function extraerPath(href: string): string {
    if (href.startsWith('/')) return href;

    try {
        return new URL(href, window.location.origin).pathname;
    } catch {
        return href;
    }
}

export function GloryLink({
    href,
    children,
    forceReload = false,
    onClick,
    ...rest
}: GloryLinkProps): JSX.Element {
    const { navegar, resolverRuta, modoSPA } = useNavigationStore();

    const manejarClick = useCallback(
        (e: MouseEvent<HTMLAnchorElement>) => {
            /* Ejecutar onClick del padre si existe */
            if (onClick) onClick(e);

            /* No interceptar si ya se previno, si se usan teclas modificadoras o boton no primario */
            if (
                e.defaultPrevented ||
                e.metaKey ||
                e.ctrlKey ||
                e.shiftKey ||
                e.altKey ||
                e.button !== 0
            ) {
                return;
            }

            /* No interceptar si forzamos recarga */
            if (forceReload) return;

            /* No interceptar si no es enlace interno */
            if (!esEnlaceInterno(href)) return;

            /* No interceptar si el modo SPA esta desactivado */
            if (!modoSPA) return;

            const path = extraerPath(href);
            const ruta = resolverRuta(path);

            /* Si la ruta esta en el mapa SPA, navegar sin recarga */
            if (ruta) {
                e.preventDefault();
                navegar(path);
            }
            /* Si no esta, dejar que el browser haga nav normal */
        },
        [href, forceReload, onClick, navegar, resolverRuta, modoSPA],
    );

    return (
        <a href={href} onClick={manejarClick} {...rest}>
            {children}
        </a>
    );
}
