# GBN - Reglas y Arquitectura

> [!IMPORTANT]
> Este documento contiene las reglas inmutables, principios arquitectónicos y definiciones canónicas del sistema. Cualquier implementación que contradiga este documento se considera un error.

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
- **`GbnFeatures.php`**: Control de activación de características experimentales.

#### Sistema de Componentes (`src/Gbn/Components/`, `Schema/` y `Traits/`)
- **`ComponentLoader.php`**: **Nuevo Núcleo**. Escanea y registra automáticamente las clases de componentes que implementan `ComponentInterface`.
- **`AbstractComponent.php`**: Clase base que provee funcionalidad común.
- **`Traits/`**: Módulos reutilizables (`HasFlexbox`, `HasSpacing`) que inyectan lógica y esquema a los componentes.
- **`SchemaBuilder.php`**: API fluida para definir los campos del panel de control de forma legible y orientada a objetos.
- **`ContainerRegistry.php`**: (Legacy/Transición) Mantiene compatibilidad mientras se migra totalmente al sistema de clases.

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
- **`utils.js`**: Utilidades generales (helpers, debounce, deepClone) y acceso global a configuración.
- **`state.js`**: **Store Central (Redux-lite)**. Mantiene el registro (`registry`) de bloques activos y el índice (`elementsIndex`) para acceso O(1). Gestiona la "verdad única" en memoria mediante `dispatch`, `actions` y `subscribers`.
- **`store-subscriber.js`**: Conecta el Store con el DOM. Escucha cambios de estado y dispara actualizaciones visuales.

#### Servicios (`assets/js/services/`)
- **`content.js`**: Facade principal. Expone la API pública `Gbn.content`.
- **`css-sync.js`**: Sincronización de estilos. Genera CSS dinámico en tiempo real.
- **`persistence.js`**: Recolecta el estado del DOM (`collectBlocksPayload`) y lo envía al backend.
- **`responsive.js`**: Gestiona breakpoints, herencia de valores y simulación de viewport.
- **`responsive.js`**: Gestiona breakpoints, herencia de valores y simulación de viewport.
- **`style-generator.js`**: Genera cadenas de CSS estático para guardar en la base de datos.
- **`logger.js`**: **Remote Logger**. Servicio de diagnóstico que envía logs al servidor (Desactivado por defecto `ENABLED=false`).
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
- **`dock.js`**: Barra de herramientas flotante o acoplada.
- **`inspector.js`**: Inspector visual de elementos (hover, selección).
- **`overlay.js`**: **Debug Overlay**. Panel flotante (`Ctrl+Alt+D`) para inspección profunda de estado y estilos computados.
- **`theme/`**:
    - **`applicator.js`**: Aplica configuraciones globales al DOM (Variables CSS).
    - **`render.js`**: Renderiza el panel de configuración del tema.
    - **`index.js`**: Punto de entrada para la UI del tema.
- **`panel-fields/`**: **Sistema Modular de Campos**.
    - `index.js`: Dispatcher principal. Delega al `registry.js`.
    - `registry.js`: **Nuevo Registro (OCP)**. Permite que los campos se auto-registren.
    - `utils.js`: Utilidades compartidas para campos.
    - `sync.js`: Lógica de sincronización bidireccional UI <-> Estado.
    - Módulos de campos: `color.js`, `fraction.js`, `header.js`, `icon-group.js`, `rich-text.js`, `select.js`, `slider.js`, `spacing.js`, `text.js`, `toggle.js`, `typography.js`.
- **`renderers/`**: **Renderizadores de Bloques**.
    - `style-composer.js`: **Pure Style Composer (DRY)**. Función pura `(config, schema) => css` que genera estilos centralizados. No tiene efectos secundarios.
    - `shared.js`: Utilidades compartidas (`extractSpacingStyles`, `parseFraction`, `getResponsiveValue`, `cloneConfig`).
    - `layout-flex.js`: Lógica de estilos para layout Flexbox.
    - `layout-grid.js`: Lógica de estilos para layout Grid.
    - `principal.js`: Renderer y estilos para bloques de rol `principal` (Usa `StyleComposer`).
    - `secundario.js`: Renderer y estilos para bloques de rol `secundario` (Usa `StyleComposer`).
    - `text.js`: Renderer, estilos y lógica de actualización para bloques de texto.
    - `page-settings.js`: Manejador de actualizaciones para configuración de página.
    - `theme-settings.js`: Manejador de actualizaciones para configuración global del tema.

#### Entry Points
- **`gbn.js`**: Punto de entrada principal para el editor. Inicializa todos los subsistemas.
- **`gbn-front.js`**: Script ligero para el frontend (visitantes). Carga solo lo necesario para interactividad.

---

## 3. Arquitectura de Configuración de Tema (Theme Settings) - Definición Canónica

