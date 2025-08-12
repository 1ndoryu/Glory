function pestanas() {
    const bloques = document.querySelectorAll('.gloryTabs');
    if (!bloques.length) return;

    bloques.forEach((bloque, bloqueIndex) => {
        const pestanasContenedor = bloque.querySelector('.pestanas');
        const panelesContenido = Array.from(bloque.querySelectorAll('.pestanaContenido'));
        if (!pestanasContenedor || panelesContenido.length === 0) return;

        // Determinar pestaña activa preservando estado previo
        let activeIndex = -1;
        if (typeof bloque.dataset.activeIndex !== 'undefined') {
            const parsed = parseInt(bloque.dataset.activeIndex, 10);
            if (!Number.isNaN(parsed) && parsed >= 0 && parsed < panelesContenido.length) {
                activeIndex = parsed;
            }
        }
        if (activeIndex === -1) {
            activeIndex = panelesContenido.findIndex(p => p.classList.contains('activa'));
            if (activeIndex < 0) activeIndex = 0;
        }

        pestanasContenedor.innerHTML = '';

        panelesContenido.forEach((panel, index) => {
            const idPanel = `pestanaID-${bloqueIndex}-${index}`;
            const nombrePestana = panel.dataset.pestana || `Pestaña ${index + 1}`;
            panel.id = idPanel;
            panel.classList.remove('activa');

            const pestanaBtn = document.createElement('button');
            pestanaBtn.setAttribute('role', 'tab');
            pestanaBtn.setAttribute('aria-controls', idPanel);
            pestanaBtn.dataset.target = `#${idPanel}`;
            pestanaBtn.textContent = nombrePestana;

            pestanasContenedor.appendChild(pestanaBtn);

            if (index === activeIndex) {
                pestanaBtn.classList.add('activa');
                pestanaBtn.setAttribute('aria-selected', 'true');
                panel.classList.add('activa');
            } else {
                pestanaBtn.setAttribute('aria-selected', 'false');
            }
        });

        // Evitar múltiples listeners acumulados: usar propiedad onclick (reemplaza) en lugar de addEventListener
        pestanasContenedor.onclick = (e) => {
            const pestanaClickeada = e.target.closest('button');
            if (!pestanaClickeada) return;

            const botones = Array.from(pestanasContenedor.querySelectorAll('button'));
            botones.forEach(btn => {
                btn.classList.remove('activa');
                btn.setAttribute('aria-selected', 'false');
            });
            panelesContenido.forEach(p => p.classList.remove('activa'));

            pestanaClickeada.classList.add('activa');
            pestanaClickeada.setAttribute('aria-selected', 'true');
            const targetSel = pestanaClickeada.dataset.target;
            const targetPanel = targetSel ? bloque.querySelector(targetSel) : null;
            if (targetPanel) targetPanel.classList.add('activa');

            // Guardar índice activo para preservar entre reinicios
            const newIndex = botones.indexOf(pestanaClickeada);
            if (newIndex >= 0) {
                bloque.dataset.activeIndex = String(newIndex);
            }
        };
    });
}

document.addEventListener('gloryRecarga', pestanas);