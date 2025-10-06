<?php

namespace Glory\Handler\Form;

/**
 * Interface que todos los manejadores de formularios deben implementar.
 */
interface FormHandlerInterface
{
    /**
     * Procesa la petición del formulario y devuelve una respuesta para el cliente.
     *
     * @param array $postDatos  Datos enviados mediante POST.
     * @param array $archivos   Archivos enviados mediante el formulario.
     *
     * @return array Debe devolver, como mínimo, una clave 'alert' con el mensaje para el usuario.
     */
    public function procesar(array $postDatos, array $archivos): array;
}
