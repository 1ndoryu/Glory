/**
 * Gestiona la previsualización de imágenes para inputs de tipo "file",
 * incluyendo funcionalidad de arrastrar y soltar (drag and drop).
 *
 * La estructura HTML esperada es un contenedor padre (.imagenInput) con:
 * - Un elemento para previsualizar (.preview).
 * - Un input[type="file"].
 *
 * Se recomienda añadir estilos para la clase '.drag-over', por ejemplo:
 * <style>
 * .preview.drag-over {
 * border-color: #007bff;
 * border-style: dashed;
 * box-shadow: inset 0 0 10px rgba(0, 123, 255, 0.5);
 * }
 * </style>
 */
function previeImagen() {

    /**
     * Muestra un archivo de imagen en un elemento de previsualización.
     * @param {File} archivo El archivo de imagen a mostrar.
     * @param {HTMLElement} previewElement El elemento del DOM donde se mostrará la imagen.
     */
    function mostrarImagen(archivo, previewElement) {
        // Valida que el archivo sea una imagen.
        if (!archivo || !archivo.type.startsWith('image/')) {
            console.warn('El archivo proporcionado no es una imagen válida.');
            return;
        }

        const lector = new FileReader();
        lector.onload = function(e) {
            previewElement.innerHTML = ''; // Limpia el contenido anterior.
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'contain';
            previewElement.appendChild(img);
        };
        lector.readAsDataURL(archivo);
    }

    // --- Manejo de Clic para abrir selector de archivos ---
    document.addEventListener('click', function(evento) {
        const preview = evento.target.closest('.preview');
        if (!preview) return;

        const inputFile = preview.parentElement.querySelector('input[type="file"]');
        if (inputFile) {
            inputFile.click();
        } else {
            console.error('No se encontró un input[type="file"] asociado.', preview);
        }
    });

    // --- Manejo de Selección de Archivo ---
    document.addEventListener('change', function(evento) {
        const inputFile = evento.target;
        if (!inputFile.matches('input[type="file"]') || !inputFile.parentElement.querySelector('.preview')) {
            return;
        }

        if (inputFile.files && inputFile.files[0]) {
            const preview = inputFile.parentElement.querySelector('.preview');
            mostrarImagen(inputFile.files[0], preview);
        }
    });

    // --- Manejo de Arrastrar y Soltar (Drag and Drop) ---

    // Previene el comportamiento por defecto cuando un archivo se arrastra sobre la zona.
    document.addEventListener('dragover', function(evento) {
        const preview = evento.target.closest('.preview');
        if (preview) {
            evento.preventDefault();
            preview.classList.add('drag-over');
        }
    });

    // Quita el indicador visual cuando el archivo sale de la zona.
    document.addEventListener('dragleave', function(evento) {
        const preview = evento.target.closest('.preview');
        if (preview) {
            evento.preventDefault();
            preview.classList.remove('drag-over');
        }
    });

    // Maneja el archivo cuando se suelta en la zona.
    document.addEventListener('drop', function(evento) {
        const preview = evento.target.closest('.preview');
        if (preview) {
            evento.preventDefault();
            preview.classList.remove('drag-over');

            const inputFile = preview.parentElement.querySelector('input[type="file"]');
            const archivos = evento.dataTransfer.files;

            if (inputFile && archivos && archivos.length > 0) {
                inputFile.files = archivos; // Asigna el archivo soltado al input.
                mostrarImagen(archivos[0], preview); // Muestra la previsualización.
            }
        }
    });
}

document.addEventListener('gloryRecarga', previeImagen);