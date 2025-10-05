/**
 * Glory Export Panel JavaScript
 * Maneja la interactividad del panel de exportación
 */

(function($) {
    'use strict';

    // Objeto principal
    const GloryExport = {
        
        /**
         * Inicializa el módulo
         */
        init: function() {
            this.bindEvents();
            this.validateCurrentUrl();
            this.setupUrlSuggestions();
        },

        /**
         * Vincula eventos
         */
        bindEvents: function() {
            // Validación en tiempo real del campo URL
            $('#new_url').on('input', this.validateNewUrl.bind(this));
            
            // Confirmar antes de exportar
            $('.glory-export-form').on('submit', this.confirmExport.bind(this));
            
            // Auto-rellenar URL desde el portapapeles
            $('#new_url').on('paste', this.handlePaste.bind(this));
            
            // Copiar URL actual al portapapeles
            $('#current_url').on('click', this.copyCurrentUrl.bind(this));
            
            // Animación de carga
            $(document).on('click', '.glory-export-submit .button-primary', function() {
                $(this).addClass('loading');
            });

            // Mostrar/ocultar información adicional
            this.setupTooltips();
        },

        /**
         * Valida la URL actual
         */
        validateCurrentUrl: function() {
            const $currentUrl = $('#current_url');
            const currentUrl = $currentUrl.val();
            
            if (currentUrl) {
                // Detectar si es localhost o desarrollo
                if (this.isLocalUrl(currentUrl)) {
                    this.showDevEnvironmentNotice();
                }
            }
        },

        /**
         * Valida la URL nueva en tiempo real
         */
        validateNewUrl: function(e) {
            const $input = $(e.target);
            const url = $input.val().trim();
            const $fieldGroup = $input.closest('.glory-export-field-group');
            
            // Eliminar mensajes previos
            $fieldGroup.find('.validation-message').remove();
            $input.removeClass('invalid valid');

            if (!url) {
                return;
            }

            // Validar formato básico
            if (!this.isValidUrl(url)) {
                this.showValidationMessage($fieldGroup, 'Por favor, ingresa una URL válida (ej: https://ejemplo.com)', 'error');
                $input.addClass('invalid');
                return;
            }

            // Advertir si es la misma URL
            const currentUrl = $('#current_url').val();
            if (this.normalizeUrl(url) === this.normalizeUrl(currentUrl)) {
                this.showValidationMessage($fieldGroup, 'Esta es la misma URL actual. No se realizarán reemplazos.', 'warning');
                $input.addClass('valid');
                return;
            }

            // Advertir si es localhost
            if (this.isLocalUrl(url)) {
                this.showValidationMessage($fieldGroup, 'Advertencia: Esta es una URL local/desarrollo.', 'warning');
                $input.addClass('valid');
                return;
            }

            // URL válida
            this.showValidationMessage($fieldGroup, '✓ URL válida', 'success');
            $input.addClass('valid');
        },

        /**
         * Muestra mensaje de validación
         */
        showValidationMessage: function($container, message, type) {
            const iconMap = {
                'error': 'no-alt',
                'warning': 'warning',
                'success': 'yes-alt'
            };

            const colorMap = {
                'error': '#d63638',
                'warning': '#dba617',
                'success': '#00a32a'
            };

            const icon = iconMap[type] || 'info';
            const color = colorMap[type] || '#646970';

            const $message = $('<p>', {
                class: 'validation-message',
                html: `<span class="dashicons dashicons-${icon}"></span> ${message}`,
                css: {
                    color: color,
                    fontSize: '13px',
                    marginTop: '8px',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '5px'
                }
            });

            $container.append($message);
        },

        /**
         * Confirma antes de exportar
         */
        confirmExport: function(e) {
            const newUrl = $('#new_url').val().trim();
            const currentUrl = $('#current_url').val();
            const includeDropTables = $('input[name="include_drop_tables"]').is(':checked');

            if (!newUrl) {
                alert('Por favor, ingresa una URL de destino.');
                e.preventDefault();
                return false;
            }

            let message = `¿Confirmar exportación?\n\n`;
            message += `URL Actual: ${currentUrl}\n`;
            message += `URL Nueva: ${newUrl}\n\n`;
            
            if (includeDropTables) {
                message += `⚠️ Se incluirán DROP TABLE statements.\n`;
            }
            
            message += `\nEsto puede tomar algunos minutos dependiendo del tamaño de tu base de datos.`;

            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }

            // Agregar clase de carga al formulario
            $(e.target).addClass('loading');
        },

        /**
         * Maneja el pegado de URL
         */
        handlePaste: function(e) {
            setTimeout(() => {
                $(e.target).trigger('input');
            }, 10);
        },

        /**
         * Copia la URL actual al portapapeles
         */
        copyCurrentUrl: function(e) {
            const $input = $(e.target);
            $input.select();
            
            try {
                document.execCommand('copy');
                this.showTemporaryTooltip($input, 'Copiado al portapapeles');
            } catch (err) {
                console.error('Error al copiar:', err);
            }
        },

        /**
         * Muestra tooltip temporal
         */
        showTemporaryTooltip: function($element, message) {
            const $tooltip = $('<div>', {
                class: 'glory-export-tooltip',
                text: message,
                css: {
                    position: 'absolute',
                    background: '#1d2327',
                    color: '#fff',
                    padding: '8px 12px',
                    borderRadius: '4px',
                    fontSize: '12px',
                    zIndex: 1000,
                    whiteSpace: 'nowrap',
                    pointerEvents: 'none'
                }
            });

            const offset = $element.offset();
            $tooltip.css({
                top: offset.top - 40,
                left: offset.left
            });

            $('body').append($tooltip);

            setTimeout(() => {
                $tooltip.fadeOut(200, function() {
                    $(this).remove();
                });
            }, 2000);
        },

        /**
         * Configura sugerencias de URL
         */
        setupUrlSuggestions: function() {
            const currentUrl = $('#current_url').val();
            
            // Si es localhost, sugerir URL de producción
            if (this.isLocalUrl(currentUrl)) {
                const domain = this.extractDomain(currentUrl);
                if (domain && domain !== 'localhost') {
                    // Intentar sugerir versión de producción
                    const suggestion = `https://${domain}`;
                    this.addUrlSuggestion(suggestion);
                }
            }
        },

        /**
         * Agrega sugerencia de URL
         */
        addUrlSuggestion: function(url) {
            const $fieldGroup = $('#new_url').closest('.glory-export-field-group');
            const $suggestion = $('<div>', {
                class: 'url-suggestion',
                html: `
                    <span class="dashicons dashicons-lightbulb"></span>
                    Sugerencia: ¿Quieres usar <a href="#" class="use-suggestion" data-url="${url}">${url}</a>?
                `,
                css: {
                    background: '#f0f6fc',
                    border: '1px solid #c3ddf4',
                    borderRadius: '4px',
                    padding: '10px 15px',
                    marginTop: '10px',
                    fontSize: '13px',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px'
                }
            });

            $fieldGroup.append($suggestion);

            // Click en sugerencia
            $suggestion.find('.use-suggestion').on('click', function(e) {
                e.preventDefault();
                const suggestedUrl = $(this).data('url');
                $('#new_url').val(suggestedUrl).trigger('input');
                $suggestion.fadeOut(300, function() { $(this).remove(); });
            });
        },

        /**
         * Muestra aviso de entorno de desarrollo
         */
        showDevEnvironmentNotice: function() {
            const $notice = $('<div>', {
                class: 'notice notice-info inline',
                html: `
                    <p>
                        <span class="dashicons dashicons-info"></span>
                        <strong>Entorno de Desarrollo Detectado:</strong> 
                        Estás en un entorno local. Esta herramienta es perfecta para exportar 
                        tu base de datos local a producción.
                    </p>
                `,
                css: {
                    marginTop: '15px'
                }
            });

            $('.glory-export-form').prepend($notice);
        },

        /**
         * Configura tooltips
         */
        setupTooltips: function() {
            // Tooltip para el checkbox de DROP TABLES
            const $dropTablesLabel = $('input[name="include_drop_tables"]').parent();
            $dropTablesLabel.attr('title', 'Recomendado solo para bases de datos nuevas');
        },

        /**
         * Valida si una URL es válida
         */
        isValidUrl: function(url) {
            try {
                const urlObj = new URL(url);
                return urlObj.protocol === 'http:' || urlObj.protocol === 'https:';
            } catch (e) {
                return false;
            }
        },

        /**
         * Normaliza URL para comparación
         */
        normalizeUrl: function(url) {
            return url.trim().toLowerCase().replace(/\/$/, '');
        },

        /**
         * Detecta si una URL es local
         */
        isLocalUrl: function(url) {
            const localPatterns = [
                'localhost',
                '127.0.0.1',
                '192.168.',
                '10.0.',
                '.local',
                '.test',
                '.dev'
            ];

            const urlLower = url.toLowerCase();
            return localPatterns.some(pattern => urlLower.includes(pattern));
        },

        /**
         * Extrae el dominio de una URL
         */
        extractDomain: function(url) {
            try {
                const urlObj = new URL(url);
                let hostname = urlObj.hostname;
                
                // Remover extensiones de desarrollo
                hostname = hostname.replace(/\.(local|test|dev)$/, '.com');
                
                return hostname !== 'localhost' ? hostname : null;
            } catch (e) {
                return null;
            }
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        if ($('.glory-export-wrap').length) {
            GloryExport.init();
        }
    });

    // Exponer globalmente para debugging
    window.GloryExport = GloryExport;

})(jQuery);

