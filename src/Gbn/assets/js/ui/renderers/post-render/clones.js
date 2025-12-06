;(function (global) {
    'use strict';

    /**
     * POST RENDER - CLONES MODULE
     * 
     * Maneja la creación, sincronización y gestión de clones
     * para el preview WYSIWYG del PostRender.
     * 
     * @module Gbn.ui.renderers.postRender.clones
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};
    Gbn.ui.renderers.postRenderModules = Gbn.ui.renderers.postRenderModules || {};

    /**
     * Crea un clon del template poblado con datos de un post.
     * El clon reflejará cambios estructurales del template.
     * 
     * IMPORTANTE: Los clones son solo visuales - no interactivos.
     * Se deshabilita pointer-events para evitar confusión y eventos hover innecesarios.
     * 
     * @param {HTMLElement} template Elemento template (PostItem original)
     * @param {Object} post Datos del post
     * @returns {HTMLElement} Clon poblado
     */
    function createSyncedClone(template, post) {
        // Referencia al módulo de fields para poblar datos
        var fields = Gbn.ui.renderers.postRenderModules.fields;
        
        var clone = template.cloneNode(true);
        clone.setAttribute('data-gbn-pr-clone', 'true');
        clone.removeAttribute('data-gbn-is-template');
        clone.removeAttribute('data-gbn-original-structure');
        
        // DESHABILITAR INTERACCIÓN EN CLONES
        // Los clones son solo para preview visual, no deben ser editables
        // Esto previene eventos hover/click que causan parpadeo con el MutationObserver
        clone.style.pointerEvents = 'none';
        clone.style.opacity = '0.85'; // Ligeramente atenuado para indicar que es clon
        clone.style.cursor = 'default';
        
        // Limpiar atributos de preview del template original
        var previewFields = clone.querySelectorAll('[data-gbn-preview-field]');
        previewFields.forEach(function(field) {
            field.removeAttribute('data-gbn-original-content');
            field.removeAttribute('data-gbn-preview-field');
        });
        
        // Limpiar IDs de GBN de los clones para evitar conflictos con el store
        clone.removeAttribute('data-gbn-id');
        var gbnElements = clone.querySelectorAll('[data-gbn-id]');
        gbnElements.forEach(function(el) {
            el.removeAttribute('data-gbn-id');
        });
        
        // Poblar con datos del post correspondiente
        if (fields && fields.populateCloneFields) {
            fields.populateCloneFields(clone, post);
        }
        
        return clone;
    }
    
    /**
     * Sincroniza la estructura del template a todos los clones.
     * Preserva los datos de cada post mientras actualiza la estructura.
     * 
     * OPTIMIZACIÓN: Debounce de 300ms para evitar sincronización excesiva
     * durante interacciones del usuario (drag, edición de texto, etc.)
     * 
     * @param {HTMLElement} template Elemento template
     * @param {Array} clones Array de clones creados
     * @param {Array} posts Array de posts
     */
    function syncTemplateToClones(template, clones, posts) {
        // Debounce para evitar múltiples actualizaciones rápidas
        if (template._syncTimeout) {
            clearTimeout(template._syncTimeout);
        }
        
        // Flag para prevenir sincronización recursiva
        if (template._isSyncing) {
            return;
        }
        
        template._syncTimeout = setTimeout(function() {
            // Marcar inicio de sincronización
            template._isSyncing = true;
            
            try {
                clones.forEach(function(clone, index) {
                    var postIndex = index + 1; // Clones empiezan desde el segundo post
                    if (posts[postIndex] && clone.parentNode) {
                        // Recrear el clon con la estructura actual del template
                        var newClone = createSyncedClone(template, posts[postIndex]);
                        clone.parentNode.replaceChild(newClone, clone);
                        clones[index] = newClone;
                    }
                });
            } finally {
                // Fin de sincronización (con pequeño delay para evitar falsos positivos)
                setTimeout(function() {
                    template._isSyncing = false;
                }, 50);
            }
        }, 300); // Aumentado de 100ms a 300ms para mayor estabilidad
    }

    /**
     * Configura MutationObserver para sincronizar cambios del template a los clones.
     * 
     * @param {HTMLElement} container Elemento contenedor PostRender
     * @param {HTMLElement} template Elemento template
     * @param {Array} clones Array de clones creados
     * @param {Array} posts Array de posts
     */
    function setupTemplateObserver(container, template, clones, posts) {
        // Desconectar observer anterior si existe
        if (container._gbnTemplateObserver) {
            container._gbnTemplateObserver.disconnect();
            container._gbnTemplateObserver = null;
        }

        if (clones.length === 0) return;

        var observer = new MutationObserver(function(mutations) {
            // FILTRAR MUTACIONES IRRELEVANTES
            var hasRelevantMutation = mutations.some(function(mutation) {
                // Cambios estructurales (elementos agregados/removidos)
                if (mutation.type === 'childList') {
                    var target = mutation.target;
                    // Ignorar si el target es un clon o parte de un clon
                    if (target.hasAttribute && target.hasAttribute('data-gbn-pr-clone')) {
                        return false;
                    }
                    if (target.closest && target.closest('[data-gbn-pr-clone]')) {
                        return false;
                    }
                    return true;
                }
                
                // Cambios de atributos: filtrar los del editor GBN
                if (mutation.type === 'attributes') {
                    var attrName = mutation.attributeName;
                    var target = mutation.target;
                    
                    if (!attrName) return false;
                    
                    // Ignorar cambios en atributos de datos internos de GBN
                    if (attrName.indexOf('data-gbn-') === 0) {
                        return false;
                    }
                    
                    // Ignorar cambios de clase del editor (selección, hover)
                    if (attrName === 'class') {
                        var classes = target.className || '';
                        if (classes.includes('gbn-selected') || 
                            classes.includes('gbn-hovered') ||
                            classes.includes('gbn-simulated')) {
                            return false;
                        }
                    }
                    
                    // Ignorar cambios de style (temporales)
                    if (attrName === 'style') {
                        return false;
                    }
                    
                    // Otros atributos sí son relevantes (src, href, etc.)
                    return true;
                }
                
                return false;
            });
            
            if (hasRelevantMutation) {
                syncTemplateToClones(template, clones, posts);
            }
        });
        
        observer.observe(template, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'src', 'href', 'alt']
        });
        
        // Guardar referencia para poder desconectar después
        container._gbnTemplateObserver = observer;
    }

    /**
     * Limpia todos los clones y observers de un contenedor.
     * 
     * @param {HTMLElement} container Elemento contenedor PostRender
     */
    function cleanup(container) {
        // Desconectar observer
        if (container._gbnTemplateObserver) {
            container._gbnTemplateObserver.disconnect();
            container._gbnTemplateObserver = null;
        }

        // Limpiar clones anteriores
        var clones = container.querySelectorAll('[data-gbn-pr-clone]');
        clones.forEach(function(clone) {
            clone.remove();
        });

        // Limpiar mensajes de preview
        var existingMessage = container.querySelector('.gbn-pr-preview-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        var existingIndicator = container.querySelector('.gbn-pr-posts-indicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }
    }

    // Exportar módulo
    Gbn.ui.renderers.postRenderModules.clones = {
        createSyncedClone: createSyncedClone,
        syncTemplateToClones: syncTemplateToClones,
        setupTemplateObserver: setupTemplateObserver,
        cleanup: cleanup
    };

})(typeof window !== 'undefined' ? window : this);
