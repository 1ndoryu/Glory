	(function(){
	'use strict';

	// Debug utility (no-op por defecto)
	function log(){}

	/**
	 * Espera a que los scripts de Avada Forms estén disponibles antes de inicializar
	 * @param {Function} callback - Función a ejecutar cuando estén listos
	 * @param {number} maxAttempts - Número máximo de intentos
	 */
	function waitForAvadaScripts(callback, maxAttempts){
		maxAttempts = maxAttempts || 50; // 50 intentos = 5 segundos máximo
		var attempts = 0;
		
		function check(){
			attempts++;
			var scriptsReady = window.fusionForms && 
			                   typeof window.fusionForms.onLoad === 'function' &&
			                   typeof window.fusionForms.onReady === 'function';
			
			if (scriptsReady) {
				log('Avada Forms scripts ready after', attempts, 'attempts');
				callback();
			} else if (attempts < maxAttempts) {
				setTimeout(check, 100);
			} else {
				log('Avada Forms scripts not found after', attempts, 'attempts');
			}
		}
		
		check();
	}

	/**
	 * Bridge mejorado: re-inicializa Avada Forms si están presentes
	 * Espera a que los scripts estén disponibles y los ejecuta en orden correcto
	 */
	function initForms(){
		try{
			log('initForms start');
			
			// Verificar si hay formularios en la página
			var wrappers = document.querySelectorAll('.fusion-form-builder.fusion-form-form-wrapper');
			log('wrappers count:', wrappers.length);
			
			if (!wrappers.length) {
				log('No forms found, skipping initialization');
				return;
			}
			
			wrappers.forEach(function(w){
				var cfg = w.getAttribute('data-config');
				try{ cfg = cfg ? JSON.parse(cfg) : null; }catch(_e){}
				var form = w.querySelector('form.fusion-form');
				log('wrapper:', { 
					id: w.getAttribute('data-form-id'), 
					nonce_method: cfg && cfg.nonce_method, 
					action: form && form.getAttribute('action') 
				});
			});

			// Esperar a que los scripts estén disponibles antes de ejecutar
			waitForAvadaScripts(function(){
				log('Initializing Avada Forms...');
				
				// PASO 1: onLoad para refrescar view/nonce cuando nonce_method = 'ajax'
				// Este método hace una llamada AJAX que puede tardar, así que usamos un callback
				if (window.fusionForms && typeof window.fusionForms.onLoad === 'function'){
					log('calling fusionForms.onLoad');
					window.fusionForms.onLoad();
				}
				
				// PASO 2: Esperar más tiempo porque onLoad hace peticiones AJAX
				// Aumentamos de 150ms a 500ms para dar tiempo a que complete
				setTimeout(function(){
					log('Continuing initialization after onLoad...');
					
					if (window.fusionForms && typeof window.fusionForms.onReady === 'function'){
						log('calling fusionForms.onReady');
						window.fusionForms.onReady();
					}
					
					// PASO 3: startFlatpickr para date/time pickers
					if (window.fusionForms && typeof window.fusionForms.startFlatpickr === 'function'){
						log('calling fusionForms.startFlatpickr');
						window.fusionForms.startFlatpickr();
					}
					
					// PASO 4: Form Logics (lógica condicional de campos)
					if (window.fusionFormLogics){
						if (typeof window.fusionFormLogics.onReady === 'function'){
							log('calling fusionFormLogics.onReady');
							window.fusionFormLogics.onReady();
						}
						if (typeof window.fusionFormLogics.formLogics === 'function'){
							log('calling fusionFormLogics.formLogics');
							window.fusionFormLogics.formLogics();
						}
					}
					
					// PASO 5: Recalcular dimensiones de elementos visuales
					if (window.fusionForms && typeof window.fusionForms.calcSelectArrowDimensions === 'function'){
						log('calling fusionForms.calcSelectArrowDimensions');
						window.fusionForms.calcSelectArrowDimensions();
					}
					if (window.fusionForms && typeof window.fusionForms.calcTooltipWidth === 'function'){
						log('calling fusionForms.calcTooltipWidth');
						window.fusionForms.calcTooltipWidth();
					}
					
					log('Avada Forms initialization complete');
				}, 500); // Aumentado de 150ms a 500ms para dar tiempo a AJAX de onLoad
			});
			
		}catch(e){
			if (window.console && console.warn){ 
				console.warn('[AvadaFormBridge] init error', e); 
			}
		}
	}

	// Al cargar DOM por primera vez
	document.addEventListener('DOMContentLoaded', function(){
		log('DOMContentLoaded, navConfig:', (window.gloryNavConfig||null));
		// En carga inicial, dar un poco más de tiempo para que todo esté listo
		setTimeout(initForms, 100);
	});

	// En cada recarga AJAX de Glory
	document.addEventListener('gloryRecarga', function(){
		log('gloryRecarga event received');
		// En recarga AJAX, ejecutar inmediatamente
		initForms();
	});

	// === Logs de debugging para diagnóstico ===
	document.addEventListener('click', function(e){
		var btn = e.target && e.target.closest && e.target.closest('.form-form-submit');
		if (btn){ 
			log('click submit button', { 
				formNumber: btn.getAttribute('data-form-number'),
				fusionFormsAvailable: !!window.fusionForms 
			}); 
		}
	}, true);

	document.addEventListener('submit', function(e){
		if (e.target && e.target.matches && e.target.matches('form.fusion-form')){
			log('native submit captured', { 
				action: e.target.getAttribute('action'), 
				method: e.target.getAttribute('method'),
				fusionFormsAvailable: !!window.fusionForms
			});
		}
	}, true);

	// Hooks de jQuery si disponible (eventos que Avada dispara)
	if (window.jQuery){
		try{
			window.jQuery(window).on('fusion-form-ajax-submit-done', function(_e, payload){ 
				log('fusion-form-ajax-submit-done', payload); 
			});
			window.jQuery(window).on('fusion-form-ajax-submit-fail', function(_e, payload){ 
				log('fusion-form-ajax-submit-fail', payload); 
			});
			window.jQuery(window).on('fusion-form-ajax-submitted', function(_e, payload){ 
				log('fusion-form-ajax-submitted', payload); 
			});
			window.jQuery(document).ajaxSend(function(_e, jqXHR, options){ 
				log('ajaxSend', { url: options && options.url, type: options && options.type }); 
			});
			window.jQuery(document).ajaxComplete(function(_e, jqXHR, options){ 
				log('ajaxComplete', { url: options && options.url, status: jqXHR && jqXHR.status }); 
			});
		}catch(e){ 
			log('jQuery hook error', e); 
		}
	}
})();


