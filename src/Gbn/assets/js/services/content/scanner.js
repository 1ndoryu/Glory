;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    
    Gbn.content = Gbn.content || {};

    function scan(target) {
        var roles = Gbn.content.roles;
        var builder = Gbn.content.builder;
        var ROLE_MAP = roles.getMap();

        var root = target || document;
        var selectorParts = [];
        Object.keys(ROLE_MAP).forEach(function (role) {
            var map = ROLE_MAP[role];
            if (!map) {
                return;
            }
            if (map.attr) {
                selectorParts.push('[' + map.attr + ']');
            }
            if (map.dataAttr) {
                selectorParts.push('[' + map.dataAttr + ']');
            }
        });
        if (!selectorParts.length) {
            return [];
        }
        var nodes = utils.qsa(selectorParts.join(','), root);
        var blocks = [];
        
        // Reconciliación: Si estamos en modo editor y hay configuración guardada,
        // usamos la configuración como fuente de la verdad para la existencia de bloques.
        var cfg = utils.getConfig();
        var presets = cfg.presets || {};
        var configMap = presets.config;
        var shouldReconcile = cfg.contentMode === 'editor' && configMap && Object.keys(configMap).length > 0;

        nodes.forEach(function (el) {
            // Verificar si el elemento debe existir antes de construirlo
            if (shouldReconcile) {
                // Intentamos obtener el ID sin registrar todavía
                var id = el.getAttribute('data-gbn-id');
                // Si tiene ID y ese ID no está en la config guardada, es un bloque borrado.
                // Si no tiene ID, es un bloque nuevo en el HTML (posiblemente agregado manualmente),
                // en modo editor estricto podríamos borrarlo, pero por seguridad lo dejamos si no tiene ID.
                // Sin embargo, GBN asigna IDs estables. Si el usuario borró el bloque, el ID estaba en el HTML anterior.
                // Al recargar, el HTML sigue teniendo el ID.
                if (id && !configMap[id]) {
                    // Bloque borrado en sesión anterior
                    if (el.parentNode) {
                        el.parentNode.removeChild(el);
                    }
                    return; // Skip buildBlock
                }
            }

            var block = builder.buildBlock(el);
            if (block) {
                blocks.push(block);
            }
        });
        return blocks;
    }

    Gbn.content.scanner = {
        scan: scan
    };

})(window);
