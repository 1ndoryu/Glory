;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var content = Gbn.content;

    var modal = null;
    var currentTarget = null;
    var insertPosition = 'append'; // 'append', 'after'

    var COMPONENT_TEMPLATES = {
        section: {
            label: 'Sección (divPrincipal)',
            role: 'principal',
            html: '<div gloryDiv class="divPrincipal"><div gloryDivSecundario class="divSecundario"></div></div>'
        },
        container: {
            label: 'Contenedor (divSecundario)',
            role: 'secundario',
            html: '<div gloryDivSecundario class="divSecundario"></div>'
        },
        content_list: {
            label: 'Lista de Entradas',
            role: 'content',
            html: '<div gloryContentRender="post" opciones="publicacionesPorPagina: 3, claseContenedor: \'gbn-content-grid\', claseItem: \'gbn-content-card\'"></div>'
        },
        term_list: {
            label: 'Lista de Términos',
            role: 'term_list',
            html: '<div gloryTermRender="category" opciones="numero: 5"></div>'
        },
        image: {
            label: 'Imagen',
            role: 'image',
            html: '<div gloryImage="1" opciones="image_url: \'https://via.placeholder.com/300\'"></div>'
        },
        text: {
            label: 'Texto (gloryTexto)',
            role: 'text',
            html: '<div gloryTexto="p" opciones="texto: \'Nuevo texto\'">Nuevo texto</div>'
        }
    };

    function createModal() {
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = 'gbn-library-modal';
        modal.className = 'gbn-modal';
        modal.innerHTML = 
            '<div class="gbn-modal-overlay"></div>' +
            '<div class="gbn-modal-content">' +
                '<div class="gbn-modal-header">' +
                    '<h3>Biblioteca de Componentes</h3>' +
                    '<button class="gbn-modal-close">&times;</button>' +
                '</div>' +
                '<div class="gbn-modal-body">' +
                    '<div class="gbn-library-grid" id="gbn-library-grid"></div>' +
                '</div>' +
            '</div>';

        document.body.appendChild(modal);

        modal.querySelector('.gbn-modal-close').addEventListener('click', close);
        modal.querySelector('.gbn-modal-overlay').addEventListener('click', close);

        return modal;
    }

    function renderLibrary(allowedRoles) {
        var grid = modal.querySelector('#gbn-library-grid');
        grid.innerHTML = '';

        Object.keys(COMPONENT_TEMPLATES).forEach(function (key) {
            var tpl = COMPONENT_TEMPLATES[key];
            // Filtrar según contexto
            if (allowedRoles && allowedRoles.length > 0 && allowedRoles.indexOf(tpl.role) === -1) {
                return;
            }

            var item = document.createElement('div');
            item.className = 'gbn-library-item';
            item.textContent = tpl.label;
            item.addEventListener('click', function () {
                insertComponent(key);
            });
            grid.appendChild(item);
        });
    }

    function insertComponent(key) {
        var tpl = COMPONENT_TEMPLATES[key];
        if (!tpl || !currentTarget) return;

        var temp = document.createElement('div');
        temp.innerHTML = tpl.html;
        var newEl = temp.firstElementChild;

        if (insertPosition === 'append') {
            currentTarget.appendChild(newEl);
        } else if (insertPosition === 'after') {
            currentTarget.parentNode.insertBefore(newEl, currentTarget.nextSibling);
        }

        // Escanear e hidratar el nuevo bloque
        // content.scan devuelve un array de bloques
        var newBlocks = content.scan(newEl.parentNode); 
        // Nota: scan escanea todo el padre, así que puede devolver bloques ya existentes.
        // Pero hydrate es idempotente si se maneja bien.
        // Sin embargo, queremos identificar SOLO los nuevos para el evento.
        // Una mejor estrategia es escanear solo el nuevo elemento si es posible, 
        // pero scan suele requerir contexto.
        // Dado que scan devuelve TODOS los bloques encontrados en el root pasado,
        // podemos filtrar los que correspondan a newEl o sus hijos.
        
        // Simplificación: scan devuelve todos. Hydrate los registra.
        // Vamos a recoger los IDs de los bloques que corresponden a newEl y sus descendientes.
        
        var hydrationResult = content.hydrate(newBlocks); // Asumimos que hydrate devuelve algo o actualiza state
        
        // Buscar los bloques recién creados.
        // Podemos usar state.getByElement(newEl)
        var createdBlock = Gbn.state.getByElement(newEl);
        var createdIds = [];
        if (createdBlock) {
            createdIds.push(createdBlock.id);
            // Si tiene hijos, también.
            var descendants = newEl.querySelectorAll('[data-gbn-role]');
            descendants.forEach(function(child) {
                var childBlock = Gbn.state.getByElement(child);
                if (childBlock) createdIds.push(childBlock.id);
            });
        }

        // Notificar cambio
        var event;
        if (typeof global.CustomEvent === 'function') {
            event = new CustomEvent('gbn:contentHydrated', { detail: { ids: createdIds } });
        } else {
            event = document.createEvent('CustomEvent');
            event.initCustomEvent('gbn:contentHydrated', false, false, { ids: createdIds });
        }
        global.dispatchEvent(event);

        close();
    }

    function open(target, position, allowed) {
        createModal();
        currentTarget = target;
        insertPosition = position || 'append';
        renderLibrary(allowed);
        modal.classList.add('is-open');
    }

    function close() {
        if (modal) {
            modal.classList.remove('is-open');
        }
        currentTarget = null;
    }

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.library = {
        open: open,
        close: close
    };

})(window);
