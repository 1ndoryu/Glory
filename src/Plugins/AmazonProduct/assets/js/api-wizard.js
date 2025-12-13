/**
 * API Setup Wizard JavaScript
 * Maneja la interactividad del wizard de configuracion de API
 */
(function ($) {
    'use strict';

    const ApiWizard = {
        currentStep: 1,
        totalSteps: 4,

        init: function () {
            this.bindEvents();
            this.updateNavigation();
        },

        bindEvents: function () {
            $('#wizard-next').on('click', () => this.nextStep());
            $('#wizard-prev').on('click', () => this.prevStep());
            $('#wizard-test-api').on('click', () => this.testApiConnection());
            $('#wizard-save-api').on('click', () => this.saveApiKey());

            // Permitir navegar haciendo click en los pasos completados
            $('.progress-step').on('click', e => {
                const clickedStep = parseInt($(e.currentTarget).data('step'));
                if (clickedStep < this.currentStep) {
                    this.goToStep(clickedStep);
                }
            });
        },

        nextStep: function () {
            if (this.currentStep < this.totalSteps) {
                this.goToStep(this.currentStep + 1);
            }
        },

        prevStep: function () {
            if (this.currentStep > 1) {
                this.goToStep(this.currentStep - 1);
            }
        },

        goToStep: function (step) {
            // Ocultar paso actual
            $(`.wizard-step[data-step="${this.currentStep}"]`).removeClass('active');
            $(`.progress-step[data-step="${this.currentStep}"]`).removeClass('active');

            // Marcar pasos anteriores como completados
            for (let i = 1; i < step; i++) {
                $(`.progress-step[data-step="${i}"]`).addClass('completed');
            }

            // Remover completed de pasos siguientes
            for (let i = step; i <= this.totalSteps; i++) {
                $(`.progress-step[data-step="${i}"]`).removeClass('completed');
            }

            // Mostrar nuevo paso
            this.currentStep = step;
            $(`.wizard-step[data-step="${step}"]`).addClass('active');
            $(`.progress-step[data-step="${step}"]`).addClass('active');

            this.updateNavigation();
        },

        updateNavigation: function () {
            // Actualizar boton anterior
            $('#wizard-prev').prop('disabled', this.currentStep === 1);

            // Actualizar boton siguiente
            if (this.currentStep === this.totalSteps) {
                $('#wizard-next').hide();
            } else {
                $('#wizard-next').show();
            }
        },

        testApiConnection: function () {
            const apiKey = $('#wizard-api-key').val().trim();
            const $result = $('#wizard-api-result');
            const $button = $('#wizard-test-api');

            if (!apiKey) {
                this.showResult('error', 'Por favor, ingresa tu API Key antes de probar la conexion.');
                return;
            }

            // Mostrar loading
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Probando...');
            this.showResult('loading', 'Verificando conexion con la API...');

            // Hacer peticion de prueba
            $.ajax({
                url: apiWizardData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amazon_test_api_connection',
                    nonce: apiWizardData.nonce,
                    api_key: apiKey
                },
                success: response => {
                    if (response.success) {
                        this.showResult('success', 'Conexion exitosa! La API esta funcionando correctamente.');
                    } else {
                        this.showResult('error', response.data || 'Error al conectar con la API. Verifica tu clave.');
                    }
                },
                error: () => {
                    this.showResult('error', 'Error de conexion. Intenta nuevamente.');
                },
                complete: () => {
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-networking"></span> Probar conexion');
                }
            });
        },

        saveApiKey: function () {
            const apiKey = $('#wizard-api-key').val().trim();
            const $result = $('#wizard-api-result');
            const $button = $('#wizard-save-api');

            if (!apiKey) {
                this.showResult('error', 'Por favor, ingresa tu API Key.');
                return;
            }

            // Mostrar loading
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Guardando...');
            this.showResult('loading', 'Guardando tu API Key...');

            $.ajax({
                url: apiWizardData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amazon_save_api_key',
                    nonce: apiWizardData.nonce,
                    api_key: apiKey
                },
                success: response => {
                    if (response.success) {
                        this.showResult('success', 'API Key guardada correctamente!');

                        // Mostrar mensaje de exito
                        $('#wizard-success-message').removeClass('hidden');

                        // Actualizar estado en el header
                        $('.wizard-status').removeClass('wizard-status-pending').addClass('wizard-status-success');
                        $('.wizard-status').html('<span class="dashicons dashicons-yes-alt"></span><span>API configurada correctamente</span>');

                        // Marcar todos los pasos como completados
                        for (let i = 1; i <= this.totalSteps; i++) {
                            $(`.progress-step[data-step="${i}"]`).addClass('completed');
                        }
                    } else {
                        this.showResult('error', response.data || 'Error al guardar la API Key.');
                    }
                },
                error: () => {
                    this.showResult('error', 'Error de conexion. Intenta nuevamente.');
                },
                complete: () => {
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Guardar API Key');
                }
            });
        },

        showResult: function (type, message) {
            const $result = $('#wizard-api-result');
            $result.removeClass('hidden success error loading').addClass(type).html(message);
        }
    };

    // Inicializar cuando el DOM este listo
    $(document).ready(function () {
        if ($('#api-wizard-container').length) {
            ApiWizard.init();
        }
    });
})(jQuery);
