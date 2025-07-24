# Glory Framework ✨

¡Bienvenido a Glory! Un framework de desarrollo para WordPress diseñado para acelerar y estandarizar la creación de temas y funcionalidades complejas. Glory proporciona un conjunto de herramientas y componentes robustos que abstraen las complejidades de WordPress, permitiéndote escribir código más limpio, modular y mantenible.

---

## 🚀 Funcionalidades Principales

Glory se organiza en un núcleo de Managers, Componentes y Servicios que gestionan diferentes aspectos de tu sitio WordPress, tanto en el backend como en el frontend.

### ⚙️ Gestores del Núcleo (Backend)

Estos managers automatizan tareas comunes de configuración y gestión de datos.

* **Gestor de Opciones (`OpcionManager`)**: Centraliza la definición y acceso a las opciones del tema. Utiliza un **Registro** (`OpcionRegistry`) para definir las opciones en el código y un **Repositorio** (`OpcionRepository`) para interactuar con la base de datos. Crea automáticamente un panel de administración (`OpcionPanelController`, `PanelRenderer`) para gestionar las opciones definidas y asegura la sincronización inteligente entre el código y la base de datos.

* **Gestor de Tipos de Contenido (`PostTypeManager`)**: Simplifica la creación de Tipos de Contenido Personalizados (CPTs) de forma declarativa. Genera automáticamente las etiquetas de la interfaz de WordPress y permite definir metadatos por defecto para las nuevas entradas de ese tipo.

* **Gestor de Páginas (`PageManager`)**: Asegura que las páginas esenciales de tu tema (como 'Contacto', 'Inicio') existan siempre. Crea las páginas si no existen, les asigna la plantilla correcta y puede configurar la página de inicio del sitio automáticamente.

* **Gestor de Assets (`AssetManager`)**: Unifica la gestión de scripts (JS) y estilos (CSS). Permite definir assets individualmente o cargar carpetas completas (`defineFolder`), manejar dependencias, localizar datos de PHP a JavaScript (`wp_localize_script`) y gestionar el versionado de archivos para evitar problemas de caché.

* **Gestor de Contenido por Defecto (`DefaultContentManager`)**: Define y sincroniza contenido (posts, páginas, categorías) desde el código a la base de datos a través de `DefaultContentSynchronizer`. Es ideal para asegurar que tu tema tenga el contenido inicial necesario. Soporta modos de actualización inteligentes para no sobrescribir cambios manuales.

### ⚡️ Sistema AJAX y Formularios

Glory simplifica radicalmente el manejo de peticiones AJAX y el procesamiento de formularios.

* **AJAX Genérico (`gloryAjax.js`)**: Es la función base para todas las peticiones AJAX del framework. Permite enviar tanto objetos de datos como `FormData`, soportando la subida de archivos de forma nativa.

* **Manejador de Formularios (`FormHandler` y `gloryForm.js`)**: Proporciona un sistema unificado para manejar envíos de formularios sin recargar la página. `gloryForm.js` se encarga de la validación en el frontend y del envío AJAX, mientras que `FormHandler` en el backend enruta la petición a la clase `Handler` correspondiente (ej. `CrearPublicacionHandler`, `GuardarMetaHandler`) según el parámetro `data-accion`, soportando subida de archivos de forma transparente.

* **Constructor de Formularios (`FormBuilder`)**: Un componente PHP para construir formularios complejos sin escribir HTML repetitivo. Es *stateless* (no guarda estado) y genera campos (`campoTexto`, `campoTextarea`, `campoArchivo`, etc.) basados en los arrays de opciones que le proporcionas.

### 🔍 Búsqueda y Navegación AJAX

* **Servicio de Búsqueda (`BusquedaService` y `gloryBusqueda.js`)**: Implementa una búsqueda predictiva y en vivo. El backend (`BusquedaService`) realiza las consultas en múltiples tipos de contenido (posts, usuarios, CPTs) y el frontend (`gloryBusqueda.js`) gestiona las peticiones AJAX a medida que el usuario escribe, mostrando los resultados renderizados por `BusquedaRenderer`.

* **Navegación por AJAX (`gloryAjaxNav.js`)**: Convierte tu sitio en una aplicación de página única (SPA) sin recargar la página. Intercepta los clics en los enlaces, carga el contenido de la nueva página vía AJAX, lo reemplaza en el contenedor principal y actualiza la URL en el navegador.

### 🎨 Componentes de Interfaz de Usuario (UI)

Glory incluye un conjunto de scripts listos para usar que mejoran la experiencia de usuario.

