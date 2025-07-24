# Glory Framework ✨

¡Bienvenido a Glory, un framework de desarrollo para WordPress diseñado para acelerar y estandarizar la creación de temas y funcionalidades complejas! Glory proporciona un conjunto de herramientas y componentes robustos que abstraen las complejidades de WordPress, permitiéndote escribir código más limpio, modular y mantenible.

-----

## 🚀 Funcionalidades Principales

Glory se organiza en un núcleo de Managers, Componentes y Servicios que gestionan diferentes aspectos de tu sitio WordPress, tanto en el backend como en el frontend.

### ⚙️ Gestores del Núcleo (Backend)

Estos managers automatizan tareas comunes de configuración y gestión de datos.

* **Gestor de Opciones (`OpcionManager`)**: Centraliza la definición y acceso a las opciones del tema. Crea automáticamente un panel en el administrador de WordPress para gestionar las opciones definidas y asegura la sincronización entre el código y la base de datos.

* **Gestor de Tipos de Contenido (`PostTypeManager`)**: Simplifica la creación de Tipos de Contenido Personalizados (CPTs) de forma declarativa. Genera automáticamente las etiquetas y permite definir metadatos por defecto para las nuevas entradas.

* **Gestor de Páginas (`PageManager`)**: Asegura que las páginas esenciales de tu tema (como 'Contacto', 'Inicio') existan siempre. Crea las páginas si no existen, asigna plantillas y puede configurar la página de inicio del sitio automáticamente.

* **Gestor de Assets (`AssetManager`)**: Unifica la gestión de scripts (JS) y estilos (CSS). Permite cargar carpetas completas de assets, manejar dependencias, localizar datos de PHP a JavaScript y gestionar el versionado de archivos para evitar problemas de caché.

* **Gestor de Contenido por Defecto (`DefaultContentManager`)**: Define y sincroniza contenido (posts, páginas) desde el código a la base de datos. Es ideal para asegurar que tu tema tenga el contenido inicial necesario. Soporta modos de actualización inteligentes para no sobrescribir cambios manuales.

### ⚡️ Sistema AJAX y Formularios

Glory simplifica radicalmente el manejo de peticiones AJAX y el procesamiento de formularios.

* **Manejador de Formularios (`FormHandler` y `gloryForm.js`)**: Proporciona un sistema unificado para manejar envíos de formularios. `gloryForm.js` se encarga de la validación en el frontend y del envío AJAX, mientras que `FormHandler` en el backend enruta la petición a la clase `Handler` correspondiente para su procesamiento, soportando subida de archivos de forma transparente.

* **Constructor de Formularios (`FormBuilder`)**: Un componente PHP para construir formularios complejos sin escribir HTML repetitivo. Es *stateless* y genera campos basados en las opciones que le proporcionas.

### 🔍 Búsqueda y Navegación AJAX

* **Servicio de Búsqueda (`BusquedaService` y `gloryBusqueda.js`)**: Implementa una búsqueda predictiva y en vivo. El backend (`BusquedaService`) busca en múltiples tipos de contenido (posts, usuarios, CPTs) y el frontend (`gloryBusqueda.js`) gestiona las peticiones AJAX a medida que el usuario escribe.

* **Navegación por AJAX (`gloryAjaxNav.js`)**: Convierte tu sitio en una aplicación de página única (SPA) sin recargar la página. Intercepta los clics en los enlaces, carga el contenido de la nueva página vía AJAX y lo reemplaza en el contenedor principal, actualizando la URL en el navegador.

### 🎨 Componentes de Interfaz de Usuario (UI)

Glory incluye un conjunto de scripts listos para usar que mejoran la experiencia de usuario.

* **Sistema de Modales y Fondo (`gloryModal.js` y `crearfondo.js`)**: Sistema para crear, abrir y cerrar ventanas modales. Gestiona un fondo global que se puede activar desde cualquier parte del código para enfocar la atención en el modal.

* **Alertas Personalizadas (`alertas.js`)**: Reemplaza las funciones `alert()` y `confirm()` del navegador por notificaciones personalizadas y no bloqueantes, mejor integradas con el diseño del sitio.

* **Previsualización de Archivos (`gestionarPreviews.js`)**: Gestiona la previsualización de archivos para los inputs de tipo `file`. Soporta selección por clic y arrastrar y soltar (drag & drop), mostrando una vista previa de imágenes, audio u otros archivos.

* **Pestañas y Submenús (`pestanas.js` y `submenus.js`)**: Scripts para crear sistemas de pestañas (tabs) para organizar contenido y para generar menús contextuales o submenús que se activan con clic, clic derecho o pulsación larga en dispositivos táctiles.

### 🛠️ Servicios Avanzados

* **Manejador de Git (`ManejadorGit`)**: Un potente servicio para interactuar con repositorios Git desde PHP. Permite clonar, hacer pull, push, y gestionar ramas, ideal para sistemas de autodespliegue o gestión de contenido versionado.

* **Servidor de Chat (`ServidorChat`)**: Un servidor de WebSockets basado en Ratchet para implementar funcionalidades de chat en tiempo real. Se ejecuta como un proceso independiente y permite la comunicación bidireccional entre el servidor y los clientes.

### 📋 Herramientas de Desarrollo y Administración

* **Sistema de Sincronización (`SyncManager`)**: Añade botones a la barra de administración de WordPress para forzar la sincronización del contenido por defecto o para restablecerlo a sus valores originales definidos en el código, facilitando el desarrollo y la depuración.

* **Metadatos para Taxonomías (`TaxonomyMetaManager`)**: Permite añadir campos personalizados a las taxonomías, como una imagen destacada para cada categoría, directamente desde el editor de términos de WordPress.

* **Registro de Eventos (`GloryLogger`)**: Un sistema de logging centralizado para registrar eventos, advertencias y errores de la aplicación de forma organizada y eficiente, utilizando el sistema de logs de PHP.

* **Gestor de Licencias (`LicenseManager`)**: Sistema integrado que verifica una clave de licencia contra un servidor remoto para validar el uso del framework, con un período de gracia en caso de fallos de comunicación.

### 🧩 Utilidades y Helpers

* **Renderizadores de Contenido (`ContentRender` y `TermRender`)**: Componentes para imprimir listas de posts o términos de taxonomías con plantillas personalizadas, paginación y opciones de ordenación.

* **Utilidades (`AssetsUtility`, `EmailUtility`, `PostUtility`, `UserUtility`, `ScheduleManager`)**: Conjunto de clases con métodos estáticos para tareas comunes como obtener metadatos, verificar roles de usuario, enviar correos, gestionar horarios y trabajar con assets.

* **Optimización de Imágenes (`functions.php`)**: Incluye una función `optimizarImagen()` que utiliza el CDN de Jetpack (Photon) o un fallback para comprimir y servir imágenes de forma optimizada, mejorando el rendimiento del sitio.

-----

## 📄 Archivos de Interés

* **`load.php`**: El punto de entrada principal del framework.
* **`Config/scriptSetup.php`**: Archivo central para definir y registrar todos los assets (JS/CSS) usando `AssetManager`.
* **`src/Core/Setup.php`**: Clase que inicializa los componentes principales del framework.