<?
namespace Glory\Handler\Form;

class GuardarPerfilHandler implements FormHandlerInterface
{
    public function procesar(array $postDatos, array $archivos): array
    {
        // 1. Validación de campos (ejemplo)
        $nombreUsuario = sanitize_text_field($postDatos['nombreUsuario'] ?? '');
        if (empty($nombreUsuario)) {
            // Nota: En una aplicación real, se lanzarían excepciones más específicas.
            throw new \Exception('El nombre de usuario no puede estar vacío.');
        }

        if (strlen($nombreUsuario) > 20) {
            throw new \Exception('El nombre de usuario excede los 20 caracteres.');
        }

        // 2. Lógica de negocio (ejemplo)
        // Aquí iría el código para actualizar el nombre del usuario en la base de datos.
        // Por ejemplo: update_user_meta(get_current_user_id(), 'first_name', $nombreUsuario);

        // También se procesarían los archivos de $archivos si existieran.
        // Ejemplo: Manejar la subida de la foto de perfil.

        // 3. Respuesta al frontend
        // Este array se convertirá a JSON y se enviará al navegador.
        return [
            'alert' => '¡Perfil guardado con éxito!',
            // Podríamos añadir otras directivas para el frontend, como:
            // 'accionAdicional' => 'refrescarComponente',
            // 'selector' => '#perfil-usuario'
        ];
    }
}