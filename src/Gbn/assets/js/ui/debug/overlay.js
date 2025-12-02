;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.debug = Gbn.ui.debug || {};

    var overlay = null;
    var content = null;
    var currentBlockId = null;
    var isVisible = false;

    function createOverlay() {
        if (overlay) return;

        overlay = document.createElement('div');
        overlay.id = 'gbn-debug-overlay';
        overlay.style.cssText = 'position: fixed; bottom: 10px; right: 10px; width: 400px; max-height: 80vh; background: rgba(0, 0, 0, 0.9); color: #0f0; font-family: monospace; font-size: 12px; z-index: 99999; overflow-y: auto; padding: 10px; border-radius: 4px; display: none; box-shadow: 0 0 10px rgba(0,0,0,0.5); border: 1px solid #333;';
        
        var header = document.createElement('div');
        header.style.cssText = 'display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #333; padding-bottom: 5px; font-weight: bold; color: #fff;';
        header.innerHTML = '<span>GBN GOD MODE</span><button id="gbn-debug-close" style="background:none;border:none;color:#f00;cursor:pointer;">X</button>';
        
        content = document.createElement('div');
        content.id = 'gbn-debug-content';

        overlay.appendChild(header);
        overlay.appendChild(content);
        document.body.appendChild(overlay);

        document.getElementById('gbn-debug-close').addEventListener('click', toggle);
    }

    function toggle() {
        if (!overlay) createOverlay();
        isVisible = !isVisible;
        overlay.style.display = isVisible ? 'block' : 'none';
        if (isVisible) update();
    }

    function getComputedStyles(el) {
        if (!el) return {};
        var style = window.getComputedStyle(el);
        return {
            display: style.display,
            position: style.position,
            width: style.width,
            height: style.height,
            padding: style.padding,
            margin: style.margin,
            flexDirection: style.flexDirection,
            flexWrap: style.flexWrap,
            justifyContent: style.justifyContent,
            alignItems: style.alignItems,
            gap: style.gap,
            backgroundColor: style.backgroundColor,
            color: style.color
        };
    }

    function update() {
        if (!isVisible || !currentBlockId) {
            if (content) content.innerHTML = '<div style="color:#888;text-align:center;padding:20px;">Select a block to inspect</div>';
            return;
        }

        var block = Gbn.state ? Gbn.state.getBlock(currentBlockId) : null;
        if (!block) {
            content.innerHTML = '<div style="color:#f00;">Block not found in State</div>';
            return;
        }

        var el = document.querySelector('[data-gbn-id="' + currentBlockId + '"]');
        var computed = getComputedStyles(el);
        var themeVars = getThemeVars(el);

        var html = '';

        // 1. Identity
        html += '<div style="margin-bottom:10px;"><strong style="color:#fff;">ID:</strong> ' + block.id + ' <span style="color:#888;">(' + (block.role || 'unknown') + ')</span></div>';

        // 2. Computed vs Config
        html += '<table style="width:100%; border-collapse:collapse; margin-bottom:10px;">';
        html += '<tr style="border-bottom:1px solid #333; color:#fff;"><th style="text-align:left;">Prop</th><th style="text-align:left;">Config</th><th style="text-align:left;">Computed</th></tr>';
        
        var props = ['padding', 'margin', 'width', 'height', 'display', 'flexDirection', 'flexWrap', 'gap', 'backgroundColor'];
        props.forEach(function(prop) {
            var configVal = (block.config && block.config[prop]) ? JSON.stringify(block.config[prop]) : '-';
            var compVal = computed[prop] || '-';
            var color = configVal !== '-' && compVal !== '-' && !compareValues(configVal, compVal) ? '#ff9900' : '#888';
            
            html += '<tr>';
            html += '<td style="color:#ccc;">' + prop + '</td>';
            html += '<td style="color:#fff;">' + configVal + '</td>';
            html += '<td style="color:' + color + ';">' + compVal + '</td>';
            html += '</tr>';
        });
        html += '</table>';

        // 3. Theme Context
        html += '<div style="margin-bottom:10px; border-top:1px solid #333; padding-top:5px;">';
        html += '<strong style="color:#fff;">Theme Context (CSS Vars):</strong><br/>';
        html += '<div style="font-size:10px; color:#aaa;">';
        for (var key in themeVars) {
            html += key + ': <span style="color:#fff;">' + themeVars[key] + '</span><br/>';
        }
        html += '</div></div>';

        // 4. Raw Config
        html += '<details><summary style="cursor:pointer;color:#fff;">Raw Config JSON</summary>';
        html += '<pre style="white-space:pre-wrap; color:#aaa;">' + JSON.stringify(block.config, null, 2) + '</pre>';
        html += '</details>';

        content.innerHTML = html;
    }

    function compareValues(conf, comp) {
        // Very basic comparison, mostly to highlight obvious diffs
        // Real logic would need unit normalization
        return conf.replace(/['"]/g, '') === comp;
    }

    function getThemeVars(el) {
        if (!el) return {};
        var styles = window.getComputedStyle(el);
        var vars = {};
        // We can't easily iterate all vars, but we can check key ones
        var keys = [
            '--gbn-principal-padding-top', 
            '--gbn-principal-background', 
            '--gbn-secundario-padding-top',
            '--gbn-secundario-display',
            '--gbn-secundario-flex-wrap'
        ];
        keys.forEach(function(k) {
            var v = styles.getPropertyValue(k).trim();
            if (v) vars[k] = v;
        });
        return vars;
    }

    // Hook into Inspector
    // We assume Gbn.ui.inspector exposes an event or we can monkey-patch select
    // Since we don't have a formal event bus yet, we'll monkey patch for now if needed, 
    // or rely on a global hook if one exists.
    // Checking Gbn.ui.inspector...
    
    function init() {
        createOverlay();
        
        // Listen for selection changes
        // Strategy: Polling for Gbn.ui.inspector.selectedBlockId if no event exists
        // Or hooking into Gbn.ui.inspector.selectBlock if accessible.
        
        // Let's try to hook into the function if it exists
        if (Gbn.ui && Gbn.ui.inspector) {
            var originalSelect = Gbn.ui.inspector.selectBlock; // Hypothetical name
            // If we can't find the function name, we might need to rely on the user telling us or looking at inspector.js
            // For now, let's assume we can listen to a document event or just poll.
            
            // Better: Listen to a custom event if the system emits one.
            // If not, let's add a global listener for 'gbn:block-selected' just in case we added it before.
            document.addEventListener('gbn:block-selected', function(e) {
                currentBlockId = e.detail.blockId;
                update();
            });
        }

        // Keyboard Shortcut: Alt+Shift+D (Avoids Ctrl+Shift+D bookmark conflict)
        document.addEventListener('keydown', function(e) {
            if (e.altKey && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                toggle();
            }
        });
        
        console.log('GBN Debug Overlay Initialized. Press Alt+Shift+D to toggle.');
    }

    Gbn.ui.debug.overlay = {
        init: init,
        toggle: toggle,
        update: update,
        setBlock: function(id) {
            currentBlockId = id;
            update();
        }
    };

})(typeof window !== 'undefined' ? window : this);
