function gloryModal() {
    let modalActivo = null;
    let fondoModal = null;

    const crearFondo = () => {
        if (fondoModal) return;
        fondoModal = document.createElement('div');
        fondoModal.id = 'modalBackground';
        fondoModal.style.position = 'fixed';
        fondoModal.style.top = '0';
        fondoModal.style.left = '0';
        fondoModal.style.width = '100%';
        fondoModal.style.height = '100%';
        fondoModal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        fondoModal.style.display = 'none';
        fondoModal.style.zIndex = '999';
        document.body.appendChild(fondoModal);
        fondoModal.addEventListener('click', cerrarModalActivo);
    };

    const abrirModal = idModal => {
        const modal = document.getElementById(idModal);
        if (!modal) {
            console.warn(`No se encontró el modal con id: ${idModal}`);
            return;
        }

        if (modalActivo) cerrarModalActivo();

        crearFondo();

        modalActivo = modal;
        modalActivo.style.display = 'flex'; // O 'flex', según tu CSS
        fondoModal.style.display = 'block';
    };

    const cerrarModalActivo = () => {
        if (!modalActivo) return;
        modalActivo.style.display = 'none';
        if (fondoModal) {
            fondoModal.style.display = 'none';
        }
        modalActivo = null;
    };

    document.addEventListener('click', event => {
        const disparador = event.target.closest('.openModal');
        if (disparador) {
            event.preventDefault();
            const idModal = disparador.dataset.modal;
            if (!idModal) return; // Si el modal solicitado ya está activo, ciérralo. De lo contrario, ábrelo.

            if (modalActivo && modalActivo.id === idModal) {
                cerrarModalActivo();
            } else {
                abrirModal(idModal);
            }
            return;
        }

        if (event.target.closest('.modalBackground')) {
            event.preventDefault();
            cerrarModalActivo();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modalActivo) {
            cerrarModalActivo();
        }
    });
}

document.addEventListener('gloryRecarga', gloryModal);
