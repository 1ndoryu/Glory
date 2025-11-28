function gloryAdaptiveHeader() {
    const headerSelector = '.siteMenuW';
    const classToToggle = 'has-dark-background';
    const LUMINANCE_THRESHOLD = 140;

    const header = document.querySelector(headerSelector);
    if (!header) {
        return;
    }
    if (header.dataset.adaptiveHeaderInitialized) {
        return;
    }
    header.dataset.adaptiveHeaderInitialized = 'true';

    let isTicking = false;

    function getLuminance(r, g, b) {
        return (r * 299 + g * 587 + b * 114) / 1000;
    }

    function getBackgroundColorAtPoint(x, y) {
        header.style.pointerEvents = 'none';
        let elementAtPoint = document.elementFromPoint(x, y);
        header.style.pointerEvents = '';

        if (!elementAtPoint) return {r: 255, g: 255, b: 255};

        let currentElement = elementAtPoint;
        while (currentElement && header.contains(currentElement)) {
            currentElement = currentElement.parentElement;
        }

        while (currentElement) {
            const style = window.getComputedStyle(currentElement);
            const bgColor = style.backgroundColor;

            if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
                const match = bgColor.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
                if (match) {
                    return {r: parseInt(match[1]), g: parseInt(match[2]), b: parseInt(match[3])};
                }
            }
            currentElement = currentElement.parentElement;
        }
        return {r: 255, g: 255, b: 255};
    }

    function updateHeaderContrast() {
        const textElements = Array.from(header.querySelectorAll('.siteMenuNav a, .siteMenuLogo a'));
        if (!textElements.length) return;

        let totalLuminance = 0;
        let validSamples = 0;

        textElements.forEach(el => {
            const rect = el.getBoundingClientRect();
            if (rect.width === 0 || rect.height === 0) return;

            const sampleX = rect.left + rect.width / 2;
            const sampleY = rect.top + rect.height / 2;

            if (sampleX > 0 && sampleX < window.innerWidth && sampleY > 0 && sampleY < window.innerHeight) {
                const color = getBackgroundColorAtPoint(sampleX, sampleY);
                totalLuminance += getLuminance(color.r, color.g, color.b);
                validSamples++;
            }
        });

        let averageLuminance;
        if (validSamples > 0) {
            averageLuminance = totalLuminance / validSamples;
        } else {
            const headerRect = header.getBoundingClientRect();
            const sampleX = headerRect.left + headerRect.width / 2;
            const sampleY = headerRect.bottom;
            const fallbackColor = getBackgroundColorAtPoint(sampleX, sampleY);
            averageLuminance = getLuminance(fallbackColor.r, fallbackColor.g, fallbackColor.b);
        }

        const logoImg = header.querySelector('.siteMenuLogo img');
        const logoSvg = header.querySelector('.siteMenuLogo svg');
        // --- INICIO: CÓDIGO MODIFICADO Y MEJORADO ---
        const logoLink = header.querySelector('.siteMenuLogo a');
        const isImageLink = logoLink ? !!logoLink.querySelector('img, svg') : false;
        const logoTextElement = (logoLink && !isImageLink) ? logoLink : null;
        // --- FIN: CÓDIGO MODIFICADO Y MEJORADO ---

        if (averageLuminance < LUMINANCE_THRESHOLD) {
            if (!header.classList.contains(classToToggle)) {
                header.classList.add(classToToggle);
            }
            if (logoImg) {
                logoImg.style.filter = 'brightness(0) invert(1)';
            }
            if (logoSvg) {
                logoSvg.style.filter = 'invert(1)';
            }
            // --- INICIO: CÓDIGO AÑADIDO ---
            if (logoTextElement) {
                logoTextElement.style.color = '#FFFFFF';
            }
            // --- FIN: CÓDIGO AÑADIDO ---
        } else {
            if (header.classList.contains(classToToggle)) {
                header.classList.remove(classToToggle);
            }
            if (logoImg) {
                logoImg.style.filter = '';
            }
            if (logoSvg) {
                logoSvg.style.filter = '';
            }
            // --- INICIO: CÓDIGO AÑADIDO ---
            if (logoTextElement) {
                logoTextElement.style.color = '';
            }
            // --- FIN: CÓDIGO AÑADIDO ---
        }
    }

    function onScrollOrResize() {
        if (!isTicking) {
            window.requestAnimationFrame(() => {
                updateHeaderContrast();
                isTicking = false;
            });
            isTicking = true;
        }
    }

    const observer = new MutationObserver(onScrollOrResize);
    const mainContent = document.querySelector('main') || document.body;
    observer.observe(mainContent, {childList: true, subtree: true});

    setTimeout(updateHeaderContrast, 300);
    window.addEventListener('scroll', onScrollOrResize, {passive: true});
    window.addEventListener('resize', onScrollOrResize, {passive: true});
    document.addEventListener('gloryRecarga', () => setTimeout(updateHeaderContrast, 150));
}

