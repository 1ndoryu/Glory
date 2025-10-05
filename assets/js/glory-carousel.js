
(function(){
	'use strict';

	var instanceState = new WeakMap();

	function initInstance(rootEl, options){
		if (!rootEl || rootEl.dataset.circular === 'true') return;

		var speed = (options && typeof options.speed === 'number') ? options.speed : 20; // px/s
		var offset = 0;
		var lastTimestamp = null;
		var rafId = 0;
		var running = false;

		rootEl.style.willChange = 'transform';
		rootEl.style.animation = 'none';

		function readGap(){
			var cs = getComputedStyle(rootEl);
			var g = parseInt(cs.gap || cs.columnGap || cs.rowGap || 0, 10);
			return isNaN(g) ? 0 : g;
		}

		var gap = readGap();

		function measureContentWidth(){
			var children = rootEl.children;
			if (!children || !children.length) return 0;
			var w = 0;
			for (var i=0;i<children.length;i++){
				w += children[i].offsetWidth + gap;
			}
			return w;
		}

		function hasEnoughWidthForLoop(){
			var first = rootEl.firstElementChild;
			var firstW = first ? (first.offsetWidth + gap) : 0;
			var contentW = measureContentWidth();
			// Arranca si el contenido es más ancho que el contenedor (sin exigir + primer ítem)
			return (contentW - rootEl.clientWidth) > 1 && firstW > 0;
		}

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

		function startLoopIfPossible(){
			if (running) return;
			if (!hasEnoughWidthForLoop()) return;
			running = true;
			offset = 0;
			lastTimestamp = null;
			rafId = requestAnimationFrame(loop);
		}

		function stopLoop(){
			if (!running) return;
			running = false;
			rafId && cancelAnimationFrame(rafId);
			rafId = 0;
			rootEl.style.transform = '';
			offset = 0;
			lastTimestamp = null;
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

		// Recalcular gap al redimensionar y gestionar inicio/parada del loop
		var onResize = function(){
			gap = readGap();
			if (!hasEnoughWidthForLoop()) {
				stopLoop();
			} else {
				startLoopIfPossible();
			}
		};
		window.addEventListener('resize', onResize);
		// Reintentos tras el primer paint y tras posibles cargas de imagen
		var retryId1 = setTimeout(startLoopIfPossible, 250);
		var retryId2 = setTimeout(startLoopIfPossible, 1000);
		var onImgLoad = function(){ startLoopIfPossible(); };
		rootEl.addEventListener('load', onImgLoad, true);

		rootEl.dataset.circular = 'true';
		startLoopIfPossible();

		instanceState.set(rootEl, { rafId: rafId, onResize: onResize, onOver: onOver, onOut: onOut, onImgLoad: onImgLoad, retryId1: retryId1, retryId2: retryId2 });

		// Reinicio suave en eventos personalizados
		document.addEventListener('gloryRecarga', function(){
			if (rootEl){
				stopLoop();
				startLoopIfPossible();
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
			if (state.onImgLoad) rootEl.removeEventListener('load', state.onImgLoad, true);
			if (state.retryId1) clearTimeout(state.retryId1);
			if (state.retryId2) clearTimeout(state.retryId2);
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


