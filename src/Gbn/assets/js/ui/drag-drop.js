;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;

    var dragSrcEl = null;
    var dropLine = null;
    var currentDropTarget = null;
    var currentDropEdge = null; // 'top', 'bottom', 'left', 'right', 'inside'
    var customDragImage = null;

    function createDropLine() {
        if (dropLine) return;
        dropLine = document.createElement('div');
        dropLine.className = 'gbn-drop-line';
        document.body.appendChild(dropLine);
    }

    function removeDropLine() {
        if (dropLine && dropLine.parentNode) {
            dropLine.parentNode.removeChild(dropLine);
        }
        dropLine = null;
    }

    function updateDropLine(target, edge) {
        if (!dropLine) createDropLine();
        
        var rect = target.getBoundingClientRect();
        var scrollX = window.pageXOffset || document.documentElement.scrollLeft;
        var scrollY = window.pageYOffset || document.documentElement.scrollTop;

        dropLine.style.display = 'block';
        dropLine.className = 'gbn-drop-line'; // Reset classes

        if (edge === 'top') {
            dropLine.classList.add('horizontal');
            dropLine.style.width = rect.width + 'px';
            dropLine.style.height = '4px';
            dropLine.style.left = (rect.left + scrollX) + 'px';
            dropLine.style.top = (rect.top + scrollY - 2) + 'px';
        } else if (edge === 'bottom') {
            dropLine.classList.add('horizontal');
            dropLine.style.width = rect.width + 'px';
            dropLine.style.height = '4px';
            dropLine.style.left = (rect.left + scrollX) + 'px';
            dropLine.style.top = (rect.bottom + scrollY - 2) + 'px';
        } else if (edge === 'left') {
            dropLine.classList.add('vertical');
            dropLine.style.width = '4px';
            dropLine.style.height = rect.height + 'px';
            dropLine.style.left = (rect.left + scrollX - 2) + 'px';
            dropLine.style.top = (rect.top + scrollY) + 'px';
        } else if (edge === 'right') {
            dropLine.classList.add('vertical');
            dropLine.style.width = '4px';
            dropLine.style.height = rect.height + 'px';
            dropLine.style.left = (rect.right + scrollX - 2) + 'px';
            dropLine.style.top = (rect.top + scrollY) + 'px';
        } else if (edge === 'inside') {
            // Visualmente 'inside' (append)
            dropLine.classList.add('horizontal');
            dropLine.style.width = (rect.width - 20) + 'px';
            dropLine.style.height = '4px';
            dropLine.style.left = (rect.left + scrollX + 10) + 'px';
            dropLine.style.top = (rect.bottom + scrollY - 4) + 'px'; 
        }
    }

    function createCustomDragImage(role) {
        var el = document.createElement('div');
        el.className = 'gbn-drag-proxy';
        
        // Estilos base para el proxy
        el.style.width = '40px';
        el.style.height = '40px';
        el.style.borderRadius = '4px';
        el.style.display = 'flex';
        el.style.alignItems = 'center';
        el.style.justifyContent = 'center';
        el.style.color = 'white';
        el.style.fontWeight = 'bold';
        el.style.fontSize = '12px';
        el.style.position = 'absolute';
        el.style.top = '-9999px';
        el.style.zIndex = '100000';
        
        // Icono simple (SVG inline o texto)
        var icon = '';
        var color = '#666';

        if (role === 'principal') {
            color = '#3b82f6'; // Azul
            icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>';
        } else if (role === 'secundario') {
            color = '#f97316'; // Naranja
            icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>';
        } else {
            color = '#10b981'; // Verde (contenido)
            icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';
        }

        el.style.backgroundColor = color;
        el.innerHTML = icon;
        
        document.body.appendChild(el);
        return el;
    }

    function handleDragStart(e) {
        if (!utils.isGbnActive()) return;
        
        // IMPORTANTE: Detener propagación para evitar que el padre también inicie drag
        e.stopPropagation();
        
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
        
        // Custom Drag Image
        var role = this.getAttribute('data-gbn-role');
        customDragImage = createCustomDragImage(role);
        
        // setDragImage requiere que el elemento esté visible en el DOM (aunque sea fuera de pantalla a veces falla en algunos navegadores si está hidden)
        // Lo posicionamos fuera pero visible
        if (e.dataTransfer.setDragImage) {
            e.dataTransfer.setDragImage(customDragImage, 20, 20); // Centrado aprox
        }
    }

    function isValidDropTarget(target, source) {
        if (!target || !source) return false;
        if (target === source) return false;
        if (source.contains(target)) return false;

        var sourceRole = source.getAttribute('data-gbn-role');
        var targetRole = target.getAttribute('data-gbn-role');
        
        if (sourceRole === 'principal') {
            if (targetRole === 'principal') return true;
            return false;
        }
        
        if (sourceRole === 'secundario') {
            if (targetRole === 'secundario') return true;
            if (targetRole === 'principal') return true;
            return false;
        }

        if (sourceRole === 'content' || sourceRole === 'image' || sourceRole === 'text') {
             if (targetRole === 'secundario') return true;
             if (targetRole === 'content' || targetRole === 'image' || targetRole === 'text') return true;
        }
        
        return true;
    }

    function handleDragOver(e) {
        if (e.preventDefault) { e.preventDefault(); }
        e.dataTransfer.dropEffect = 'move';
        
        if (!utils.isGbnActive() || !dragSrcEl) return false;

        var target = e.target.closest('.gbn-node');
        
        if (!target || target === dragSrcEl || target.contains(dragSrcEl)) {
            removeDropLine();
            currentDropTarget = null;
            return false;
        }
        
        if (!isValidDropTarget(target, dragSrcEl)) {
            removeDropLine();
            currentDropTarget = null;
            return false;
        }

        currentDropTarget = target;

        var sourceRole = dragSrcEl.getAttribute('data-gbn-role');
        var targetRole = target.getAttribute('data-gbn-role');

        // Lógica de posición
        if (sourceRole === 'secundario' && targetRole === 'principal') {
            currentDropEdge = 'inside';
        } else {
            // Detección de borde más cercano
            var rect = target.getBoundingClientRect();
            var relX = e.clientX - rect.left;
            var relY = e.clientY - rect.top;
            
            var distTop = relY;
            var distBottom = rect.height - relY;
            var distLeft = relX;
            var distRight = rect.width - relX;
            
            var min = Math.min(distTop, distBottom, distLeft, distRight);
            
            if (min === distTop) currentDropEdge = 'top';
            else if (min === distBottom) currentDropEdge = 'bottom';
            else if (min === distLeft) currentDropEdge = 'left';
            else if (min === distRight) currentDropEdge = 'right';
        }

        updateDropLine(target, currentDropEdge);
        
        return false;
    }

    function handleDragEnter(e) {
        // Feedback visual opcional
    }

    function handleDragLeave(e) {
        // No limpiar aquí para evitar parpadeos
    }

    function handleDrop(e) {
        // Detener propagación para que no lo maneje el padre también
        if (e.stopPropagation) { e.stopPropagation(); }
        
        if (dragSrcEl && currentDropTarget && currentDropEdge) {
            
            var parent = currentDropTarget.parentNode;
            
            if (currentDropEdge === 'top' || currentDropEdge === 'left') {
                // Insertar antes
                parent.insertBefore(dragSrcEl, currentDropTarget);
            } else if (currentDropEdge === 'bottom' || currentDropEdge === 'right') {
                // Insertar después
                parent.insertBefore(dragSrcEl, currentDropTarget.nextSibling);
            } else if (currentDropEdge === 'inside') {
                // Append
                currentDropTarget.appendChild(dragSrcEl);
            }
            
            // Notificar cambio
            var event;
            if (typeof global.CustomEvent === 'function') {
                event = new CustomEvent('gbn:layoutChanged', { detail: { id: dragSrcEl.getAttribute('data-gbn-id') } });
            } else {
                event = document.createEvent('CustomEvent');
                event.initCustomEvent('gbn:layoutChanged', false, false, { id: dragSrcEl.getAttribute('data-gbn-id') });
            }
            global.dispatchEvent(event);
        }
        
        removeDropLine();
        return false;
    }

    function handleDragEnd(e) {
        this.style.opacity = '1';
        this.classList.remove('is-dragging');
        removeDropLine();
        
        if (customDragImage && customDragImage.parentNode) {
            customDragImage.parentNode.removeChild(customDragImage);
            customDragImage = null;
        }
        
        dragSrcEl = null;
        currentDropTarget = null;
        currentDropEdge = null;
    }

    function enableDragAndDrop() {
        var items = document.querySelectorAll('.gbn-node');
        items.forEach(function(item) {
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
