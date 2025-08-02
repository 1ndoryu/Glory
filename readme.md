# Glory Framework

¡Bienvenido a Glory\! Un framework de desarrollo para WordPress diseñado para acelerar y estandarizar la creación de temas y funcionalidades complejas. Glory proporciona un conjunto de herramientas y componentes robustos que abstraen las complejidades de WordPress, permitiéndote escribir código más limpio, modular y mantenible.

## 🚀 Instalación

Instalar y empezar a usar Glory es muy sencillo. Sigue estos pasos:

1.  **Arranca WordPress**: Te recomendamos usar una herramienta de desarrollo local como [LocalWP](https://localwp.com/).
2.  **Clona el Tema Base**: Navega al directorio `wp-content/themes` de tu instalación de WordPress y clona el tema `glorytemplate`.
    ```bash
    git clone https://github.com/1ndoryu/glorytemplate.git
    ```
3.  **Instala las Dependencias**: Entra en la carpeta del tema e instala las dependencias de Composer.
    ```bash
    cd glorytemplate
    composer install
    ```
4.  **Clona el Framework Glory**: Dentro de la carpeta `glorytemplate`, clona el repositorio de Glory.
    ```bash
    git clone https://github.com/1ndoryu/Glory.git
    ```
5.  **Activa el Tema**: Ve al panel de administración de WordPress (`Apariencia -> Temas`) y activa el tema **Template Glory**.

¡Listo\! El framework Glory ya está instalado y funcionando.

## ✨ Características Principales

Glory se organiza en un núcleo de Managers, Componentes y Servicios que gestionan diferentes aspectos de tu sitio WordPress, tanto en el backend como en el frontend.

-----

### 🏛️ Managers (Núcleo)

Estos managers automatizan tareas comunes de configuración y gestión de datos.

#### AssetManager

Unifica la gestión de scripts (JS) y estilos (CSS). Permite definir assets individualmente o cargar carpetas completas, manejar dependencias, localizar datos de PHP a JavaScript y gestionar el versionado de archivos para evitar problemas de caché.

**Ejemplo: Registrar un script con datos localizados en `Glory/Config/scriptSetup.php`**

```php
AssetManager::define(
    'script',
    'mi-script-personalizado',
    '/assets/js/mi-script.js',
    [
        'deps'      => ['jquery'],
        'in_footer' => true,
        'localize'  => [
            'nombreObjeto' => 'misDatos',
            'datos'        => [
                'idUsuario' => get_current_user_id(),
                'nonce'     => wp_create_nonce('mi_nonce_seguridad'),
            ],
        ]
    ]
);
```

#### OpcionManager

Centraliza la definición y el acceso a las opciones del tema. Crea automáticamente un panel de administración para gestionar las opciones definidas y asegura la sincronización inteligente entre el código y la base de datos.

**Ejemplo: Registrar una opción en `Glory/Config/options.php`**

```php
use Glory\Manager\OpcionManager;

OpcionManager::register('color_primario_tema', [
    'valorDefault'  => '#0073aa',
    'tipo'          => 'color',
    'etiqueta'      => 'Color Primario',
    'descripcion'   => 'Elige el color principal para los elementos del tema.',
    'seccion'       => 'diseno',
    'etiquetaSeccion' => 'Diseño General',
]);
```

**Uso en el tema:**

```php
$color_primario = OpcionManager::get('color_primario_tema');
// Resultado: '#0073aa' o el valor guardado en el panel.
```

#### PostTypeManager

Simplifica la creación de Tipos de Contenido Personalizados (CPTs) de forma declarativa.

**Ejemplo: Crear un CPT "Libro" en `App/Config/postType.php`**

```php
use Glory\Core\PostTypeManager;

PostTypeManager::define(
    'libro',
    ['public' => true, 'has_archive' => true, 'supports' => ['title', 'editor', 'thumbnail']],
    'Libro',
    'Libros'
);
```

#### PageManager

Asegura que las páginas esenciales de tu tema (como 'Contacto', 'Inicio') existan siempre, asignándoles la plantilla correcta.

**Ejemplo: Definir páginas en `App/Config/config.php`**

```php
use Glory\Core\PageManager;

PageManager::define('home'); // Título: Home, Plantilla: TemplateHome.php
PageManager::define('contacto', 'Página de Contacto', 'template-contacto.php');
```

#### DefaultContentManager

Define y sincroniza contenido (posts, páginas, categorías) desde el código a la base de datos. Es ideal para asegurar que tu tema tenga el contenido inicial necesario.

**Ejemplo: (Uso avanzado)**

```php
use Glory\Manager\DefaultContentManager;

DefaultContentManager::define('evento', [
    [
        'slugDefault' => 'evento-anual-2025',
        'titulo'      => 'Evento Anual 2025',
        'contenido'   => 'Contenido del evento...',
        'metaEntrada' => ['fecha_evento' => '2025-10-20']
    ]
]);
```

-----

### ajax ⚡ Peticiones AJAX y Formularios

Glory simplifica radicalmente el manejo de peticiones AJAX y el procesamiento de formularios.

#### gloryAjax.js

Función base para todas las peticiones AJAX del framework. Permite enviar tanto objetos de datos como `FormData`, soportando la subida de archivos de forma nativa.

**Ejemplo: (JavaScript)**

```javascript
async function miFuncion() {
    const respuesta = await gloryAjax('mi_accion_ajax', { id: 123, dato: 'valor' });
    if (respuesta.success) {
        console.log(respuesta.data);
    }
}
```

#### FormBuilder y FormHandler

`FormBuilder` (PHP) construye formularios complejos sin HTML repetitivo. `gloryForm.js` (JS) gestiona la validación y el envío. `FormHandler` (PHP) enruta la petición a la clase `Handler` correspondiente para su procesamiento.

**Ejemplo: Crear un formulario**

```php
use Glory\Components\FormBuilder;

echo FormBuilder::inicio(['atributos' => ['data-meta-target' => 'user']]);

echo FormBuilder::campoTexto([
    'nombre' => 'nombre_usuario',
    'label'  => 'Nombre de Usuario',
    'valor'  => UserUtility::meta('nombre_usuario')
]);

echo FormBuilder::botonEnviar([
    'accion' => 'guardarMeta', // Esto buscará la clase GuardarMetaHandler
    'texto'  => 'Guardar Cambios'
]);

echo FormBuilder::fin();
```

-----

### 🛠️ Servicios

#### BusquedaService y gloryBusqueda.js

Implementa una búsqueda predictiva y en vivo en múltiples tipos de contenido.

**Ejemplo: (HTML)**

```html
<input type="text" class="busqueda"
       data-tipos="post,libro"
       data-cantidad="3"
       data-target="#resultados-busqueda"
       data-renderer="Glory\Components\BusquedaRenderer">

<div id="resultados-busqueda"></div>
```

#### gloryAjaxNav.js

Convierte tu sitio en una aplicación de página única (SPA) cargando el contenido sin recargar la página. Se activa por defecto, no requiere configuración inicial.

-----

### 🎨 Componentes de Interfaz (UI)

Glory incluye un conjunto de scripts listos para usar que mejoran la experiencia de usuario.

  * **Sistema de Modales (`gloryModal.js`)**: Crea, abre y cierra ventanas modales.
    **Ejemplo:**
    ```html
    <button class="openModal" data-modal="miModal">Abrir Modal</button>
    <div id="miModal" class="modal" style="display:none;">Contenido del modal...</div>
    ```
  * **Alertas Personalizadas (`alertas.js`)**: Reemplaza `alert()` y `confirm()` del navegador por notificaciones no bloqueantes.
  * **Previsualización de Archivos (`gestionarPreviews.js`)**: Gestiona la previsualización de archivos para inputs `type="file"`, con soporte para arrastrar y soltar.
  * **Pestañas y Submenús (`pestanas.js`, `submenus.js`)**: Scripts para crear sistemas de pestañas y menús contextuales.

-----

### 🔧 Herramientas de Desarrollo

  * **SyncManager**: Añade botones a la barra de administración de WordPress para forzar la sincronización (`Sincronizar Todo`) o para restablecer el contenido a sus valores por defecto (`Restablecer a Default`).
  * **TaxonomyMetaManager**: Permite añadir campos personalizados a las taxonomías, como una imagen destacada para cada categoría.
  * **GloryLogger**: Un sistema de logging centralizado para registrar eventos y errores de forma organizada.
    **Ejemplo:**
    ```php
    use Glory\Core\GloryLogger;
    GloryLogger::info('Proceso completado.', ['id_usuario' => 25]);
    ```

-----

### 📄 Renderizadores y Utilidades

  * **ContentRender**: Imprime listas de posts, usando plantillas personalizadas y con opciones de paginación.
    **Ejemplo:**
    ```php
    use Glory\Components\ContentRender;
    // Imprime una lista de 'libros' con paginación AJAX
    ContentRender::print('libro', ['publicacionesPorPagina' => 5, 'paginacion' => true]);
    ```
  * **optimizarImagen()**: Función global que utiliza el CDN de Jetpack (Photon) para comprimir y servir imágenes de forma optimizada.
  * **Clases de Utilidad**: Conjunto de clases con métodos estáticos para tareas comunes: `AssetsUtility`, `EmailUtility`, `PostUtility`, `UserUtility`, `ScheduleManager`, `PostActionManager`.

-----

### 📁 Archivos Clave

  * `Glory/load.php`: El punto de entrada principal del framework que carga la configuración y el núcleo.
  * `Glory/Config/scriptSetup.php`: Archivo central para definir y registrar todos los assets (JS/CSS) usando `AssetManager`.
  * `Glory/src/Core/Setup.php`: La clase que inicializa todos los componentes principales del framework.
  * `Glory/src/Handler/Form/`: Directorio donde residen las clases que procesan la lógica de cada formulario (ej. `GuardarMetaHandler.php`).