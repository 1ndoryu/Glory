async function gloryRealtimePoll(channels, { intervalMs = 4000 } = {}) {
    if (!Array.isArray(channels) || channels.length === 0) {
        return () => {};
    }
    let stopped = false;
    let last = {};
    const listeners = new Map();

    const on = (channel, handler) => {
        if (!listeners.has(channel)) listeners.set(channel, new Set());
        listeners.get(channel).add(handler);
        return () => listeners.get(channel)?.delete(handler);
    };

    const emit = (channel, data) => {
        const set = listeners.get(channel);
        if (!set) return;
        set.forEach(fn => {
            try { fn(data); } catch (_) {}
        });
    };

    const tick = async () => {
        if (stopped) return;
        try {
            const payload = { channels };
            const resp = typeof window.gloryAjax === 'function'
                ? await window.gloryAjax('glory_realtime_versions', payload)
                : await (async () => {
                    const params = new URLSearchParams({ action: 'glory_realtime_versions' });
                    channels.forEach(c => params.append('channels[]', c));
                    const url = (typeof window.ajax_params !== 'undefined' && window.ajax_params.ajax_url)
                        ? window.ajax_params.ajax_url
                        : '/wp-admin/admin-ajax.php';
                    const r = await fetch(url, { method: 'POST', body: params });
                    return await r.json();
                })();
            // Silencioso por defecto; descomenta para debug:
            // if (window && window.console && console.debug) { console.debug('[gloryRealtime] poll response', resp); }
            const map = (resp && resp.data && resp.data.channels) ? resp.data.channels : {};
            for (const ch of channels) {
                const curr = map[ch] || { version: 0 };
                const prev = last[ch] || { version: 0 };
                if (curr.version !== prev.version) {
                    emit(ch, curr);
                    try {
                        // Propagar también el payload si viene del backend
                        document.dispatchEvent(new CustomEvent('gloryRealtime:update', { detail: { channel: ch, info: curr, payload: curr && curr.payload } }));
                    } catch(_) {}
                }
                last[ch] = curr;
            }
        } catch (err) { /* silencioso */ }
        finally {
            if (!stopped) setTimeout(tick, intervalMs);
        }
    };

    setTimeout(tick, intervalMs);

    return () => { stopped = true; };
}

window.gloryRealtimePoll = gloryRealtimePoll;


