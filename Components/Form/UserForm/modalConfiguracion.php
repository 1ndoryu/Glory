<?php


function modalConfiguracion()
{
?>
    <div class="bloque modal gloryForm" id="modalConfiguracion">
        <div class="modalContenido flex gap columna">
            <p>Configuracion de perfil</p>

            <div class="imagenInput">
                <div class="preview" id="previewImagenPerfil">
                    Arrastra tu foto de perfil
                </div>
                <input type="file" id="imagenPerfil" accept="image/*" style="display:none;" data-limit="2048576">
            </div>

            <div class="nombreInput">
                <label for="nombre">Nombre</label>
                <input type="text" name="nombreUsuario" data-limit="20"/>
            </div>

            <div class="usernameInput">
                <label for="username">Username</label>
                <input type="text" name="username" data-limit="20"/>
            </div>

            <div class="descripcionInput">
                <label for="descripcion">Descripcion</label>
                <textarea name="descripcion" rows="2" data-limit="260"></textarea>
            </div>

            <div class="enlaceInput">
                <label for="enlace">Enlace</label>
                <input type="text" name="enlace" data-limit="100"/>
            </div>

            <div class="flex botonesBloques">
                <button class="dataSubir borde" data-accion="userDataService">Guardar</button>
            </div>

        </div>

    </div>
<?
}

add_action('wp_footer', 'modalConfiguracion');