> [!IMPORTANT]
> **ESTA SECCIÓN ES LA FUENTE ÚNICA DE VERDAD.** Cualquier implementación que contradiga estos principios será considerada un BUG.
> El objetivo es erradicar el problema recurrente de sincronización y herencia de estilos.

### 1. Principio de Sincronización Universal (CSS <-> JS)
**Regla de Oro:** TODAS LAS OPCIONES DEFAULT TIENEN QUE SER CAPACES DE LEER SU VARIANTE EN CSS Y SINCRONIZARSE CON ELLA.

-   **Lectura Bidireccional:** El panel no debe "suponer" valores. Debe leer lo que realmente se está aplicando en el CSS (Computed Styles o Variables CSS).
-   **Sincronización:** Si el CSS dice `blue`, el control de color debe mostrar `blue`. Si no tiene valor, debe mostrar vacío/default.

### 2. Lógica de Fallback y Defaults
SINO TIENE UN VALOR EN EL CSS ENTONCES:
1.  **Opción A (Limpia):** No asignar ningún valor. Dejar que la cascada CSS actúe.
2.  **Opción B (Comodidad):** Tener un default genérico explícito, pero que se inyecte como variable, no como estilo inline duro.

### 3. Jerarquía de Herencia Global
LAS OPCIONES DEL TEMA MODIFICAN Y AFECTAN A LOS COMPONENTES QUE NO TIENEN UN VALOR FIJO.

**Flujo de Prioridad (Cascade) - Jerarquía de Especificidad:**

```text
      [ NIVEL 1: ALTA PRIORIDAD (Overrides) ]
      
      A. Configuración del Panel (Builder)      B. Clases Personalizadas (Código)
      ------------------------------------      ---------------------------------
      Genera: style="color: red;"               Genera: .mi-clase { color: blue; }
      (Gana casi siempre por ser Inline)        (Gana a la base por especificidad)
                    |                                         |
                    +--------------------+--------------------+
                                         |
                                         v
      [ NIVEL 2: BASE DEL COMPONENTE (Default) ]
      
                     Clase Base del Sistema
                     (.gbn-principal, .gbn-texto)
                                 |
                                 v
                     Consume Variable Global
                     (color: var(--gbn-color))
                                 |
                                 v
      [ NIVEL 3: CONFIGURACIÓN DEL TEMA (Source) ]
      
                     Theme Settings Panel
                     Define: --gbn-color: black
```

**Explicación del Flujo:**
1.  **Theme Settings**: Define la "pintura" base (Variables).
2.  **Clase Base**: Es el lienzo que usa esa pintura por defecto.
3.  **Overrides**:
    *   **Clases CSS (.divClaseTest):** Si el usuario añade una clase manual, esta "tapa" la clase base (si tiene mayor especificidad o carga después).
    *   **Estilos Inline (Panel):** Si el usuario usa los controles del Builder, se inyecta `style="..."`, que tiene la máxima prioridad (excepto `!important`).

**Regla de Sincronización:**
El Panel del Builder debe reflejar la "realidad computada". Si un elemento tiene `padding: 50px` (ya sea por clase `.divClaseTest` o por `style="..."`), el input de Padding debería mostrar `50px`.

### 4. Diagnóstico del Problema Recurrente
¿Por qué falla esto a pesar de la refactorización SOLID?
-   **Bloqueo por Inline Styles:** `style-composer.js` a veces emite valores "default" como estilos inline, rompiendo la herencia de las variables CSS.
-   **Desconexión de Variables:** `applicator.js` actualiza las variables, pero los componentes no las están consumiendo correctamente en su estado base.

**Solución Mandatoria:**
-   `applicator.js`: Solo gestiona Variables CSS globales.
-   `style-composer.js`: **SOLO** emite estilos si hay una configuración explícita del usuario en el bloque. Si no, **NO DEBE EMITIR NADA**, permitiendo que las variables globales actúen.

### 5. Ciclo de Vida del Estilo (Detallado)

**Diagrama de Flujo Circular:**

```text
            [ 1. CARGA / RECARGA ]
                     |
                     v
            +--------+--------+
            |    NAVEGADOR    |
            |   (Renderiza)   |
            +--------+--------+
                     |
                     v
            +--------+--------+
            |    JS (GBN)     | <--- Lee getComputedStyle()
            |  (Sincroniza)   |      (La "Verdad Visual")
            +--------+--------+
                     |
                     v
            +--------+--------+
            |      PANEL      |
            |  (Muestra UI)   |
            +--------+--------+
                     |
                     | (Usuario Edita: "80px")
                     v
            +--------+--------+
            |    JS (GBN)     | ---> Inyecta style="padding: 80px"
            |   (Actualiza)   |      (Gana al CSS base)
            +--------+--------+
                     |
                     v
            +--------+--------+
            |   PERSISTENCIA  |
            |  (Guarda HTML)  |
            +--------+--------+
                     |
                     +---> (Vuelta al Inicio)
```

