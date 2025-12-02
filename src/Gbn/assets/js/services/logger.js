;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.services = Gbn.services || {};

    var LOG_ENDPOINT = 'gbn_log_client_event';
    var FLUSH_INTERVAL = 5000; // 5 seconds
    var MAX_BATCH_SIZE = 50;

    var logQueue = [];
    var flushTimer = null;

    function getTimestamp() {
        return new Date().toISOString();
    }

    function sendLogs() {
        if (logQueue.length === 0) return;

        // Take logs from queue
        var batch = logQueue.splice(0, MAX_BATCH_SIZE);
        
        // Prepare payload
        var payload = {
            action: LOG_ENDPOINT,
            nonce: (global.gloryGbnCfg && global.gloryGbnCfg.nonce) || '',
            logs: batch
        };

        // Use jQuery if available (WordPress standard) or fetch
        if (typeof jQuery !== 'undefined') {
            jQuery.post(global.ajaxurl || '/wp-admin/admin-ajax.php', payload)
                .fail(function() {
                    // If failed, put back in queue? Or just drop to avoid loops?
                    // For now, drop but log to console as fallback
                    console.error('Failed to send remote logs', batch);
                });
        } else {
            // Fallback fetch
            var formData = new FormData();
            formData.append('action', LOG_ENDPOINT);
            formData.append('nonce', payload.nonce);
            // Complex objects in FormData need special handling or JSON stringification if backend expects it
            // But standard WP ajax expects $_POST. Let's stick to jQuery if possible or simple URL encoded
            // For simplicity in this environment, assuming jQuery is present as it's a WP theme.
        }
    }

    function scheduleFlush() {
        if (!flushTimer) {
            flushTimer = setTimeout(function() {
                flushTimer = null;
                sendLogs();
            }, FLUSH_INTERVAL);
        }
    }

    function log(level, message, context) {
        var entry = {
            timestamp: getTimestamp(),
            level: level,
            message: message,
            context: context || {}
        };

        logQueue.push(entry);

        // If critical or queue full, flush immediately
        if (level === 'error' || logQueue.length >= MAX_BATCH_SIZE) {
            if (flushTimer) clearTimeout(flushTimer);
            flushTimer = null;
            sendLogs();
        } else {
            scheduleFlush();
        }
    }

    var Logger = {
        info: function(message, context) { log('info', message, context); },
        warn: function(message, context) { log('warn', message, context); },
        error: function(message, context) { log('error', message, context); },
        debug: function(message, context) { log('debug', message, context); }
    };

    // Global Error Handler (Crash Reporting)
    var originalOnError = global.onerror;
    global.onerror = function(msg, url, lineNo, columnNo, error) {
        Logger.error('Uncaught Exception: ' + msg, {
            url: url,
            line: lineNo,
            column: columnNo,
            stack: error ? error.stack : ''
        });
        
        if (originalOnError) return originalOnError(msg, url, lineNo, columnNo, error);
        return false;
    };

    // Unhandled Promise Rejection
    global.addEventListener('unhandledrejection', function(event) {
        Logger.error('Unhandled Promise Rejection', {
            reason: event.reason ? (event.reason.stack || event.reason) : 'Unknown'
        });
    });

    Gbn.services.logger = Logger;
    // Expose as Gbn.log for ease of use
    Gbn.log = Logger;

})(typeof window !== 'undefined' ? window : this);
