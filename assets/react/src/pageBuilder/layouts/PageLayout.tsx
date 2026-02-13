/**
 * PageLayout - Layout principal para paginas con Page Builder
 *
 * Este componente envuelve cualquier pagina y proporciona:
 * - Navigation (configurable)
 * - Footer (configurable)
 * - Integracion automatica con PageBuilder (opcional)
 *
 * USO EN APP:
 * ```tsx
 * // Pagina con bloques
 * <PageLayout
 *     siteName="Mi Sitio"
 *     blocks={blocks}
 *     isAdmin={isAdmin}
 *     saveEndpoint={saveEndpoint}
 *     restNonce={restNonce}
 * />
 *
 * // Pagina sin bloques (contenido custom)
 * <PageLayout siteName="Mi Sitio">
 *     <h1>Contenido personalizado</h1>
 * </PageLayout>
 *
 * // Pagina sin Page Builder
 * <PageLayout siteName="Mi Sitio" usePageBuilder={false}>
 *     <MyCustomContent />
 * </PageLayout>
 * ```
 */

import type {ReactNode} from 'react';
import {PageBuilder} from '../components/PageBuilder';
import type {BlockData} from '../types';

export interface NavLink {
    text: string;
    href: string;
    hideOnMobile?: boolean;
}

export interface SocialLink {
    type: 'twitter' | 'github' | 'mail' | 'linkedin' | 'facebook' | 'instagram';
    href: string;
}

export interface PageLayoutProps {
    /** Contenido de la pagina (alternativa a blocks) */
    children?: ReactNode;

    /* Configuracion general */
    /** Nombre del sitio */
    siteName?: string;
    /** URL del logo (opcional) */
    logoUrl?: string;

    /* Navegacion */
    /** Links del nav */
    navLinks?: NavLink[];
    /** Texto del boton CTA del nav */
    navCtaText?: string;
    /** URL del boton CTA del nav */
    navCtaUrl?: string;
    /** Ocultar navegacion */
    hideNav?: boolean;

    /* Footer */
    /** Texto de copyright */
    copyright?: string;
    /** Links sociales */
    socialLinks?: SocialLink[];
    /** Ocultar footer */
    hideFooter?: boolean;

    /* Page Builder */
    /** Usar Page Builder (default: true si hay blocks) */
    usePageBuilder?: boolean;
    /** Bloques iniciales */
    blocks?: BlockData[] | null;
    /** Si el usuario puede editar */
    isAdmin?: boolean;
    /** Endpoint REST para guardar */
    saveEndpoint?: string | null;
    /** Nonce para autenticacion */
    restNonce?: string | null;
    /** Tipos de bloque permitidos */
    allowedBlockTypes?: string[];
    /** Texto del boton editar */
    editButtonText?: string;
    /** Titulo del toolbar */
    toolbarTitle?: string;

    /* Estilos */
    /** Clase CSS adicional para el contenedor */
    className?: string;
    /** Color de fondo (default: #050505) */
    bgColor?: string;
}

/*
 * Iconos sociales inline (evitar dependencia de lucide para el layout)
 */
function SocialIcon({type}: {type: SocialLink['type']}): JSX.Element {
    const iconStyle = {width: 18, height: 18};

    switch (type) {
        case 'twitter':
            return (
                <svg style={iconStyle} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z" />
                </svg>
            );
        case 'github':
            return (
                <svg style={iconStyle} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22" />
                </svg>
            );
        case 'mail':
            return (
                <svg style={iconStyle} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <rect x="2" y="4" width="20" height="16" rx="2" />
                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                </svg>
            );
        case 'linkedin':
            return (
                <svg style={iconStyle} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z" />
                    <rect x="2" y="9" width="4" height="12" />
                    <circle cx="4" cy="4" r="2" />
                </svg>
            );
        case 'facebook':
            return (
                <svg style={iconStyle} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
                </svg>
            );
        case 'instagram':
            return (
                <svg style={iconStyle} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
                </svg>
            );
    }
}

export function PageLayout({children, siteName = 'Glory', logoUrl, navLinks = [], navCtaText = 'Login', navCtaUrl = '#', hideNav = false, copyright, socialLinks = [], hideFooter = false, usePageBuilder, blocks, isAdmin = false, saveEndpoint, restNonce, allowedBlockTypes, editButtonText, toolbarTitle, className = '', bgColor = '#050505'}: PageLayoutProps): JSX.Element {
    /* Determinar si usar Page Builder */
    const shouldUsePageBuilder = usePageBuilder !== undefined ? usePageBuilder : !!blocks && blocks.length > 0;

    /* Copyright por defecto */
    const currentYear = new Date().getFullYear();
    const copyrightText = copyright || `© ${currentYear} ${siteName}. Todos los derechos reservados.`;

    return (
        <div id="page-layout" className={`min-h-screen text-white font-sans antialiased overflow-x-hidden selection:bg-white selection:text-black ${className}`} style={{background: bgColor}}>
            {/* Navigation */}
            {!hideNav && (
                <nav id="main-nav" className="fixed w-full z-50 top-0 backdrop-blur-md border-b border-white/5" style={{background: `${bgColor}cc`}}>
                    <div className="max-w-[1200px] mx-auto px-6 h-16 flex items-center justify-between">
                        {/* Logo / Nombre */}
                        <div className="font-bold text-xl tracking-tight flex items-center gap-2">
                            {logoUrl ? <img src={logoUrl} alt={siteName} className="h-6" /> : <div className="w-5 h-5 bg-white rounded-full"></div>}
                            {siteName}
                        </div>

                        {/* Links */}
                        <div className="flex items-center gap-6">
                            {navLinks.map((link, i) => (
                                <a key={link.href || i} href={link.href} className={`text-sm text-gray-400 hover:text-white transition-colors ${link.hideOnMobile ? 'hidden md:block' : ''}`}>
                                    {link.text}
                                </a>
                            ))}

                            {/* CTA Button */}
                            <a href={navCtaUrl} className="text-sm bg-[#1a1a1a] hover:bg-[#222] text-white px-4 py-2 rounded-full transition-colors border border-white/5">
                                {navCtaText}
                            </a>
                        </div>
                    </div>
                </nav>
            )}

            {/* Contenido principal */}
            <main id="main-content">{shouldUsePageBuilder && blocks ? <PageBuilder blocks={blocks} isAdmin={isAdmin} saveEndpoint={saveEndpoint} restNonce={restNonce} allowedBlockTypes={allowedBlockTypes} editButtonText={editButtonText} toolbarTitle={toolbarTitle} /> : children}</main>

            {/* Footer */}
            {!hideFooter && (
                <footer id="main-footer" className="py-12 border-t border-white/5">
                    <div className="max-w-[1200px] mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
                        <div className="text-sm text-gray-500">{copyrightText}</div>

                        {socialLinks.length > 0 && (
                            <div className="flex gap-6">
                                {socialLinks.map((social, i) => (
                                    <a key={social.href || i} href={social.href} className="text-gray-500 hover:text-white transition-colors">
                                        <SocialIcon type={social.type} />
                                    </a>
                                ))}
                            </div>
                        )}
                    </div>
                </footer>
            )}
        </div>
    );
}

export default PageLayout;
