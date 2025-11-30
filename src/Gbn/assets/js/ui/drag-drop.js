;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;

    var dragSrcEl = null;
    var placeholder = null;

    function handleDragStart(e) {
        if (!utils.isGbnActive()) return;
        
        // No arrastrar si se hace clic en controles
        if (e.target.closest('.gbn-controls-group') || e.target.closest('button') || e.target.closest('input')) {
            e.preventDefault();
            return;
        }

        this.style.opacity = '0.4';
        this.classList.add('is-dragging');
        dragSrcEl = this;

        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.innerHTML);
        
        // Crear placeholder visual mejorado
        placeholder = document.createElement('div');
        placeholder.className = 'gbn-sortable-placeholder';
        // Copiar dimensiones aproximadas
        var rect = this.getBoundingClientRect();
        placeholder.style.height = rect.height + 'px';
        placeholder.style.width = '100%';
    }

    function isValidDropTarget(target, source) {
        if (!target || !source) return false;
        if (target === source) return false;
        if (source.contains(target)) return false; // No soltar padre dentro de hijo

        var sourceRole = source.getAttribute('data-gbn-role');
        var targetRole = target.getAttribute('data-gbn-role');
        
        // Reglas de anidamiento
        if (sourceRole === 'principal') {
            // Principal solo puede moverse entre otros principales (hermanos)
            // No puede ir dentro de secundario
            if (targetRole === 'secundario') return false;
            // Si el target es principal, ok (hermanos)
        }
        
        // Evitar soltar secundario directamente en root si no es permitido (depende del diseño)
        
        return true;
    }

    function handleDragOver(e) {
        if (e.preventDefault) { e.preventDefault(); }
        e.dataTransfer.dropEffect = 'move';
        
        if (!utils.isGbnActive() || !dragSrcEl) return false;

        var target = e.target.closest('.gbn-node');
        
        // Si no hay target válido o es el mismo, o reglas de negocio fallan
        if (!target || target === dragSrcEl || target.contains(dragSrcEl)) return false;
        
        if (!isValidDropTarget(target, dragSrcEl)) return false;

        // Calcular posición relativa
        var rect = target.getBoundingClientRect();
        var relY = e.clientY - rect.top;
        var height = rect.bottom - rect.top;
        var parent = target.parentNode;
        
        // Determinar si insertar antes o después
        if (relY > height / 2) {
            parent.insertBefore(placeholder, target.nextSibling);
        } else {
            parent.insertBefore(placeholder, target);
        }
        
        return false;
    }

    function handleDragEnter(e) {
        // Feedback visual opcional en el target
    }

    function handleDragLeave(e) {
        // Limpiar feedback
    }

    function handleDrop(e) {
        if (e.stopPropagation) { e.stopPropagation(); }
        
        if (dragSrcEl && placeholder && placeholder.parentNode) {
            // Mover el elemento real a la posición del placeholder
            placeholder.parentNode.insertBefore(dragSrcEl, placeholder);
            placeholder.parentNode.removeChild(placeholder);
            
            // Notificar cambio de layout
            var event;
            if (typeof global.CustomEvent === 'function') {
                event = new CustomEvent('gbn:layoutChanged', { detail: { id: dragSrcEl.getAttribute('data-gbn-id') } });
            } else {
                event = document.createEvent('CustomEvent');
                event.initCustomEvent('gbn:layoutChanged', false, false, { id: dragSrcEl.getAttribute('data-gbn-id') });
            }
            global.dispatchEvent(event);
        }
        
        return false;
    }

    function handleDragEnd(e) {
        this.style.opacity = '1';
        this.classList.remove('is-dragging');
        
        if (placeholder && placeholder.parentNode) {
            placeholder.parentNode.removeChild(placeholder);
        }
        
        dragSrcEl = null;
        placeholder = null;
    }

    function enableDragAndDrop() {
        var items = document.querySelectorAll('.gbn-node');
        items.forEach(function(item) {
            // Permitir arrastrar TODOS los nodos GBN
            item.setAttribute('draggable', true);
            item.addEventListener('dragstart', handleDragStart, false);
            item.addEventListener('dragenter', handleDragEnter, false);
            item.addEventListener('dragover', handleDragOver, false);
            item.addEventListener('dragleave', handleDragLeave, false);
            item.addEventListener('drop', handleDrop, false);
            item.addEventListener('dragend', handleDragEnd, false);
        });
    }

    function disableDragAndDrop() {
        var items = document.querySelectorAll('.gbn-node');
        items.forEach(function(item) {
            item.removeAttribute('draggable');
            item.removeEventListener('dragstart', handleDragStart);
            item.removeEventListener('dragenter', handleDragEnter);
            item.removeEventListener('dragover', handleDragOver);
            item.removeEventListener('dragleave', handleDragLeave);
            item.removeEventListener('drop', handleDrop);
            item.removeEventListener('dragend', handleDragEnd);
        });
    }

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.dragDrop = {
        enable: enableDragAndDrop,
        disable: disableDragAndDrop
    };

})(window);
