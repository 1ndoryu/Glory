# ✨ Glory Framework README

¡Bienvenido a Glory\! Un framework de desarrollo para WordPress diseñado para acelerar y estandarizar la creación de temas y funcionalidades complejas. Glory proporciona un conjunto de herramientas y componentes robustos que abstraen las complejidades de WordPress, permitiéndote escribir código más limpio, modular y mantenible.

## 🏁 Empezando

Instalar y empezar a usar Glory es muy sencillo. Sigue estos pasos:

1.  **Arranca WordPress**: Te recomendamos usar una herramienta de desarrollo local como [LocalWP](https://localwp.com/).
2.  **Clona el Tema Base**: Navega al directorio `wp-content/themes` de tu instalación de WordPress y clona el tema `glorytemplate`.
    ```bash
    git clone https://github.com/user/glorytemplate.git
    ```
3.  **Instala las Dependencias**: Entra en la carpeta del tema e instala las dependencias de Composer.
    ```bash
    cd glorytemplate
    composer install
    ```
4.  **Clona el Framework Glory**: Dentro de la carpeta `glorytemplate`, clona el repositorio de Glory.
    ```bash
    git clone https://github.com/user/glory.git
    ```
5.  **Activa el Tema**: Ve al panel de administración de WordPress (`Apariencia -> Temas`) y activa el tema **Template Glory**.

¡Listo\! El framework Glory ya está instalado y funcionando.

## 🧰 Gestores Principales (Managers)

Estos gestores automatizan tareas comunes de configuración y gestión de datos, centralizando la lógica en los archivos dentro de `App/Config/`.

### 🎨 AssetManager

Unifica la gestión de scripts (JS) y estilos (CSS). Permite definir assets individualmente o cargar carpetas enteras, manejar dependencias, localizar datos de PHP a JavaScript y gestionar el versionado de archivos para evitar problemas de caché.

Nota rápida sobre control por "feature"

- **Controlar assets por feature**: cuando registres assets del framework (no los del tema), puedes pasar una clave opcional `feature` en la configuración para que el `AssetManager` decida si registrar el asset según el estado de esa feature. Ejemplos válidos:
  - `'feature' => 'modales'` (forma corta, infiere la opción en BD como `glory_componente_modales_activado` si existe)
  - `'feature' => ['modales', 'glory_componente_modales_activado']` (forma explícita con opción)

- **Comportamiento**: si la feature fue desactivada por código con `GloryFeatures::disable('modales')` o la opción en BD (`glory_componente_modales_activado`) está en `false`, el asset no se registrará. Esto centraliza el control y evita `if` dispersos en los archivos de configuración.

### Control de features: `isEnabled` vs `isActive`

- **`GloryFeatures::isEnabled($feature)`**: devuelve únicamente el *override por código* (true | false | null). Úsalo cuando quieras saber si el desarrollador forzó el estado desde el código.
- **`GloryFeatures::isActive($feature, $optionKey = null, $default = true)`**: combina el override por código **y** la opción almacenada en la base de datos. Primero consulta `isEnabled()` y, si no hay override, obtiene la opción correspondiente (o usa el default). Úsalo cuando la decisión debe respetar tanto la configuración del panel como la posible anulación por código.

Recomendación: en el framework, usar `isActive()` para decidir si registrar/enqueuear funcionalidades que dependan de la configuración del panel; dejar `isEnabled()` solo para casos en los que se quiera dar prioridad absoluta al override por código (por ejemplo, en `OpcionManager::get()` donde el código debe poder forzar el valor de una opción).

**Ejemplo: Registrar un script con datos localizados en `App/Config/assets.php`**

```php
use Glory\Manager\AssetManager;

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

**Ejemplo: Registrar una carpeta completa de estilos en `App/Config/assets.php`**

```php
use Glory\Manager\AssetManager;

AssetManager::defineFolder(
    'style',
    '/assets/css/',
    ['deps' => [], 'media' => 'all'],
    'tema-' // Prefijo para los handles
);
```

### 🎛️ OpcionManager

Centraliza la definición y el acceso a las opciones del tema. Crea automáticamente un panel de administración para gestionar las opciones definidas y asegura la sincronización inteligente entre el código y la base de datos.

**Ejemplo: Registrar una opción de color en `App/Config/opcionesTema.php`**

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
$colorPrimario = OpcionManager::get('color_primario_tema');
```

### 📄 PostTypeManager

Simplifica la creación de Tipos de Contenido Personalizados (CPTs) de forma declarativa.

**Ejemplo: Crear un CPT "Libro" en `App/Config/postType.php`**

```php
use Glory\Manager\PostTypeManager;

PostTypeManager::define(
    'libro',
    [
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-book',
    ],
    'Libro',  // Nombre Singular
    'Libros'  // Nombre Plural
);
```

### 🗺️ PageManager

Asegura que las páginas clave de tu tema existan siempre, asignándoles una función de renderizado específica. Este sistema utiliza una única plantilla (`TemplateGlory.php`) que actúa como un enrutador.

**Cómo funciona:**

1.  Defines una página con un `slug` y un `handler` (el nombre de una función) en `App/Config/config.php`.
2.  Creas la función `handler` correspondiente dentro de un archivo en la carpeta `templates/Pages/`.
3.  Cuando un usuario visita esa página, `TemplateGlory.php` ejecuta la función `handler` para renderizar el contenido.

**Ejemplo: Definir la página de inicio en `App/Config/config.php`**

```php
use Glory\Manager\PageManager;

// Asigna el slug 'home' a la función de renderizado 'home'
PageManager::define('home', 'home');

// Asigna el slug 'contacto' a la función 'paginaDeContacto'
PageManager::define('contacto', 'paginaDeContacto');
```

**Ejemplo: Crear la función `handler` en `templates/Pages/Home.php`**

```php
<?php
// El nombre de la función debe coincidir con el handler definido

function home() {
    ?>
    <section>
        <h1>Bienvenido a la Página de Inicio</h1>
        <p>Este contenido es renderizado por la función home().</p>
    </section>
    <?php
}
```

### 💾 DefaultContentManager

Define y sincroniza contenido (posts, páginas, categorías) desde el código a la base de datos, ideal para asegurar que tu tema tenga el contenido inicial necesario.

**Ejemplo: Definir posts para el CPT "libro" en `App/Config/defaultContent.php`**

```php
use Glory\Manager\DefaultContentManager;

DefaultContentManager::define('libro', [
    [
        'slugDefault' => 'el-principito',
        'titulo'      => 'El Principito',
        'contenido'   => 'Una novela poética...',
        'metaEntrada' => ['autor' => 'Antoine de Saint-Exupéry'],
        'imagenDestacadaAsset' => 'elements::libros/principito.jpeg' // Ruta relativa al alias
    ]
]);
```

### 🔌 IntegrationsManager

Gestiona la inserción de códigos de seguimiento (Google Analytics, GSC) y meta etiquetas de verificación de forma centralizada. Se configura a través de `OpcionManager` en `App/Config/opcionesTema.php`.

**Ejemplo de registro de opción:**

```php
OpcionManager::register('glory_ga4_measurement_id', [
    'valorDefault'  => '',
    'tipo'          => 'text',
    'etiqueta'      => 'Google Analytics 4 Measurement ID',
    'seccion'       => 'integrations',
]);
```

-----

## ⚡ AJAX y Formularios 📝

Glory simplifica radicalmente el manejo de peticiones AJAX y el procesamiento de formularios.

### Realtime por AJAX (Polling)

Permite actualizar vistas en el cliente cuando cambian datos en el servidor, sin websockets. Se basa en:

- `Glory\Services\EventBus` (PHP): bus simple de eventos por canal. Usa `EventBus::emit('mi_canal', $payloadOpcional)` para incrementar la versión del canal cuando ocurre un cambio.
- `Glory\Handler\RealtimeAjaxHandler` (PHP): endpoint AJAX `glory_realtime_versions` que devuelve la versión actual de una lista de canales.
- `Glory/assets/js/Services/gloryRealtime.js` (JS): función `gloryRealtimePoll(channels, { intervalMs })` que hace polling y dispara `gloryRealtime:update` cuando detecta cambios.

Cómo usarlo en tu tema:

1) Activa la feature `gloryRealtime` (viene activa por defecto). Si quieres, puedes forzarla por código:

