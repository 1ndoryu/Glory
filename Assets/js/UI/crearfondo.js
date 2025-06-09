/**
 * IIFE (Immediately Invoked Function Expression) para encapsular la lógica del fondo modal
 * y no contaminar el scope global más de lo necesario, exponiendo solo las funciones necesarias
 * en el objeto `window`.
 *
 * @param {Window} window El objeto global.
 */
(function(window) {
    'use strict';

    // Se mantiene una única referencia al elemento del fondo para toda la aplicación.
    let fondoElemento = null;

    /**
     * Oculta todos los elementos que tengan la clase 'modal' y también el propio fondo.
     * Esta es la función que se ejecuta al hacer clic en el fondo.
     */
    function ocultarModalesYFondo() {
        // Ocultar todos los elementos que actúan como un modal.
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });

        // Ocultar el fondo.
        if (fondoElemento) {
            fondoElemento.style.display = 'none';
        }
    }

    /**
     * Se asegura de que el elemento de fondo exista en el DOM. Si no existe,
     * lo crea, le aplica los estilos necesarios, le añade el evento de clic
     * y lo agrega al final del `body`. Esta función se ejecuta solo una vez.
     */
    function asegurarExistenciaFondo() {
        // Si el elemento ya fue creado, no hace nada más.
        if (fondoElemento) {
            return;
        }

        // Si ya existe en el DOM por alguna razón (ej. recarga de scripts), lo reutiliza.
        fondoElemento = document.getElementById('fondoModalGlobal');
        if (fondoElemento) {
            // Nos aseguramos de que el evento de clic esté asignado.
            fondoElemento.addEventListener('click', ocultarModalesYFondo);
            return;
        }

        // Si no existe, se crea el elemento <div>.
        fondoElemento = document.createElement('div');
        fondoElemento.id = 'fondoModalGlobal';

        // Se aplican los estilos esenciales para que funcione como un fondo oscuro.
        // Para proyectos más complejos, esto se manejaría con una clase CSS.
        Object.assign(fondoElemento.style, {
            position: 'fixed',
            top: '0',
            left: '0',
            width: '100%',
            height: '100%',
            backgroundColor: 'rgba(0, 0, 0, 0.65)',
            zIndex: '999',
            display: 'none', // Comienza oculto.
            cursor: 'pointer'
        });

        // Se asigna la función principal de cierre al evento 'click'.
        fondoElemento.addEventListener('click', ocultarModalesYFondo);

        // Se añade el elemento al cuerpo del documento.
        document.body.appendChild(fondoElemento);
    }

    /**
     * Muestra el fondo modal.
     * Esta función se hace global y puede ser llamada desde cualquier parte de tu código.
     * @global
     */
    window.mostrarFondo = function() {
        asegurarExistenciaFondo(); // Se asegura que el fondo esté listo.
        if(fondoElemento) {
           fondoElemento.style.display = 'block';
        }
    };

    /**
     * Oculta el fondo modal y todos los elementos '.modal'.
     * Útil para ser llamada desde botones de "Cerrar" o al presionar la tecla 'Escape'.
     * @global
     */
    window.ocultarFondo = function() {
        ocultarModalesYFondo();
    };

})(window);
