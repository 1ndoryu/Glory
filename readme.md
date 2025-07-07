# Glory Framework ✨

¡Bienvenido a Glory, un framework de desarrollo para WordPress diseñado para acelerar y estandarizar la creación de temas y funcionalidades complejas\! Glory proporciona un conjunto de herramientas y componentes robustos que abstraen las complejidades de WordPress, permitiéndote escribir código más limpio, modular y mantenible.

-----

## 🚀 Funcionalidades Principales

Glory está construido sobre un núcleo de "Managers" que gestionan diferentes aspectos de tu sitio de WordPress.

### ⚙️ Gestor de Opciones (`OpcionManager`)

El `OpcionManager` centraliza la definición y el acceso a las opciones del tema, eliminando la necesidad de interactuar directamente con la API de Opciones de WordPress.

**Características:**

  - **Definición centralizada**: Registra todas las opciones de tu tema en un solo lugar.
  - **Sincronización automática**: Las opciones se sincronizan al iniciar, asegurando que los valores por defecto del código se establezcan en la base de datos si no existen.
  - **Acceso tipado**: Proporciona métodos `helper` para obtener valores con el tipo de dato correcto (ej. `texto()`, `richText()`, `imagen()`, `menu()`).
  - **Panel de Opciones Integrado**: Crea automáticamente un panel en el administrador de WordPress para gestionar las opciones definidas, agrupadas por secciones y subsecciones.

**Uso Básico:**

```php
// En tu archivo de configuración
use Glory\Manager\OpcionManager;

// Registrar una opción
OpcionManager::register('color_primario', [
    'valorDefault' => '#007acc',
    'etiqueta' => 'Color Primario',
    'seccion' => 'diseno',
    'etiquetaSeccion' => 'Diseño General',
    'descripcion' => 'Color principal usado en botones y enlaces.'
]);

// Obtener el valor de la opción en tu tema
$color = OpcionManager::texto('color_primario');
```

-----

### 🧱 Gestor de Tipos de Contenido (`PostTypeManager`)

Crea Tipos de Contenido Personalizados (CPTs) de forma declarativa y sencilla.

**Características:**

  - **Generación automática de etiquetas**: Solo necesitas proveer el nombre singular y plural.
  - **Metadatos por defecto**: Define valores meta por defecto que se asignarán automáticamente al crear una nueva entrada de ese tipo.
  - **Configuración simplificada**: Argumentos comunes como `public` y `supports` se establecen con valores por defecto inteligentes.

**Uso Básico:**

```php
// En tu archivo de configuración
use Glory\Core\PostTypeManager;

PostTypeManager::define(
    'proyecto',
    ['menu_icon' => 'dashicons-portfolio'],
    'Proyecto', // Singular
    'Proyectos', // Plural
    ['estado_proyecto' => 'iniciado'] // Meta por defecto
);

// Registrar los CPTs definidos
PostTypeManager::register();
```

-----

### 📄 Gestor de Páginas (`PageManager`)

Asegura que las páginas esenciales de tu tema (como 'Contacto', 'Sobre Nosotros', 'Inicio') existan siempre.

**Características:**

  - **Creación y reconciliación**: Define páginas en tu código y Glory se asegurará de que existan en la base de datos.
  - **Asignación de plantillas**: Asigna automáticamente la plantilla de página correcta.
  - **Página de Inicio**: Al definir una página con el slug `home`, se configurará automáticamente como la página de inicio del sitio.
  - **Limpieza automática**: Las páginas que dejes de definir en el código se enviarán a la papelera.

**Uso Básico:**

```php
// En tu archivo de configuración
use Glory\Core\PageManager;

PageManager::define('contacto', 'Página de Contacto', 'template-contacto.php');
PageManager::define('home', 'Inicio'); // Se asignará como página frontal

// Registrar las páginas para que se procesen
PageManager::register();
```

-----

### 🎨 Gestor de Assets (`AssetManager`)

