
(function(){
	'use strict';

	var instanceState = new WeakMap();

	function initInstance(rootEl, options){
		if (!rootEl || rootEl.dataset.circular === 'true') return;

		var speed = (options && typeof options.speed === 'number') ? options.speed : 20; // px/s
		var offset = 0;
		var lastTimestamp = null;
		var rafId = 0;

		rootEl.style.willChange = 'transform';
		rootEl.style.animation = 'none';

		function readGap(){
			var cs = getComputedStyle(rootEl);
			var g = parseInt(cs.gap || cs.columnGap || cs.rowGap || 0, 10);
			return isNaN(g) ? 0 : g;
		}

		var gap = readGap();

		// Asegurar que el contenido llena el ancho visible (y uno extra) para evitar huecos
		(function ensureFilled(){
			var children = rootEl.children;
			if (!children || !children.length) return;
			// Medir ancho actual del contenido
			var contentWidth = 0;
			for (var i=0;i<children.length;i++){
				contentWidth += children[i].offsetWidth + gap;
			}
			var firstEl = children[0];
			var firstW = firstEl ? (firstEl.offsetWidth + gap) : 0;
			// Necesitamos al menos viewport + ancho de un ítem para que el bucle sea fluido
			var needed = rootEl.clientWidth + firstW;
			// Clonar en bloques de originales hasta llenar
			var originals = Array.prototype.slice.call(children);
			var safety = 0;
			while (contentWidth < needed && safety < 4){
				for (var j=0;j<originals.length && contentWidth < needed; j++){
					var ref = originals[j];
					var w = ref.offsetWidth + gap;
					var clone = ref.cloneNode(true);
					clone.setAttribute('data-glory-clone','1');
					rootEl.appendChild(clone);
					contentWidth += w;
				}
				safety++;
			}
		})();

		function loop(ts){
			if (!lastTimestamp) lastTimestamp = ts;
			var delta = (ts - lastTimestamp) / 1000;
			lastTimestamp = ts;

			offset -= speed * delta;

			var first = rootEl.firstElementChild;
			if (first){
				var firstWidth = first.offsetWidth + gap;
				if (Math.abs(offset) >= firstWidth){
					offset += firstWidth;
					rootEl.appendChild(first);
				}
			}

			rootEl.style.transform = 'translateX(' + offset + 'px)';
			rafId = requestAnimationFrame(loop);
		}

		// Hover por item: añadir/quitar .is-hover en el item específico
		var instanceClass = (function(){
			var list = Array.prototype.slice.call(rootEl.classList);
			for (var i=0;i<list.length;i++){
				if (list[i].indexOf('glory-cr-') === 0) return list[i];
			}
			return '';
		})();
		var itemSelector = instanceClass ? ('.' + instanceClass + '__item') : null;
		var onOver = function(e){
			if (!itemSelector) return;
			var item = e.target && e.target.closest ? e.target.closest(itemSelector) : null;
			if (!item) return;
			if (item.contains(e.relatedTarget)) return;
			item.classList.add('is-hover');
		};
		var onOut = function(e){
			if (!itemSelector) return;
			var item = e.target && e.target.closest ? e.target.closest(itemSelector) : null;
			if (!item) return;
			if (item.contains(e.relatedTarget)) return;
			item.classList.remove('is-hover');
		};
		rootEl.addEventListener('mouseover', onOver);
		rootEl.addEventListener('mouseout', onOut);

		// Recalcular gap al redimensionar
		var onResize = function(){ gap = readGap(); };
		window.addEventListener('resize', onResize);

		rootEl.dataset.circular = 'true';
		rafId = requestAnimationFrame(loop);

		instanceState.set(rootEl, { rafId: rafId, onResize: onResize, onOver: onOver, onOut: onOut });

		// Reinicio suave en eventos personalizados
		document.addEventListener('gloryRecarga', function(){
			if (rootEl){
				rootEl.dataset.circular = '';
				rootEl.style.transform = '';
				lastTimestamp = null;
				offset = 0;
				rafId && cancelAnimationFrame(rafId);
				rafId = requestAnimationFrame(loop);
			}
		});
	}

	function stopInstance(rootEl){
		if (!rootEl) return;
		var state = instanceState.get(rootEl);
		if (state){
			state.rafId && cancelAnimationFrame(state.rafId);
			if (state.onResize) window.removeEventListener('resize', state.onResize);
			if (state.onOver) rootEl.removeEventListener('mouseover', state.onOver);
			if (state.onOut) rootEl.removeEventListener('mouseout', state.onOut);
			instanceState.delete(rootEl);
		}
		rootEl.dataset.circular = '';
		rootEl.style.transform = '';
	}

	window.GloryCarousel = window.GloryCarousel || {
		init: function(selector, opts){
			if (typeof selector === 'string'){
				var el = document.querySelector(selector);
				if (el) initInstance(el, opts);
			} else if (selector && selector.nodeType === 1){
				initInstance(selector, opts);
			}
		},
		stop: function(selector){
			if (typeof selector === 'string'){
				var el = document.querySelector(selector);
				if (el) stopInstance(el);
			} else if (selector && selector.nodeType === 1){
				stopInstance(selector);
			}
		}
	};

	// Procesar cola si hubo llamadas antes de que cargara este script
	var q = window.GloryCarouselQueue || [];
	while (q.length){
		try {
			var job = q.shift();
			if (job && job.selector){ window.GloryCarousel.init(job.selector, job.options || {}); }
		} catch(_e){}
	}
	window.GloryCarouselQueue = [];
})();


