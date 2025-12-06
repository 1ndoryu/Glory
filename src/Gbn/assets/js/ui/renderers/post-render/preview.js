;(function (global) {
    'use strict';

    /**
     * POST RENDER - PREVIEW MODULE
     * 
     * Maneja la solicitud de preview al backend y renderizado de posts.
     * 
     * @module Gbn.ui.renderers.postRender.preview
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};
    Gbn.ui.renderers.postRenderModules = Gbn.ui.renderers.postRenderModules || {};

    /**
     * Solicita un preview del contenido desde el backend.
     * Se usa cuando cambian propiedades de query (postType, order, etc.)
     * 
     * @param {Object} block Referencia al bloque
     */
    function requestPreview(block) {
        if (!block || !block.element) return;
        
        // Obtener el bloque fresco del store para tener la config actualizada
        var freshBlock = Gbn.state.get(block.id);
        if (freshBlock) {
            block = freshBlock;
        }
        
        if (!block.config) return;

        var el = block.element;
        var config = block.config;
        
        console.log('[PostRender] Requesting preview with config:', config);

        // Mostrar indicador de carga
        el.classList.add('gbn-loading');

        // Obtener configuración AJAX
        var ajaxUrl = (global.gloryGbnCfg && global.gloryGbnCfg.ajaxUrl) || '/wp-admin/admin-ajax.php';
        var nonce = (global.gloryGbnCfg && global.gloryGbnCfg.nonce) || '';

        // Preparar datos para la petición
        var formData = new FormData();
        formData.append('action', 'gbn_post_render_preview');
        formData.append('nonce', nonce);
        formData.append('config', JSON.stringify(config));
        formData.append('limit', '3'); // Límite de posts para preview

        // Hacer petición AJAX
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            el.classList.remove('gbn-loading');

            if (data.success && data.data && data.data.posts) {
                renderPreviewPosts(block, data.data.posts);
            } else {
                renderPreviewError(block, data.data ? data.data.message : 'Error desconocido');
            }
        })
        .catch(function(error) {
            el.classList.remove('gbn-loading');
            console.error('[PostRender] Preview error:', error);
            renderPreviewError(block, 'Error de conexión');
        });
    }

    /**
     * Renderiza los posts para preview en el editor.
     * 
     * @param {Object} block Referencia al bloque
     * @param {Array} posts Array de posts obtenidos del backend
     */
    function renderPreviewPosts(block, posts) {
        var el = block.element;
        var config = block.config;

        // Referencia a módulos
        var clones = Gbn.ui.renderers.postRenderModules.clones;
        var fields = Gbn.ui.renderers.postRenderModules.fields;

        // Limpiar preview anterior
        if (clones && clones.cleanup) {
            clones.cleanup(el);
        }

        // Buscar el template original (gloryPostItem) - case-insensitive
        var template = el.querySelector('[gloryPostItem]:not([data-gbn-pr-clone])') || 
                       el.querySelector('[glorypostitem]:not([data-gbn-pr-clone])');

        if (!template) {
            var msg = document.createElement('div');
            msg.className = 'gbn-pr-preview-message';
            msg.style.cssText = 'padding: 20px; text-align: center; background: #f0f0f0; border-radius: 8px; color: #666;';
            msg.innerHTML = '<p style="margin:0">📋 <strong>' + posts.length + '</strong> ' + 
                           (config.postType || 'post') + '(s) encontrados</p>' +
                           '<p style="margin:8px 0 0; font-size: 12px;">Agrega un gloryPostItem para definir la estructura</p>';
            el.appendChild(msg);
            return;
        }

        // Guardar estructura original del template para persistencia
        if (!template.hasAttribute('data-gbn-original-structure')) {
            template.setAttribute('data-gbn-original-structure', template.innerHTML);
        }

        // El template es el primer item editable
        template.style.display = '';
        template.setAttribute('data-gbn-is-template', 'true');
        template.removeAttribute('data-gbn-pr-clone');
        
        // Poblar template con datos del primer post
        if (posts.length > 0 && fields && fields.populateTemplateWithPreview) {
            fields.populateTemplateWithPreview(template, posts[0]);
        }
        
        // Crear clones para los DEMÁS posts (a partir del segundo)
        var clonesCreated = [];
        if (clones && clones.createSyncedClone) {
            for (var i = 1; i < posts.length; i++) {
                var clone = clones.createSyncedClone(template, posts[i]);
                el.appendChild(clone);
                clonesCreated.push(clone);
            }
        }
        
        // Configurar MutationObserver para sincronizar cambios
        if (clones && clones.setupTemplateObserver) {
            clones.setupTemplateObserver(el, template, clonesCreated, posts);
        }

        // RE-ESCANEAR ELEMENTOS DEL TEMPLATE PARA HACERLOS INTERACTIVOS
        if (Gbn.content && Gbn.content.scan) {
            setTimeout(function() {
                try {
                    var newBlocks = Gbn.content.scan(template);
                    console.log('[PostRender] Re-escaneados ' + newBlocks.length + ' elementos del template');
                    
                    if (typeof global.CustomEvent === 'function') {
                        var event = new CustomEvent('gbn:blocksAdded', { 
                            detail: { blocks: newBlocks, source: 'postRender' } 
                        });
                        global.dispatchEvent(event);
                    }
                } catch (e) {
                    console.warn('[PostRender] Error al re-escanear template:', e);
                }
            }, 100);
        }

        console.log('[PostRender] Preview WYSIWYG: 1 template editable + ' + clonesCreated.length + ' clones sincronizados');
    }

    /**
     * Muestra un mensaje de error en el preview.
     * 
     * @param {Object} block Referencia al bloque
     * @param {string} message Mensaje de error
     */
    function renderPreviewError(block, message) {
        var el = block.element;

        // Limpiar mensaje anterior
        var existingMessage = el.querySelector('.gbn-pr-preview-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        var msg = document.createElement('div');
        msg.className = 'gbn-pr-preview-message gbn-pr-error';
        msg.style.cssText = 'padding: 20px; text-align: center; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; color: #856404;';
        msg.innerHTML = '<p style="margin:0">⚠️ ' + message + '</p>';
        el.appendChild(msg);
    }

    // Exportar módulo
    Gbn.ui.renderers.postRenderModules.preview = {
        requestPreview: requestPreview,
        renderPreviewPosts: renderPreviewPosts,
        renderPreviewError: renderPreviewError
    };

})(typeof window !== 'undefined' ? window : this);
