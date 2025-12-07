(function (global) {
    'use strict';

    var Gbn = (global.Gbn = global.Gbn || {});
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelRender = Gbn.ui.panelRender || {};

    /**
     * Style Resolvers - Mapa de funciones que obtienen estilos por rol
     *
     * Cada resolver delega a su renderer correspondiente para obtener
     * los estilos CSS calculados para un bloque.
     *
     * Este módulo centraliza la lógica de resolución de estilos que antes
     * estaba hardcodeada en panel-render.js
     */

    /**
     * Mapa de resolvers por rol.
     * Cada función recibe (config, block) y retorna un objeto de estilos CSS.
     */
    var resolvers = {
        // Layout básico
        principal: function (config, block) {
            return Gbn.ui.renderers.principal ? Gbn.ui.renderers.principal.getStyles(config, block) : {};
        },
        secundario: function (config, block) {
            return Gbn.ui.renderers.secundario ? Gbn.ui.renderers.secundario.getStyles(config, block) : {};
        },
        content: function () {
            return {};
        },

        // Componentes de contenido
        text: function (config, block) {
            return Gbn.ui.renderers.text ? Gbn.ui.renderers.text.getStyles(config, block) : {};
        },
        button: function (config, block) {
            return Gbn.ui.renderers.button ? Gbn.ui.renderers.button.getStyles(config, block) : {};
        },
        image: function (config, block) {
            return Gbn.ui.renderers.image ? Gbn.ui.renderers.image.getStyles(config, block) : {};
        },

        // Fase 13: PostRender - Contenido Dinámico
        postRender: function (config, block) {
            return Gbn.ui.renderers.postRender ? Gbn.ui.renderers.postRender.getStyles(config, block) : {};
        },
        postItem: function (config, block) {
            return Gbn.ui.renderers.postItem ? Gbn.ui.renderers.postItem.getStyles(config, block) : {};
        },
        postField: function (config, block) {
            return Gbn.ui.renderers.postField ? Gbn.ui.renderers.postField.getStyles(config, block) : {};
        },

        // Fase 14: Form Components
        form: function (config, block) {
            return Gbn.ui.renderers.form ? Gbn.ui.renderers.form.getStyles(config, block) : {};
        },
        input: function (config, block) {
            return Gbn.ui.renderers.input ? Gbn.ui.renderers.input.getStyles(config, block) : {};
        },
        textarea: function (config, block) {
            return Gbn.ui.renderers.textarea ? Gbn.ui.renderers.textarea.getStyles(config, block) : {};
        },
        select: function (config, block) {
            return Gbn.ui.renderers.select ? Gbn.ui.renderers.select.getStyles(config, block) : {};
        },
        submit: function (config, block) {
            return Gbn.ui.renderers.submit ? Gbn.ui.renderers.submit.getStyles(config, block) : {};
        },

        // Fase 15: Layout Components (Header, Footer, Menu, Logo)
        header: function (config, block) {
            return Gbn.ui.renderers.header ? Gbn.ui.renderers.header.getStyles(config, block) : {};
        },
        logo: function (config, block) {
            return Gbn.ui.renderers.logo ? Gbn.ui.renderers.logo.getStyles(config, block) : {};
        },
        menu: function (config, block) {
            return Gbn.ui.renderers.menu ? Gbn.ui.renderers.menu.getStyles(config, block) : {};
        },
        footer: function (config, block) {
            return Gbn.ui.renderers.footer ? Gbn.ui.renderers.footer.getStyles(config, block) : {};
        },
        menuItem: function (config, block) {
            return Gbn.ui.renderers.menuItem ? Gbn.ui.renderers.menuItem.getStyles(config, block) : {};
        },
        // TarjetaComponent - Tarjetas con imagen de fondo
        tarjeta: function (config, block) {
            return Gbn.ui.renderers.tarjeta ? Gbn.ui.renderers.tarjeta.getStyles(config, block) : {};
        }
    };

    /**
     * Obtiene el resolver para un rol específico
     * @param {string} role - Rol del bloque
     * @returns {Function} Función resolver
     */
    function getResolver(role) {
        return (
            resolvers[role] ||
            function () {
                return {};
            }
        );
    }

    /**
     * Aplica estilos a un bloque usando su resolver
     * @param {Object} block - Bloque a estilizar
     */
    function applyBlockStyles(block) {
        var styleManager = Gbn.styleManager;

        if (!block || !styleManager || !styleManager.update) {
            return;
        }

        var resolver = getResolver(block.role);
        var computedStyles = resolver(block.config || {}, block) || {};
        styleManager.update(block, computedStyles);
    }

    /**
     * Registra un nuevo resolver para un rol
     * @param {string} role - Nombre del rol
     * @param {Function} resolver - Función resolver (config, block) => styles
     */
    function registerResolver(role, resolver) {
        if (typeof resolver === 'function') {
            resolvers[role] = resolver;
        }
    }

    /**
     * Lista de roles que soportan edición de estados (hover/focus)
     */
    var statesSupportedRoles = ['principal', 'secundario', 'text', 'button', 'image', 'postRender', 'postItem', 'postField', 'form', 'input', 'textarea', 'select', 'submit', 'header', 'logo', 'menu', 'footer', 'menuItem', 'tarjeta'];

    /**
     * Verifica si un rol soporta edición de estados
     * @param {string} role
     * @returns {boolean}
     */
    function supportsStates(role) {
        return statesSupportedRoles.indexOf(role) !== -1;
    }

    // API Pública
    Gbn.ui.panelRender.styleResolvers = {
        getResolver: getResolver,
        applyBlockStyles: applyBlockStyles,
        registerResolver: registerResolver,
        supportsStates: supportsStates,
        // Acceso directo al mapa para compatibilidad
        resolvers: resolvers
    };
})(window);
