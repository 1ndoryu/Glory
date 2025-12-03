;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var content = Gbn.content;

    var modal = null;
    var currentTarget = null;
    var insertPosition = 'append'; // 'append', 'after'

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

        var config = utils.getConfig();
        var components = config.containers || {};

        Object.keys(components).forEach(function (key) {
            var comp = components[key];
            // Filtrar según contexto
            if (allowedRoles && allowedRoles.length > 0 && allowedRoles.indexOf(comp.role) === -1) {
                return;
            }

            var item = document.createElement('div');
            item.className = 'gbn-library-item';
            
            var iconHtml = comp.icon || '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>';
            
            item.innerHTML = 
                '<div class="gbn-library-icon">' + iconHtml + '</div>' +
                '<div class="gbn-library-label">' + (comp.label || comp.role) + '</div>';
                
            item.addEventListener('click', function () {
                insertComponent(key);
            });
            grid.appendChild(item);
        });
    }

    function insertComponent(key) {
        var config = utils.getConfig();
        var components = config.containers || {};
        var comp = components[key];
        
        if (!comp || !currentTarget) return;

        var html = comp.template;
        if (!html) {
            utils.error('Library: No template for component ' + key);
            return;
        }

        var temp = document.createElement('div');
        temp.innerHTML = html;
        var newEl = temp.firstElementChild;

        if (insertPosition === 'append') {
            currentTarget.appendChild(newEl);
        } else if (insertPosition === 'after') {
            currentTarget.parentNode.insertBefore(newEl, currentTarget.nextSibling);
        } else if (insertPosition === 'before') {
            currentTarget.parentNode.insertBefore(newEl, currentTarget);
        }

        // Escanear e hidratar el nuevo bloque
        var newBlocks = content.scan(newEl.parentNode); 
        var hydrationResult = content.hydrate(newBlocks);
        
        // Buscar los bloques recién creados.
        var createdBlock = Gbn.state.getByElement(newEl);
        var createdIds = [];
        if (createdBlock) {
            createdIds.push(createdBlock.id);
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
