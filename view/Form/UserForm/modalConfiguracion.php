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
            // 1. AÑADIMOS EL CONTEXTO: 'user'. El objectId se obtiene automáticamente (usuario actual).
            echo Form::inicio([
                'metaTarget' => 'user',
                'extraClasses' => 'flex gap columna'
            ]);

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

            // 3. CAMBIAMOS 'username' por 'user_login' para mayor claridad.
            echo Form::campoTexto([
                'nombre' => 'user_login',
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
            // 2. CORREGIMOS LA ACCIÓN para usar el nuevo handler genérico.
            echo Form::botonEnviar([
                'accion' => 'guardarMeta', 
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