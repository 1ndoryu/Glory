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
        // Limpiar efectos previos de todos los items (case-insensitive)
        var items = el.querySelectorAll('[gloryPostItem], [glorypostitem]');
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
        if (!block || !block.element) return;
        
        // Obtener el bloque fresco del store para tener la config actualizada
        var freshBlock = Gbn.state.get(block.id);
        if (freshBlock) {
            block = freshBlock;
        }
        
        if (!block.config) return;

        var el = block.element;
        var config = block.config;
        
        // Debug: Log config para verificar que postType está presente
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

        // Buscar el template original (gloryPostItem) - case-insensitive
        var template = el.querySelector('[gloryPostItem]:not([data-gbn-pr-clone])') || 
                       el.querySelector('[glorypostitem]:not([data-gbn-pr-clone])');
        
        // Limpiar mensajes de preview anteriores
        var existingMessage = el.querySelector('.gbn-pr-preview-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Limpiar indicadores anteriores
        var existingIndicator = el.querySelector('.gbn-pr-posts-indicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }

        // Limpiar clones anteriores
        var clones = el.querySelectorAll('[data-gbn-pr-clone]');
        clones.forEach(function(clone) {
            clone.remove();
        });
        
        // Desconectar observer anterior si existe
        if (el._gbnTemplateObserver) {
            el._gbnTemplateObserver.disconnect();
            el._gbnTemplateObserver = null;
        }

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
        if (posts.length > 0) {
            populateTemplateWithPreview(template, posts[0]);
        }
        
        // Crear clones para los DEMÁS posts (a partir del segundo)
        var clonesCreated = [];
        for (var i = 1; i < posts.length; i++) {
            var clone = createSyncedClone(template, posts[i]);
            el.appendChild(clone);
            clonesCreated.push(clone);
        }
        
        // Configurar MutationObserver para sincronizar cambios del template a los clones
        // SOLO sincroniza en cambios estructurales, no en hover/selección del editor
        if (clonesCreated.length > 0) {
            var observer = new MutationObserver(function(mutations) {
                // FILTRAR MUTACIONES IRRELEVANTES
                // Solo sincronizamos en cambios estructurales reales:
                // 1. childList: elementos agregados/removidos
                // 2. attributes: solo si NO son cambios de hover/selección del editor
                var hasRelevantMutation = mutations.some(function(mutation) {
                    // Cambios estructurales (elementos agregados/removidos) siempre son relevantes
                    if (mutation.type === 'childList') {
                        // Ignorar si el target es un clon o parte de un clon
                        var target = mutation.target;
                        if (target.hasAttribute && target.hasAttribute('data-gbn-pr-clone')) {
                            return false;
                        }
                        if (target.closest && target.closest('[data-gbn-pr-clone]')) {
                            return false;
                        }
                        return true;
                    }
                    
                    // Cambios de atributos: filtrar los del editor GBN (hover, selección, etc.)
                    if (mutation.type === 'attributes') {
                        var attrName = mutation.attributeName;
                        var target = mutation.target;
                        
                        // Seguridad: attrName podría ser null en casos extremos
                        if (!attrName) {
                            return false;
                        }
                        
                        // Ignorar cambios en atributos de datos internos de GBN
                        if (attrName.indexOf('data-gbn-') === 0) {
                            return false;
                        }
                        
                        // Ignorar cambios de clase que son del editor (selección, hover)
                        if (attrName === 'class') {
                            var classes = target.className || '';
                            // Si el cambio involucra clases del editor GBN, ignorar
                            if (classes.includes('gbn-selected') || 
                                classes.includes('gbn-hovered') ||
                                classes.includes('gbn-simulated')) {
                                return false;
                            }
                        }
                        
                        // Ignorar cambios de style que son temporales (hover CSS nativo)
                        // Solo nos interesa sincronizar estilos inline persistentes
                        if (attrName === 'style') {
                            // No sincronizamos por cambios de estilo, solo estructurales
                            return false;
                        }
                        
                        // Otros atributos sí son relevantes (ej: cambiaron un src, href, etc.)
                        return true;
                    }
                    
                    return false;
                });
                
                if (hasRelevantMutation) {
                    syncTemplateTOClones(template, clonesCreated, posts);
                }
            });
            
            observer.observe(template, {
                childList: true,
                subtree: true,
                attributes: true,
                // Observar solo atributos que indican cambios estructurales reales
                attributeFilter: ['class', 'src', 'href', 'alt']
            });
            
            // Guardar referencia para poder desconectar después
            el._gbnTemplateObserver = observer;
        }

        // RE-ESCANEAR ELEMENTOS DEL TEMPLATE PARA HACERLOS INTERACTIVOS
        // Los elementos dentro del template (PostItem, PostFields) necesitan ser
        // registrados en el store de GBN para que el inspector y drag-drop funcionen.
        // Esto es necesario porque el preview AJAX modifica/carga elementos DESPUÉS
        // del escaneo inicial de GBN.
        if (Gbn.content && Gbn.content.scan) {
            // Pequeño delay para asegurar que el DOM está estable
            setTimeout(function() {
                try {
                    // Escanear solo el template (no los clones)
                    var newBlocks = Gbn.content.scan(template);
                    console.log('[PostRender] Re-escaneados ' + newBlocks.length + ' elementos del template');
                    
                    // Emitir evento para que otros sistemas sepan que hay nuevos bloques
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
     * Crea un clon del template poblado con datos de un post.
     * El clon reflejará cambios estructurales del template.
     * 
     * IMPORTANTE: Los clones son solo visuales - no interactivos.
     * Se deshabilita pointer-events para evitar confusión y eventos hover innecesarios.
     */
    function createSyncedClone(template, post) {
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
        populateCloneFields(clone, post);
        
        return clone;
    }
    
    /**
     * Sincroniza la estructura del template a todos los clones.
     * Preserva los datos de cada post mientras actualiza la estructura.
     * 
     * OPTIMIZACIÓN: Debounce de 300ms para evitar sincronización excesiva
     * durante interacciones del usuario (drag, edición de texto, etc.)
     */
    function syncTemplateTOClones(template, clones, posts) {
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
     * Puebla los campos de un clon con datos del post.
     */
    function populateCloneFields(clone, post) {
        var fields = clone.querySelectorAll('[gloryPostField], [glorypostfield]');
        
        fields.forEach(function(field) {
            var fieldType = field.getAttribute('gloryPostField') || field.getAttribute('glorypostfield');
            
            switch (fieldType) {
                case 'title':
                    var titleLink = field.querySelector('a');
                    if (titleLink) {
                        titleLink.href = post.link || '#';
                        titleLink.textContent = post.title || 'Título';
                    } else {
                        field.textContent = post.title || 'Título';
                    }
                    break;
                case 'excerpt':
                    field.textContent = post.excerpt || 'Extracto...';
                    break;
                case 'date':
                    field.textContent = post.date || 'Fecha';
                    break;
                case 'author':
                    field.textContent = post.author || 'Autor';
                    break;
                case 'featuredImage':
                    var img = field.querySelector('img');
                    if (img && post.featuredImage) {
                        img.src = post.featuredImage;
                        img.alt = post.title || '';
                    }
                    break;
                case 'categories':
                    if (post.categories && post.categories.length > 0) {
                        field.textContent = post.categories.map(function(c) { return c.name; }).join(', ');
                    }
                    break;
            }
        });
    }
    
    /**
     * Puebla el template original con datos de un post para preview.
     * Guarda los valores originales para poder restaurarlos antes de persistir.
     * 
     * @param {HTMLElement} template Elemento template (PostItem)
     * @param {Object} post Datos del post para preview
     */
    function populateTemplateWithPreview(template, post) {
        var fields = template.querySelectorAll('[gloryPostField], [glorypostfield]');
        
        fields.forEach(function(field) {
            var fieldType = field.getAttribute('gloryPostField') || field.getAttribute('glorypostfield');
            
            // Guardar contenido original para restaurar antes de persistir
            if (!field.hasAttribute('data-gbn-original-content')) {
                field.setAttribute('data-gbn-original-content', field.innerHTML);
            }
            
            // Marcar como campo con preview
            field.setAttribute('data-gbn-preview-field', 'true');
            
            switch (fieldType) {
                case 'title':
                    // Buscar enlace dentro del campo
                    var titleLink = field.querySelector('a');
                    if (titleLink) {
                        titleLink.href = post.link || '#';
                        titleLink.textContent = post.title || 'Título del post';
                    } else {
                        field.textContent = post.title || 'Título del post';
                    }
                    break;
                    
                case 'excerpt':
                    field.textContent = post.excerpt || 'Extracto del post...';
                    break;
                    
                case 'content':
                    field.innerHTML = post.content || '<p>Contenido del post...</p>';
                    break;
                    
                case 'date':
                    field.textContent = post.date || 'Fecha';
                    break;
                    
                case 'author':
                    field.textContent = post.author || 'Autor';
                    break;
                    
                case 'featuredImage':
                    if (post.featuredImage) {
                        var img = field.querySelector('img');
                        if (img) {
                            img.src = post.featuredImage;
                            img.alt = post.title || '';
                        } else {
                            field.style.backgroundImage = 'url(' + post.featuredImage + ')';
                        }
                    }
                    break;
                    
                case 'categories':
                    if (post.categories && post.categories.length > 0) {
                        field.textContent = post.categories.map(function(c) { return c.name; }).join(', ');
                    }
                    break;
                    
                case 'tags':
                    if (post.tags && post.tags.length > 0) {
                        field.textContent = post.tags.map(function(t) { return t.name; }).join(', ');
                    }
                    break;
            }
        });
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
        
        // Obtener el bloque fresco del store para asegurar que tenemos la config actualizada
        // (incluyendo las opciones parseadas del atributo HTML)
        var freshBlock = Gbn.state.get(block.id);
        if (freshBlock) {
            block = freshBlock;
        }

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
