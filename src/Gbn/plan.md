# GBN - Glory Builder Nativo

**GBN** es un constructor visual moderno e inteligente para WordPress que permite construir sitios web desde HTML plano con capacidad de edición visual en el front-end. Mantiene la UI en inglés para usuarios finales, pero todo el código interno está en español siguiendo principios SOLID.

## 1. Filosofía y Principios

1. **HTML Limpio**: Sin shortcodes, markup semántico y válido.
2. **Independencia de GBN**: Las páginas funcionan correctamente aunque GBN esté desactivado (estilos inline persisten).
3. **Código Minimalista**: Principios SOLID, evitar duplicación de código.
4. **Componentes Agnósticos**: Los componentes de Glory aceptan progresivamente GBN sin romper su uso independiente.
5. **Sincronización Bidireccional**: Cambios en código o interfaz se reflejan correctamente en ambos lados.

---

## 2. Arquitectura y Sistemas Core

### Contexto General
La arquitectura de GBN se divide en tres capas principales que interactúan entre sí:
- **Glory/ (Framework)**: Provee los managers base (`AssetManager`, `PageManager`) y utilidades agnósticas.
- **App/ (Tema)**: Implementación concreta que consume GBN.
- **GBN Core**: El motor del constructor, ubicado en `Glory/src/Gbn/`.

### Backend: Flujo de Datos y Endpoints
El backend (PHP) actúa como el cerebro del sistema, gestionando la carga de recursos, la seguridad, la validación de datos y la persistencia.

#### Core (`src/Gbn/`)
- **`GbnManager.php`**: Clase maestra que inicializa el sistema.
    - **Bootstrap**: Verifica si GBN está activo (`GloryFeatures`).
    - **Assets**: Encola CSS/JS condicionalmente (Frontend vs Editor).
    - **Localización**: Inyecta el objeto global `gloryGbnCfg` en JS con datos vitales: `nonce`, `pageId`, `isEditor`, `themeSettings`, y `roleSchemas` (generados desde `ContainerRegistry`).
- **`GbnAjaxHandler.php`**: Proxy que delega el registro de endpoints AJAX a `Registrar`.
- **`Logger.php`**: Sistema de logs dedicado (`gbn.log`) para depuración interna.

#### Configuración (`src/Gbn/Config/`)
- **`ContainerRegistry.php`**: **Fuente de Verdad**. Define los roles de componentes (`principal`, `secundario`, `content`, `text`), sus selectores CSS, y sus esquemas de configuración (campos del panel) y valores por defecto.
- **`RoleConfig.php`**: Define la configuración base de roles permitidos en el sistema.

#### Sistema AJAX (`src/Gbn/Ajax/`)
- **`Registrar.php`**: Centraliza todos los `add_action('wp_ajax_...')`. Mapea cada acción a su Handler correspondiente.

##### Handlers (Controladores)
- **`ContentHandler.php`**: Facade que redirige peticiones de contenido a `OptionsHandler`, `ConfigHandler` o `PreviewHandler`.
- **`PageSettingsHandler.php`**: API para leer/guardar configuración específica de página (`gbn_page_settings` en `post_meta`).
- **`ThemeSettingsHandler.php`**: API para leer/guardar configuración global del tema (`gbn_theme_settings` en `wp_options`).
- **`LibraryHandler.php`**: Gestiona la creación/edición de elementos reutilizables (Headers, Links).
- **`DeleteHandler.php`**: Procesa la eliminación de bloques.
- **`OrderHandler.php`**: Persiste el orden visual de los bloques en el DOM.

##### Lógica de Negocio (`src/Gbn/Ajax/Handlers/` y `Services/`)
- **`ConfigHandler.php`**: Maneja el guardado masivo de la página. Recibe el árbol JSON, lo procesa y guarda en `post_meta`.
- **`OptionsHandler.php`**: Guarda opciones granulares de un bloque específico.
- **`PreviewHandler.php`**: Genera el HTML de previsualización para un bloque.
- **`DomProcessor.php`**: Servicio de normalización HTML.
    - Genera `data-gbn-id` únicos si faltan.
    - Limpia atributos internos antes de guardar.
    - Asegura la integridad del HTML almacenado.

### Frontend: Arquitectura SPA
El frontend (JS) es una aplicación reactiva que manipula el DOM directamente, sin Virtual DOM, para máxima performance y fidelidad "What You See Is What You Get".

#### Core (`assets/js/core/`)
- **`utils.js`**: Utilidades generales (helpers, debounce, deepClone) y acceso global a configuración.
- **`state.js`**: **Store Central**. Mantiene el registro (`registry`) de bloques activos y el índice (`elementsIndex`) para acceso O(1). Gestiona la "verdad única" en memoria.

