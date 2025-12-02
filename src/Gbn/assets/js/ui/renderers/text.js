;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    function getStyles(config, block) {
        var styles = {};
        if (config.alineacion) { styles['text-align'] = config.alineacion; }
        if (config.color) { styles['color'] = config.color; }
        if (config.size) { styles['font-size'] = config.size; } // Legacy support
        
        if (config.typography) {
            var t = config.typography;
            if (t.font && t.font !== 'System' && t.font !== 'Default') { styles['font-family'] = t.font; }
            if (t.size) { styles['font-size'] = t.size; }
            if (t.lineHeight) { styles['line-height'] = t.lineHeight; }
            if (t.letterSpacing) { styles['letter-spacing'] = t.letterSpacing; }
            if (t.transform && t.transform !== 'none') { styles['text-transform'] = t.transform; }
        }
        return styles;
    }

    function handleUpdate(block, path, value) {
        if (path === 'tag') {
             // Switch tag
             var oldEl = block.element;
             var newTag = value || 'p';
             if (oldEl.tagName.toLowerCase() !== newTag.toLowerCase()) {
                 var newEl = document.createElement(newTag);
                 
                 // Copy attributes
                 Array.from(oldEl.attributes).forEach(function(attr) {
                     newEl.setAttribute(attr.name, attr.value);
                 });
                 
                 // Copy content
                 newEl.innerHTML = oldEl.innerHTML;
                 
                 // Replace in DOM
                 if (oldEl.parentNode) {
                    oldEl.parentNode.replaceChild(newEl, oldEl);
                    block.element = newEl;
                 }
             }
         }
         
         // Apply inline styles directly for text
         if (path === 'texto') {
             var controls = block.element.querySelector('.gbn-controls-group');
             block.element.innerHTML = value; // Use innerHTML for rich text
             if (controls) {
                 block.element.appendChild(controls);
             }
         }
         if (path === 'alineacion') {
             block.element.style.textAlign = value;
         }
         if (path === 'color') {
             block.element.style.color = value;
         }
         if (path === 'size') {
             var sizeVal = value;
             if (sizeVal && !isNaN(parseFloat(sizeVal)) && isFinite(sizeVal)) {
                 // Check if it already has units. If it's just a number string "22", add px.
                 // If it is "22px" or "1.5rem", leave it.
                 if (!/^[0-9.]+[a-z%]+$/i.test(sizeVal)) {
                     sizeVal += 'px';
                 }
             }
             block.element.style.fontSize = sizeVal;
         }
         
         return true; // Handled
    }

    Gbn.ui.renderers.text = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
