<?
use Glory\Component\FormBuilder as Form;

function modalConfiguracion()
{
    // La estructura del modal se mantiene, pero el formulario interno se construye con el Builder.
?>
    <div class="bloque modal" id="modalConfiguracion">
        <div class="modalContenido flex gap columna">
            <p>Configuracion de perfil</p>

            <?
            // Aquí comienza la magia del FormBuilder
            echo Form::inicio(['extraClasses' => 'flex gap columna']);

            echo Form::campoArchivo([
                'nombre' => 'imagenPerfil',
                'idPreview' => 'previewImagenPerfil',
                'textoPreview' => 'Arrastra tu foto de perfil',
                'limite' => 2048576, // 2MB
                'accept' => 'image/*',
                'extraClassesContenedor' => 'imagenInput'
            ]);

            echo Form::campoTexto([
                'nombre' => 'nombreUsuario',
                'label' => 'Nombre',
                'limite' => 20,
                'extraClassesContenedor' => 'nombreInput'
            ]);

            echo Form::campoTexto([
                'nombre' => 'username',
                'label' => 'Username',
                'limite' => 20,
                'extraClassesContenedor' => 'usernameInput'
            ]);

            echo Form::campoTextarea([
                'nombre' => 'descripcion',
                'label' => 'Descripcion',
                'rows' => 2,
                'limite' => 260,
                'extraClassesContenedor' => 'descripcionInput'
            ]);

            echo Form::campoTexto([
                'nombre' => 'enlace',
                'label' => 'Enlace',
                'limite' => 100,
                'extraClassesContenedor' => 'enlaceInput'
            ]);
            ?>

            <div class="flex botonesBloques">
            <?
            echo Form::botonEnviar([
                // Esta acción debe coincidir con el nombre de una clase Handler.
                // 'guardarPerfil' -> buscará la clase 'GuardarPerfilHandler'
                'accion' => 'guardarPerfil', 
                'texto' => 'Guardar',
                'extraClasses' => 'borde'
            ]);
            ?>
            </div>

            <?
            echo Form::fin();
            ?>
        </div>
    </div>
<?
}

add_action('wp_footer', 'modalConfiguracion');