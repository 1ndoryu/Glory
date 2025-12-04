;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.fieldUtils = Gbn.ui.fieldUtils || {};

    /**
     * Obtiene un valor anidado de un objeto usando notación de punto
     * @param {Object} obj - Objeto fuente
     * @param {string} path - Ruta en notación punto (ej: 'padding.superior')
     * @returns {*} Valor encontrado o undefined
     */
    function getDeepValue(obj, path) {
        if (!obj || !path) return undefined;
        var value = obj;
        var segments = path.split('.');
        for (var i = 0; i < segments.length; i += 1) {
            if (value === null || value === undefined) return undefined;
            value = value[segments[i]];
        }
        return value;
    }

    /**
     * Obtiene el valor por defecto del tema para un rol y propiedad específicos
     * Jerarquía: Gbn.config.themeSettings (local) > gloryGbnCfg.themeSettings (servidor) > cssSync
     * @param {string} role - Rol del bloque (principal, secundario, etc.)
     * @param {string} path - Ruta de la propiedad (ej: 'padding.superior')
     * @returns {*} Valor del tema o undefined
     */
    function getThemeDefault(role, path) {
        if (!role) return undefined;

        // Delegar a responsive.js si está disponible
        if (Gbn.responsive && Gbn.responsive.getThemeResponsiveValue && Gbn.responsive.getCurrentBreakpoint) {
             var bp = Gbn.responsive.getCurrentBreakpoint();
             return Gbn.responsive.getThemeResponsiveValue(role, path, bp);
        }
        
        // 1. PRIMERO: Intentar desde estado local (Gbn.config.themeSettings)
        // Este tiene prioridad porque puede contener cambios no guardados
        if (Gbn.config && Gbn.config.themeSettings) {
            var localSettings = Gbn.config.themeSettings;
            if (localSettings.components && localSettings.components[role]) {
                var localVal = getDeepValue(localSettings.components[role], path);
                if (localVal !== undefined && localVal !== null && localVal !== '') {
                    return localVal;
                }
            }
        }
        
        // 2. SEGUNDO: Intentar desde gloryGbnCfg (valores del servidor)
        if (typeof gloryGbnCfg !== 'undefined' && gloryGbnCfg.themeSettings) {
            var serverSettings = gloryGbnCfg.themeSettings;
            if (serverSettings.components && serverSettings.components[role]) {
                var serverVal = getDeepValue(serverSettings.components[role], path);
                if (serverVal !== undefined && serverVal !== null && serverVal !== '') {
                    return serverVal;
                }
            }
        }
        
        // 3. Fallback: leer valores actuales desde el CSS via cssSync
        if (Gbn.cssSync && Gbn.cssSync.readDefaults) {
            var cssDefaults = Gbn.cssSync.readDefaults();
            if (cssDefaults && cssDefaults.components && cssDefaults.components[role]) {
                var cssVal = getDeepValue(cssDefaults.components[role], path);
                if (cssVal !== undefined && cssVal !== null && cssVal !== '') {
                    return cssVal;
                }
            }
        }
        
        return undefined;
    }

    /**
     * Obtiene valor considerando breakpoint activo y herencia responsive
     */
    function getResponsiveConfigValue(block, path, breakpoint) {
        if (!Gbn.responsive || !Gbn.responsive.getResponsiveValue) {
            // Fallback si responsive no está disponible
            return getConfigValue(block, path);
        }
        
        return Gbn.responsive.getResponsiveValue(block, path, breakpoint);
    }

    /**
     * Determina el origen del valor actual para mostrar indicador correcto
     */
    function getValueSource(block, path, breakpoint) {
        breakpoint = breakpoint || (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint()) || 'desktop';
        
        var utils = Gbn.ui.fieldUtils;
        if (!utils) return 'css';
        
        // 1. Override específico del breakpoint
        if (breakpoint !== 'desktop' && block.config._responsive && block.config._responsive[breakpoint]) {
            var val = utils.getDeepValue(block.config._responsive[breakpoint], path);
            if (val !== undefined) return 'override';
        }
        
        // 2. Heredado de tablet (solo para mobile)
        if (breakpoint === 'mobile' && block.config._responsive && block.config._responsive.tablet) {
            var tabletVal = utils.getDeepValue(block.config._responsive.tablet, path);
            if (tabletVal !== undefined) return 'tablet';
        }
        
        // 3. Desktop (base del bloque)
        var desktopVal = utils.getDeepValue(block.config, path);
        if (desktopVal !== undefined) return 'block';
        
        // 4-6. Theme settings
        var themeSettings = (Gbn.config && Gbn.config.themeSettings) || (gloryGbnCfg && gloryGbnCfg.themeSettings) || {};
        var roleConfig = themeSettings.components && themeSettings.components[block.role];
        
        if (roleConfig && roleConfig._responsive && roleConfig._responsive[breakpoint]) {
            var themeVal = utils.getDeepValue(roleConfig._responsive[breakpoint], path);
            if (themeVal !== undefined) return 'theme';
        }
        
        if (breakpoint === 'mobile' && roleConfig && roleConfig._responsive && roleConfig._responsive.tablet) {
            var themeTablet = utils.getDeepValue(roleConfig._responsive.tablet, path);
            if (themeTablet !== undefined) return 'theme';
        }
        
        if (roleConfig) {
            var themeDesktop = utils.getDeepValue(roleConfig, path);
            if (themeDesktop !== undefined) return 'theme';
        }
        
        // 7. Valores computados del DOM (estilos de clases CSS o inline sin estar en config)
        if (block.element) {
            var computedValue = getComputedValueForPath(block.element, path);
            if (computedValue !== undefined && computedValue !== null && computedValue !== '') {
                // [BUG-SYNC FIX] Verificar si es diferente a valores por defecto del navegador
                // Extendido para incluir position, z-index, overflow, width, height
                var isDefaultValue = false;
                
                // Padding defaults
                if (path.indexOf('padding') === 0 && (computedValue === '0px' || computedValue === '0')) {
                    isDefaultValue = true;
                }
                // Margin defaults
                else if (path.indexOf('margin') === 0 && (computedValue === '0px' || computedValue === '0')) {
                    isDefaultValue = true;
                }
                // Background defaults
                else if ((path === 'fondo' || path === 'background' || path === 'backgroundColor') && 
                          (computedValue === 'rgba(0, 0, 0, 0)' || computedValue === 'transparent')) {
                    isDefaultValue = true;
                } 
                else if (path === 'backgroundImage' && computedValue === 'none') {
                    isDefaultValue = true;
                } 
                else if (path === 'gap' && (computedValue === 'normal' || computedValue === '0px')) {
                    isDefaultValue = true;
                }
                // [BUG-SYNC FIX] Position defaults
                else if (path === 'position' && computedValue === 'static') {
                    isDefaultValue = true;
                }
                // [BUG-SYNC FIX] z-index defaults (auto se reporta como 'auto' o como número en algunos casos)
                else if (path === 'zIndex' && (computedValue === 'auto' || computedValue === '0')) {
                    isDefaultValue = true;
                }
                // [BUG-SYNC FIX] Overflow defaults
                else if ((path === 'overflow' || path === 'overflowX' || path === 'overflowY') && computedValue === 'visible') {
                    isDefaultValue = true;
                }
                // [BUG-SYNC FIX] Width/Height: 'auto' es el default, pero si hay un valor en px, es relevante
                // Nota: No marcamos width/height en px como default porque probablemente viene de una clase CSS
                else if ((path === 'ancho' || path === 'width') && computedValue === 'auto') {
                    isDefaultValue = true;
                }
                else if (path === 'height' && computedValue === 'auto') {
                    isDefaultValue = true;
                }
                
                if (!isDefaultValue) {
                    return 'computed';
                }
            }
        }
        
        // 8. CSS defaults (sin valor real, solo herencia de CSS)
        return 'css';
    }

    /**
     * Obtiene el valor de configuración de un bloque, con fallback a defaults del tema
     * @param {Object} block - Bloque con config y role
     * @param {string} path - Ruta de la propiedad
     * @returns {*} Valor encontrado o undefined
     */
    function getConfigValue(block, path) {
        if (!block || !path) return undefined;
        
        // 0. Intentar obtener valor responsive si el sistema está activo
        if (Gbn.responsive && Gbn.responsive.getResponsiveValue && Gbn.responsive.getCurrentBreakpoint) {
            var bp = Gbn.responsive.getCurrentBreakpoint();
            var val = Gbn.responsive.getResponsiveValue(block, path, bp);
            return val;
        }
        
        // 1. Intentar desde config del bloque
        var value = getDeepValue(block.config, path);
        if (value !== undefined && value !== null && value !== '') {
            return value;
        }

        // 2. Intentar desde defaults del tema (excepto para theme/page)
        if (block.role && block.role !== 'theme' && block.role !== 'page') {
            var themeVal = getThemeDefault(block.role, path);
            if (themeVal !== undefined && themeVal !== null && themeVal !== '') {
                return themeVal;
            }
        }

        return undefined;
    }

    /**
     * Agrega descripción/hint a un campo
     * @param {HTMLElement} container - Contenedor del campo
     * @param {Object} field - Definición del campo
     */
    function appendFieldDescription(container, field) {
        if (!field || !field.descripcion) return;
        var hint = document.createElement('p');
        hint.className = 'gbn-field-hint';
        hint.textContent = field.descripcion;
        container.appendChild(hint);
    }

    /**
     * Parsea un valor de spacing en valor numérico y unidad
     * @param {*} raw - Valor crudo (ej: '20px', 20, '1.5rem')
     * @param {string} fallbackUnit - Unidad por defecto
     * @returns {{valor: string, unidad: string}}
     */
    function parseSpacingValue(raw, fallbackUnit) {
        if (raw === null || raw === undefined || raw === '') {
            return { valor: '', unidad: fallbackUnit || 'px' };
        }
        if (typeof raw === 'number') {
            return { valor: String(raw), unidad: fallbackUnit || 'px' };
        }
        var match = /^(-?\d+(?:\.\d+)?)([a-z%]*)$/i.exec(String(raw).trim());
        if (!match) {
            return { valor: String(raw), unidad: fallbackUnit || 'px' };
        }
        return { valor: match[1], unidad: match[2] || fallbackUnit || 'px' };
    }

    /**
     * Íconos SVG para campos de spacing
     */
    var ICONS = {
        superior: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke-opacity="0.3"></rect><path d="M4 6h16"></path></svg>',
        derecha: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke-opacity="0.3"></rect><path d="M18 4v16"></path></svg>',
        inferior: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke-opacity="0.3"></rect><path d="M4 18h16"></path></svg>',
        izquierda: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke-opacity="0.3"></rect><path d="M6 4v16"></path></svg>'
    };

    /**
     * Mapeo de propiedades de config a propiedades CSS
     * [BUG-SYNC FIX] Agregadas propiedades faltantes: ancho, position, overflow, zIndex
     * que causaban que getComputedValueForPath no pudiera leer estilos de clases CSS
     */
    var CONFIG_TO_CSS_MAP = {
        'padding.superior': 'paddingTop',
        'padding.derecha': 'paddingRight',
        'padding.inferior': 'paddingBottom',
        'padding.izquierda': 'paddingLeft',
        'margin.superior': 'marginTop',
        'margin.derecha': 'marginRight',
        'margin.inferior': 'marginBottom',
        'margin.izquierda': 'marginLeft',
        'fondo': 'backgroundColor',
        'background': 'backgroundColor',
        'backgroundColor': 'backgroundColor',
        'backgroundImage': 'backgroundImage',
        'backgroundSize': 'backgroundSize',
        'backgroundPosition': 'backgroundPosition',
        'backgroundRepeat': 'backgroundRepeat',
        'backgroundAttachment': 'backgroundAttachment',
        'gap': 'gap',
        'layout': 'display',
        'display': 'display',
        'flexDirection': 'flexDirection',
        'flexWrap': 'flexWrap',
        'flexJustify': 'justifyContent',
        'flexAlign': 'alignItems',
        'height': 'height',
        'alineacion': 'textAlign',
        'textAlign': 'textAlign',
        'maxAncho': 'maxWidth',
        'color': 'color',
        'size': 'fontSize',
        // Typography - Todas las propiedades
        'typography.font': 'fontFamily',
        'typography.size': 'fontSize',
        'typography.weight': 'fontWeight',
        'typography.lineHeight': 'lineHeight',
        'typography.letterSpacing': 'letterSpacing',
        'typography.transform': 'textTransform',
        // Text Effects
        'textShadow': 'textShadow',
        // Border
        'borderWidth': 'borderWidth',
        'borderStyle': 'borderStyle',
        'borderColor': 'borderColor',
        'borderRadius': 'borderRadius',
        // [BUG-SYNC FIX] Dimensiones y Posicionamiento - Propiedades faltantes
        'ancho': 'width',
        'width': 'width',
        'minHeight': 'minHeight',
        'minWidth': 'minWidth',
        // Posicionamiento
        'position': 'position',
        'zIndex': 'zIndex',
        // Overflow
        'overflow': 'overflow',
        'overflowX': 'overflowX',
        'overflowY': 'overflowY',
        // Button/Element specific
        'cursor': 'cursor',
        'transition': 'transition',
        'transform': 'transform'
    };

    /**
     * Obtiene el valor computado de una propiedad CSS de un elemento
     * @param {HTMLElement} element - Elemento DOM
     * @param {string} cssProperty - Propiedad CSS (camelCase, ej: 'paddingTop')
     * @returns {string|undefined} Valor computado o undefined
     */
    function getComputedValue(element, cssProperty) {
        if (!element || !cssProperty) return undefined;
        try {
            // Remover clases y atributos del inspector/GBN temporalmente (Bug 7 fix)
            // Esto previene interferencia de estilos hover (#1d8ff1) que usan selectores de atributo [data-gbnPrincipal]:hover
            var removedClasses = [];
            var classesToRemove = ['gbn-show-controls', 'gbn-block', 'gbn-block-active', 'gbn-node'];
            
            classesToRemove.forEach(function(className) {
                if (element.classList.contains(className)) {
                    removedClasses.push(className);
                    element.classList.remove(className);
                }
            });
            
            // También remover atributos data-gbn* que disparan estilos
            var removedAttributes = {};
            var attributesToRemove = ['data-gbnPrincipal', 'data-gbnSecundario', 'data-gbn-role'];
            
            attributesToRemove.forEach(function(attr) {
                if (element.hasAttribute(attr)) {
                    removedAttributes[attr] = element.getAttribute(attr);
                    element.removeAttribute(attr);
                }
            });
            
            // Si removimos algo, forzar reflow
            if (removedClasses.length > 0 || Object.keys(removedAttributes).length > 0) {
                void element.offsetHeight; // Trigger reflow
            }
            
            var computed = window.getComputedStyle(element);
            var value = computed[cssProperty];
            
            // Restaurar atributos
            Object.keys(removedAttributes).forEach(function(attr) {
                element.setAttribute(attr, removedAttributes[attr]);
            });
            
            // Restaurar clases
            removedClasses.forEach(function(className) {
                element.classList.add(className);
            });
            
            // Retornar undefined si es vacío o no existe
            if (value === '' || value === undefined || value === null) {
                return undefined;
            }
            return value;
        } catch (e) {
            return undefined;
        }
    }

    /**
     * Obtiene el valor computado para una ruta de configuración
     * Usa el mapeo CONFIG_TO_CSS_MAP para traducir la ruta a propiedad CSS
     * @param {HTMLElement} element - Elemento DOM
     * @param {string} configPath - Ruta de configuración (ej: 'padding.superior')
     * @returns {string|undefined} Valor computado o undefined
     */
    function getComputedValueForPath(element, configPath) {
        if (!element || !configPath) return undefined;
        var cssProperty = CONFIG_TO_CSS_MAP[configPath];
        if (!cssProperty) return undefined;
        return getComputedValue(element, cssProperty);
    }

    /**
     * Determina el valor efectivo para un campo del panel
     * Prioridad: 1) block.config, 2) computedStyle, 3) themeDefault
     * Retorna { value, source, placeholder }
     * @param {Object} block - Bloque con config, role y element
     * @param {string} path - Ruta de la propiedad
     * @returns {{value: *, source: string, placeholder: *}}
     */
    function getEffectiveValue(block, path) {
        var result = { value: undefined, source: 'none', placeholder: undefined };
        
        // Determinar el estado actual de edición (Normal, Hover, Focus)
        var currentState = 'normal';
        if (Gbn.ui.panelRender && Gbn.ui.panelRender.getCurrentState) {
            currentState = Gbn.ui.panelRender.getCurrentState();
        }

        // --- LÓGICA PARA ESTADOS (Hover, Focus, etc.) ---
        if (currentState !== 'normal') {
            // 1. Configuración guardada para el estado
            var stateConfigVal = getStateConfig(block, currentState, path);
            if (stateConfigVal !== undefined && stateConfigVal !== null && stateConfigVal !== '') {
                result.value = stateConfigVal;
                result.source = 'state-config';
                return result;
            }

            // 2. Estilos computados del estado (desde CSS)
            // Usamos el servicio stateStyles para leer pseudo-clases
            if (Gbn.services.stateStyles && Gbn.services.stateStyles.getStateStyles) {
                var cssProp = CONFIG_TO_CSS_MAP[path];
                if (cssProp) {
                    var stateStyles = Gbn.services.stateStyles.getStateStyles(block.element, currentState);
                    var computedStateVal = stateStyles[cssProp];
                    
                    if (computedStateVal !== undefined && computedStateVal !== null && computedStateVal !== '') {
                        result.value = computedStateVal;
                        result.source = 'state-computed';
                        return result;
                    }
                }
            }

            // 3. Fallback: Mostrar el valor "Normal" como placeholder/referencia
            // Esto ayuda al usuario a saber qué está sobrescribiendo
            // NOTA: No podemos llamar getEffectiveValue recursivamente porque
            // getCurrentState() seguiría devolviendo el estado actual (hover/focus),
            // causando recursión infinita. Leemos directamente la config base.
            var baseConfig = getDeepValue(block.config, path);
            if (baseConfig !== undefined) {
                result.placeholder = baseConfig;
            } else {
                // Si no hay config base, intentar computed base (estado normal)
                var baseComputed = getComputedValueForPath(block.element, path);
                if (baseComputed !== undefined) {
                    result.placeholder = baseComputed;
                }
            }
            
            return result;
        }

        // --- LÓGICA NORMAL (Desktop/Responsive) ---
        var savedValue;
        // Usar getResponsiveValue para aprovechar la lógica de theme settings
        if (Gbn.responsive && Gbn.responsive.getResponsiveValue) {
            var bp = Gbn.responsive.getCurrentBreakpoint();
            savedValue = Gbn.responsive.getResponsiveValue(block, path, bp);
        } else {
            savedValue = getDeepValue(block.config, path);
        }
        
        if (savedValue !== undefined && savedValue !== null && savedValue !== '') {
            result.value = savedValue;
            result.source = 'config';
        }
        
        // 2. Si no hay valor guardado, leer del computedStyle
        if (result.source === 'none' && block.element) {
            
            // [FIX] Inferencia de hasBorder desde estilos computados
            // Permite que el toggle se active si el elemento ya tiene borde por CSS
            if (path === 'hasBorder') {
                var bWidth = getComputedValueForPath(block.element, 'borderWidth');
                var bStyle = getComputedValueForPath(block.element, 'borderStyle');
                
                // Si tiene ancho > 0 y estilo != none, tiene borde
                if (bWidth && bWidth !== '0px' && bWidth !== '0' && bStyle && bStyle !== 'none') {
                    result.value = true;
                    result.source = 'computed';
                    return result;
                }
                // Si no, asumimos false (que es el default)
                return result; 
            }

            var computedValue = getComputedValueForPath(block.element, path);
            if (computedValue !== undefined && computedValue !== null && computedValue !== '') {
                // Obtener theme default para comparar
                var themeDefault = getThemeDefault(block.role, path);
                
                // [BUG-SYNC FIX] Verificar si es un valor por defecto del navegador
                // Estos valores no deben mostrarse como "computed" porque son defaults
                var isBrowserDefault = false;
                
                // Position: 'static' es el default
                if (path === 'position' && computedValue === 'static') {
                    isBrowserDefault = true;
                }
                // z-index: 'auto' es el default
                else if (path === 'zIndex' && (computedValue === 'auto' || computedValue === '0')) {
                    isBrowserDefault = true;
                }
                // Overflow: 'visible' es el default
                else if ((path === 'overflow' || path === 'overflowX' || path === 'overflowY') && computedValue === 'visible') {
                    isBrowserDefault = true;
                }
                // Width/Height: 'auto' es el default (pero en px es un valor calculado real, lo mostramos)
                else if ((path === 'ancho' || path === 'width') && computedValue === 'auto') {
                    isBrowserDefault = true;
                }
                else if (path === 'height' && computedValue === 'auto') {
                    isBrowserDefault = true;
                }
                // Padding/Margin: '0px' es el default
                else if ((path.indexOf('padding') === 0 || path.indexOf('margin') === 0) && 
                         (computedValue === '0px' || computedValue === '0')) {
                    isBrowserDefault = true;
                }
                // Background: transparent/none son defaults
                else if ((path === 'fondo' || path === 'backgroundColor') && 
                         (computedValue === 'rgba(0, 0, 0, 0)' || computedValue === 'transparent')) {
                    isBrowserDefault = true;
                }
                else if (path === 'backgroundImage' && computedValue === 'none') {
                    isBrowserDefault = true;
                }
                // Gap: 'normal' o '0px' son defaults
                else if (path === 'gap' && (computedValue === 'normal' || computedValue === '0px')) {
                    isBrowserDefault = true;
                }
                
                // Si NO es un default del navegador, verificar si es diferente al tema
                if (!isBrowserDefault) {
                    // Lógica especial para backgroundImage
                    if (path === 'backgroundImage') {
                        if (computedValue !== 'none' && computedValue !== themeDefault) {
                            result.value = computedValue;
                            result.source = 'computed';
                        }
                    } else {
                        var parsedComputed = parseSpacingValue(computedValue);
                        var parsedTheme = parseSpacingValue(themeDefault);
                        
                        // Si el valor computado es diferente al default del tema, 
                        // significa que hay estilos inline o de clase que debemos mostrar
                        if (parsedComputed.valor !== parsedTheme.valor) {
                            result.value = computedValue;
                            result.source = 'computed';
                        }
                    }
                }
            }
        }
        
        // 3. Placeholder: siempre es el valor del tema (si existe)
        var themePlaceholder = getThemeDefault(block.role, path);
        if (themePlaceholder !== undefined && themePlaceholder !== null) {
            result.placeholder = themePlaceholder;
        }
        
        return result;
    }

    /**
     * Evalúa si un campo debe mostrarse basado en su condición
     * Usa getEffectiveValue para incluir valores computados del DOM
     * Para Theme Settings con campos de componentes (ej: 'components.principal.layout'),
     * la condición se evalúa relativa al componente
     * @param {Object} block - Bloque actual
     * @param {Object} field - Definición del campo
     * @returns {boolean}
     */
    function shouldShowField(block, field) {
        if (!field || !field.condicion || !Array.isArray(field.condicion)) {
            return true;
        }
        
        var cond = field.condicion;
        var key, operator, value;

        if (cond.length === 2) {
            key = cond[0];
            operator = '==';
            value = cond[1];
        } else if (cond.length === 3) {
            key = cond[0];
            operator = cond[1];
            value = cond[2];
        } else {
            return true;
        }

        var current;
        
        // Para Theme Settings con campos de componentes
        // Si field.id es 'components.{role}.{prop}', buscar condición en 'components.{role}.{key}'
        if (field.id && field.id.indexOf('components.') === 0) {
            var parts = field.id.split('.');
            if (parts.length >= 3) {
                var componentPath = parts.slice(0, 2).join('.') + '.' + key;
                current = getDeepValue(block.config, componentPath);
            }
        }
        
        // Si no encontramos valor con la lógica de componentes, usar getEffectiveValue normal
        if (current === undefined || current === null) {
            var effective = getEffectiveValue(block, key);
            current = effective.value;
            
            // Para 'layout', mapear 'flex' desde computedStyle 'display: flex'
            if (key === 'layout' && effective.source === 'computed') {
                if (current === 'flex') current = 'flex';
                else if (current === 'grid') current = 'grid';
                else if (current === 'block' || current === 'block flow') current = 'block';
            }
        }

        switch (operator) {
            case '==': return current === value;
            case '!=': return current !== value;
            case 'in': return Array.isArray(value) && value.indexOf(current) !== -1;
            case '!in': return Array.isArray(value) && value.indexOf(current) === -1;
            default: return true;
        }
    }

    /**
     * Obtiene el schema completo de un role desde ContainerRegistry
     * Prioridad: 1) gloryGbnCfg.roleSchemas (del servidor), 2) Gbn.content.roles (runtime)
     * @param {string} role - Nombre del role ('principal', 'secundario', 'content', etc.)
     * @returns {Array} - Schema del role (array de definiciones de campos)
     */
    function obtenerSchemaDelRole(role) {
        if (!role) return [];
        
        // 1. Intentar desde gloryGbnCfg.roleSchemas (pasado desde PHP)
        if (typeof gloryGbnCfg !== 'undefined' && gloryGbnCfg.roleSchemas && gloryGbnCfg.roleSchemas[role]) {
            var roleData = gloryGbnCfg.roleSchemas[role];
            if (roleData.schema && Array.isArray(roleData.schema)) {
                return roleData.schema;
            }
        }
        
        // 2. Fallback: usar Gbn.content.roles si está disponible (estado runtime)
        if (Gbn.content && Gbn.content.roles && Gbn.content.roles.getRoleDefaults) {
            var defaults = Gbn.content.roles.getRoleDefaults(role);
            if (defaults && defaults.schema && Array.isArray(defaults.schema)) {
                return defaults.schema;
            }
        }
        
        return [];
    }

    /**
     * Estados CSS soportados para pseudo-clases
     * Centralizado aquí para consistencia entre módulos
     * (Fase 10: Soporte Hover/Focus)
     */
    var SUPPORTED_STATES = ['hover', 'focus', 'active', 'visited', 'focus-visible', 'focus-within'];

    /**
     * Obtiene la configuración de un estado específico del bloque
     * @param {Object} block - Bloque GBN
     * @param {string} state - Estado ('hover', 'focus', etc.)
     * @param {string} path - Ruta de la propiedad (opcional)
     * @returns {*} Configuración del estado o undefined
     */
    function getStateConfig(block, state, path) {
        if (!block || !block.config || !block.config._states) return undefined;
        if (SUPPORTED_STATES.indexOf(state) === -1) return undefined;
        
        var stateConfig = block.config._states[state];
        if (!stateConfig) return undefined;
        
        if (path) {
            // [FIX] Mapear el path de configuración (ej: 'typography.size') 
            // a la propiedad CSS correspondiente (ej: 'fontSize')
            // ya que _states almacena propiedades CSS planas.
            var cssProp = CONFIG_TO_CSS_MAP[path];
            
            // Si hay mapeo, usar la propiedad CSS
            if (cssProp) {
                return stateConfig[cssProp];
            }
            
            // Si no hay mapeo, intentar buscar por el path directo (fallback)
            // aunque esto raramente funcionará para estructuras anidadas
            return getDeepValue(stateConfig, path);
        }
        return stateConfig;
    }

    /**
     * Verifica si un bloque tiene estilos de estados definidos
     * @param {Object} block - Bloque GBN
     * @returns {boolean}
     */
    function hasStateStyles(block) {
        if (!block || !block.config || !block.config._states) return false;
        return Object.keys(block.config._states).length > 0;
    }

    // Exportar utilidades
    Gbn.ui.fieldUtils = {
        getDeepValue: getDeepValue,
        getThemeDefault: getThemeDefault,
        getConfigValue: getConfigValue,
        getComputedValue: getComputedValue,
        getComputedValueForPath: getComputedValueForPath,
        getEffectiveValue: getEffectiveValue,
        appendFieldDescription: appendFieldDescription,
        parseSpacingValue: parseSpacingValue,
        shouldShowField: shouldShowField,
        obtenerSchemaDelRole: obtenerSchemaDelRole,
        getResponsiveConfigValue: getResponsiveConfigValue,
        getValueSource: getValueSource,
        CONFIG_TO_CSS_MAP: CONFIG_TO_CSS_MAP,
        ICONS: ICONS,
        // Fase 10: Estados Hover/Focus
        SUPPORTED_STATES: SUPPORTED_STATES,
        getStateConfig: getStateConfig,
        hasStateStyles: hasStateStyles
    };


})(window);