Para entender "dónde quedan" los cambios, veamos el ciclo paso a paso:

#### A. Al Cargar la Página (Hidratación)
1.  **Browser:** Renderiza el HTML y aplica todo el CSS (Tema + Clases + Inline).
2.  **JS (GBN):** Al seleccionar un componente, pregunta al navegador: *"¿Qué estilo final tiene este elemento?"* (`getComputedStyle`).
3.  **Panel:** Se rellena con ese valor real.
    *   *Ejemplo:* Si tu clase `.divClaseTest` pone `padding: 50px`, el input de Padding mostrará `50px`.

#### B. Al Editar en el Panel (Interacción)
1.  **Usuario:** Cambia el Padding a `80px` en el panel.
2.  **JS (GBN):** Inyecta `style="padding: 80px"` directamente en el elemento HTML.
3.  **Browser:** El estilo inline (`80px`) **gana** a la clase CSS (`50px`). El elemento crece.

#### C. Al Guardar y Recargar
1.  **Persistencia:** Se guarda el HTML con el nuevo `style="padding: 80px"`.
2.  **Recarga:** Volvemos al paso A. El navegador ve el `style` inline y lo aplica. El panel lee `80px`.

#### D. Conflicto: Panel vs Código
Si después vas al código y cambias la clase a `padding: 100px !important`:
1.  **Browser:** `!important` gana al inline. El elemento tiene `100px`.
2.  **Panel:** Al seleccionar, leerá `100px` (la realidad).
3.  **Sincronización:** El panel siempre muestra la **realidad visual**, venga de donde venga.

---

## 4. Mecanismo de Prevención de Regresiones (Zombie Bugs)

> [!CAUTION]
> **LEER ANTES DE TOCAR CUALQUIER LÓGICA DE ESTADO O UI.**
> El sistema ha sufrido de bugs recurrentes (Layout Delay, Responsive Persistence, Theme Globals). Para detener esto, se imponen las siguientes reglas de arquitectura:

### 1. Principio de Reactividad Única (Single Source of Truth)
**PROHIBIDO:** Tener lógica de sincronización dispersa en eventos `change` individuales.
**MANDATORIO:** Todo cambio en el UI debe mutar el **Estado Central (`state.js`)**.
-   El UI **NUNCA** debe tocar el DOM directamente.
-   El UI despacha una acción -> El Estado se actualiza -> Los Suscriptores (Renderers) reaccionan y actualizan el DOM.

### 2. Flujo de Datos Unidireccional (One-Way Data Flow)
Para evitar el "Layout Delay":
1.  **Input Change:** Usuario mueve un slider.
2.  **Action:** `Gbn.actions.updateBlock(id, { key: value })`.
3.  **State Update:** El objeto de configuración del bloque se actualiza en memoria.
4.  **Reaction:** El `StyleComposer` recalcula **TODOS** los estilos del bloque basado en el nuevo estado y los aplica al DOM.
5.  **Persistencia:** Se marca el bloque como "sucio" para guardado.

**Anti-Patrón a Evitar:**
```javascript
// MAL: Parche rápido que causa desincronización
input.addEventListener('change', function() {
    element.style.padding = this.value; // NO! Esto ignora el responsive, el tema, etc.
});
```

### 3. Integridad de Datos Responsive
Para evitar la pérdida de datos al cambiar vistas:
-   **Lectura:** Al cambiar de breakpoint, el UI debe pedir al Estado: *"Dame la configuración efectiva para este breakpoint"*.
-   **Escritura:** Al guardar un valor, se debe especificar explícitamente el breakpoint destino: `updateBlock(id, { padding: 20 }, 'tablet')`.
-   **Prohibido:** "Suponer" que el estado actual del input es válido para el nuevo breakpoint sin recargar desde el modelo.

### 4. Validación de Theme Settings
-   Las variables CSS globales (`--gbn-*`) son la **única** forma de comunicar Theme Settings a los componentes.
-   Si un componente no responde a un cambio global, es porque **no está consumiendo la variable**.
-   **Regla:** Todo estilo default en `style-composer.js` debe ser `undefined` o `var(--gbn-...)`. NUNCA un valor fijo (`10px`, `flex`, `block`).


### 5. Protocolo de Registro de Componentes
**Regla de Integridad:** Antes de que un nuevo componente sea accesible en la UI del constructor, debe pasar una validación automatizada.
1.  **Validación API:** El sistema debe consultar `gbn_diagnostics_validate` para el nuevo componente.
2.  **Criterios de Aceptación:**
    -   Debe tener un `role` único.
    -   Debe tener un `selector` DOM válido (Atributo o Clase) para permitir la observabilidad.
    -   Debe tener un Schema JSON válido.
3.  **Fallo:** Si la validación falla, el componente debe ser rechazado o marcado como "Inestable" (no visible en producción).