```php
use Glory\Core\GloryFeatures;
GloryFeatures::enable('gloryRealtime');
```

2) Emite eventos desde el servidor cuando cambien tus datos (ej. tras guardar un post):

```php
use Glory\Services\EventBus;
EventBus::emit('post_reserva');
```

3) Suscríbete desde JS y refresca tu UI cuando llegue una actualización:

```javascript
// Cargar deps: 'glory-ajax' y 'glory-gloryrealtime'
const stop = await window.gloryRealtimePoll(['post_reserva'], { intervalMs: 3000 });
document.addEventListener('gloryRealtime:update', (e) => {
  if (e.detail.channel === 'post_reserva') {
    // Llama tu endpoint para re-renderizar la vista
    gloryAjax('mi_endpoint_render', {/* filtros actuales */}).then((resp) => {
      // Actualiza el DOM con resp.data.html
    });
  }
});
```

Notas:
- Es agnóstico: usa canales (strings) y no asume dominios concretos.
- No necesita WebSockets; el polling es liviano y configurable.
- Control por feature (panel u override por código). El asset JS se registra con handle `glory-gloryrealtime`.

### `gloryAjax.js`

Es la base para todas las peticiones AJAX del framework. Permite enviar tanto objetos de datos como `FormData`, soportando la subida de archivos de forma nativa.