* **Sistema de Modales y Fondo (`gloryModal.js` y `crearfondo.js`)**: Un sistema robusto para crear, abrir y cerrar ventanas modales. Gestiona un fondo global (`crearfondo.js`) que se puede activar desde cualquier parte del código para enfocar la atención en el modal activo.

* **Alertas Personalizadas (`alertas.js`)**: Reemplaza las funciones nativas `alert()` y `confirm()` del navegador por notificaciones personalizadas y no bloqueantes, mejor integradas con el diseño del sitio.

* **Previsualización de Archivos (`gestionarPreviews.js`)**: Gestiona la previsualización de archivos para los inputs de tipo `file`. Soporta selección por clic y arrastrar y soltar (drag & drop), mostrando una vista previa de imágenes, audio u otros archivos.

* **Pestañas y Submenús (`pestanas.js` y `submenus.js`)**: Scripts para crear sistemas de pestañas (tabs) para organizar contenido y para generar menús contextuales o submenús que se activan con clic, clic derecho o pulsación larga en dispositivos táctiles.

### 🛠️ Servicios Avanzados

* **Manejador de Git (`ManejadorGit`)**: Un potente servicio para interactuar con repositorios Git directamente desde PHP. Permite clonar, hacer pull, push, y gestionar ramas. Es ideal para sistemas de autodespliegue o gestión de contenido versionado, e incluye una excepción personalizada (`ExcepcionComandoFallido`) para un mejor manejo de errores.

* **Servidor de Chat (`ServidorChat`)**: Un servidor de WebSockets basado en Ratchet para implementar funcionalidades de chat en tiempo real. Se ejecuta como un proceso independiente y permite la comunicación bidireccional entre el servidor y los clientes conectados.

### 📋 Herramientas de Desarrollo y Administración

* **Sistema de Sincronización (`SyncManager`)**: Añade botones a la barra de administración de WordPress para forzar la sincronización del contenido por defecto (`sincronizar`) o para restablecerlo a sus valores originales definidos en el código (`restablecer`), facilitando el desarrollo y la depuración.

* **Metadatos para Taxonomías (`TaxonomyMetaManager`)**: Permite añadir campos personalizados a las taxonomías, como una imagen destacada para cada categoría, directamente desde el editor de términos de WordPress.

* **Registro de Eventos (`GloryLogger`)**: Un sistema de logging centralizado para registrar eventos, advertencias y errores de la aplicación de forma organizada y eficiente, utilizando el sistema de logs de PHP (`error_log`).

* **Gestor de Licencias (`LicenseManager`)**: Sistema integrado que verifica una clave de licencia (`GLORY_LICENSE_KEY`) contra un servidor remoto para validar el uso del framework. Incluye un período de gracia en caso de fallos de comunicación para no interrumpir el funcionamiento del sitio.

### 🧩 Utilidades y Helpers

* **Renderizadores de Contenido (`ContentRender`, `TermRender`, `PerfilRenderer`)**: Componentes para imprimir listas de posts, términos de taxonomías o la imagen de perfil de un usuario, usando plantillas personalizadas y diversas opciones de configuración (paginación, orden, etc.).

* **Clases de Utilidad**: Conjunto de clases con métodos estáticos para tareas comunes:
    * **`AssetsUtility`**: Para obtener URLs de imágenes en `assets/` y gestionarlas en la biblioteca de medios.
    * **`EmailUtility`**: Para enviar correos de forma sencilla a los administradores.
    * **`PostUtility`** y **`UserUtility`**: Para obtener metadatos de posts o usuarios de forma abreviada.
    * **`ScheduleManager`**: Para gestionar y verificar horarios de apertura y cierre.
    * **`PostActionManager`**: Para crear, actualizar o eliminar posts de forma segura.

* **Optimización de Imágenes (`functions.php`)**: Incluye la función `optimizarImagen()` que utiliza el CDN de Jetpack (Photon) o un fallback para comprimir y servir imágenes de forma optimizada, mejorando el rendimiento del sitio.

---

## 📄 Archivos de Interés

* **`load.php`**: El punto de entrada principal del framework que carga la configuración y el núcleo.
* **`Config/scriptSetup.php`**: Archivo central para definir y registrar todos los assets (JS/CSS) usando `AssetManager`.
* **`src/Core/Setup.php`**: La clase que inicializa todos los componentes principales del framework.
* **`src/Handler/Form/`**: Directorio donde deben residir las clases que procesan la lógica de cada formulario.