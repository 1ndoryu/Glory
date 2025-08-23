/* global jQuery */
(function ($) {
    'use strict';

    var STORAGE_KEY = 'glory_badgelist_state_v1';

    function loadState() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (e) {
            return {};
        }
    }

    function saveState(state) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {
            // noop
        }
    }

    function applyVisibility($root, activeSet) {
        // Mostrar/ocultar sólo elementos que coincidan con los targets presentes en la lista de badges
        if (!activeSet) return;

        var allowed = $root.find('.badge').map(function () { return $(this).data('target'); }).get();
        if (!allowed || allowed.length === 0) return;

        // Si todos los targets están activos, quitamos cualquier clase de oculto aplicada
        // anteriormente a los elementos correspondientes y no ocultamos nada.
        var anyInactive = allowed.some(function (t) { return !activeSet[t]; });
        if (!anyInactive) {
            $('[data-category], [id]').each(function () {
                var $el = $(this);
                var id = $el.attr('id');
                var cat = $el.data('category');

                var matchesAllowed = (id && allowed.indexOf(id) !== -1) || (cat && allowed.indexOf(cat) !== -1);
                if (!matchesAllowed) return;

                $el.removeClass('oculto');
            });
            return;
        }

        // Iterar sólo sobre elementos que tengan data-category o id y cuyo valor esté en allowed
        $('[data-category], [id]').each(function () {
            var $el = $(this);
            var id = $el.attr('id');
            var cat = $el.data('category');

            var matchesAllowed = (id && allowed.indexOf(id) !== -1) || (cat && allowed.indexOf(cat) !== -1);
            if (!matchesAllowed) return; // no es responsabilidad del componente

            var shouldShow = false;
            if (id && activeSet[id]) shouldShow = true;
            if (cat && activeSet[cat]) shouldShow = true;
            $el.toggleClass('oculto', !shouldShow);
        });
    }

    function init($root) {
        var mode = $root.data('mode') || 'toggle';
        var state = loadState() || {};
        $root.find('.badge').each(function () {
            var $b = $(this);
            var target = $b.data('target');
            // si no existe en state, por defecto true (visible)
            var active = state.hasOwnProperty(target) ? !!state[target] : true;
            // asegurar que el estado local incluya la key (soporta badges nuevos)
            state[target] = active;
            $b.toggleClass('activo', active).toggleClass('inactivo', !active);

            $b.on('click', function (e) {
                // Alt+click => mostrar solo lo seleccionado (solo en modo toggle o siempre)
                var isAlt = !!(e && e.altKey);

                if (isAlt) {
                    // construir nuevo estado: todo false excepto target
                    Object.keys(state).forEach(function (k) { state[k] = false; });
                    state[target] = true;

                    // actualizar clases visuales
                    $root.find('.badge').each(function () {
                        var t = $(this).data('target');
                        var a = !!state[t];
                        $(this).toggleClass('activo', a).toggleClass('inactivo', !a);
                    });

                    saveState(state);
                    applyVisibility($root, state);
                    try{ document.dispatchEvent(new Event('glory:seccionesActualizadas')); }catch(e){}
                    // Llamada directa a API de Glory si existe (fallback más fiable)
                    try{ if(window.Glory && typeof window.Glory.recalcularMasonryRowMajor === 'function'){ setTimeout(window.Glory.recalcularMasonryRowMajor, 60); } }catch(err){}

                    if (mode === 'navigate') {
                        var el = document.getElementById(target);
                        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }

                    return;
                }

                if (mode === 'navigate') {
                    var el = document.getElementById(target);
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    return;
                }

                if (mode === 'tab') {
                    // contar activos
                    var keys = Object.keys(state);
                    var activeCount = keys.reduce(function (acc, k) { return acc + (state[k] ? 1 : 0); }, 0);
                    var clickedActive = !!state[target];

                    if (activeCount === 1 && clickedActive) {
                        // estamos en single-mode y se hace click al mismo -> restaurar todo
                        keys.forEach(function (k) { state[k] = true; });
                    } else {
                        // seleccionar solo el objetivo
                        keys.forEach(function (k) { state[k] = (k === target); });
                    }

                    // actualizar clases visuales
                    $root.find('.badge').each(function () {
                        var t = $(this).data('target');
                        var a = !!state[t];
                        $(this).toggleClass('activo', a).toggleClass('inactivo', !a);
                    });

                    saveState(state);
                    applyVisibility($root, state);
                    try{ document.dispatchEvent(new Event('glory:seccionesActualizadas')); }catch(e){}
                    try{ if(window.Glory && typeof window.Glory.recalcularMasonryRowMajor === 'function'){ setTimeout(window.Glory.recalcularMasonryRowMajor, 60); } }catch(err){}
                    return;
                }

                // toggle normal
                var now = !$b.hasClass('activo');
                $b.toggleClass('activo', now).toggleClass('inactivo', !now);
                state[target] = now;
                saveState(state);

                // aplicar cambios de visibilidad localmente
                applyVisibility($root, state);
                try{ document.dispatchEvent(new Event('glory:seccionesActualizadas')); }catch(e){}
                try{ if(window.Glory && typeof window.Glory.recalcularMasonryRowMajor === 'function'){ setTimeout(window.Glory.recalcularMasonryRowMajor, 60); } }catch(err){}
            });
        });

        // Si tras procesar los badges NO hay ningún valor activo, mostramos todos por defecto
        var anyActive = Object.values(state).some(function (v) { return !!v; });
        if (!anyActive) {
            $root.find('.badge').each(function () {
                var t = $(this).data('target');
                state[t] = true;
                $(this).removeClass('inactivo').addClass('activo');
            });
            saveState(state);
        } else {
            // guardar posibles keys nuevas (cuando hay mezcla de guardado y badges nuevos)
            saveState(state);
        }

        // Aplicar al inicio
        applyVisibility($root, state);
        try{ document.dispatchEvent(new Event('glory:seccionesActualizadas')); }catch(e){}
        try{ if(window.Glory && typeof window.Glory.recalcularMasonryRowMajor === 'function'){ setTimeout(window.Glory.recalcularMasonryRowMajor, 60); } }catch(err){}
    }

    $(function () {
        $('.badge-list, .badgeList').each(function () {
            init($(this));
        });
    });
})(jQuery);