Unifica la gestión de todos tus scripts (JS) y estilos (CSS).

**Características:**

  - **Carga de carpetas completas**: Define una carpeta y Glory cargará todos los archivos `.js` o `.css` que contenga.
  - **Localización de datos**: Envía datos desde PHP a tus scripts de JavaScript de forma segura con `wp_localize_script`.
  - **Manejo de dependencias**: Especifica dependencias como `jquery` fácilmente.
  - **Versión automática**: En modo desarrollo, la versión del archivo se basa en su fecha de modificación para evitar problemas de caché.

**Uso Básico:**

```php
// En tu archivo de configuración (ej. scriptSetup.php)
use Glory\Core\AssetManager;

// Cargar todos los JS de la carpeta UI
AssetManager::defineFolder('script', '/Assets/js/UI/');

// Definir un script específico con datos localizados
AssetManager::define(
    'script',
    'glory-ajax-nav',
    '/Assets/js/genericAjax/gloryAjaxNav.js',
    [
        'localize' => [
            'nombreObjeto' => 'dataGlobal',
            'datos' => ['nonce' => wp_create_nonce('globalNonce')]
        ]
    ]
);
```

-----

### 💳 Gestor de Créditos (`CreditosManager`)

Integra un sistema de créditos o puntos para los usuarios de tu sitio.

**Características:**

  - **Operaciones sencillas**: `getCreditos()`, `agregar()`, `quitar()`, y `setCreditos()`.
  - **Recarga periódica**: Configura una recarga automática de créditos (ej. recargar hasta 100 créditos cada día) para todos los usuarios que estén por debajo de esa cantidad.
  - **Registro de transacciones**: Cada operación se registra con un motivo para auditoría.

**Uso Básico:**

```php
use Glory\Manager\CreditosManager;

// Inicializar el sistema
CreditosManager::init();

// Configurar una recarga diaria
CreditosManager::recargaPeriodica(true, 100); // Activa, recarga hasta 100 créditos

// Quitar 10 créditos a un usuario por una acción
$usuarioId = get_current_user_id();
CreditosManager::quitar($usuarioId, 10, 'Compra de artículo virtual');
```

-----

## ⚡️ Sistema AJAX y Formularios

Glory simplifica radicalmente el manejo de peticiones AJAX y el procesamiento de formularios.

### `gloryAjax.js` y `FormHandler`

  - **Punto de entrada único**: Utiliza la función `gloryAjax('miAccion', { ...datos })` en tu JavaScript para todas las llamadas AJAX.
  - **Enrutamiento automático**: En PHP, `FormHandler` recibe la petición, busca una clase `Manejador` correspondiente a `miAccion` (ej. `MiAccionHandler`) y ejecuta su método `procesar()`.
  - **Soporte para archivos**: `gloryAjax` maneja transparentemente el envío de `FormData`, permitiendo subir archivos sin configuración extra.

**Ejemplo de JS:**

```javascript
// Enviar datos de un formulario
const miFormulario = document.getElementById('mi-form');
const datos = new FormData(miFormulario);

// La acción 'guardarMeta' buscará y ejecutará GuardarMetaHandler.php
const respuesta = await gloryAjax('guardarMeta', datos);

if (respuesta.success) {
    alert(respuesta.data.alert);
}
```

### `FormBuilder`

Construye formularios complejos en PHP sin escribir HTML repetitivo. Es un componente *stateless* que genera campos basados en las opciones que le proporcionas.

**Ejemplo en PHP:**

```php
use Glory\Components\FormBuilder;

// Iniciar un formulario que será procesado por GuardarMetaHandler
echo FormBuilder::inicio([
    'atributos' => ['data-meta-target' => 'user']
]);

// Añadir un campo de texto para el nombre
echo FormBuilder::campoTexto([
    'nombre' => 'nombre_completo',
    'label' => 'Nombre Completo',
    'valor' => UserUtility::meta('nombre_completo') // Obtener valor actual
]);

// Añadir un botón de envío
echo FormBuilder::botonEnviar([
    'accion' => 'guardarMeta', // Acción que usa el FormHandler
    'texto' => 'Guardar Perfil'
]);

echo FormBuilder::fin();
```

