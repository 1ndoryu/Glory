function gloryModal() {
    let modalActivo = null;

    // La función de cierre ahora es mucho más simple.
    // Llama a la función global y resetea la variable local.
    const cerrarModalActivo = () => {
        window.ocultarFondo(); // Oculta el fondo y TODOS los elementos con clase .modal
        modalActivo = null;
    };

    const abrirModal = idModal => {
        const modal = document.getElementById(idModal);
        if (!modal) {
            console.warn(`No se encontró el modal con id: ${idModal}`);
            return;
        }

        // Si ya hay un modal abierto, lo cerramos antes de abrir el nuevo.
        if (modalActivo) {
            cerrarModalActivo();
        }

        // 1. Mostramos el fondo global
        window.mostrarFondo();

        // 2. Mostramos el modal específico
        modalActivo = modal;
        // Asegúrate de que tus modales tengan la clase "modal" en su HTML
        // para que window.ocultarFondo() pueda encontrarlos.
        modalActivo.style.display = 'flex'; // O 'block', según tu CSS
    };

    // Listener para los disparadores que abren los modales.
    document.addEventListener('click', event => {
        const disparador = event.target.closest('.openModal');
        if (!disparador) return;

        event.preventDefault();
        const idModal = disparador.dataset.modal;
        if (!idModal) return;

        // Lógica para abrir/cerrar el modal al hacer clic en su disparador.
        if (modalActivo && modalActivo.id === idModal) {
            cerrarModalActivo();
        } else {
            abrirModal(idModal);
        }
    });

    // Listener para la tecla 'Escape'.
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modalActivo) {
            cerrarModalActivo();
        }
    });
}


document.addEventListener('gloryRecarga', gloryModal);
