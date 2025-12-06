;(function (global) {
    'use strict';

    /**
     * POST RENDER RENDERER (Orquestador)
     * 
     * Renderer principal para el componente contenedor PostRender.
     * 
     * ARQUITECTURA MODULAR (Refactorizado Dic 2025):
     * Este archivo actúa como orquestador. La lógica está dividida en:
     * - post-render/styles.js   → Generación de estilos CSS y layout
     * - post-render/clones.js   → Creación y sincronización de clones WYSIWYG
     * - post-render/fields.js   → Población de campos semánticos (PostFields)
     * - post-render/preview.js  → Solicitud de preview al backend
     * 
     * @module Gbn.ui.renderers.postRender
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    // Referencias a módulos (cargados como dependencias)
    var modules = Gbn.ui.renderers.postRenderModules || {};
    var traits = Gbn.ui.renderers.traits;

    /**
     * Obtiene referencia a los módulos (lazy loading para dependencias cíclicas)
     */
    function getModules() {
        if (!modules.styles || !modules.clones || !modules.fields || !modules.preview) {
            modules = Gbn.ui.renderers.postRenderModules || {};
        }
        return modules;
    }

    /**
     * Genera estilos CSS para el contenedor PostRender.
     * Delega al módulo styles.
     * 
     * @param {Object} config Configuración del bloque
     * @param {Object} block Referencia al bloque
     * @returns {Object} Estilos CSS como objeto
     */
    function getStyles(config, block) {
        var m = getModules();
        if (m.styles && m.styles.getStyles) {
            return m.styles.getStyles(config, block);
        }
        // Fallback básico si el módulo no está cargado
        return traits.getCommonStyles(config);
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
        var m = getModules();

        // === PROPIEDADES DE LAYOUT ===
        
        if (path === 'displayMode') {
            if (m.styles && m.styles.applyDisplayMode) {
                m.styles.applyDisplayMode(el, value, block.config);
            }
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
            if (m.preview && m.preview.requestPreview) {
                m.preview.requestPreview(block);
            }
            return true;
        }

        // === LAYOUT PATTERN ===
        
        if (path === 'layoutPattern') {
            if (m.styles && m.styles.applyLayoutPattern) {
                m.styles.applyLayoutPattern(el, value);
            }
            return true;
        }

        // === HOVER EFFECT ===
        
        if (path === 'hoverEffect') {
            if (m.styles && m.styles.applyHoverEffect) {
                m.styles.applyHoverEffect(el, value);
            }
            return true;
        }

        // === DELEGAR A TRAITS COMUNES ===
        return traits.handleCommonUpdate(el, path, value);
    }

    /**
     * Solicita un preview del contenido desde el backend.
     * Delega al módulo preview.
     * 
     * @param {Object} block Referencia al bloque
     */
    function requestPreview(block) {
        var m = getModules();
        if (m.preview && m.preview.requestPreview) {
            m.preview.requestPreview(block);
        }
    }

    /**
     * Inicializa el componente PostRender cuando se detecta en el DOM.
     * 
     * @param {Object} block Referencia al bloque
     */
    function init(block) {
        if (!block || !block.element) return;
        
        // Obtener el bloque fresco del store para asegurar que tenemos la config actualizada
        var freshBlock = Gbn.state.get(block.id);
        if (freshBlock) {
            block = freshBlock;
        }

        var config = block.config || {};
        var m = getModules();

        // Aplicar estilos iniciales
        var styles = getStyles(config, block);
        Object.keys(styles).forEach(function(prop) {
            block.element.style[prop.replace(/-([a-z])/g, function(g) { return g[1].toUpperCase(); })] = styles[prop];
        });

        // Aplicar layout pattern si está configurado
        if (config.layoutPattern && config.layoutPattern !== 'none') {
            if (m.styles && m.styles.applyLayoutPattern) {
                m.styles.applyLayoutPattern(block.element, config.layoutPattern);
            }
        }

        // Marcar como inicializado
        block.element.dataset.gbnInitialized = 'true';

        // Solicitar preview inicial de posts
        setTimeout(function() {
            requestPreview(block);
            
            // Aplicar hover effect después del preview (cuando los items ya existen)
            if (config.hoverEffect && config.hoverEffect !== 'none') {
                setTimeout(function() {
                    if (m.styles && m.styles.applyHoverEffect) {
                        m.styles.applyHoverEffect(block.element, config.hoverEffect);
                    }
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
