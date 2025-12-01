;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;
    var styleManager = Gbn.styleManager;
    
    Gbn.content = Gbn.content || {};

    function buildBlock(el) {
        var roles = Gbn.content.roles;
        var configHelpers = Gbn.content.config;
        var domHelpers = Gbn.content.dom;

        var role = roles.detectRole(el);
        if (!role) {
            return null;
        }
        domHelpers.normalizeAttributes(el, role);
        var meta = {};
        
        var ROLE_MAP = roles.getMap();
        var FALLBACK_SELECTORS = roles.getFallback();

        if (role === 'content') {
            var attrName = null;
            if (ROLE_MAP.content && ROLE_MAP.content.attr) {
                attrName = ROLE_MAP.content.attr;
            } else if (FALLBACK_SELECTORS.content && (FALLBACK_SELECTORS.content.attribute || FALLBACK_SELECTORS.content.attr)) {
                attrName = FALLBACK_SELECTORS.content.attribute || FALLBACK_SELECTORS.content.attr;
            }
            var typeAttr = attrName ? el.getAttribute(attrName) : null;
            meta.postType = typeAttr && typeAttr !== 'content' ? typeAttr : 'post';
            var opcionesAttr = el.getAttribute('opciones') || el.getAttribute('data-gbn-opciones') || '';
            meta.optionsString = opcionesAttr;
            var parsedOptions = configHelpers.parseOptions(opcionesAttr);
            var estilosAttr = el.getAttribute('estilos');
            meta.inlineArgs = configHelpers.extractInlineArgs(estilosAttr);
            meta.options = utils.assign({}, parsedOptions);
            // Ensure postType is in options so it populates the config
            if (meta.postType) {
                meta.options.postType = meta.postType;
            }
        }
        
        // Pre-process text content for text role
        if (role === 'text') {
            var existingConfig = configHelpers.readJsonAttr(el, 'data-gbn-config');
            // Prioritize existing content in DOM if config is missing or if we want to sync
            var currentText = el.innerHTML; // Use innerHTML to preserve basic formatting if any
            
            if (!existingConfig) {
                existingConfig = {};
            }
            
            // If config has no text, or if we want to ensure what's on screen is what's in config
            // (Usually what's on screen is the source of truth initially)
            if (currentText && currentText.trim() !== '') {
                 existingConfig.texto = currentText;
            } else if (!existingConfig.texto) {
                 // Fallback if both are empty?
                 existingConfig.texto = 'Nuevo texto';
                 el.innerHTML = 'Nuevo texto';
            }
            
            el.setAttribute('data-gbn-config', JSON.stringify(existingConfig));
        }

        var block = state.register(role, el, meta);
        el.classList.add('gbn-node');

        // Asegurar que los valores inline iniciales se reflejen en la configuración del bloque
        // SOLO si hay estilos inline REALES (escritos en el HTML)
        // Esto evita que los fallbacks CSS se guarden como configuración del componente
        try {
            var hasRealInlineStyles = el.hasAttribute('style') && el.getAttribute('style').trim() !== '';
            
            if (hasRealInlineStyles) {
                var roleDefaults = roles.getRoleDefaults(role);
                // NO pasar roleDefaults.config, solo un objeto vacío para evitar herencia CSS
                var inlineConfig = configHelpers.syncInlineStyles(block.styles.inline, roleDefaults.schema, {});
                
                // Merge options from attributes into inlineConfig so they populate the panel
                if (meta.options) {
                    inlineConfig = utils.assign({}, inlineConfig, meta.options);
                }

                var currentConfig = utils.assign({}, block.config || {});
                var mergedWithInline = configHelpers.mergeIfEmpty(currentConfig, inlineConfig);
                block = state.updateConfig(block.id, mergedWithInline) || block;
            } else if (meta.options) {
                // Si no hay estilos inline pero sí hay opciones de atributos (ej: gloryContentRender)
                // Solo aplicar las opciones, sin valores CSS
                var currentConfig = utils.assign({}, block.config || {});
                var mergedWithOptions = configHelpers.mergeIfEmpty(currentConfig, meta.options);
                block = state.updateConfig(block.id, mergedWithOptions) || block;
            }
            // Si NO hay estilos inline NI opciones, no hacer nada
            // El componente heredará valores del Panel de Tema vía variables CSS
        } catch (_) {}

        // Aplicar presets persistidos (config y estilos) si existen para este bloque
        try {
            var presets = utils.getConfig().presets || {};
            if (presets.config && presets.config[block.id]) {
                var presetCfg = presets.config[block.id];
                var nextCfg = (presetCfg && typeof presetCfg === 'object' && presetCfg.config) ? presetCfg.config : presetCfg;
                if (nextCfg && typeof nextCfg === 'object') {
                    state.updateConfig(block.id, nextCfg);
                }
            }
            if (presets.styles && presets.styles[block.id]) {
                // Aplicar estilos persistidos directamente al elemento
                var currentInline = utils.parseStyleString(el.getAttribute('style') || '');
                var mergedStyles = utils.assign({}, currentInline, presets.styles[block.id]);
                var styleString = utils.stringifyStyles(mergedStyles);
                if (styleString) {
                    el.setAttribute('style', styleString);
                }
                block.styles.current = utils.assign({}, presets.styles[block.id]);
            }
        } catch (_) {}
        
        // Ensure text content is applied from config if it exists (persistence fix)
        if (role === 'text' && block.config && block.config.texto) {
             // Only update if different to avoid unnecessary DOM touches, 
             // but we must trust config over initial HTML if config exists and we are in editor mode
             // actually, we should just apply it.
             var currentHTML = el.innerHTML;
             if (block.config.texto !== currentHTML) {
                 // Check for controls to preserve them
                 var controls = el.querySelector('.gbn-controls-group');
                 el.innerHTML = block.config.texto;
                 if (controls) {
                     el.appendChild(controls);
                 }
             }
             
             // Also apply typography styles if present in config but not in inline styles
             // This is a bit redundant if presets.styles handled it, but good for safety
             if (block.config.typography && Gbn.ui && Gbn.ui.panelApi && Gbn.ui.panelApi.applyBlockStyles) {
                 Gbn.ui.panelApi.applyBlockStyles(block);
             }
        }

        // Solo aplicar baseline si no hay presets
        if (!presets.styles || !presets.styles[block.id]) {
            styleManager.ensureBaseline(block);
        }

        return block;
    }

    Gbn.content.builder = {
        buildBlock: buildBlock
    };

})(window);