#### Servicios (`assets/js/services/`)
- **`content.js`**: Facade principal. Expone la API pública `Gbn.content`.
- **`css-sync.js`**: Sincronización de estilos. Genera CSS dinámico en tiempo real.
- **`persistence.js`**: Recolecta el estado del DOM (`collectBlocksPayload`) y lo envía al backend.
- **`responsive.js`**: Gestiona breakpoints, herencia de valores y simulación de viewport.
- **`style-generator.js`**: Genera cadenas de CSS estático para guardar en la base de datos.
- **`content/`**:
    - **`builder.js`**: Construye nuevos bloques HTML desde esquemas JSON.
    - **`config.js`**: Motor de configuración. Parsea opciones y sincroniza estilos inline.
    - **`dom.js`**: Manipulación directa del DOM. Inyecta atributos y normaliza HTML.
    - **`hydrator.js`**: Convierte HTML estático en bloques interactivos al cargar el editor.
    - **`roles.js`**: Detecta roles (`principal`, `secundario`) en el HTML existente.
    - **`scanner.js`**: Escanea el DOM buscando elementos editables.

#### Render (`assets/js/render/`)
- **`styleManager.js`**: Gestiona la aplicación de estilos computados y la resolución de herencia.

#### UI (`assets/js/ui/`)
- **`panel-core.js`**: Gestión del ciclo de vida del panel lateral.
- **`panel-render.js`**: Renderiza los controles del panel para un bloque específico.
- **`drag-drop.js`**: Sistema de arrastrar y soltar bloques.
- **`library.js`**: Gestión de la biblioteca de componentes reutilizables.
- **`dock.js`**: Barra de herramientas flotante o acoplada.
- **`inspector.js`**: Inspector visual de elementos (hover, selección).
- **`theme/`**:
    - **`applicator.js`**: Aplica configuraciones globales al DOM (Variables CSS).
    - **`render.js`**: Renderiza el panel de configuración del tema.
    - **`index.js`**: Punto de entrada para la UI del tema.
- **`panel-fields/`**: **Sistema Modular de Campos**.
    - `index.js`: Dispatcher/Factory de campos.
    - `utils.js`: Utilidades compartidas para campos.
    - `sync.js`: Lógica de sincronización bidireccional UI <-> Estado.
    - Módulos de campos: `color.js`, `fraction.js`, `header.js`, `icon-group.js`, `rich-text.js`, `select.js`, `slider.js`, `spacing.js`, `text.js`, `toggle.js`, `typography.js`.
- **`renderers/`**: **Renderizadores de Bloques**.
    - `shared.js`: Utilidades compartidas (`extractSpacingStyles`, `parseFraction`, `getResponsiveValue`, `cloneConfig`).
    - `layout-flex.js`: Lógica de estilos para layout Flexbox.
    - `layout-grid.js`: Lógica de estilos para layout Grid.
    - `principal.js`: Renderer y estilos para bloques de rol `principal`.
    - `secundario.js`: Renderer y estilos para bloques de rol `secundario`.
    - `text.js`: Renderer, estilos y lógica de actualización para bloques de texto.
    - `page-settings.js`: Manejador de actualizaciones para configuración de página.
    - `theme-settings.js`: Manejador de actualizaciones para configuración global del tema.

#### Entry Points
- **`gbn.js`**: Punto de entrada principal para el editor. Inicializa todos los subsistemas.
- **`gbn-front.js`**: Script ligero para el frontend (visitantes). Carga solo lo necesario para interactividad.

---

## 3. Funcionalidades Actuales (Estado del Arte)

### 1. Sistema de Componentes Inteligente
GBN no usa componentes propietarios opacos.
- **Detección Automática**: `roles.js` escanea el HTML y asigna roles (`principal`, `secundario`) basados en atributos estándar (`gloryDiv`).
- **Esquemas Dinámicos**: Los paneles de edición se generan en tiempo real (`panel-render.js`) leyendo el esquema definido en `ContainerRegistry.php`. Si añades un campo en PHP, aparece automáticamente en JS.

### 2. Motor de Estilos en Tiempo Real
- **Variables CSS**: El sistema prefiere variables CSS sobre estilos inline duros. Cambiar el "Color Primario" en el panel actualiza `--gbn-primary`, repintando todo el sitio instantáneamente (0ms delay).
- **Herencia Visual**: El panel indica visualmente si un valor es heredado.
    - *Azul*: Valor propio del bloque/página.
    - *Amarillo*: Valor heredado (del Tema o Breakpoint superior).

