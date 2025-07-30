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
            // Si no se encuentran elementos válidos para muestrear (p. ej. móvil sin enlaces visibles),
            // tomamos una muestra de respaldo centrada justo debajo del header.
            const headerRect = header.getBoundingClientRect();
            const sampleX = headerRect.left + headerRect.width / 2;
            const sampleY = headerRect.bottom;
            const fallbackColor = getBackgroundColorAtPoint(sampleX, sampleY);
            averageLuminance = getLuminance(fallbackColor.r, fallbackColor.g, fallbackColor.b);
        }

        // -- Manejo de logo: imagen o SVG --
        //   Si el logo es negro por defecto, aplicamos un filtro para volverlo blanco
        //   cuando el fondo es oscuro (texto blanco) y lo quitamos cuando el fondo es claro.
        const logoImg = header.querySelector('.siteMenuLogo img');
        const logoSvg = header.querySelector('.siteMenuLogo svg');

        if (averageLuminance < LUMINANCE_THRESHOLD) {
            if (!header.classList.contains(classToToggle)) {
                header.classList.add(classToToggle);
            }
            // Aplicamos filtros al logo
            if (logoImg) {
                logoImg.style.filter = 'brightness(0) invert(1)';
            }
            if (logoSvg) {
                // Invertimos el color del SVG entero; esto funciona para la mayoría de SVGs negros.
                logoSvg.style.filter = 'invert(1)';
            }
        } else {
            if (header.classList.contains(classToToggle)) {
                header.classList.remove(classToToggle);
            }
            // Quitamos los filtros del logo
            if (logoImg) {
                logoImg.style.filter = '';
            }
            if (logoSvg) {
                logoSvg.style.filter = '';
            }
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

    // Si estamos en escritorio (>= 834px) salimos para no modificar la navegación.
    if (!header || !burger || !background || !navMenu) {
        console.error('Faltan elementos del menú para inicializar.');
        return;
    }

    const navTitle = navMenu.querySelector('.nav-title');
    const navItems = navMenu.querySelectorAll('ul > li');

    let isMenuOpen = false;

    // Preparamos los elementos para la animación (ocultos por defecto)
    gsap.set(navMenu, {yPercent: -100, autoAlpha: 0});

    const openMenu = () => {
        if (isMenuOpen) return;
        isMenuOpen = true;

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
            ) // Inicia al mismo tiempo que el menú entra
            .to(
                navItems,
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.6,
                    ease: 'power3.out',
                    stagger: 0.05 // La magia del escalonado
                },
                '-=0.5'
            );
    };

    const closeMenu = () => {
        if (!isMenuOpen) return;
        isMenuOpen = false;

        const tl = gsap.timeline({
            onComplete: () => {
                // Limpiamos las clases solo cuando la animación termina
                header.classList.remove('open');
                document.body.classList.remove('menu-open');
            }
        });

        tl.to([navTitle, ...navItems].reverse(), {
            // Animamos en orden inverso
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

    burger.addEventListener('click', () => {
        isMenuOpen ? closeMenu() : openMenu();
    });

    background.addEventListener('click', closeMenu);

    // Cerrar con la tecla 'Escape'
    window.addEventListener('keyup', e => {
        if (e.key === 'Escape') {
            closeMenu();
        }
    });
}

document.addEventListener('gloryRecarga', gloryMenu);
document.addEventListener('gloryRecarga', gloryAdaptiveHeader);
