(function(){
	if (window.gloryRealtime) return;

	function createEvent(detail){
		try { return new CustomEvent('gloryRealtime:update', { detail: detail }); } catch(_) { return null; }
	}

	function dispatch(detail){
		var ev1 = createEvent(detail);
		var ev2 = createEvent(detail);
		if (ev1) { try { window.dispatchEvent(ev1); } catch(_){} }
		if (ev2) { try { document.dispatchEvent(ev2); } catch(_){} }
	}

	function createBroadcaster(){
		var bc = null;
		try { if (typeof BroadcastChannel !== 'undefined') { bc = new BroadcastChannel('gloryRealtime'); } } catch(_){}
		function send(msg){
			if (bc) { try { bc.postMessage(msg); } catch(_){} }
			try {
				localStorage.setItem('gloryRealtimePing', JSON.stringify({ t: Date.now(), r: Math.random(), msg: msg }));
				localStorage.removeItem('gloryRealtimePing');
			} catch(_){}
		}
		function listen(){
			if (bc) { bc.onmessage = function(ev){ var d = ev && ev.data; if (d && d.channel) { dispatch({ channel: d.channel, version: d.version }); } }; }
			window.addEventListener('storage', function(e){
				if (!e || e.key !== 'gloryRealtimePing') return;
				try { var d = JSON.parse(e.newValue || '{}'); var m = d && d.msg; if (m && m.channel) { dispatch({ channel: m.channel, version: m.version }); } } catch(_){}
			});
		}
		listen();
		return { send: send };
	}

	function startPolling(channels, options){
		var opts = options || {};
		var intervalActive = Math.max(500, opts.intervalMsActive || 2500);
		var intervalHidden = Math.max(intervalActive, opts.intervalMsHidden || 15000);
		var idleMs = Math.max(5000, opts.idleMs || 30000);
		var offWhenIdle = opts.offWhenIdle !== false;
		var backoffFactor = Math.max(1, opts.backoffFactor || 2);
		var backoffMaxMs = Math.max(intervalHidden, opts.backoffMaxMs || 60000);
		var actionName = opts.actionName || 'glory_realtime_versions';
		var ajaxUrl = (opts.ajaxUrl) || ((typeof window.ajax_params !== 'undefined' && window.ajax_params.ajax_url) ? window.ajax_params.ajax_url : '/wp-admin/admin-ajax.php');

		var lastVersions = {};
		var timer = null;
		var stopped = false;
		var isIdle = false;
		var lastErrorMs = 0;
		var backoffMs = 0;
		var broadcaster = createBroadcaster();
		var lastActivityAt = Date.now();

		function schedule(nextMs){
			if (stopped) return;
			clearTimeout(timer);
			timer = setTimeout(tick, Math.max(250, nextMs));
		}

		function baseInterval(){
			if (offWhenIdle && isIdle) return 0;
			var hidden = (typeof document.hidden !== 'undefined') && document.hidden;
			return hidden ? intervalHidden : intervalActive;
		}

		function computeNextInterval(){
			var b = baseInterval();
			if (b === 0) return 0;
			if (lastErrorMs > 0) {
				backoffMs = Math.min(backoffMs > 0 ? backoffMs * backoffFactor : b * backoffFactor, backoffMaxMs);
				return b + backoffMs;
			}
			backoffMs = 0;
			return b;
		}

		function onSuccess(map){
			lastErrorMs = 0;
			Object.keys(map).forEach(function(ch){
				var info = map[ch] || {};
				var v = typeof info.version !== 'undefined' ? info.version : undefined;
				if (typeof v === 'undefined') return;
				var prev = lastVersions[ch];
				if (prev !== v) {
					lastVersions[ch] = v;
					// Propagar también payload si está disponible
					dispatch({ channel: ch, version: v, prevVersion: prev, payload: info.payload });
				}
			});
		}

		function tick(){
			if (stopped) return;
			if (offWhenIdle && isIdle) { return; }
			var body = new URLSearchParams();
			body.append('action', actionName);
			(channels || []).forEach(function(ch){ body.append('channels[]', ch); });
			fetch(ajaxUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body })
				.then(function(r){ return r.json().catch(function(){ return null; }); })
				.then(function(json){ if (json && json.success && json.data && json.data.channels) { onSuccess(json.data.channels); } else { lastErrorMs = Date.now(); } })
				.catch(function(){ lastErrorMs = Date.now(); })
				.finally(function(){ var next = computeNextInterval(); if (next > 0) schedule(next); });
		}

		function markActive(){
			lastActivityAt = Date.now();
			if (isIdle) { isIdle = false; if (!stopped) schedule(500); }
			resetIdleTimer();
		}

		var idleTimer = null;
		function resetIdleTimer(){
			clearTimeout(idleTimer);
			if (!offWhenIdle) return;
			idleTimer = setTimeout(function(){ isIdle = true; }, idleMs);
		}

		['mousemove','keydown','click','touchstart','scroll','focus'].forEach(function(evt){
			window.addEventListener(evt, markActive, true);
		});
		document.addEventListener('visibilitychange', function(){
			if (stopped) return;
			if ((typeof document.hidden !== 'undefined') && document.hidden) {
				if (offWhenIdle) { isIdle = true; }
			} else {
				markActive();
			}
		});

		resetIdleTimer();
		schedule(350);

		function stop(){
			stopped = true;
			clearTimeout(timer);
			clearTimeout(idleTimer);
		}

		function notify(channel, version){
			broadcaster.send({ channel: channel, version: version });
			dispatch({ channel: channel, version: version });
		}

		return { stop: stop, notify: notify };
	}

	window.gloryRealtime = {
		start: function(channels, options){
			var client = startPolling(channels, options || {});
			return client.stop;
		},
		notify: function(channel, version){
			try {
				var bc = createBroadcaster();
				bc.send({ channel: channel, version: version });
				dispatch({ channel: channel, version: version });
			} catch(_){}
		}
	};
})();


