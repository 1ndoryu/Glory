function pestanas() {
    const bloques = document.querySelectorAll('.gloryTabs');
    if (!bloques.length) return;

    bloques.forEach((bloque, bloqueIndex) => {
        const pestanasContenedor = bloque.querySelector('.pestanas');
        const panelesContenido = Array.from(bloque.querySelectorAll('.pestanaContenido'));
        if (!pestanasContenedor || panelesContenido.length === 0) return;

        // Clave de almacenamiento persistente por bloque
        const storageKey = (bloque.dataset.tabsId && String(bloque.dataset.tabsId).trim())
            ? `gloryTabs:${location.pathname}:${bloque.dataset.tabsId}`
            : (bloque.id && String(bloque.id).trim())
                ? `gloryTabs:${location.pathname}:${bloque.id}`
                : `gloryTabs:${location.pathname}:idx-${bloqueIndex}`;

        // Determinar pestaña activa preservando estado previo
        let activeIndex = -1;
        // 1) Intentar desde localStorage
        try {
            const saved = localStorage.getItem(storageKey);
            if (saved !== null) {
                const parsed = parseInt(saved, 10);
                if (!Number.isNaN(parsed) && parsed >= 0 && parsed < panelesContenido.length) {
                    activeIndex = parsed;
                }
            }
        } catch (_e) {}

        // 2) Fallback a data-attribute en el DOM (persistencia intra-sesión)
        if (activeIndex === -1 && typeof bloque.dataset.activeIndex !== 'undefined') {
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
                try {
                    localStorage.setItem(storageKey, String(newIndex));
                } catch (_e) {}
            }
        };
    });
}

document.addEventListener('gloryRecarga', pestanas);