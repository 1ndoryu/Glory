function gloryScheduler() {
    document.querySelectorAll('.glorySchedulerContenedor').forEach(inicializarCuadricula);
}

function inicializarCuadricula(contenedor) {
    if (contenedor.dataset.inicializado) {
        return;
    }
    contenedor.dataset.inicializado = 'true';

    const eventos = JSON.parse(contenedor.dataset.eventos || '[]');
    const config = JSON.parse(contenedor.dataset.config || '{}');
    if (!config.mapeoColores || typeof config.mapeoColores !== 'object') {
        config.mapeoColores = {};
    }
    const capaEventos = contenedor.querySelector('.capaEventos');
    const grid = contenedor.querySelector('.glorySchedulerGrid');

    if (!config.recursos || !capaEventos || !grid) {
        return;
    }

    const inicioMinutosTotal = convertirHoraAMinutos(config.horaInicio);
    const finMinutosTotal = convertirHoraAMinutos(config.horaFin);
    const duracionTotalMinutos = finMinutosTotal - inicioMinutosTotal;
    const numeroFilas = duracionTotalMinutos / config.intervalo;

    // Altura fija por intervalo basada en px por minuto para precisión absoluta
    const pxPorMinuto = Number(config.pxPorMinuto) > 0 ? Number(config.pxPorMinuto) : 2; // default: 2px/minuto
    const altoIntervaloPx = pxPorMinuto * config.intervalo;

    const gridTemplateColumns = `60px repeat(${config.recursos.length}, minmax(160px, 1fr))`;
    const gridTemplateRows = `auto repeat(${numeroFilas}, ${altoIntervaloPx}px)`;

    grid.style.gridTemplateColumns = gridTemplateColumns;
    grid.style.gridTemplateRows = gridTemplateRows;

    capaEventos.style.gridTemplateColumns = gridTemplateColumns;
    capaEventos.style.gridTemplateRows = gridTemplateRows;

    // Alinear la altura de la fila de encabezado en la capa de eventos con la de la grilla base
    function aplicarAlturaHeader() {
        const celdaEncabezado = grid.querySelector('.celdaEncabezadoTiempo');
        if (celdaEncabezado) {
            const altoHeader = Math.round(celdaEncabezado.getBoundingClientRect().height);
            if (altoHeader > 0) {
                capaEventos.style.gridTemplateRows = `${altoHeader}px repeat(${numeroFilas}, ${altoIntervaloPx}px)`;
            }
        }
    }
    // Aplicación inmediata y reintentos para esperar fuentes/layout
    aplicarAlturaHeader();
    try {
        requestAnimationFrame(function() {
            aplicarAlturaHeader();
            setTimeout(aplicarAlturaHeader, 50);
            setTimeout(aplicarAlturaHeader, 150);
        });
    } catch (_e) {
        setTimeout(aplicarAlturaHeader, 50);
    }
    // Ajuste reactivo ante cambios de tamaño del header
    if (typeof ResizeObserver !== 'undefined') {
        const celdaEncabezadoObs = grid.querySelector('.celdaEncabezadoTiempo');
        if (celdaEncabezadoObs) {
            try {
                const ro = new ResizeObserver(aplicarAlturaHeader);
                ro.observe(celdaEncabezadoObs);
            } catch (_err) {}
        }
    }

    renderizarEventos(eventos, capaEventos, config, inicioMinutosTotal, { pxPorMinuto, altoIntervaloPx });

    // Notificar que el scheduler ha sido renderizado para permitir mejoras del tema
    try {
        const evt = new CustomEvent('glorySchedulerRendered', { bubbles: true, cancelable: false, detail: { container: contenedor, eventos, config } });
        contenedor.dispatchEvent(evt);
    } catch (_e) {}
}

function convertirHoraAMinutos(hora) {
    const [horas, minutos] = hora.split(':').map(Number);
    return horas * 60 + minutos;
}

