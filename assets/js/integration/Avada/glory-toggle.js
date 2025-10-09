(function(){
	'use strict';

	var instanceState = new WeakMap();

	function parseAutoOpen(list){
		if (!Array.isArray(list)) {
			return [];
		}
		var out = [];
		for (var i = 0; i < list.length; i++) {
			var val = parseInt(list[i], 10);
			if (!isNaN(val) && val > 0) {
				out.push(val);
			}
		}
		return out;
	}

	function setExpanded(item, expanded){
		item.classList.toggle('is-open', expanded);
		item.setAttribute('aria-expanded', expanded ? 'true' : 'false');
		var triggers = item.querySelectorAll('[data-glory-toggle-trigger]');
		for (var i = 0; i < triggers.length; i++) {
			triggers[i].setAttribute('aria-expanded', expanded ? 'true' : 'false');
		}
		var content = item.querySelector('[data-glory-toggle-content]');
		if (content) {
			if (expanded) {
				content.hidden = false;
				content.style.opacity = '1';
				// Temporarily remove maxHeight to measure true height
				var prevMaxHeight = content.style.maxHeight;
				content.style.maxHeight = '';
				var fullHeight = content.scrollHeight;
				content.style.maxHeight = '0px';
				content.offsetHeight; // Trigger reflow
				content.style.maxHeight = fullHeight + 'px';
			} else {
				content.style.opacity = '0';
				content.style.maxHeight = '0px';
				var onTransitionEnd = function() {
					if (content.style.maxHeight === '0px') {
						content.hidden = true;
						content.style.maxHeight = '';
						content.style.opacity = '';
					}
					content.removeEventListener('transitionend', onTransitionEnd);
				};
				content.addEventListener('transitionend', onTransitionEnd);
				// Fallback in case transitionend doesn't fire
				setTimeout(function() {
					if (content.style.maxHeight === '0px') {
						content.hidden = true;
						content.style.maxHeight = '';
						content.style.opacity = '';
					}
				}, 350);
			}
		}
	}

	function toggleItem(item, desired){
		var expanded = item.classList.contains('is-open');
		var shouldOpen = (typeof desired === 'boolean') ? desired : !expanded;
		setExpanded(item, shouldOpen);
	}

	function getItemFromEvent(root, target){
		if (!target) return null;
		var trigger = target.closest('[data-glory-toggle-trigger]');
		if (trigger && root.contains(trigger)) {
			return trigger.closest('[data-glory-toggle-item]');
		}
		var item = target.closest('[data-glory-toggle-item]');
		if (!item || !root.contains(item)) return null;
		if (target.closest('[data-glory-toggle-content]') && !target.closest('[data-glory-toggle-trigger]')) {
			return null;
		}
		return item;
	}

	function prepareItem(item, config){
		item.setAttribute('aria-expanded', 'false');
		if (!item.hasAttribute('tabindex')) {
			item.setAttribute('tabindex', '0');
		}
		if (!item.hasAttribute('role')) {
			item.setAttribute('role', 'button');
		}
		var content = item.querySelector('[data-glory-toggle-content]');
		if (content) {
			content.hidden = true;
			content.setAttribute('data-glory-toggle-processed', 'true');
			if (!content.hasAttribute('role')) {
				content.setAttribute('role', 'region');
			}
			content.style.overflow = 'hidden';
			content.style.transition = 'max-height 0.3s ease-out, opacity 0.3s ease-out';
			content.style.maxHeight = '0px';
			content.style.opacity = '0';
		}
		var triggers = item.querySelectorAll('[data-glory-toggle-trigger]');
		if (!triggers.length) {
			item.setAttribute('data-glory-toggle-trigger', 'true');
		} else {
			for (var i = 0; i < triggers.length; i++) {
				var el = triggers[i];
				if (!el.hasAttribute('role')) {
					el.setAttribute('role', 'button');
				}
				if (!el.hasAttribute('tabindex')) {
					el.setAttribute('tabindex', '0');
				}
				el.setAttribute('aria-expanded', 'false');
			}
		}
		if (!config.separator) {
			var sep = item.querySelectorAll('.servicio-separador');
			for (var j = 0; j < sep.length; j++) {
				sep[j].style.display = 'none';
			}
		} else if (config.separatorColor) {
			var seps = item.querySelectorAll('.servicio-separador');
			for (var k = 0; k < seps.length; k++) {
				seps[k].style.backgroundColor = config.separatorColor;
			}
		}
	}

	function initInstance(rootEl, options){
		if (!rootEl) return;
		var existing = instanceState.get(rootEl);
		if (existing && typeof existing.destroy === 'function') {
			existing.destroy();
		}

		var config = {
			separator: !!(options && options.separator),
			separatorColor: options && typeof options.separatorColor === 'string' ? options.separatorColor : 'rgba(0,0,0,0.1)',
			autoOpen: parseAutoOpen(options && (options.autoOpen || options.autoopen)),
			defaultState: options && typeof options.defaultState === 'string' ? options.defaultState : 'collapsed',
			instanceClass: options && options.instanceClass ? String(options.instanceClass) : ''
		};

		var items = Array.prototype.slice.call(rootEl.querySelectorAll('[data-glory-toggle-item]'));
		if (!items.length) {
			return;
		}

		for (var i = 0; i < items.length; i++) {
			prepareItem(items[i], config);
		}

		var autoOpenSet = new Set(config.autoOpen);
		var hasAutoOpen = autoOpenSet.size > 0;
		for (var idx = 0; idx < items.length; idx++) {
			var item = items[idx];
			var indexAttr = parseInt(item.getAttribute('data-glory-toggle-index'), 10);
			var matchIndex = idx + 1;
			var shouldOpen = false;
			if (hasAutoOpen) {
				// Si hay autoOpen específico, respetarlo
				if (autoOpenSet.has(matchIndex)) {
					shouldOpen = true;
				} else if (!isNaN(indexAttr) && autoOpenSet.has(indexAttr)) {
					shouldOpen = true;
				}
			} else {
				// Si no hay autoOpen, aplicar estado por defecto
				shouldOpen = (config.defaultState === 'expanded');
			}
			toggleItem(item, shouldOpen);
		}

		function onClick(e){
			var item = getItemFromEvent(rootEl, e.target);
			if (!item) return;
			e.preventDefault();
			toggleItem(item);
		}

		function onKeydown(e){
			var key = e.key || e.keyCode;
			if (!(key === 'Enter' || key === ' ' || key === 13 || key === 32)) {
				return;
			}
			var item = getItemFromEvent(rootEl, e.target);
			if (!item) return;
			e.preventDefault();
			toggleItem(item);
		}

		rootEl.addEventListener('click', onClick);
		rootEl.addEventListener('keydown', onKeydown);

		var destroy = function(){
		rootEl.removeEventListener('click', onClick);
		rootEl.removeEventListener('keydown', onKeydown);
		instanceState.delete(rootEl);
		};

		instanceState.set(rootEl, {
			destroy: destroy,
			options: config
		});
		rootEl.setAttribute('data-glory-toggle-initialized', 'true');
		rootEl.setAttribute('data-glory-toggle-options', JSON.stringify(config));
	}

	function destroyInstance(rootEl){
		if (!rootEl) return;
		var state = instanceState.get(rootEl);
		if (state && typeof state.destroy === 'function') {
			state.destroy();
		}
	}

	window.GloryToggle = window.GloryToggle || {
		init: function(selector, opts){
			if (typeof selector === 'string') {
				var nodes = document.querySelectorAll(selector);
				if (!nodes.length) {
					return;
				}
				for (var i = 0; i < nodes.length; i++) {
					initInstance(nodes[i], opts);
				}
			} else if (selector && selector.nodeType === 1) {
				initInstance(selector, opts);
			}
		},
		destroy: function(selector){
			if (typeof selector === 'string') {
				var nodes = document.querySelectorAll(selector);
				for (var i = 0; i < nodes.length; i++) {
					destroyInstance(nodes[i]);
				}
			} else if (selector && selector.nodeType === 1) {
				destroyInstance(selector);
			}
		}
	};

	var queue = window.GloryToggleQueue || [];
	while (queue.length) {
		try {
			var job = queue.shift();
			if (job && job.selector) {
				var options = job.options || job.opt || {};
				window.GloryToggle.init(job.selector, options);
			}
		} catch (err) {
			if (window.console && console.error) {
				console.error('[GloryToggle] Error procesando cola', err);
			}
		}
	}
	window.GloryToggleQueue = [];

	document.addEventListener('gloryRecarga', function(){
		var roots = document.querySelectorAll('[data-glory-toggle-initialized="true"]');
		for (var i = 0; i < roots.length; i++) {
			var rootEl = roots[i];
			destroyInstance(rootEl);
			var optionsStr = rootEl.getAttribute('data-glory-toggle-options');
			var options = null;
			try {
				if (optionsStr) {
					options = JSON.parse(optionsStr);
				}
			} catch (e) {
				if (window.console && console.warn) {
					console.warn('[GloryToggle] Error parsing saved options:', e);
				}
			}
			window.GloryToggle.init(rootEl, options);
		}
	});

})();

