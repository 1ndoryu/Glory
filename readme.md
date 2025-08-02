### ARCHIVO: Glory/readme.md

¡Bienvenido a Glory\! Un framework de desarrollo para WordPress diseñado para acelerar y estandarizar la creación de temas y funcionalidades complejas. Glory proporciona un conjunto de herramientas y componentes robustos que abstraen las complejidades de WordPress, permitiéndote escribir código más limpio, modular y mantenible.

## Instalación

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
    git clone https://github.com/1ndoryu/glory.git
    ```
5.  **Activa el Tema**: Ve al panel de administración de WordPress (`Apariencia -> Temas`) y activa el tema **Template Glory**.

¡Listo\! El framework Glory ya está instalado y funcionando.

## 1\. Gestores Principales (Configuración y Datos)

Estos gestores automatizan tareas comunes de configuración y gestión de datos.

### AssetManager: Scripts y Estilos

Unifica la gestión de scripts (JS) y estilos (CSS). Permite definir assets individualmente o cargar carpetas enteras, manejar dependencias, localizar datos de PHP a JavaScript y gestionar el versionado de archivos para evitar problemas de caché.

**Ejemplo: Registrar un script con datos en `Glory/Config/scriptSetup.php`**

```php
AssetManager::define(
    'script',
    'miScriptPersonalizado',
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

### OpcionManager: Panel de Opciones del Tema

Centraliza la definición y el acceso a las opciones del tema. Crea automáticamente un panel de administración para gestionar las opciones definidas y asegura la sincronización inteligente entre el código y la base de datos.

**Ejemplo: Registrar una opción de color en `Glory/Config/options.php`**

```php
use Glory\Manager\OpcionManager;

OpcionManager::register('colorPrimarioTema', [
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
$colorPrimario = OpcionManager::get('colorPrimarioTema');
```

### PostTypeManager: Tipos de Contenido Personalizados

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

### PageManager: Páginas Esenciales

Asegura que las páginas clave de tu tema (como 'Contacto', 'Inicio') existan siempre, asignándoles la plantilla correcta.

**Ejemplo: Definir páginas en `App/Config/config.php`**

```php
use Glory\Core\PageManager;

PageManager::define('home'); // Asigna por defecto TemplateHome.php
PageManager::define('contacto', 'Página de Contacto', 'template-contacto.php');
```

### DefaultContentManager: Contenido por Defecto

Define y sincroniza contenido (posts, páginas, categorías) desde el código a la base de datos, ideal para asegurar que tu tema tenga el contenido inicial necesario.

**Ejemplo de uso:**

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

### IntegrationsManager: SEO y Tracking

Gestiona la inserción de códigos de seguimiento y meta etiquetas de verificación de forma centralizada a través de las Opciones del Tema.

**Ejemplo: (Se configura en `Glory/Config/options.php`)**

```php
OpcionManager::register('glory_ga4_measurement_id', [
    'valorDefault'  => '',
    'tipo'          => 'text',
    'etiqueta'      => 'Google Analytics 4 Measurement ID',
    'seccion'       => 'integrations',
]);
```

---

## 2\. Sistema de Formularios y AJAX

Glory simplifica radicalmente el manejo de peticiones AJAX y el procesamiento de formularios.

### gloryAjax.js: La Función Principal de AJAX

Es la base para todas las peticiones AJAX del framework. Permite enviar tanto objetos de datos como `FormData`, soportando la subida de archivos de forma nativa.

**Ejemplo en JavaScript:**

```javascript
async function miFuncion() {
    const respuesta = await gloryAjax('mi_accion_ajax', {id: 123, dato: 'valor'});
    if (respuesta.success) {
        console.log(respuesta.data);
    }
}
```

### FormBuilder y FormHandler: Creación y Procesamiento de Formularios

`FormBuilder` (PHP) construye formularios complejos sin HTML repetitivo. `gloryForm.js` (JS) gestiona la validación y el envío. `FormHandler` (PHP) enruta la petición a la clase `Handler` correspondiente para su procesamiento.

**Ejemplo: Crear un formulario para guardar un meta de usuario**

```php
use Glory\Components\FormBuilder;
use Glory\Utility\UserUtility;

// Atributo 'data-meta-target' define el contexto (user, post, etc.)
echo FormBuilder::inicio(['atributos' => ['data-meta-target' => 'user']]);

echo FormBuilder::campoTexto([
    'nombre' => 'nombre_usuario',
    'label'  => 'Nombre de Usuario',
    'valor'  => UserUtility::meta('nombre_usuario')
]);

// Atributo 'data-accion' define qué clase Handler procesará los datos
echo FormBuilder::botonEnviar([
    'accion' => 'guardarMeta',
    'texto'  => 'Guardar Cambios'
]);

echo FormBuilder::fin();
```

---

## 3\. Componentes de Frontend

Componentes listos para renderizar contenido dinámico en tus plantillas.

### ContentRender: Listas de Posts

Imprime listas de posts, usando plantillas personalizadas y con opciones de paginación AJAX integrada.

**Ejemplo: Mostrar 5 libros por página con paginación**

```php
use Glory\Components\ContentRender;

ContentRender::print('libro', [
    'publicacionesPorPagina' => 5,
    'paginacion' => true
]);
```

### TermRender: Listas de Términos

Similar a `ContentRender`, pero para mostrar listas de términos de una taxonomía (ej. categorías, etiquetas).

**Ejemplo: Mostrar todas las categorías de la taxonomía 'category'**

```php
use Glory\Components\TermRender;

TermRender::print('category');
```

### Sistema de Búsqueda en Vivo

Implementa una búsqueda predictiva y en vivo en múltiples tipos de contenido con un simple input HTML.

**Ejemplo en HTML:**

```html
<input type="text" class="busqueda" data-tipos="post,libro" data-cantidad="3" data-target="#resultados-busqueda" data-renderer="Glory\Components\BusquedaRenderer" />

<div id="resultados-busqueda"></div>
```

### Navegación AJAX (SPA)

Convierte tu sitio en una aplicación de página única (SPA) cargando el contenido sin recargar la página. Se activa por defecto en `Glory/Config/scriptSetup.php` y no requiere configuración inicial.

### LogoRenderer: Logo Dinámico

Permite mostrar el logo del sitio en cualquier lugar mediante un shortcode, respetando la configuración del panel de opciones y permitiendo modificaciones.

**Ejemplo de shortcode en el editor de WordPress:**

```
[theme_logo width="150px" filter="white"]
```

---

## 4\. Scripts de UI (Experiencia de Usuario)

Glory incluye un conjunto de scripts listos para usar que mejoran la interacción del usuario.

-   **Sistema de Modales (`gloryModal.js`)**: Crea, abre y cierra ventanas modales.
    **Ejemplo:**
    ```html
    <button class="openModal" data-modal="miModal">Abrir Modal</button>
    <div id="miModal" class="modal" style="display:none;">Contenido...</div>
    ```
-   **Alertas Personalizadas (`alertas.js`)**: Reemplaza `alert()` y `confirm()` por notificaciones no bloqueantes.
-   **Previsualización de Archivos (`gestionarPreviews.js`)**: Gestiona la previsualización de archivos para inputs `type="file"`, con soporte para arrastrar y soltar.
-   **Pestañas y Submenús (`pestanas.js`, `submenus.js`)**: Scripts para crear sistemas de pestañas y menús contextuales.
-   **Header Adaptativo (`adaptiveHeader.js`)**: Cambia el color del texto del header automáticamente según el color del fondo sobre el que se encuentra.

---

## 5\. Herramientas de Administración y Desarrollo

Funcionalidades para facilitar la gestión y depuración del sitio.

### SyncManager: Controles en la Barra de Administración

Añade un menú "Glory Sync" en la barra de administración de WordPress para:

-   **Sincronizar Todo**: Fuerza la sincronización de Opciones, Páginas y Contenido por Defecto.
-   **Restablecer a Default**: Restaura el contenido a sus valores definidos en el código.
-   **Borrar Caché de Glory**: Limpia los transients generados por el framework.

### TaxonomyMetaManager: Campos Personalizados para Taxonomías

Permite añadir campos personalizados a las taxonomías, como una imagen destacada para cada categoría.

### GloryLogger: Sistema de Logs

Un sistema de logging centralizado para registrar eventos y errores de forma organizada en el log de depuración de PHP.
**Ejemplo:**

```php
use Glory\Core\GloryLogger;

GloryLogger::info('Proceso completado.', ['id_usuario' => 25, 'resultado' => 'éxito']);
GloryLogger::error('Falló la conexión a la API externa.');
```

---

## 6\. Clases de Utilidad y Funciones Globales

Helpers para tareas comunes.

-   **`AssetsUtility`**: Obtiene la URL de imágenes guardadas en el tema. `AssetsUtility::imagenUrl('glory::default1.jpg');`
-   **`EmailUtility`**: Envía correos al administrador del sitio. `EmailUtility::sendToAdmins('Asunto', 'Mensaje');`
-   **`PostUtility`**: Obtiene metadatos de un post. `PostUtility::meta('mi_meta_key');`
-   **`UserUtility`**: Comprueba si el usuario está logueado o tiene un rol. `UserUtility::logeado();` `UserUtility::tieneRoles('editor');`
-   **`ScheduleManager`**: Gestiona y muestra horarios de apertura y cierre.
-   **`optimizarImagen()`**: Función global que utiliza el CDN de Jetpack (Photon) para comprimir y servir imágenes de forma optimizada.

---

## 7\. Archivos Clave

-   `Glory/load.php`: Punto de entrada principal del framework.
-   `Glory/Config/scriptSetup.php`: Registro central de todos los assets (JS/CSS) con `AssetManager`.
-   `Glory/Config/options.php`: Registro central de todas las opciones del tema con `OpcionManager`.
-   `App/Config/config.php`: Configuraciones a nivel de tema (páginas, versión, etc.).
-   `App/Config/postType.php`: Definiciones de Tipos de Contenido Personalizados.
-   `Glory/src/Core/Setup.php`: Clase que inicializa todos los componentes del framework.
-   `Glory/src/Handler/Form/`: Directorio para las clases que procesan la lógica de cada formulario (ej. `GuardarMetaHandler.php`).