function renderizarEventos(eventos, capa, config, inicioCuadriculaMinutos, medidas) {
    capa.innerHTML = '';

    eventos.forEach(evento => {
        const recursoIndex = config.recursos.indexOf(evento.recurso);
        if (recursoIndex === -1) {
            console.warn(`Recurso '${evento.recurso}' no encontrado en la configuración.`);
            return;
        }

        const bloqueEvento = document.createElement('div');
        bloqueEvento.className = 'bloqueEvento';

        const minutosDesdeInicio = (evento.inicioMinutos - inicioCuadriculaMinutos);
        const filaInicio = Math.floor(minutosDesdeInicio / config.intervalo) + 2; // anclar a fila base
        const offsetMinutos = ((minutosDesdeInicio % config.intervalo) + config.intervalo) % config.intervalo; // manejar negativos
        const offsetPx = (offsetMinutos / config.intervalo) * medidas.altoIntervaloPx;
        const alturaPx = Math.max(1, Math.round(evento.duracionMinutos * medidas.pxPorMinuto));

        bloqueEvento.style.gridColumn = `${recursoIndex + 2} / span 1`;
        // Usamos una sola fila y aplicamos desplazamiento y altura en píxeles para precisión de minutos
        bloqueEvento.style.gridRow = `${filaInicio} / span 1`;
        bloqueEvento.style.transform = `translateY(${offsetPx}px)`;
        bloqueEvento.style.height = `${alturaPx}px`;

        // Contenido agnóstico: dejar contenedor vacío para que el tema lo decore
        bloqueEvento.innerHTML = `<div class="eventoContenido"></div>`;

        // Exponer datos en data-attributes para scripts del tema
        try {
            bloqueEvento.dataset.titulo = String(evento.titulo || '');
            bloqueEvento.dataset.detalle = String(evento.detalle || '');
            bloqueEvento.dataset.recurso = String(evento.recurso || '');
            bloqueEvento.dataset.horaInicio = String(evento.horaInicio || '');
            bloqueEvento.dataset.horaFin = String(evento.horaFin || '');
            if (evento.telefono) bloqueEvento.dataset.telefono = String(evento.telefono);
            if (evento.exclusividad) bloqueEvento.dataset.exclusividad = String(!!evento.exclusividad);
            if (evento.tipo || evento.tipoServicio || evento.tipo_evento) {
                bloqueEvento.dataset.tipo = String(evento.tipo || evento.tipoServicio || evento.tipo_evento);
            }
        } catch (_e) {}

        const tipoClave = (evento && (evento.tipo || evento.tipoServicio || evento.tipo_evento)) || 'default';
        const color = (config.mapeoColores && (config.mapeoColores[tipoClave] || config.mapeoColores['default'])) || '#A0A0A0';
        bloqueEvento.style.backgroundColor = color;

        // Calcular color de texto (negro o blanco) según contraste con el fondo
        function hexToRgb(hex) {
            if (!hex) return null;
            hex = hex.replace('#', '');
            if (hex.length === 3) {
                hex = hex.split('').map(ch => ch + ch).join('');
            }
            const intVal = parseInt(hex, 16);
            if (isNaN(intVal)) return null;
            return {
                r: (intVal >> 16) & 255,
                g: (intVal >> 8) & 255,
                b: intVal & 255
            };
        }

        function getRelativeLuminance(r, g, b) {
            // convertir a sRGB [0..1]
            const sr = r / 255;
            const sg = g / 255;
            const sb = b / 255;

            const transform = (c) => (c <= 0.03928) ? (c / 12.92) : Math.pow((c + 0.055) / 1.055, 2.4);

            const R = transform(sr);
            const G = transform(sg);
            const B = transform(sb);

            return 0.2126 * R + 0.7152 * G + 0.0722 * B;
        }

        function getTextColorForBackground(hex) {
            const rgb = hexToRgb(hex);
            if (!rgb) return '#000';
            const L = getRelativeLuminance(rgb.r, rgb.g, rgb.b);
            const contrastWhite = (1.05) / (L + 0.05);
            const contrastBlack = (L + 0.05) / 0.05;
            // Elegir el color con mayor ratio de contraste
            return contrastWhite >= contrastBlack ? '#ffffff' : '#000000';
        }

        const textoColor = getTextColorForBackground(color);
        bloqueEvento.style.color = textoColor;
        const titulo = String(evento.titulo || '');
        const recurso = String(evento.recurso || '');
        const hi = String(evento.horaInicio || '');
        const hf = String(evento.horaFin || '');
        const tel = String(evento.telefono || '');
        bloqueEvento.title = `${titulo}${recurso ? ' (' + recurso + ')' : ''}\n${hi && hf ? 'De ' + hi + ' a ' + hf : ''}${tel ? '\nTel: ' + tel : ''}`;

        capa.appendChild(bloqueEvento);
    });
}

function esc_html(str) {
    const p = document.createElement("p");
    p.textContent = str;
    return p.innerHTML;
}

document.addEventListener('DOMContentLoaded', gloryScheduler);
document.addEventListener('gloryRecarga', gloryScheduler);