function gloryModal() {
    let modalActivo = null;
    let ultimoDisparador = null;

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
        document.dispatchEvent(new CustomEvent('gloryModal:open', { detail: { modal: modalActivo, modalId: idModal, trigger: ultimoDisparador } }));
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
            ultimoDisparador = disparador;
            abrirModal(idModal);
        }
    });

    // Cerrar modal al hacer clic fuera del contenido (sobre el overlay del propio modal)
    document.addEventListener('click', event => {
        const overlay = event.target.closest('.modal');
        if (!overlay) return;
        const dentroContenido = event.target.closest('.modalContenido');
        if (!dentroContenido && modalActivo && overlay === modalActivo) {
            const modalCerrado = modalActivo;
            cerrarModalActivo();
            document.dispatchEvent(new CustomEvent('gloryModal:close', { detail: { modal: modalCerrado, modalId: modalCerrado?.id } }));
        }
    });

    // Listener para la tecla 'Escape'.
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modalActivo) {
            const modalCerrado = modalActivo;
            cerrarModalActivo();
            document.dispatchEvent(new CustomEvent('gloryModal:close', { detail: { modal: modalCerrado, modalId: modalCerrado?.id } }));
        }
    });
}


document.addEventListener('gloryRecarga', gloryModal);
document.addEventListener('DOMContentLoaded', gloryModal);
