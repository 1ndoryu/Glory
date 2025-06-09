<?
namespace Glory\Handler\Form;

interface FormHandlerInterface
{
    /**
     * Procesa los datos del formulario recibidos vía AJAX.
     *
     * @param array $postDatos Los datos recibidos de $_POST.
     * @param array $archivos Los datos recibidos de $_FILES.
     * @return array La respuesta que se enviará al frontend en formato JSON.
     */
    public function procesar(array $postDatos, array $archivos): array;
}