function gloryMenu() {
    const header = document.querySelector('.siteMenuW');
    const burger = document.querySelector('.burger');
    const background = document.querySelector('.background');
    const navMenu = document.querySelector('.siteMenuNav');

    if (!header || !burger || !background || !navMenu) {
        console.error('Faltan elementos del menú para inicializar.');
        return;
    }

    // Previene múltiples inicializaciones
    if (header && header.dataset.menuInitialized) {
        return;
    }
    if (header) {
        header.dataset.menuInitialized = 'true';
    }

    const navTitle = navMenu.querySelector('.navTitle');
    const navItems = navMenu.querySelectorAll('ul > li');

    const isMobile = window.matchMedia('(max-width: 999px)').matches;
    let isMenuOpen = false;

    if (isMobile) {
        gsap.set(navMenu, {yPercent: -100, autoAlpha: 0});
    } else {
        // En escritorio evitamos transforms/opacidad inline para que backdrop-filter funcione
        //navMenu.style.removeProperty('transform');
        //navMenu.style.removeProperty('opacity');
        header.classList.remove('open');
        document.body.classList.remove('menu-open');
    }

    const openMenu = () => {
        if (isMenuOpen) return;
        isMenuOpen = true;
        if (!isMobile) return; // solo móvil
        document.body.classList.add('menu-open');
        header.classList.add('open');

        const tl = gsap.timeline();
        tl.to(navMenu, {
            yPercent: 0,
            autoAlpha: 1,
            duration: 0.8,
            ease: 'expo.inOut'
        })
            .to(
                navTitle,
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.6,
                    ease: 'power3.out'
                },
                '-=0.6'
            )
            .to(
                navItems,
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.6,
                    ease: 'power3.out',
                    stagger: 0.05
                },
                '-=0.5'
            );
    };

    const closeMenu = () => {
        if (!isMenuOpen) return;
        isMenuOpen = false;
        if (!isMobile) return; // solo móvil
        const tl = gsap.timeline({
            onComplete: () => {
                header.classList.remove('open');
                document.body.classList.remove('menu-open');
            }
        });

        tl.to([navTitle, ...navItems].reverse(), {
            autoAlpha: 0,
            y: 20,
            duration: 0.4,
            ease: 'power2.in',
            stagger: 0.03
        }).to(
            navMenu,
            {
                yPercent: -100,
                autoAlpha: 0,
                duration: 0.8,
                ease: 'expo.inOut'
            },
            '-=0.3'
        );
    };

    if (isMobile) {
        burger.addEventListener('click', () => {
            isMenuOpen ? closeMenu() : openMenu();
        });

        background.addEventListener('click', closeMenu);

        window.addEventListener('keyup', e => {
            if (e.key === 'Escape') {
                closeMenu();
            }
        });

        // Comportamiento móvil: togglear submenús con tap y evitar navegación en padre
        const topLevelItems = navMenu.querySelectorAll('ul.menu > li.menu-item-has-children');
        topLevelItems.forEach(li => {
            const link = li.querySelector(':scope > a');
            const submenu = li.querySelector(':scope > ul.sub-menu');
            if (!submenu) return;

            // Asegurar cerrado por defecto en móvil
            li.classList.remove('open');

            if (link) {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    // Toggle del submenú
                    if (li.classList.contains('open')) {
                        li.classList.remove('open');
                    } else {
                        // cerrar otros abiertos
                        topLevelItems.forEach(otro => {
                            if (otro !== li) otro.classList.remove('open');
                        });
                        li.classList.add('open');
                    }
                });
            }
        });
    }
}