-----

## 🖼️ Componentes de Interfaz de Usuario (UI)

Glory viene con scripts de JavaScript listos para usar que añaden interactividad a tu tema. Estos se activan automáticamente en cada carga de página (incluidas las cargas por AJAX).

  - **`gestionarPreviews.js`**: Crea zonas de previsualización para `input[type="file"]`. Soporta drag & drop, muestra previsualizaciones de imágenes y placeholders para otros tipos de archivo.
  - **`alertas.js`**: Reemplaza las funciones `alert()` y `confirm()` del navegador por notificaciones y modales de confirmación más elegantes y no bloqueantes.
  - **`gloryModal.js` y `crearfondo.js`**: Sistema completo para crear y gestionar modales. Usa `data-modal="mi-modal-id"` en un botón para abrir el modal con el ID `mi-modal-id`.
  - **`submenus.js`**: Crea menús contextuales o desplegables que se posicionan de forma inteligente y pueden ser activados por clic, clic derecho o pulsación larga en dispositivos táctiles.
  - **`pestanas.js`**: Genera automáticamente una interfaz de pestañas a partir de una estructura HTML simple.

-----

## 🔍 Búsqueda y Navegación

### `BusquedaService` y `gloryBusqueda.js`

Implementa una búsqueda predictiva y en vivo.

  - **Backend**: `BusquedaService` permite buscar en múltiples tipos de contenido a la vez (posts, páginas, usuarios, CPTs) y balancear los resultados.
  - **Frontend**: `gloryBusqueda.js` se asocia a cualquier input con la clase `.busqueda` y, a medida que el usuario escribe, envía una petición AJAX, recibe el HTML de los resultados y los muestra en un contenedor designado.

**Ejemplo de HTML para el input de búsqueda:**

```html
<input type="text"
       class="busqueda"
       data-tipos="post,page,perfiles"
       data-cantidad="3"
       data-target="#resultados-busqueda">
<div id="resultados-busqueda"></div>
```

### `gloryAjaxNav.js`

Transforma la navegación de tu sitio en una experiencia de aplicación de una sola página (SPA).

  - **Carga sin recarga**: Navega entre páginas sin recargar el navegador. El contenido de un selector (ej. `#content`) se reemplaza dinámicamente.
  - **Caché y pre-carga**: Almacena en caché las páginas visitadas para una navegación casi instantánea.
  - **Indicadores de carga**: Muestra una barra de progreso durante la carga.
  - **Compatibilidad**: Respeta los enlaces con `target="_blank"`, enlaces a archivos y se puede deshabilitar añadiendo la clase `.noAjax`.

-----

## 🛠️ Servicios Adicionales

  - **`ManejadorGit`**: Un potente servicio para interactuar con repositorios Git desde PHP. Permite clonar, hacer pull, push, commit y gestionar ramas, ideal para sistemas de autodespliegue o gestión de contenido versionado.
  - **`ServidorChat`**: Un servidor de WebSockets basado en Ratchet para implementar funcionalidades de chat en tiempo real. Se ejecuta como un proceso independiente en la línea de comandos.

-----

## 📋 Archivos de Interés

  - **`load.php`**: El punto de entrada principal del framework.
  - **`Config/scriptSetup.php`**: Archivo central para definir y registrar todos los assets (JS/CSS) usando `AssetManager`.
  - **`todo.md` y `Status.md`**: Archivos de seguimiento del desarrollo y refactorización del proyecto.

Este README provee una visión general. Para un entendimiento más profundo, se recomienda revisar el código fuente de cada componente, que está ampliamente documentado. ¡Feliz desarrollo con Glory\!