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

    const gridTemplateColumns = `60px repeat(${config.recursos.length}, 1fr)`;
    const gridTemplateRows = `auto repeat(${numeroFilas}, 1fr)`;

    grid.style.gridTemplateColumns = gridTemplateColumns;
    grid.style.gridTemplateRows = gridTemplateRows;

    capaEventos.style.gridTemplateColumns = gridTemplateColumns;
    capaEventos.style.gridTemplateRows = gridTemplateRows;

    renderizarEventos(eventos, capaEventos, config, inicioMinutosTotal);
}

function convertirHoraAMinutos(hora) {
    const [horas, minutos] = hora.split(':').map(Number);
    return horas * 60 + minutos;
}

function renderizarEventos(eventos, capa, config, inicioCuadriculaMinutos) {
    capa.innerHTML = '';

    eventos.forEach(evento => {
        const recursoIndex = config.recursos.indexOf(evento.recurso);
        if (recursoIndex === -1) {
            console.warn(`Recurso '${evento.recurso}' no encontrado en la configuración.`);
            return;
        }

        const bloqueEvento = document.createElement('div');
        bloqueEvento.className = 'bloqueEvento';

        const filaInicio = Math.floor((evento.inicioMinutos - inicioCuadriculaMinutos) / config.intervalo) + 2;
        const numFilas = evento.duracionMinutos / config.intervalo;

        bloqueEvento.style.gridColumn = `${recursoIndex + 2} / span 1`;
        bloqueEvento.style.gridRow = `${filaInicio} / span ${numFilas}`;

        bloqueEvento.innerHTML = `
            <div class="eventoContenido">
                <strong class="eventoTitulo">${esc_html(evento.titulo)} ${evento.exclusividad ? '<span class="iconoExclusividad">❤️</span>' : ''}</strong>
                <span class="eventoDetalle">${esc_html(evento.detalle)}</span>
                <span class="eventoTelefono">${esc_html(evento.telefono || '')}</span>
                <span class="eventoHorario">${esc_html(evento.horaInicio)} - ${esc_html(evento.horaFin)}</span>
            </div>
        `;

        const color = config.mapeoColores[evento.tipoServicio] || config.mapeoColores['default'] || '#A0A0A0';
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
        bloqueEvento.title = `${evento.titulo} (${evento.recurso})\nDe ${evento.horaInicio} a ${evento.horaFin}\nTel: ${evento.telefono || 'N/A'}`;

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