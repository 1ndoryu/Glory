// gloryCalendario: componente UI agnóstico para mostrar un calendario
(function (global) {
    const calNombresMeses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    let calAnio = null;
    let calMes = null;
    let contextoCalendario = null;

    function crearEstructura() {
        let cal = document.getElementById('calCont');
        if (!cal) {
            cal = document.createElement('div');
            cal.id = 'calCont';
            cal.className = 'calCont';
            cal.style.display = 'none';
            cal.style.position = 'absolute';
            cal.style.zIndex = '1001';
            document.body.appendChild(cal);
        }

        if (!document.getElementById('calPrev') || !document.getElementById('calNext') || !document.getElementById('calMesAnio')) {
            const nav = document.createElement('div');
            nav.className = 'calNav';
            const btnPrev = document.createElement('button');
            btnPrev.type = 'button';
            btnPrev.id = 'calPrev';
            btnPrev.className = 'calNavBtn';
            const spanMesAnio = document.createElement('span');
            spanMesAnio.id = 'calMesAnio';
            spanMesAnio.className = 'calMesAnio';
            const btnNext = document.createElement('button');
            btnNext.type = 'button';
            btnNext.id = 'calNext';
            btnNext.className = 'calNavBtn';
            nav.appendChild(btnPrev);
            nav.appendChild(spanMesAnio);
            nav.appendChild(btnNext);
            cal.appendChild(nav);
        }

        if (!document.querySelector('.calTabla')) {
            const tabla = document.createElement('table');
            tabla.className = 'calTabla';
            const thead = document.createElement('thead');
            const trDias = document.createElement('tr');
            trDias.id = 'calDiasSemana';
            thead.appendChild(trDias);
            const tbody = document.createElement('tbody');
            tbody.id = 'calBody';
            tbody.className = 'calBody';
            tabla.appendChild(thead);
            tabla.appendChild(tbody);
            cal.appendChild(tabla);
        }

        if (!document.getElementById('calHoyBtn') || !document.getElementById('calBorrarBtn')) {
            const acciones = document.createElement('div');
            acciones.className = 'calAcciones';
            const hoyBtn = document.createElement('button');
            hoyBtn.type = 'button';
            hoyBtn.id = 'calHoyBtn';
            hoyBtn.className = 'calBtnAccion';
            hoyBtn.textContent = 'Hoy';
            const borrarBtn = document.createElement('button');
            borrarBtn.type = 'button';
            borrarBtn.id = 'calBorrarBtn';
            borrarBtn.className = 'calBtnAccion';
            borrarBtn.textContent = 'Borrar';
            acciones.appendChild(hoyBtn);
            acciones.appendChild(borrarBtn);
            cal.appendChild(acciones);
        }

        if (!document.getElementById('inputFechaLimite')) {
            const input = document.createElement('input');
            input.type = 'date';
            input.id = 'inputFechaLimite';
            input.style.display = 'none';
            cal.appendChild(input);
        }
    }

    function mostrarCal(elementoRef, fechaActualISO) {
        const cal = document.getElementById('calCont');
        if (!cal || !elementoRef) return;

        const rect = elementoRef.getBoundingClientRect();
        cal.style.top = rect.bottom + window.scrollY + 5 + 'px';
        cal.style.left = rect.left + window.scrollX + 'px';

        let usarFechaActualPredeterminada = true;
        if (fechaActualISO) {
            const partes = fechaActualISO.split('-');
            if (partes.length === 3) {
                const anioParseado = parseInt(partes[0], 10);
                const mesParseado0Index = parseInt(partes[1], 10) - 1;
                if (anioParseado > 0 && mesParseado0Index >= 0 && mesParseado0Index <= 11) {
                    calAnio = anioParseado;
                    calMes = mesParseado0Index;
                    usarFechaActualPredeterminada = false;
                }
            }
        }

        if (usarFechaActualPredeterminada) {
            const hoy = new Date();
            calAnio = hoy.getFullYear();
            calMes = hoy.getMonth();
        }

        cal.style.display = 'block';
        renderCal();

        if (!document._calClickListener) {
            document._calClickListener = event => {
                if (cal.style.display === 'block' && !cal.contains(event.target) && contextoCalendario && contextoCalendario.elementoDisparador && !contextoCalendario.elementoDisparador.contains(event.target)) {
                    ocultarCal();
                }
            };
            setTimeout(() => document.addEventListener('click', document._calClickListener), 0);
        }
    }

    function ocultarCal() {
        const cal = document.getElementById('calCont');
        if (cal) cal.style.display = 'none';
        if (document._calClickListener) {
            document.removeEventListener('click', document._calClickListener);
            document._calClickListener = null;
        }
    }

    function renderCal() {
        const calMesAnioEl = document.getElementById('calMesAnio');
        const calBodyEl = document.getElementById('calBody');
        if (!calMesAnioEl || !calBodyEl) return;

        calMesAnioEl.textContent = `${calNombresMeses[calMes]} ${calAnio}`;
        calBodyEl.innerHTML = '';

        const primerDiaMes = new Date(calAnio, calMes, 1);
        const diasEnMes = new Date(calAnio, calMes + 1, 0).getDate();
        let diaSemanaPrimerDia = primerDiaMes.getDay();
        diaSemanaPrimerDia = diaSemanaPrimerDia === 0 ? 6 : diaSemanaPrimerDia - 1;

        const hoy = new Date();
        const hoyStr = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`;

        let fechaSeleccionadaActual = null;
        if (contextoCalendario && contextoCalendario.esParaTareaEspecifica && contextoCalendario.elementoLiTarea) {
            if (contextoCalendario.tipoFecha === 'proxima') {
                fechaSeleccionadaActual = contextoCalendario.elementoLiTarea.dataset.proxima;
            } else {
                fechaSeleccionadaActual = contextoCalendario.elementoLiTarea.dataset.fechalimite;
            }
        } else if (contextoCalendario && !contextoCalendario.esParaTareaEspecifica && contextoCalendario.tipoFecha === 'limite') {
            fechaSeleccionadaActual = contextoCalendario.fechaLimiteValor || null;
        }

        let fechaActualDia = 1;
        for (let i = 0; i < 6; i++) {
            const fila = document.createElement('tr');
            for (let j = 0; j < 7; j++) {
                const celda = document.createElement('td');
                const divDia = document.createElement('div');
                divDia.classList.add('calDia');

                if ((i === 0 && j < diaSemanaPrimerDia) || fechaActualDia > diasEnMes) {
                    divDia.classList.add('calDiaFuera');
                } else {
                    const spanNum = document.createElement('span');
                    spanNum.classList.add('calDiaNum');
                    spanNum.textContent = fechaActualDia;
                    divDia.appendChild(spanNum);

                    const fechaCompletaStr = `${calAnio}-${String(calMes + 1).padStart(2, '0')}-${String(fechaActualDia).padStart(2, '0')}`;
                    celda.dataset.fecha = fechaCompletaStr;

                    if (fechaCompletaStr === hoyStr) divDia.classList.add('calDiaHoy');
                    if (fechaSeleccionadaActual === fechaCompletaStr) divDia.classList.add('calDiaSel');

                    celda.onclick = e => {
                        const fechaSel = e.currentTarget.dataset.fecha;
                        if (contextoCalendario && contextoCalendario.onSelect) {
                            contextoCalendario.onSelect(fechaSel, contextoCalendario);
                        }
                        ocultarCal();
                    };
                    fechaActualDia++;
                }
                celda.appendChild(divDia);
                fila.appendChild(celda);
            }
            calBodyEl.appendChild(fila);
            if (fechaActualDia > diasEnMes && i >= Math.floor((diaSemanaPrimerDia + diasEnMes - 1) / 7)) break;
        }
    }

    function init(options = {}) {
        crearEstructura();

        const selector = options.triggerSelector || '.gloryCalendario';
        const triggers = document.querySelectorAll(selector);

        // Ensure we have a shared contextoCalendario object that will be updated
        contextoCalendario = contextoCalendario || {};
        contextoCalendario.esParaTareaEspecifica = false;
        contextoCalendario.idTarea = null;
        contextoCalendario.elementoSpanTexto = null;
        contextoCalendario.elementoLiTarea = null;
        contextoCalendario.tipoFecha = options.tipoFecha || 'limite';
        contextoCalendario.fechaLimiteValor = options.fechaLimiteValor || null;
        // Handler por defecto: respeta data-target y data-target-input del trigger
        contextoCalendario.onSelect = options.onSelect || function (fechaISO, contexto) {
            try {
                const trigger = contexto && contexto.elementoDisparador;
                if (!trigger) return;

                const targetSel = trigger.dataset.target;
                if (targetSel) {
                    const targetEl = document.querySelector(targetSel);
                    if (targetEl) targetEl.textContent = fechaISO || '-';
                }

                const inputSel = trigger.dataset.targetInput;
                if (inputSel) {
                    const inputEl = document.querySelector(inputSel);
                    if (inputEl) inputEl.value = fechaISO || '';
                }

                if (fechaISO) trigger.dataset.selected = fechaISO; else delete trigger.dataset.selected;
            } catch (err) {
                // No interrumpir si el handler por defecto falla
                console.error('gloryCalendario onSelect default error', err);
            }
        };

        if (!triggers || triggers.length === 0) {
            // No triggers found; the component sigue siendo utilizable mediante API.
            return;
        }

        triggers.forEach(el => {
            // Remove previous handler if present
            if (el._gloryCalHandler) el.removeEventListener('click', el._gloryCalHandler);

            const handler = e => {
                e.stopPropagation();
                // Update contexto for this trigger
                contextoCalendario.elementoDisparador = el;
                // Allow per-element data attributes: data-fechalimite, data-tipofecha
                const dataFecha = el.dataset.fechalimite || options.fechaLimiteValor || null;
                const dataTipo = el.dataset.tipofecha || options.tipoFecha || 'limite';
                contextoCalendario.fechaLimiteValor = dataFecha;
                contextoCalendario.tipoFecha = dataTipo;

                if (document.getElementById('calCont').style.display === 'block') {
                    ocultarCal();
                } else {
                    mostrarCal(el, contextoCalendario.fechaLimiteValor);
                }
            };

            el.addEventListener('click', handler);
            el._gloryCalHandler = handler;
        });

        // Navegación básica
        const calPrevBtn = document.getElementById('calPrev');
        const calNextBtn = document.getElementById('calNext');
        const calHoyBtn = document.getElementById('calHoyBtn');
        const calBorrarBtn = document.getElementById('calBorrarBtn');

        if (calPrevBtn) calPrevBtn.onclick = () => { calMes--; if (calMes < 0) { calMes = 11; calAnio--; } renderCal(); };
        if (calNextBtn) calNextBtn.onclick = () => { calMes++; if (calMes > 11) { calMes = 0; calAnio++; } renderCal(); };
        if (calHoyBtn) calHoyBtn.onclick = () => { const hoy = new Date(); const fechaHoyStr = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`; if (contextoCalendario.onSelect) contextoCalendario.onSelect(fechaHoyStr, contextoCalendario); ocultarCal(); };
        if (calBorrarBtn) calBorrarBtn.onclick = () => { if (contextoCalendario.onSelect) contextoCalendario.onSelect(null, contextoCalendario); ocultarCal(); };
    }

    // Exponer API mínima
    global.gloryCalendario = {
        init,
        mostrar: mostrarCal,
        ocultar: ocultarCal,
        render: renderCal
    };
})(window);

// Auto-inicializar el calendario si existen elementos con la clase `.gloryCalendario`
document.addEventListener('DOMContentLoaded', function () {
    try {
        if (document.querySelectorAll && document.querySelectorAll('.gloryCalendario').length && window.gloryCalendario && typeof window.gloryCalendario.init === 'function') {
            window.gloryCalendario.init();
        }
    } catch (e) {
        // No hacer nada si algo falla al auto-iniciar
    }
});