**Ejemplo en JavaScript:**

```javascript
async function miFuncionAjax() {
    const datos = { id: 123, valor: 'ejemplo' };
    const respuesta = await gloryAjax('mi_accion_ajax', datos);
    
    if (respuesta.success) {
        console.log('Datos recibidos:', respuesta.data);
    } else {
        console.error('Error:', respuesta.message);
    }
}
```

### Sistema de Formularios

Compuesto por tres partes:

1.  **`FormBuilder` (PHP)**: Construye formularios complejos sin HTML repetitivo.
2.  **`gloryForm.js` (JS)**: Gestiona la validación del lado del cliente y el envío AJAX.
3.  **`FormHandler` (PHP)**: Enruta la petición AJAX al `Handler` específico para su procesamiento en el servidor.

**Ejemplo: Crear un formulario para guardar un metadato de usuario**

```php
// En una plantilla PHP
use Glory\Components\FormBuilder;
use Glory\Utility\UserUtility;

// 'data-meta-target' indica que se guardarán metas de 'user'
echo FormBuilder::inicio(['atributos' => ['data-meta-target' => 'user']]);

echo FormBuilder::campoTexto([
    'nombre' => 'profesion', // La clave del metadato
    'label'  => 'Tu Profesión',
    'valor'  => UserUtility::meta('profesion') // Obtiene el valor actual
]);

echo FormBuilder::botonEnviar([
    'accion' => 'guardarMeta', // Apunta al handler GuardarMetaHandler.php
    'texto'  => 'Guardar Cambios'
]);

echo FormBuilder::fin();
```

-----

## 🧱 Componentes Reutilizables (Renderers)

Componentes listos para renderizar contenido dinámico en tus plantillas.

### `ContentRender`

Imprime listas de posts, usando plantillas personalizadas y con opciones de paginación AJAX integrada.

**Ejemplo: Mostrar 5 libros por página con paginación AJAX**

```php
use Glory\Components\ContentRender;

ContentRender::print('libro', [
    'publicacionesPorPagina' => 5,
    'paginacion'             => true, // Activa la paginación AJAX
    'plantillaCallback'      => 'plantillaLibro' // Nombre de la función de plantilla
]);
```

### `TermRender`

Similar a `ContentRender`, pero para mostrar listas de términos de una taxonomía (ej. categorías, etiquetas).

**Ejemplo: Mostrar todas las categorías de la taxonomía 'category'**

```php
use Glory\Components\TermRender;

TermRender::print('category');
```

### `BusquedaRenderer` 🔍

Implementa una búsqueda predictiva y en vivo en múltiples tipos de contenido con un simple input HTML.

**Ejemplo en HTML:**

```html
<input type="text" 
       class="busqueda" 
       data-tipos="post,libro" 
       data-cantidad="3" 
       data-target="#resultados-busqueda"
       data-renderer="BusquedaRenderer">

<div id="resultados-busqueda"></div>
```

### Navegación AJAX (`gloryAjaxNav.js`) 🚀

Convierte tu sitio en una aplicación de página única (SPA) cargando el contenido sin recargar la página. Se activa por defecto en `Glory/Config/scriptSetup.php` y no requiere configuración inicial.

### `LogoRenderer`