### 3. Sistema Responsive "True-View"
- **Simulación Real**: No usa iframes lentos. Simula viewports limitando el ancho del contenedor `body` (`responsive.js`), lo que garantiza que el CSS se comporte exactamente igual que en un dispositivo real.
- **Persistencia por Breakpoint**: Los valores se guardan asociados a su breakpoint (`_responsive: { mobile: { ... } }`).

### 4. Gestión de Contenido Híbrida
- **`gloryContentRender`**: Componente especial que puede renderizar contenido dinámico (PHP) dentro del editor visual.
- **AJAX Partial Views**: `ContentHandler` sirve fragmentos HTML renderizados por el servidor, permitiendo editar loops y queries complejos visualmente.

---

- **Bug 12 (UI):** Inconsistencia en etiquetas ("Separación (Gap)" vs "Separación entre elementos") y orden de opciones Flex en Secundario. **Estado:** Resuelto.
- **Bug 13 (UI):** Falta opción de Fondo en componente Secundario. **Estado:** Resuelto.
- **Bug 14 (UI):** Bug visual en opciones de Tipografía (botones cortados) y mejora estética con iconos. **Estado:** Resuelto.
- **Bug 15 (JS):** Error de sintaxis en `roles.js` (falta de cierre de array). **Estado:** Resuelto.
- **Bug 16 (UI):** Indicador de cambios en botón Guardar del Dock mal posicionado. **Estado:** Resuelto.
- **Bug 17 (UI):** Barra de herramientas de componentes descentrada y sin efecto hover. **Estado:** Resuelto.
- **Bug 18 (UI):** Elementos vacíos invisibles en el builder (falta de dimensiones mínimas). **Estado:** Resuelto.
- **Bug 19 (UI):** Refinamiento de Tipografía (iconos dentro de inputs, limpieza de clases). **Estado:** Resuelto.
- **Bug 20 (UI):** Hover de componentes sin fondo de color. **Estado:** Resuelto.
- **Bug 21 (UI):** Barra de herramientas descentrada por conflicto de estilos `transform` (corregido en múltiples selectores). **Estado:** Resuelto.
- **Bug 22 (UI):** Orden incorrecto de opciones Flex en componente Secundario. **Estado:** Resuelto.

## 6. Estrategia de Refactorización (Futuro)

Para evitar inconsistencias futuras entre PHP (`ContainerRegistry`) y JS (`roles.js`), se propone:

1.  **Fuente Única de Verdad:** Migrar la definición de esquemas completamente a PHP.
2.  **Inyección Automática:** El frontend (`roles.js`) no debería tener definiciones hardcoded. Debe recibir todo el esquema vía `wp_localize_script` desde PHP.
3.  **Generación Dinámica:** El panel (`render.js`) debe construir la UI iterando ciegamente sobre el esquema recibido, sin lógica condicional específica por rol (como `if (role === 'secundario')`).

---

## 7. Historial de Cambios y Resoluciones (Resumen)

- **Bug 1-5 (Responsive):** Corregidos problemas de simulación de viewport, persistencia de breakpoints y detección de estilos computados.
- **Bug 6 (Panel State):** Implementada persistencia de estado de navegación del panel al cambiar breakpoints.
- **Bug 7 (Color Detection):** Corregida detección de color de fondo inicial.
- **Bug 8 (Responsive Persistence):** Solucionado. Los valores responsive ahora persisten y se aplican correctamente en el editor.
- **Bug 10 (Layout Persistence):** Solucionado. Las opciones de layout (Flexbox) ahora persisten y se aplican correctamente al recargar.
- **Bug 12-14 (UI Consistency):** Unificadas etiquetas, reordenadas opciones, añadido fondo a Secundario y mejorada UI de tipografía con iconos.
- **Bug 15-18 (UI/UX):** Corregido error en `roles.js`, mejorado botón Dock, centrada barra de herramientas y asegurada visibilidad de elementos vacíos.
- **Bug 19-20 (UI Refinements):** Tipografía más limpia (iconos internos) y mejor feedback visual en hover de componentes.

## 7. Historial de Cambios y Resoluciones (Detallado)

### Diciembre 2025

#### ✅ Bug 10: Persistencia de Layout Options (Flexbox)
-   **Problema**: Opciones como `flex-wrap: nowrap` configuradas en Theme Settings funcionaban en vivo pero se perdían al recargar.
-   **Causa**: `applicator.js` aplicaba los estilos en tiempo real, pero el generador de CSS estático (`style-generator.js`) o la hidratación inicial no estaban leyendo correctamente estas propiedades específicas del objeto de configuración del tema.
-   **Solución**: Se actualizó la lógica de hidratación y aplicación de estilos para incluir explícitamente propiedades de layout (`direction`, `wrap`, `justify`, `align`) al procesar los defaults del tema.

