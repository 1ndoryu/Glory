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
    const capaEventos = contenedor.querySelector('.capaEventos');
    const grid = contenedor.querySelector('.glorySchedulerGrid');

    if (!eventos.length || !config.recursos || !capaEventos || !grid) {
        return;
    }

    // Configuración de CSS Grid desde JS
    const inicioMinutosTotal = convertirHoraAMinutos(config.horaInicio);
    const finMinutosTotal = convertirHoraAMinutos(config.horaFin);
    const duracionTotalMinutos = finMinutosTotal - inicioMinutosTotal;
    const numeroFilas = duracionTotalMinutos / config.intervalo;

    grid.style.gridTemplateRows = `auto repeat(${numeroFilas}, 1fr)`;
    capaEventos.style.gridColumn = `2 / span ${config.recursos.length}`;
    capaEventos.style.gridRow = `2 / span ${numeroFilas}`;

    renderizarEventos(eventos, capaEventos, config, inicioMinutosTotal);
}

function convertirHoraAMinutos(hora) {
    const [horas, minutos] = hora.split(':').map(Number);
    return horas * 60 + minutos;
}

function renderizarEventos(eventos, capa, config, inicioCuadriculaMinutos) {
    capa.innerHTML = ''; // Limpiar eventos previos

    eventos.forEach(evento => {
        const recursoIndex = config.recursos.indexOf(evento.recurso);
        if (recursoIndex === -1) {
            console.warn(`Recurso '${evento.recurso}' no encontrado en la configuración.`);
            return;
        }

        const bloqueEvento = document.createElement('div');
        bloqueEvento.className = 'bloqueEvento';

        // Posicionamiento
        const filaInicio = ((evento.inicioMinutos - inicioCuadriculaMinutos) / config.intervalo) + 1;
        const numFilas = evento.duracionMinutos / config.intervalo;

        bloqueEvento.style.gridColumn = `${recursoIndex + 1} / span 1`;
        bloqueEvento.style.gridRow = `${filaInicio} / span ${numFilas}`;

        // Contenido
        bloqueEvento.innerHTML = `
            <strong class="eventoTitulo">${esc_html(evento.titulo)}</strong>
            <span class="eventoDetalle">${esc_html(evento.detalle)}</span>
        `;
        
        // Código de colores
        const color = config.mapeoColores[evento.tipoServicio] || '#6c757d'; // Color por defecto
        bloqueEvento.style.backgroundColor = color;
        
        // Tooltip
        bloqueEvento.title = `${evento.titulo} (${evento.recurso})\nDe ${evento.horaInicio.split(':')[0]}:${evento.horaInicio.split(':')[1]} a ${evento.horaFin.split(':')[0]}:${evento.horaFin.split(':')[1]}`;

        capa.appendChild(bloqueEvento);
    });
}

function esc_html(str) {
    const p = document.createElement("p");
    p.textContent = str;
    return p.innerHTML;
}

document.addEventListener('gloryRecarga', gloryScheduler);