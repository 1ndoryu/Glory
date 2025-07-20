function pestanas() {
    const pestanasContenedor = document.querySelector('.pestanas');
    const panelesContenido = Array.from(document.querySelectorAll('.pestanaContenido')).slice(0, 3);

    if (!pestanasContenedor || panelesContenido.length === 0) {
        return;
    }

    pestanasContenedor.innerHTML = ''; 

    panelesContenido.forEach((panel, index) => {
        const idPanel = `pestanaID-${index}`;
        const nombrePestana = panel.dataset.pestana || `Pestaña ${index + 1}`;
        panel.id = idPanel;
        panel.classList.remove('activa');

        const pestanaBtn = document.createElement('button');
        pestanaBtn.setAttribute('role', 'tab');
        pestanaBtn.setAttribute('aria-controls', idPanel);
        pestanaBtn.dataset.target = `#${idPanel}`;
        pestanaBtn.textContent = nombrePestana;

        pestanasContenedor.appendChild(pestanaBtn);

        if (index === 0) {
            pestanaBtn.classList.add('activa');
            pestanaBtn.setAttribute('aria-selected', 'true');
            panel.classList.add('activa');
        } else {
            pestanaBtn.setAttribute('aria-selected', 'false');
        }
    });

    pestanasContenedor.addEventListener('click', (e) => {
        const pestanaClickeada = e.target.closest('button');
        if (!pestanaClickeada) return;

        // Desactivar todas las pestañas y paneles
        pestanasContenedor.querySelectorAll('button').forEach(btn => {
            btn.classList.remove('activa');
            btn.setAttribute('aria-selected', 'false');
        });
        panelesContenido.forEach(p => p.classList.remove('activa'));

        // Activar la pestaña y panel seleccionados
        pestanaClickeada.classList.add('activa');
        pestanaClickeada.setAttribute('aria-selected', 'true');
        document.querySelector(pestanaClickeada.dataset.target)?.classList.add('activa');
    });
}

document.addEventListener('gloryRecarga', pestanas);