function gloryMenuBridge() {
    const header = document.querySelector('.siteMenuW');
    const burger = header ? header.querySelector('.burger') : null;
    const backgroundBtn = header ? header.querySelector('.background') : null;
    const navMenu = header ? header.querySelector('.siteMenuNav') : null;

    if (!header || !navMenu) {
        return;
    }

    // Evitar inicialización duplicada por ciclo de vida (pero permitir re-enlazar enlaces en gloryRecarga).
    if (!header.dataset.menuBridgeObserved) {
        header.dataset.menuBridgeObserved = 'true';
    } else {
        // Ya observamos las clases; solo re-enlazamos enlaces más abajo.
    }

    const isMobile = () => window.matchMedia('(max-width: 999px)').matches;
    const getTextColor = () => 'var(--text)';

    const applyOpenColors = () => {
        const logo = header.querySelector('.siteMenuLogo');
        const logoLink = header.querySelector('.siteMenuLogo a');
        const logoImg = header.querySelector('.siteMenuLogo img');
        const logoSvg = header.querySelector('.siteMenuLogo svg');

        if (burger) {
            burger.style.color = getTextColor();
        }
        if (logo) {
            logo.style.color = getTextColor();
        }
        if (logoLink) {
            logoLink.style.color = getTextColor();
        }
        if (logoImg) {
            // Forzar versión sin invertir (logo oscuro) cuando el menú móvil está abierto.
            logoImg.style.filter = 'brightness(0) invert(0)';
        }
        if (logoSvg) {
            // Evitar filtros de adaptiveHeader mientras el menú está abierto.
            logoSvg.style.filter = '';
            logoSvg.style.color = getTextColor();
        }
    };

    const clearOpenColors = () => {
        const logo = header.querySelector('.siteMenuLogo');
        const logoLink = header.querySelector('.siteMenuLogo a');
        const logoImg = header.querySelector('.siteMenuLogo img');
        const logoSvg = header.querySelector('.siteMenuLogo svg');

        if (burger) {
            burger.style.removeProperty('color');
        }
        if (logo) {
            logo.style.removeProperty('color');
        }
        if (logoLink) {
            logoLink.style.removeProperty('color');
        }
        if (logoImg) {
            logoImg.style.removeProperty('filter');
        }
        if (logoSvg) {
            logoSvg.style.removeProperty('filter');
            logoSvg.style.removeProperty('color');
        }
    };

    const closeMenu = () => {
        if (!header.classList.contains('open')) {
            return;
        }
        if (backgroundBtn) {
            // Reutiliza el cierre nativo del menú de Glory (incluye animaciones GSAP).
            backgroundBtn.click();
        } else if (burger) {
            burger.click();
        } else {
            document.body.classList.remove('menu-open');
            header.classList.remove('open');
        }
    };

    const handleHeaderClassChange = () => {
        if (header.classList.contains('open')) {
            applyOpenColors();
            // Mientras esté abierto, asegurar colores ante scroll/resize.
            window.addEventListener('scroll', applyOpenColors, { passive: true });
            window.addEventListener('resize', applyOpenColors, { passive: true });
        } else {
            clearOpenColors();
            window.removeEventListener('scroll', applyOpenColors);
            window.removeEventListener('resize', applyOpenColors);
            // Forzar recalculo inmediato del contraste del header por parte de adaptiveHeader.
            requestAnimationFrame(() => {
                try {
                    window.dispatchEvent(new Event('resize'));
                } catch (_) {}
                try {
                    window.dispatchEvent(new Event('scroll'));
                } catch (_) {}
            });
        }
    };

    // Observar cambios en la clase del header (para detectar .open).
    if (!header.__gloryMenuBridgeObserver) {
        const obs = new MutationObserver(muts => {
            for (let i = 0; i < muts.length; i++) {
                const m = muts[i];
                if (m.type === 'attributes' && m.attributeName === 'class') {
                    handleHeaderClassChange();
                    break;
                }
            }
        });
        obs.observe(header, { attributes: true });
        header.__gloryMenuBridgeObserver = obs;
        // Aplicar estado inicial si ya está abierto por alguna razón.
        handleHeaderClassChange();
    }

    // Enlaces que cierran el menú en móvil cuando son ítems hoja (sin submenú).
    const bindNavCloseOnClick = () => {
        const links = navMenu.querySelectorAll('a');
        links.forEach(a => {
            if (a.__gloryMenuBridgeBound) {
                return;
            }
            a.__gloryMenuBridgeBound = true;
            a.addEventListener('click', () => {
                if (!isMobile()) {
                    return;
                }
                const li = a.closest('li');
                const isParentWithChildren =
                    li &&
                    li.classList &&
                    li.classList.contains('menu-item-has-children') &&
                    li.querySelector(':scope > ul.sub-menu');

                // No cerrar si es un padre que solo abre/cierra submenú.
                if (isParentWithChildren) {
                    return;
                }

                // Ítem hoja: cerrar el menú inmediatamente para mejor respuesta.
                closeMenu();
            });
        });
    };

    bindNavCloseOnClick();

    // Re-enlazar cuando Glory recarga contenido (AJAX).
    document.addEventListener('gloryRecarga', () => {
        setTimeout(() => {
            bindNavCloseOnClick();
            handleHeaderClassChange();
        }, 100);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    gloryMenu();
    gloryAdaptiveHeader();
    gloryMenuBridge();
});

// Fallback por si los recursos se cargan después del DOMContentLoaded (por ejemplo, en algunos constructores de páginas)
window.addEventListener('load', () => {
    gloryMenu();
    gloryAdaptiveHeader();
    gloryMenuBridge();
});

document.addEventListener('gloryRecarga', gloryMenu);
document.addEventListener('gloryRecarga', gloryAdaptiveHeader);
document.addEventListener('gloryRecarga', gloryMenuBridge);