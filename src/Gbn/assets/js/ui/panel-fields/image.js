;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };

    /**
     * Construye un campo de imagen (URL + Preview + Galería WP)
     */
    function buildImageField(block, field) {
        var u = utils();
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-field';
        
        var label = document.createElement('label');
        label.className = 'gbn-field-label';
        label.textContent = field.etiqueta || field.id;
        wrapper.appendChild(label);
        
        var container = document.createElement('div');
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '10px';

        // Grupo Input + Botón
        var inputGroup = document.createElement('div');
        inputGroup.style.display = 'flex';
        inputGroup.style.gap = '5px';

        // Input URL
        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'gbn-input';
        input.placeholder = 'https://...';
        input.style.flex = '1';
        
        // Botón Galería
        var btnGallery = document.createElement('button');
        btnGallery.type = 'button';
        btnGallery.className = 'gbn-btn-secondary';
        btnGallery.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';
        btnGallery.title = 'Abrir Galería';
        btnGallery.style.padding = '5px 10px';
        btnGallery.style.display = 'flex';
        btnGallery.style.alignItems = 'center';
        btnGallery.style.justifyContent = 'center';
        
        inputGroup.appendChild(input);
        inputGroup.appendChild(btnGallery);
        
        // Preview (Clickable para abrir galería)
        var preview = document.createElement('img');
        preview.style.maxWidth = '100%';
        preview.style.height = 'auto';
        preview.style.maxHeight = '200px';
        preview.style.objectFit = 'contain';
        preview.style.borderRadius = '4px';
        preview.style.border = '1px solid #333';
        preview.style.display = 'none';
        preview.style.marginTop = '5px';
        preview.style.backgroundColor = '#1a1a1a'; // Fondo oscuro para ver PNGs transparentes
        preview.style.cursor = 'pointer'; // Indicador de click
        preview.title = 'Click para cambiar imagen';
        
        // Hover effect via JS styles (simple)
        preview.addEventListener('mouseenter', function() {
            preview.style.borderColor = '#007bff'; // Highlight color
            preview.style.opacity = '0.8';
        });
        preview.addEventListener('mouseleave', function() {
            preview.style.borderColor = '#333';
            preview.style.opacity = '1';
        });

        // Helper para parsear URL de CSS (url("..."))
        function parseCssUrl(val) {
            if (!val || val === 'none') return null;
            // Regex robusto para url(...) con comillas simples, dobles o sin ellas
            var match = /url\(\s*(['"]?)(.*?)\1\s*\)/.exec(val);
            return match ? match[2] : val;
        }

        var current = u.getConfigValue(block, field.id);
        var themeDefault = u.getThemeDefault(block.role, field.id);
        var computedVal = null;

        // Intentar leer del DOM si es backgroundImage
        if (field.id === 'backgroundImage' && (current === undefined || current === null)) {
            var effective = u.getEffectiveValue(block, field.id);
            if (effective.source === 'computed' && effective.value) {
                computedVal = parseCssUrl(effective.value);
            }
        }
        
        if (current === undefined || current === null) {
            wrapper.classList.add('gbn-field-inherited');
            if (computedVal) {
                input.value = computedVal;
                preview.src = computedVal;
                preview.style.display = 'block';
                wrapper.classList.add('gbn-source-computed');
                wrapper.title = 'Imagen desde CSS/Clase';
            } else if (themeDefault !== undefined && themeDefault !== null) {
                input.placeholder = themeDefault;
                preview.src = themeDefault;
                preview.style.display = 'block';
            } else {
                input.placeholder = field.defecto || '';
            }
        } else {
            wrapper.classList.add('gbn-field-override');
            input.value = current;
            preview.src = current;
            preview.style.display = 'block';
        }
        
        input.dataset.role = block.role;
        input.dataset.prop = field.id;
        
        function updateValue(val) {
            input.value = val;
            var event = new Event('input', { bubbles: true });
            input.dispatchEvent(event);
        }

        input.addEventListener('input', function () {
            var value = input.value.trim();
            
            if (value === '') {
                wrapper.classList.add('gbn-field-inherited');
                wrapper.classList.remove('gbn-field-override');
                preview.style.display = 'none';
            } else {
                wrapper.classList.remove('gbn-field-inherited');
                wrapper.classList.add('gbn-field-override');
                preview.src = value;
                preview.style.display = 'block';
            }
            
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) {
                api.updateConfigValue(block, field.id, value === '' ? null : value);
            }
        });

        // Integración WP Media
        var mediaFrame;
        function openMediaGallery(e) {
            if (e) e.preventDefault();
            
            if (typeof wp === 'undefined' || !wp.media) {
                alert('La galería de WordPress no está disponible. Asegúrate de estar logueado.');
                return;
            }

            if (mediaFrame) {
                mediaFrame.open();
                return;
            }

            mediaFrame = wp.media({
                title: 'Seleccionar Imagen',
                button: { text: 'Usar esta imagen' },
                multiple: false,
                library: { type: 'image' }
            });

            mediaFrame.on('select', function() {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                if (attachment && attachment.url) {
                    updateValue(attachment.url);
                }
            });

            mediaFrame.open();
        }

        btnGallery.addEventListener('click', openMediaGallery);
        preview.addEventListener('click', openMediaGallery); // Click en preview abre galería
        
        container.appendChild(inputGroup);
        container.appendChild(preview);
        wrapper.appendChild(container);
        u.appendFieldDescription(wrapper, field);
        
        return wrapper;
    }

    // Exportar
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.imageField = { build: buildImageField };

    if (Gbn.ui.panelFields && Gbn.ui.panelFields.registry) {
        Gbn.ui.panelFields.registry.register('image', buildImageField);
    }

})(window);
