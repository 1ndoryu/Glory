;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};

    var consoleLevels = ['log', 'warn', 'error'];

    function output(level, args) {
        var fn = (console && console[level]) ? console[level] : console.log;
        try {
            fn.apply(console, ['[GBN]'].concat(Array.prototype.slice.call(args || [])));
        } catch (_) {}
    }

    function toArray(list) {
        return Array.prototype.slice.call(list || []);
    }

    function camelToKebab(value) {
        return value.replace(/([a-z0-9])([A-Z])/g, '$1-$2').replace(/_/g, '-').toLowerCase();
    }

    function parseStyleString(style) {
        if (!style || typeof style !== 'string') {
            return {};
        }
        return style.split(';').reduce(function (acc, pair) {
            var chunk = pair.trim();
            if (!chunk) {
                return acc;
            }
            var idx = chunk.indexOf(':');
            if (idx === -1) {
                return acc;
            }
            var key = chunk.slice(0, idx).trim();
            var value = chunk.slice(idx + 1).trim();
            if (!key) {
                return acc;
            }
            acc[key] = value;
            return acc;
        }, {});
    }

    function stringifyStyles(styles) {
        if (!styles) {
            return '';
        }
        var parts = [];
        Object.keys(styles).forEach(function (key) {
            var value = styles[key];
            if (value === undefined || value === null || value === '') {
                return;
            }
            parts.push(key + ': ' + value);
        });
        return parts.join('; ');
    }

    function computeDomPath(el) {
        var node = el;
        var segments = [];
        while (node && node.nodeType === 1 && node !== document.body) {
            // Stop at data-gbn-root
            if (node.hasAttribute('data-gbn-root')) {
                break;
            }
            var tag = node.tagName.toLowerCase();
            
            // Ignore 'main' tag to fix inconsistency between client (with main) and server (without main)
            if (tag === 'main') {
                node = node.parentElement;
                continue;
            }

            var index = 0;
            var sibling = node;
            while (sibling.previousElementSibling) {
                sibling = sibling.previousElementSibling;
                if (sibling.tagName === node.tagName) {
                    index += 1;
                }
            }
            segments.unshift(tag + ':' + index);
            node = node.parentElement;
        }
        return segments.join('>');
    }

    function hashString(str) {
        var hash = 0, i, chr;
        if (str.length === 0) return hash;
        for (i = 0; i < str.length; i++) {
            chr = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + chr;
            hash |= 0; // Convert to 32bit integer
        }
        return Math.abs(hash);
    }

    function generateId(el) {
        var fallback = Math.floor(Math.random() * 1e6);
        try {
            var path = computeDomPath(el);
            // DEBUG: Log path generation
            // console.log('[GBN] Path for', el, 'is', path);
            return 'gbn-v3-' + hashString(path).toString(36);
        } catch (_) {
            return 'gbn-v3-' + fallback.toString(36);
        }
    }

    function isBuilderActive() {
        try {
            if (global.location && global.location.search.indexOf('fb-edit') !== -1) {
                return true;
            }
            if (global.FusionApp || global.FusionPageBuilder || global.FusionPageBuilderApp) {
                return true;
            }
            if (global.self !== global.top && global.top) {
                if (global.top.location.search.indexOf('fb-edit') !== -1) {
                    return true;
                }
                if (global.top.document && global.top.document.querySelector && global.top.document.querySelector('.fusion-builder-live-toolbar')) {
                    return true;
                }
            }
        } catch (_) {}
        return false;
    }

    function hasDocumentBody() {
        return !!(global.document && global.document.body);
    }

    function assign(target) {
        if (typeof Object.assign === 'function') {
            return Object.assign.apply(Object, arguments);
        }
        if (target === null || target === undefined) {
            throw new TypeError('Cannot convert undefined or null to object');
        }
        var output = Object(target);
        for (var index = 1; index < arguments.length; index += 1) {
            var source = arguments[index];
            if (source !== null && source !== undefined) {
                for (var key in source) {
                    if (Object.prototype.hasOwnProperty.call(source, key)) {
                        output[key] = source[key];
                    }
                }
            }
        }
        return output;
    }

    var utils = {
        consoleLevels: consoleLevels,
        debug: function () { output('log', arguments); },
        warn: function () { output('warn', arguments); },
        error: function () { output('error', arguments); },
        toArray: toArray,
        camelToKebab: camelToKebab,
        parseStyleString: parseStyleString,
        stringifyStyles: stringifyStyles,
        computeDomPath: computeDomPath,
        hashString: hashString,
        generateId: generateId,
        isBuilderActive: isBuilderActive,
        hasDocumentBody: hasDocumentBody,
        assign: assign,
        qs: function (selector, root) { return (root || document).querySelector(selector); },
        qsa: function (selector, root) { return toArray((root || document).querySelectorAll(selector)); },
        getConfig: function () { return global.gloryGbnCfg || {}; },
    };

    Gbn.utils = utils;
})(window);