#### ✅ Bug 11: Salida HTML Limpia
-   **Problema**: El atributo `data-gbn-schema` (que contiene el JSON completo de configuración del panel) se inyectaba en el frontend para todos los usuarios, inflando el tamaño del DOM innecesariamente.
-   **Solución**: Se modificó `Glory/src/Gbn/assets/js/services/content/dom.js`.
    -   Se añadió una verificación estricta: `if (config.isEditor)`.
    -   Ahora, `data-gbn-schema` solo se inyecta si el usuario logueado tiene permisos de edición. Los visitantes ven un HTML limpio.

#### ✅ Bug 8: Persistencia de Valores Responsive en Tema
-   **Problema**: Los valores definidos para Tablet/Mobile en el Panel de Tema no se guardaban o no se aplicaban al cambiar de vista.
-   **Solución**: Se corrigió `services/responsive.js` y `ui/theme/applicator.js`.
    -   Se implementó la lógica de "burbujeo" (Mobile -> Tablet -> Desktop) específicamente para el objeto `themeSettings`.
    -   Se aseguró que `getThemeResponsiveValue` consulte correctamente la estructura `_responsive` dentro de la configuración global del tema.

#### ✅ Refactorización SOLID de Paneles
-   **Cambio**: Se descompuso el archivo monolítico `panel-fields.js` en múltiples módulos (`spacing.js`, `typography.js`, etc.) dentro de `ui/panel-fields/`.
-   **Beneficio**: Mantenibilidad drásticamente mejorada. Añadir un nuevo tipo de campo ahora solo requiere crear un archivo y registrarlo en el `index.js` dispatcher.

---

## 5. Roadmap Activo

### ✅ Completado Recientemente
- [x] **Bug 10: Persistencia de Layout Options**
    -   *Solución*: Se aseguró que las propiedades de layout (`flex-wrap`, `direction`, `justify`) se guarden y apliquen correctamente tanto en `applicator.js` como en la generación de CSS estático.
- [x] **Bug 11: Salida HTML Limpia**
- [x] **Bug 8: Persistencia Responsive en Tema**
- [x] **Refactorización panel-render.js**
    -   *Solución*: Se extrajo la lógica de renderizado y actualización a `ui/renderers/` (`principal.js`, `secundario.js`, `text.js`, etc.), dejando `panel-render.js` como un orquestador limpio. Se movieron helpers a `shared.js`.

### 🚨 Prioridades Inmediatas
- [ ] **gloryContentRender Avanzado**
    -   Implementar opciones avanzadas de layout (Grid/List), paginación AJAX y filtrado por taxonomías.
    -   Paridad funcional con su version en avada.

### Candidatos a Refactorización (Por Complejidad)
Archivos que han crecido significativamente y deberían ser divididos en módulos más pequeños (Principio de Responsabilidad Única):



2.  **`ui/theme/render.js` (520 líneas)**:
    -   *Responsabilidad*: Renderiza el panel de configuración global del tema.
    -   *Problema*: Monolito que maneja colores, tipografía y layout global en un solo archivo.
    -   *Solución*: Dividir en `theme-colors.js`, `theme-typography.js`, etc.

3.  **`panel-core.js` (502 líneas)**:
    -   *Responsabilidad*: Gestión del estado del panel (abrir/cerrar, placeholder, footer).
    -   *Problema*: Mezcla lógica de presentación con lógica de estado y persistencia (Restore).
    -   *Solución*: Extraer la lógica de "Restaurar" a un módulo `restore-ui.js`.

4.  **`core/utils.js` (471 líneas)**:
    -   *Problema*: "Cajón de sastre" de utilidades.
    -   *Solución*: Agrupar en `math-utils.js`, `dom-utils.js`, `object-utils.js`.

5.  **`ContainerRegistry.php` (416 líneas)**:
    -   *Estado*: Aceptable por ahora, ya que es configuración pura, pero podría dividirse en archivos de configuración por rol si crece más.

### Fases Futuras
- [ ] **Adaptación de Componentes**: Estandarizar `TermRender`, `GloryImage` para usar el sistema `gbnDefaults` y `data-gbn-schema`.
- [ ] **CSS Sync Avanzado**: Sincronización bidireccional completa donde editar el archivo `.css` actualice los valores en el panel (parsing de CSS).
- [ ] **Grid Layout Nativo**: Soporte visual para CSS Grid (columnas, filas, áreas) con interfaz drag & drop.