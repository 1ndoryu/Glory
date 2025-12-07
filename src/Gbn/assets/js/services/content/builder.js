(function (global) {
    'use strict';

    var Gbn = (global.Gbn = global.Gbn || {});
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

        // --- GLOBAL OPTIONS PARSING ---
        // Parse 'opciones' attribute for ALL roles
        var opcionesAttr = el.getAttribute('opciones') || el.getAttribute('data-gbn-opciones') || '';
        meta.optionsString = opcionesAttr;
        var parsedOptions = configHelpers.parseOptions(opcionesAttr);
        meta.options = utils.assign({}, parsedOptions);

        // --- ROLE SPECIFIC LOGIC ---

        if (role === 'content') {
            var attrName = null;
            if (ROLE_MAP.content && ROLE_MAP.content.attr) {
                attrName = ROLE_MAP.content.attr;
            } else if (FALLBACK_SELECTORS.content && (FALLBACK_SELECTORS.content.attribute || FALLBACK_SELECTORS.content.attr)) {
                attrName = FALLBACK_SELECTORS.content.attribute || FALLBACK_SELECTORS.content.attr;
            }
            var typeAttr = attrName ? el.getAttribute(attrName) : null;
            meta.postType = typeAttr && typeAttr !== 'content' ? typeAttr : 'post';

            var estilosAttr = el.getAttribute('estilos');
            meta.inlineArgs = configHelpers.extractInlineArgs(estilosAttr);

            // Ensure postType is in options so it populates the config
            if (meta.postType) {
                meta.options.postType = meta.postType;
            }
        }

        // Pre-process text content for text role
        if (role === 'text') {
            // BUG-020 FIX: Verificar si el elemento tiene hijos GBN antes de inferir texto
            // Si tiene hijos GBN, NO capturar innerHTML como texto porque contiene estructura
            var hasGbnChildren = false;
            var children = el.children;
            for (var i = 0; i < children.length; i++) {
                var child = children[i];
                if (child.classList && child.classList.contains('gbn-controls-group')) {
                    continue;
                }
                var attrs = child.attributes;
                for (var j = 0; j < attrs.length; j++) {
                    var attrName = attrs[j].name.toLowerCase();
                    if (attrName.startsWith('glory') || attrName === 'data-gbn-id') {
                        hasGbnChildren = true;
                        break;
                    }
                }
                if (hasGbnChildren) break;
            }

            // Solo inferir 'texto' si NO tiene hijos GBN
            if (!meta.options.texto && !hasGbnChildren) {
                // Use innerHTML to preserve formatting (br, span, etc)
                // We must be careful not to capture GBN controls if they exist,
                // but buildBlock is usually called on clean nodes.
                var currentText = el.innerHTML.trim();
                if (currentText) {
                    meta.options.texto = currentText;
                } else {
                    meta.options.texto = 'Nuevo texto';
                }
            }

            // Si tiene hijos GBN, marcar en meta para que el panel sepa que es un contenedor
            if (hasGbnChildren) {
                meta.isContainer = true;
            }

            // Infer 'tag' from tagName if not provided in options
            if (!meta.options.tag) {
                meta.options.tag = el.tagName.toLowerCase();
            }
        }

        // Pre-process PostField content
        // Leer el tipo de campo desde el atributo gloryPostField
        // Esto es CRÍTICO para la hidratación correcta del panel
        if (role === 'postField') {
            // El valor del atributo gloryPostField contiene el tipo de campo (ej: 'date', 'title', 'excerpt')
            var fieldTypeAttr = el.getAttribute('gloryPostField');
            if (fieldTypeAttr && !meta.options.fieldType) {
                meta.options.fieldType = fieldTypeAttr;
            }

            // También leer otros atributos que podrían estar presentes
            // Por ejemplo: format para fechas, wordLimit para excerpts, etc.
            // Estos pueden venir como atributos separados o en 'opciones'
        }

        // Pre-process button content
        // Diseño nativo: leer valores desde atributos HTML en lugar de 'opciones='
        if (role === 'button') {
            // Infer 'texto' from innerHTML if not provided
            if (!meta.options.texto) {
                // Limpiar el texto: remover espacios extra y saltos de línea
                var currentBtnText = el.innerHTML.trim().replace(/\s+/g, ' ');
                if (currentBtnText) {
                    meta.options.texto = currentBtnText;
                }
            }

            // Infer 'url' from href attribute (diseño nativo)
            if (!meta.options.url) {
                var hrefAttr = el.getAttribute('href');
                if (hrefAttr) {
                    meta.options.url = hrefAttr;
                }
            }

            // Infer 'target' from target attribute
            if (!meta.options.target) {
                var targetAttr = el.getAttribute('target');
                if (targetAttr) {
                    meta.options.target = targetAttr;
                }
            }
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
                var nextCfg = presetCfg && typeof presetCfg === 'object' && presetCfg.config ? presetCfg.config : presetCfg;
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
        // BUG-020 FIX: Solo aplicar texto si el elemento NO tiene hijos GBN
        if (role === 'text' && block.config && block.config.texto) {
            // Verificar si tiene hijos GBN que no debemos sobrescribir
            var hasNestedGbn = false;
            var childElements = el.children;
            for (var ci = 0; ci < childElements.length; ci++) {
                var childEl = childElements[ci];
                if (childEl.classList && childEl.classList.contains('gbn-controls-group')) {
                    continue;
                }
                var childAttrs = childEl.attributes;
                for (var ai = 0; ai < childAttrs.length; ai++) {
                    var attrN = childAttrs[ai].name.toLowerCase();
                    if (attrN.startsWith('glory') || attrN === 'data-gbn-id') {
                        hasNestedGbn = true;
                        break;
                    }
                }
                if (hasNestedGbn) break;
            }

            // Solo aplicar innerHTML si NO tiene hijos GBN
            if (!hasNestedGbn) {
                // Only update if different to avoid unnecessary DOM touches,
                // but we must trust config over initial HTML if config exists and we are in editor mode
                var currentHTML = el.innerHTML;
                if (block.config.texto !== currentHTML) {
                    // Check for controls to preserve them
                    var controls = el.querySelector('.gbn-controls-group');
                    el.innerHTML = block.config.texto;
                    if (controls) {
                        el.appendChild(controls);
                    }
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

        // Inicializar renderers que tengan lógica especial de arranque
        // PostRender necesita solicitar preview de posts via AJAX
        if (role === 'postRender' && Gbn.ui && Gbn.ui.renderers && Gbn.ui.renderers.postRender && Gbn.ui.renderers.postRender.init) {
            // Diferir la inicialización para asegurar que el DOM esté listo
            setTimeout(function () {
                Gbn.ui.renderers.postRender.init(block);
            }, 50);
        }

        return block;
    }

    Gbn.content.builder = {
        buildBlock: buildBlock
    };
})(window);
