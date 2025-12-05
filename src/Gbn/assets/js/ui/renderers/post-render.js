;(function (global) {
    'use strict';

    /**
     * POST RENDER RENDERER
     * 
     * Renderer para el componente contenedor PostRender.
     * Maneja el layout del grid/flex y solicita preview desde el backend.
     * 
     * @module Gbn.ui.renderers.postRender
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    // Referencia a traits para funciones compartidas
    var traits = Gbn.ui.renderers.traits;

    /**
     * Genera estilos CSS para el contenedor PostRender.
     * 
     * @param {Object} config Configuración del bloque
     * @param {Object} block Referencia al bloque
     * @returns {Object} Estilos CSS como objeto
     */
    function getStyles(config, block) {
        var styles = traits.getCommonStyles(config);

        // Layout del contenedor según displayMode
        var displayMode = config.displayMode || 'grid';

        if (displayMode === 'grid') {
            var columns = parseInt(config.gridColumns, 10) || 3;
            var gap = config.gap || '20px';

            styles['display'] = 'grid';
            styles['grid-template-columns'] = 'repeat(' + columns + ', 1fr)';
            styles['gap'] = traits.normalizeSize(gap);
        } else if (displayMode === 'flex') {
            styles['display'] = 'flex';
            styles['flex-direction'] = config.flexDirection || 'row';
            styles['flex-wrap'] = config.flexWrap || 'wrap';
            styles['align-items'] = config.alignItems || 'stretch';
            styles['justify-content'] = config.justifyContent || 'flex-start';
            styles['gap'] = traits.normalizeSize(config.gap) || '20px';
        } else {
            styles['display'] = 'block';
        }

        return styles;
    }

    /**
     * Maneja actualizaciones en tiempo real del componente.
     * 
     * @param {Object} block Referencia al bloque
     * @param {string} path Path de la propiedad modificada
     * @param {*} value Nuevo valor
     * @returns {boolean} true si se manejó la actualización
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;

        // === PROPIEDADES DE LAYOUT ===
        
        if (path === 'displayMode') {
            // Cambiar modo de visualización
            applyDisplayMode(el, value, block.config);
            return true;
        }

        if (path === 'gridColumns') {
            if (block.config.displayMode === 'grid') {
                var cols = parseInt(value, 10) || 3;
                el.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
            }
            return true;
        }

        if (path === 'gap') {
            el.style.gap = traits.normalizeSize(value) || '20px';
            return true;
        }

        // Flex options
        if (path === 'flexDirection') {
            el.style.flexDirection = value || 'row';
            return true;
        }

        if (path === 'flexWrap') {
            el.style.flexWrap = value || 'wrap';
            return true;
        }

        if (path === 'alignItems') {
            el.style.alignItems = value || 'stretch';
            return true;
        }

        if (path === 'justifyContent') {
            el.style.justifyContent = value || 'flex-start';
            return true;
        }

        // === PROPIEDADES DE QUERY - Requieren refresh del preview ===
        
        var queryProps = ['postType', 'postsPerPage', 'orderBy', 'order', 'status', 'offset', 'postIn', 'postNotIn'];
        if (queryProps.indexOf(path) !== -1) {
            // Solicitar nuevo preview cuando cambia la query
            requestPreview(block);
            return true;
        }

        // === LAYOUT PATTERN ===
        
        if (path === 'layoutPattern') {
            applyLayoutPattern(el, value);
            return true;
        }

        // === HOVER EFFECT ===
        
        if (path === 'hoverEffect') {
            applyHoverEffect(el, value);
            return true;
        }

        // === DELEGAR A TRAITS COMUNES ===
        return traits.handleCommonUpdate(el, path, value);
    }

    /**
     * Aplica el patrón de layout al contenedor.
     * 
     * @param {HTMLElement} el Elemento contenedor
     * @param {string} pattern Patrón (none, alternado_lr, masonry)
     */
    function applyLayoutPattern(el, pattern) {
        // Limpiar patrones previos
        el.removeAttribute('data-pattern');
        
        if (pattern && pattern !== 'none') {
            el.setAttribute('data-pattern', pattern);
        }
    }

    /**
     * Aplica el efecto hover a los items del contenedor.
     * 
     * @param {HTMLElement} el Elemento contenedor
     * @param {string} effect Efecto (none, lift, scale, glow)
     */
    function applyHoverEffect(el, effect) {
        // Limpiar efectos previos de todos los items
        var items = el.querySelectorAll('[gloryPostItem]');
        items.forEach(function(item) {
            item.classList.remove('gbn-hover-lift', 'gbn-hover-scale', 'gbn-hover-glow');
        });

        // Aplicar nuevo efecto
        if (effect && effect !== 'none') {
            var effectClass = 'gbn-hover-' + effect;
            items.forEach(function(item) {
                item.classList.add(effectClass);
            });
        }
    }

    /**
     * Aplica el modo de visualización al elemento.
     * 
     * @param {HTMLElement} el Elemento contenedor
     * @param {string} mode Modo (grid, flex, block)
     * @param {Object} config Configuración actual
     */
    function applyDisplayMode(el, mode, config) {
        // Limpiar estilos de layout previos
        el.style.display = '';
        el.style.gridTemplateColumns = '';
        el.style.flexDirection = '';
        el.style.flexWrap = '';
        el.style.alignItems = '';
        el.style.justifyContent = '';

        if (mode === 'grid') {
            var cols = parseInt(config.gridColumns, 10) || 3;
            el.style.display = 'grid';
            el.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
            el.style.gap = traits.normalizeSize(config.gap) || '20px';
        } else if (mode === 'flex') {
            el.style.display = 'flex';
            el.style.flexDirection = config.flexDirection || 'row';
            el.style.flexWrap = config.flexWrap || 'wrap';
            el.style.alignItems = config.alignItems || 'stretch';
            el.style.justifyContent = config.justifyContent || 'flex-start';
            el.style.gap = traits.normalizeSize(config.gap) || '20px';
        } else {
            el.style.display = 'block';
        }
    }

    /**
     * Solicita un preview del contenido desde el backend.
     * Se usa cuando cambian propiedades de query (postType, order, etc.)
     * 
     * @param {Object} block Referencia al bloque
     */
    function requestPreview(block) {
        if (!block || !block.element || !block.config) return;

        var el = block.element;
        var config = block.config;

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

        // Buscar el template original (gloryPostItem)
        var template = el.querySelector('[gloryPostItem]');
        
        // Limpiar mensajes de error/preview anteriores
        var existingMessage = el.querySelector('.gbn-pr-preview-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        // Limpiar clones anteriores (manteniendo el template original)
        var clones = el.querySelectorAll('[data-gbn-pr-clone]');
        clones.forEach(function(clone) {
            clone.remove();
        });

        if (!template) {
            // Si no hay template, mostrar mensaje de configuración
            var msg = document.createElement('div');
            msg.className = 'gbn-pr-preview-message';
            msg.style.cssText = 'padding: 20px; text-align: center; background: #f0f0f0; border-radius: 8px; color: #666;';
            msg.innerHTML = '<p style="margin:0">📋 <strong>' + posts.length + '</strong> ' + 
                           (config.postType || 'post') + '(s) encontrados</p>' +
                           '<p style="margin:8px 0 0; font-size: 12px;">Agrega un gloryPostItem para ver el preview con estilos</p>';
            el.appendChild(msg);
            return;
        }

        // Ocultar template original (se mostrará solo como referencia en el editor)
        template.style.display = 'none';
        template.setAttribute('data-gbn-is-template', 'true');

        // Crear clones para cada post
        posts.forEach(function(post, index) {
            var clone = template.cloneNode(true);
            clone.style.display = ''; // Mostrar el clon
            clone.removeAttribute('data-gbn-is-template');
            clone.setAttribute('data-gbn-pr-clone', 'true');
            clone.setAttribute('data-post-id', post.id);

            // Poblar los campos semánticos
            populatePostFields(clone, post);

            el.appendChild(clone);
        });

        console.log('[PostRender] Rendered', posts.length, 'preview posts');
    }

    /**
     * Puebla los campos semánticos de un item con los datos del post.
     * 
     * @param {HTMLElement} item Elemento del item (clon del template)
     * @param {Object} post Datos del post
     */
    function populatePostFields(item, post) {
        var fields = item.querySelectorAll('[gloryPostField]');

        fields.forEach(function(field) {
            var fieldType = field.getAttribute('gloryPostField');

            switch (fieldType) {
                case 'title':
                    field.textContent = post.title || '';
                    break;
                case 'excerpt':
                    field.textContent = post.excerpt || '';
                    break;
                case 'content':
                    field.innerHTML = post.content || '';
                    break;
                case 'date':
                    field.textContent = post.date || '';
                    break;
                case 'author':
                    field.textContent = post.author || '';
                    break;
                case 'authorAvatar':
                    if (post.authorAvatar) {
                        if (field.tagName === 'IMG') {
                            field.src = post.authorAvatar;
                            field.alt = post.author || 'Autor';
                        } else {
                            field.innerHTML = '<img src="' + post.authorAvatar + '" alt="' + (post.author || '') + '" />';
                        }
                    }
                    break;
                case 'featuredImage':
                    if (post.featuredImage) {
                        if (field.tagName === 'IMG') {
                            field.src = post.featuredImage;
                            field.alt = post.title || '';
                        } else {
                            field.style.backgroundImage = 'url(' + post.featuredImage + ')';
                        }
                    }
                    break;
                case 'link':
                    if (field.tagName === 'A') {
                        field.href = post.link || '#';
                    }
                    // El texto del link se mantiene del template
                    break;
                case 'categories':
                    if (post.categories && post.categories.length > 0) {
                        var catNames = post.categories.map(function(c) { return c.name; }).join(', ');
                        field.textContent = catNames;
                    }
                    break;
                case 'tags':
                    if (post.tags && post.tags.length > 0) {
                        var tagNames = post.tags.map(function(t) { return t.name; }).join(', ');
                        field.textContent = tagNames;
                    }
                    break;
                case 'commentCount':
                    field.textContent = (post.commentCount || 0) + ' comentarios';
                    break;
                default:
                    // Para campos meta: o acf:, mostrar placeholder
                    if (fieldType.startsWith('meta:') || fieldType.startsWith('acf:')) {
                        field.textContent = '[' + fieldType + ']';
                    }
            }
        });
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

    /**
     * Inicializa el componente PostRender cuando se detecta en el DOM.
     * 
     * @param {Object} block Referencia al bloque
     */
    function init(block) {
        if (!block || !block.element) return;

        var config = block.config || {};

        // Aplicar estilos iniciales
        var styles = getStyles(config, block);
        Object.keys(styles).forEach(function(prop) {
            block.element.style[prop.replace(/-([a-z])/g, function(g) { return g[1].toUpperCase(); })] = styles[prop];
        });

        // Aplicar layout pattern si está configurado
        if (config.layoutPattern && config.layoutPattern !== 'none') {
            applyLayoutPattern(block.element, config.layoutPattern);
        }

        // Marcar como inicializado
        block.element.dataset.gbnInitialized = 'true';

        // Solicitar preview inicial de posts
        // Usamos setTimeout para asegurar que el DOM esté completamente cargado
        setTimeout(function() {
            requestPreview(block);
            
            // Aplicar hover effect después del preview (cuando los items ya existen)
            if (config.hoverEffect && config.hoverEffect !== 'none') {
                setTimeout(function() {
                    applyHoverEffect(block.element, config.hoverEffect);
                }, 200);
            }
        }, 100);
    }

    // Exportar renderer
    Gbn.ui.renderers.postRender = {
        getStyles: getStyles,
        handleUpdate: handleUpdate,
        init: init,
        requestPreview: requestPreview
    };

})(typeof window !== 'undefined' ? window : this);
