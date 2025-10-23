/**
 * Glory Horizontal Drag
 * Permite arrastrar horizontalmente contenedores con contenido que excede el ancho
 */
(function() {
    'use strict';

    var GloryHorizontalDrag = {
        instances: [],
        registeredSelectors: [],

        init: function(selector) {
            // Registrar selector para re-inicializar tras gloryRecarga
            if (typeof selector === 'string' && selector) {
                if (this.registeredSelectors.indexOf(selector) === -1) {
                    this.registeredSelectors.push(selector);
                }
            }
            var containers = document.querySelectorAll(selector);
            containers.forEach(function(container) {
                if (!container.hasAttribute('data-glory-horizontal-drag')) {
                    this.setupContainer(container);
                    container.setAttribute('data-glory-horizontal-drag', 'true');
                }
            }.bind(this));
        },

        setupContainer: function(container) {
            var isDown = false;
            var startX;
            var startY;
            var scrollLeft;
            var originalPaddingRight = 0;
            var didDrag = false;
            var suppressClickUntil = 0;
            var DRAG_THRESHOLD = 5;

            // Detectar el scroller real (el primero que desborde horizontalmente)
            var scroller = container;

            function hasHorizontalOverflow(el) {
                return el && (el.scrollWidth > el.clientWidth + 1);
            }

            function forceScrollable(el) {
                var ov = getComputedStyle(el).overflowX;
                if (ov === 'visible' || ov === 'hidden' || ov === 'clip') {
                    el.style.overflowX = 'auto';
                }
            }

            // Forzar overflow en contenedor
            forceScrollable(scroller);

            // Si el contenedor no desborda, buscar un hijo directo que sí
            if (!hasHorizontalOverflow(scroller)) {
                var children = Array.prototype.slice.call(container.children || []);
                for (var i = 0; i < children.length; i++) {
                    forceScrollable(children[i]);
                    if (hasHorizontalOverflow(children[i])) {
                        scroller = children[i];
                        break;
                    }
                }
            }

            // Si aún no hay overflow, envolver hijos en un wrapper inline-flex para forzar ancho total
            var INNER_CLASS = 'glory-horizontal-drag-inner';
            var appendTarget = scroller;
            if (!hasHorizontalOverflow(scroller)) {
                var inner = container.querySelector(':scope > .' + INNER_CLASS);
                if (!inner) {
                    inner = document.createElement('div');
                    inner.className = INNER_CLASS;
                    inner.style.display = 'inline-flex';
                    inner.style.flexDirection = 'row';
                    inner.style.flexWrap = 'nowrap';
                    inner.style.alignItems = 'stretch';
                    // Copiar gap del contenedor si existe
                    var cs = getComputedStyle(container);
                    var g = (cs.gap && cs.gap !== 'normal') ? cs.gap : (cs.columnGap || '0px');
                    if (g && g !== 'normal') {
                        inner.style.gap = g;
                    }
                    // Mover hijos actuales dentro de inner
                    var directChildren = Array.prototype.slice.call(container.children || []);
                    for (var j = 0; j < directChildren.length; j++) {
                        var ch = directChildren[j];
                        if (ch === inner) continue;
                        if (ch.classList && ch.classList.contains('glory-horizontal-drag-spacer')) continue;
                        inner.appendChild(ch);
                    }
                    container.appendChild(inner);
                }
                appendTarget = inner;
            }

            // Confirmar que hay overflow horizontal
            if (!hasHorizontalOverflow(scroller)) {
                return;
            }

            // Añadir un spacer real al final para permitir arrastrar más allá del último ítem
            var SPACER_CLASS = 'glory-horizontal-drag-spacer';
            var spacer = appendTarget.querySelector(':scope > .' + SPACER_CLASS);

            function computeSpacerWidth() {
                // Espacio más discreto: 4% del ancho visible, límites reducidos
                var proposed = Math.round(container.clientWidth * 0.04);
                if (!isFinite(proposed) || proposed < 0) proposed = 0;
                return Math.max(32, Math.min(80, proposed));
            }

            function ensureSpacer() {
                if (!spacer) {
                    spacer = document.createElement('div');
                    spacer.className = SPACER_CLASS;
                    spacer.setAttribute('aria-hidden', 'true');
                    spacer.style.pointerEvents = 'none';
                    spacer.style.background = 'transparent';
                    spacer.style.display = 'block';
                    // Para layouts flexibles: que no se colapse
                    spacer.style.flex = '0 0 auto';
                    appendTarget.appendChild(spacer);
                }
                var w = computeSpacerWidth();
                spacer.style.width = w + 'px';
                spacer.style.minWidth = w + 'px';
                spacer.style.flexBasis = w + 'px';
                // Altura: estirar si es flex-row; si no, no importa
                spacer.style.alignSelf = 'stretch';
            }

            ensureSpacer();

            // Padding-right extra desactivado para evitar overscroll excesivo
            var pr = parseInt(getComputedStyle(scroller).paddingRight) || 0;
            originalPaddingRight = pr;
            var extra = 0;
            scroller.style.paddingRight = (pr + extra) + 'px';

            // Actualizar el spacer en resize para mantener la proporción
            var resizeRaf = 0;
            function onResize() {
                if (resizeRaf) return;
                resizeRaf = requestAnimationFrame(function() {
                    resizeRaf = 0;
                    if (container.scrollWidth > container.clientWidth) {
                        ensureSpacer();
                        // Reposicionar el spacer absoluto también
                        if (typeof ensureAbsoluteSpacer === 'function') {
                            ensureAbsoluteSpacer();
                        }
                    }
                });
            }
            window.addEventListener('resize', onResize);

            // Spacer absoluto para forzar scrollWidth incluso en grid/flex-wrap
            var ABS_SPACER_CLASS = 'glory-horizontal-drag-abs-spacer';

            function ensurePositioning() {
                var pos = getComputedStyle(scroller).position;
                if (pos === 'static' || !pos) {
                    scroller.style.position = 'relative';
                }
            }

            function computeContentMaxRight() {
                // Si existe el wrapper interno, su scrollWidth representa el ancho total de contenido
                var innerEl = container.querySelector(':scope > .' + INNER_CLASS);
                if (innerEl) {
                    var innerRight = (innerEl.offsetLeft || 0) + innerEl.scrollWidth;
                    if (innerRight > 0) return innerRight;
                }
                // Fallback: usar el scrollWidth del propio scroller
                var sw = scroller.scrollWidth;
                if (sw && sw > 0) return sw;
                // Último recurso: iterar hijos visibles
                var maxRight = 0;
                var children2 = Array.prototype.slice.call(scroller.children || []);
                for (var k = 0; k < children2.length; k++) {
                    var el = children2[k];
                    if (!el || el.nodeType !== 1) continue;
                    if (el.classList && (el.classList.contains(ABS_SPACER_CLASS))) continue;
                    var cs2 = getComputedStyle(el);
                    var mr2 = parseFloat(cs2.marginRight) || 0;
                    var right = (el.offsetLeft || 0) + el.offsetWidth + mr2;
                    if (right > maxRight) maxRight = right;
                }
                return maxRight;
            }

            function ensureAbsoluteSpacer() {
                ensurePositioning();
                var abs = scroller.querySelector(':scope > .' + ABS_SPACER_CLASS);
                var extra2 = computeSpacerWidth();
                var left = computeContentMaxRight() + extra2;
                if (!abs) {
                    abs = document.createElement('div');
                    abs.className = ABS_SPACER_CLASS;
                    abs.setAttribute('aria-hidden', 'true');
                    abs.style.position = 'absolute';
                    abs.style.top = '0';
                    abs.style.height = '1px';
                    abs.style.width = '1px';
                    abs.style.pointerEvents = 'none';
                    scroller.appendChild(abs);
                }
                abs.style.left = left + 'px';
            }

            ensureAbsoluteSpacer();

            // Evitar drag nativo de imágenes/enlaces dentro del scroller
            scroller.addEventListener('dragstart', function(e) { e.preventDefault(); }, true);
            try {
                var imgs = scroller.querySelectorAll('img');
                imgs.forEach(function(img) {
                    if (!img.hasAttribute('draggable')) {
                        img.setAttribute('draggable', 'false');
                    }
                    img.addEventListener('dragstart', function(ev) { ev.preventDefault(); }, true);
                });
            } catch(_e) {}

            // Suprimir click si acaba de haber un drag
            scroller.addEventListener('click', function(e) {
                if (Date.now() < suppressClickUntil) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, true);

            scroller.addEventListener('mousedown', function(e) {
                isDown = true;
                scroller.classList.add('glory-dragging');
                startX = e.pageX;
                startY = e.pageY;
                scrollLeft = scroller.scrollLeft;
                didDrag = false;

                var moveHandler = function(ev) {
                    if (!isDown) return;
                    ev.preventDefault();
                    var currentX = ev.pageX;
                    var deltaX = startX - currentX;
                    if (Math.abs(deltaX) > DRAG_THRESHOLD) { didDrag = true; }
                    scroller.scrollLeft = scrollLeft + deltaX;
                };
                var upHandler = function() {
                isDown = false;
                    scroller.classList.remove('glory-dragging');
                    if (didDrag) { suppressClickUntil = Date.now() + 250; }
                    didDrag = false;
                    window.removeEventListener('mousemove', moveHandler, true);
                    window.removeEventListener('mouseup', upHandler, true);
                };
                window.addEventListener('mousemove', moveHandler, true);
                window.addEventListener('mouseup', upHandler, true);
            });

            // Soporte para touch (móviles/tablets)
            var touchStartX = 0;
            var touchStartY = 0;
            var touchScrollLeft = 0;
            var touchDidDrag = false;

            scroller.addEventListener('touchstart', function(e) {
                touchStartX = e.touches[0].pageX;
                touchStartY = e.touches[0].pageY;
                touchScrollLeft = scroller.scrollLeft;
                touchDidDrag = false;
            });

            scroller.addEventListener('touchmove', function(e) {
                // Evitar scroll vertical del body mientras se arrastra
                if (!touchStartX) return;
                e.preventDefault();
            }, { passive: false });

            scroller.addEventListener('touchstart', function(e) {
                // Reiniciar posibles listeners previos
                // (No hacemos nada adicional aquí)
            });

            scroller.addEventListener('touchstart', function(e) {
                var touchMoveHandler = function(ev) {
                    if (!touchStartX) return;
                    ev.preventDefault();
                    var touchX = ev.touches[0].pageX;
                    var deltaX = touchStartX - touchX;
                    if (Math.abs(deltaX) > DRAG_THRESHOLD) { touchDidDrag = true; }
                    var walk = deltaX * 2;
                    scroller.scrollLeft = touchScrollLeft + walk;
                };
                var touchEndHandler = function() {
                touchStartX = 0;
                    if (touchDidDrag) { suppressClickUntil = Date.now() + 250; }
                    touchDidDrag = false;
                    window.removeEventListener('touchmove', touchMoveHandler, true);
                    window.removeEventListener('touchend', touchEndHandler, true);
                };
                window.addEventListener('touchmove', touchMoveHandler, true);
                window.addEventListener('touchend', touchEndHandler, true);
            });
        }
    };

    // Exponer globalmente
    window.GloryHorizontalDrag = GloryHorizontalDrag;

    // Procesar cola si existe
    if (window.GloryHorizontalDragQueue && Array.isArray(window.GloryHorizontalDragQueue)) {
        window.GloryHorizontalDragQueue.forEach(function(item) {
            GloryHorizontalDrag.init(item.selector);
        });
        window.GloryHorizontalDragQueue = [];
    }

    // Re-inicializar tras recarga AJAX (gloryRecarga): ejecutar init en todos los selectores registrados
    document.addEventListener('gloryRecarga', function() {
        try {
            var regs = GloryHorizontalDrag.registeredSelectors || [];
            for (var i = 0; i < regs.length; i++) {
                var sel = regs[i];
                if (sel) { GloryHorizontalDrag.init(sel); }
            }
        } catch(_e) {}
    });

})();