Permite mostrar el logo del sitio en cualquier lugar mediante un shortcode, respetando la configuración del panel de opciones y permitiendo modificaciones de estilo.

**Ejemplo de shortcode en el editor de WordPress:**

```
[theme_logo width="150px" filter="white"]
```

  * `filter`: Acepta `white` (invierte el logo para fondos oscuros), `black` (lo hace negro) o un valor CSS `filter` personalizado.

-----

## ✨ Scripts de UI (Interfaz de Usuario)

Glory incluye un conjunto de scripts listos para usar que mejoran la interacción del usuario.

  - **Sistema de Modales (`gloryModal.js`)**: Crea, abre y cierra ventanas modales.
    **Ejemplo:**
    ```html
    <button class="openModal" data-modal="miModal">Abrir Modal</button>
    <div id="miModal" class="modal" style="display:none;">Contenido...</div>
    ```
  - **Alertas Personalizadas (`alertas.js`)** 🔔: Reemplaza `alert()` y `confirm()` por notificaciones no bloqueantes.
  - **Previsualización de Archivos (`gestionarPreviews.js`)** 📎: Gestiona la previsualización de archivos para inputs `type="file"`, con soporte para arrastrar y soltar.
  - **Pestañas y Submenús (`pestanas.js`, `submenus.js`)** 📂: Scripts para crear sistemas de pestañas y menús contextuales.
  - **Header Adaptativo (`adaptiveHeader.js`)** 🌓: Cambia el color del texto del header automáticamente según el color del fondo sobre el que se encuentra.

-----

## 🛠️ Herramientas de Administración y Desarrollo

Funcionalidades para facilitar la gestión y depuración del sitio.

### `SyncManager` 🔄

Añade un menú "Glory Sync" en la barra de administración de WordPress para:

  - **Sincronizar Todo**: Fuerza la sincronización de Opciones, Páginas y Contenido por Defecto.
  - **Restablecer a Default**: Restaura el contenido a sus valores definidos en el código.
  - **Borrar Caché de Glory**: Limpia los transients generados por el framework.

### `TaxonomyMetaManager` 🏷️

Permite añadir campos personalizados a las taxonomías, como una imagen destacada para cada categoría.

### `GloryLogger` 📜

Un sistema de logging centralizado para registrar eventos y errores de forma organizada en el log de depuración de PHP.
**Ejemplo:**

```php
use Glory\Core\GloryLogger;

GloryLogger::info('Proceso completado.', ['id_usuario' => 25, 'resultado' => 'éxito']);
GloryLogger::error('Falló la conexión a la API externa.');
```

-----

## 🪄 Utilidades (Helpers)

Helpers para tareas comunes.

  - **`AssetsUtility`**: Obtiene la URL de imágenes guardadas en el tema. `AssetsUtility::imagenUrl('glory::default1.jpg');`
  - **`EmailUtility`** ✉️: Envía correos al administrador del sitio. `EmailUtility::sendToAdmins('Asunto', 'Mensaje');`
  - **`PostUtility`**: Obtiene metadatos de un post. `PostUtility::meta('mi_meta_key');`
  - **`UserUtility`** 👤: Comprueba si el usuario está logueado o tiene un rol. `UserUtility::logeado();`, `UserUtility::tieneRoles('editor');`
  - **`ScheduleManager`** ⏰: Gestiona y muestra horarios de apertura y cierre.
  - **`optimizarImagen()`** 📸: Función global que utiliza el CDN de Jetpack (Photon) para comprimir y servir imágenes de forma optimizada.

-----

## 🔑 Estructura de Archivos Clave

  - `Glory/load.php`: Punto de entrada principal del framework.
  - `App/Config/config.php`: Configuraciones a nivel de tema (páginas, versión, etc.).
  - `App/Config/assets.php`: Registro central de todos los assets (JS/CSS) con `AssetManager`.
  - `App/Config/opcionesTema.php`: Registro central de todas las opciones del tema con `OpcionManager`.
  - `App/Config/postType.php`: Definiciones de Tipos de Contenido Personalizados.
  - `templates/Pages/`: Contiene los archivos con las funciones de renderizado para `PageManager`.
  - `Glory/src/Core/Setup.php`: Clase que inicializa todos los componentes del framework.
  - `Glory/src/Handler/Form/`: Directorio para las clases que procesan la lógica de cada formulario (ej. `GuardarMetaHandler.php`).