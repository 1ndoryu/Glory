// Agnóstico: gestiona acciones sobre items de ContentRender.
(function () {
    if (typeof window === 'undefined') return;

    function getItemPostId(el) {
        const m = (el.id || '').match(/post-(\d+)/);
        if (m) return parseInt(m[1], 10);
        const byClass = (el.className || '').match(/post-id-(\d+)/);
        if (byClass) return parseInt(byClass[1], 10);
        const dataId = el.getAttribute && el.getAttribute('data-post-id');
        if (dataId) return parseInt(dataId, 10);
        const attrIdPost = el.getAttribute && el.getAttribute('id-post');
        if (attrIdPost) return parseInt(attrIdPost, 10);
        return null;
    }

    function closestItem(el, selector) {
        if (!el) return null;
        if (!selector) selector = '[id^="post-"]';
        const found = el.closest(selector);
        if (found) return found;
        // fallback genérico:
        return el.closest('[id^="post-"], [class*="post-id-"], [id-post]');
    }

    async function eliminarItem(postId, postType, modo) {
        try {
            const res = await (window.gloryAjax ? window.gloryAjax('glory_content_action', {accion: 'eliminar', postId, postType, modo}) : Promise.resolve({success: false}));
            if (!res || !res.success) return false;
            return true;
        } catch (e) {
            return false;
        }
    }

    function refrescarLista(contenedor) {
        const postType = contenedor.dataset.postType || 'post';
        const publicacionesPorPagina = parseInt(contenedor.dataset.publicacionesPorPagina || '10', 10);
        const claseContenedor = contenedor.dataset.claseContenedor || contenedor.className.split(/\s+/)[0] || 'glory-content-list';
        const claseItem = contenedor.dataset.claseItem || 'glory-content-item';
        const plantilla = contenedor.dataset.templateCallback || null;
        if (typeof window.reiniciarContenido === 'function') {
            const resultado = window.reiniciarContenido({postType, publicacionesPorPagina, claseContenedor, claseItem, plantilla, argumentosConsulta: {}});
            const finalizar = function () {
                // Asegurar que se oculta cualquier overlay y se re-inicializan listeners tras el refresh
                try { if (typeof window.ocultarFondo === 'function') { window.ocultarFondo(); } } catch(_) {}
                // Reenganchar todos los manejadores de tareas (clicks, edición, etc.)
                try { if (typeof window.initTareas === 'function') { window.initTareas(); } } catch(_) {}
                try { window.dispatchEvent(new Event('reiniciar')); } catch(_) {}
            };
            if (resultado && typeof resultado.then === 'function') {
                return resultado.then(() => { finalizar(); return true; });
            } else {
                finalizar();
                return resultado;
            }
        }
        return null;
    }

    function asegurarSubmenu(item) {
        if (!item) return null;
        // Si el item ya es disparador de submenú, devolverlo
        if (item.hasAttribute('data-submenu')) return item;
        const postId = getItemPostId(item) || Math.random().toString(36).slice(2);
        const menuId = 'glory-submenu-' + postId;
        let menu = document.getElementById(menuId);
        if (!menu) {
            menu = document.createElement('div');
            menu.id = menuId;
            // Incluir clase solicitada 'submenu' y la reconocida por el core 'glory-submenu'
            menu.className = 'submenu glory-submenu';
            // Contenido mínimo con acción eliminar
            menu.innerHTML = '<button type="button" data-accion="eliminar">Eliminar</button>';
            document.body.appendChild(menu);
            // Bind acción eliminar una sola vez
            menu.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-accion]');
                if (!btn) return;
                const accion = btn.getAttribute('data-accion');
                const contenedor = item.closest('[data-post-type]');
                const postType = contenedor ? contenedor.dataset.postType || 'post' : 'post';
                const pid = getItemPostId(item);
                if (accion === 'eliminar' && pid) {
                    eliminarItem(pid, postType, 'trash').then(ok => {
                        if (ok) refrescarLista(contenedor);
                    });
                }
                // Cerrar el submenú tras ejecutar una acción
                try { if (typeof window.gloryCerrarSubmenus === 'function') { window.gloryCerrarSubmenus(); } else if (typeof submenus !== 'undefined') { submenus.iniciar().cerrar(); } } catch(_) {}
                if (typeof window.ocultarFondo === 'function') { try { window.ocultarFondo(); } catch(_) {} }
            });
        }
        // Hacer al propio item el disparador esperado por submenus.js
        item.setAttribute('data-submenu', menuId);
        item.setAttribute('data-evento', 'contextmenu');
        return item;
    }

    function abrirSubmenuParaItem(item, evento) {
        const disparador = asegurarSubmenu(item);
        if (disparador && (typeof submenus !== 'undefined')) {
            try {
                const x = (evento && evento.clientX) || null;
                const y = (evento && evento.clientY) || null;
                // Asegurar que no haya otro submenú abierto
                try { submenus.iniciar().cerrar(); } catch(_) {}
                submenus.iniciar().abrir(disparador, { cursor: (x !== null && y !== null) ? { x, y } : null });
                return;
            } catch (_) {}
        }
        // Fallback: mostrar menú básico posicionado en el cursor
        const menuId = disparador && disparador.getAttribute('data-submenu');
        const menu = menuId ? document.getElementById(menuId) : null;
        if (!menu) return;
        const x = (evento && evento.clientX) || window.innerWidth / 2;
        const y = (evento && evento.clientY) || window.innerHeight / 2;
        menu.style.position = 'fixed';
        menu.style.left = x + 'px';
        menu.style.top = y + 'px';
        menu.style.display = 'block';
        menu.classList.add('activo');
        if (typeof window.mostrarFondo === 'function') { try { window.mostrarFondo(); } catch(_) {} }
        if (!menu.__fallbackBound) {
            // Cerrar al hacer clic dentro del menú (acción) en modo fallback
            menu.addEventListener('click', function () {
                menu.style.display = '';
                menu.classList.remove('activo');
                if (typeof window.ocultarFondo === 'function') { try { window.ocultarFondo(); } catch(_) {} }
            });
            menu.__fallbackBound = true;
        }
        const onDoc = function (e) {
            if (!menu.contains(e.target)) {
                menu.style.display = '';
                menu.classList.remove('activo');
                document.removeEventListener('click', onDoc, true);
                if (typeof window.ocultarFondo === 'function') { try { window.ocultarFondo(); } catch(_) {} }
            }
        };
        document.addEventListener('click', onDoc, true);
    }

    function gestionarDblClick(contenedor, evento) {
        const selector = contenedor.dataset.itemSelector || '[id^="post-"]';
        const item = closestItem(evento.target, selector);
        if (!item) return;
        const postId = getItemPostId(item);
        if (!postId) return;

        const acciones = (contenedor.dataset.contentActions || '')
            .split(',')
            .map(s => s.trim())
            .filter(Boolean);
        const submenuEnabled = contenedor.dataset.submenuEnabled === '1';
        const postType = contenedor.dataset.postType || 'post';

        if (submenuEnabled) {
            abrirSubmenuParaItem(item, evento);
            return;
        }
        if (acciones.includes('eliminar')) {
            eliminarItem(postId, postType, 'trash').then(ok => {
                if (ok) refrescarLista(contenedor);
            });
        }
    }

    function init() {
        const contenedores = document.querySelectorAll('[data-post-type]');
        contenedores.forEach(cont => {
            const evento = cont.dataset.accionEvento || 'dblclick';
            if (cont.__gloryActionsBound) return;
            cont.__gloryActionsBound = true;
            if (evento === 'dblclick') {
                cont.addEventListener('dblclick', gestionarDblClick.bind(null, cont));
            } else if (evento === 'click') {
                cont.addEventListener('click', gestionarDblClick.bind(null, cont));
            } else if (evento === 'longpress') {
                let timer = null;
                cont.addEventListener('pointerdown', e => {
                    timer = setTimeout(() => gestionarDblClick(cont, e), 500);
                });
                cont.addEventListener('pointerup', () => {
                    if (timer) clearTimeout(timer);
                });
                cont.addEventListener('pointermove', () => {
                    if (timer) clearTimeout(timer);
                });
            }

            // Click derecho: asegurar atributos y abrir el submenú en el punto del clic
            cont.addEventListener('contextmenu', function (e) {
                const selector = cont.dataset.itemSelector || '[id^="post-"]';
                let item = closestItem(e.target, selector) || e.target.closest('[id-post]') || e.target.closest('.draggable-element');
                if (!item) return;
                e.preventDefault();
                e.stopPropagation();
                abrirSubmenuParaItem(item, e);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    window.addEventListener('reiniciar', init);
})();
