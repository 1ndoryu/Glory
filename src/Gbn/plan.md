# GBN - Glory Builder Nativo

**GBN** es un constructor visual moderno e inteligente para WordPress que permite construir sitios web desde HTML plano con capacidad de edición visual en el front-end. Mantiene la UI en inglés para usuarios finales, pero todo el código interno está en español siguiendo principios SOLID.

## Filosofía y Principios

1. **HTML Limpio**: Sin shortcodes, markup semántico y válido
2. **Independencia de GBN**: Las páginas funcionan correctamente aunque GBN esté desactivado (estilos inline persisten)
3. **Código Minimalista**: Principios SOLID, evitar duplicación de código (lecciones aprendidas de Avada)
4. **Componentes Agnósticos**: Los componentes de Glory aceptan progresivamente GBN sin romper su uso independiente
5. **Sincronización Bidireccional**: Cambios en código o interfaz se reflejan correctamente en ambos lados

## Contexto Glory, tema y PageManager
- `Glory/` actúa como micro–framework agnóstico: provee managers (assets, páginas, menús), componentes reutilizables y utilidades base. No debe incluir lógica específica del proyecto.
- El tema (carpeta `App/`) es quien orquesta la implementación concreta del sitio y registra los assets propios mediante `AssetManager`. GBN vive como paquete dentro de Glory pero sólo expone herramientas agnósticas para que cada tema active o personalice el builder.
- `PageManager` sincroniza las páginas gestionadas desde código. Define slugs, plantillas, modo de contenido (`code` vs `editor`) y mantiene metadatos (`_glory_content_hash`, `_glory_content_mode`) para detectar cambios manuales.
- GBN se apoya en `PageManager` para saber cuándo una página está en modo `editor` y así persistir configuraciones sin pisar contenido escrito a mano. El objetivo es que los cambios realizados en el front queden almacenados en metadatos propios y, opcionalmente, en el campo `post_content` sólo cuando el modo `editor` esté habilitado.

## Flujo de persistencia y sincronización planificado
1. **Captura de estado en el navegador**: `Gbn.state` mantiene un registro por bloque con `config`, `schema`, estilos y metadatos (`role`, `postType`, etc.). Cada interacción del panel actualiza el estado y refleja cambios inmediatos en el DOM.
2. **Serialización por bloque**: al guardar, se construye un payload JSON con la estructura:
   ```json
   {
     "pageId": 123,
     "blocks": [
       {
         "id": "gbn-xyz",
         "role": "principal",
         "config": {"padding": {...}},
         "styles": {"padding-top": "40px"},
         "order": 0,
         "children": [ ... ]
       }
     ]
   }
   ```
   El orden y las relaciones padre-hijo se calculan leyendo el árbol de `data-gbn-id`.
3. **Transporte AJAX**: se usarán endpoints vía `gloryAjax` con `nonce` `glory_gbn_nonce` y URL entregada en `gloryGbnCfg`:
   - `gbn_save_options` (guardar opciones/estilos por bloque)
   - `gbn_preview_block` (vista previa con overrides sin persistir)
   - `gbn_get_page_settings` / `gbn_save_page_settings` (configuraciones por página)
4. **Persistencia en WordPress**:
   - Los datos se guardarán en el metadato `gbn_config` (array por bloque) asociado al `pageId`.
   - El CSS instanciado se guarda en `gbn_styles` para poder regenerar `<style data-gbn-style="...">` sin depender del inline original.
   - Cuando la página esté en modo `editor`, `PageManager` podrá volcar un HTML renderizado a `post_content` para que el front muestre la última versión aunque GBN esté desactivado.
5. **Sincronización con PageManager**:
   - Se leerá `PageManager::getModoContenidoParaPagina($pageId)` antes de sobrescribir contenido. Si retorna `code`, se guardan sólo metadatos y se respeta el markup original.
   - Si retorna `editor`, se actualiza `post_content` con el HTML regenerado y se escribe un hash (`_glory_content_hash`) para detectar ediciones manuales. Si el hash cambia, GBN dejará de sincronizar automáticamente y mostrará un aviso.
6. **Reconstrucción al cargar**:
   - Al iniciar GBN en el front, `services/content` consultará `gloryGbnCfg.presets` (por implementar) para hidratar `data-gbn-config` desde los metadatos guardados.
   - Si no existen datos persistidos, se usan los defaults definidos por cada componente (`gbnDefaults()` en PHP o `ROLE_DEFAULTS` en JS).
7. **Rollback / Restaurar**: la opción de “Restore defaults” vacía `gbn_config`, borra `gbn_styles` y fuerza a PageManager a regenerar `post_content` desde el HTML baseline del tema.

## Endpoints y estructura de handlers (PHP)
- Registrador: `Glory/src/Gbn/Ajax/Registrar.php` centraliza los `add_action('wp_ajax_*', ...)`.
- Handlers atómicos:
  - `OrderHandler`: `gbn_save_order`.
  - `ContentHandler`: `gbn_save_options`, `gbn_preview_block`.
  - `PageSettingsHandler`: `gbn_get_page_settings`, `gbn_save_page_settings`.
  - `LibraryHandler`: `create_glory_link`, `update_glory_link`, `create_glory_header`, `update_glory_header`.
  - `DeleteHandler`: `gbn_delete_item`.
- Compatibilidad: `GbnAjaxHandler::register()` delega en `Ajax\\Registrar::register()`.

## Marcado base
Los autores pueden escribir HTML minimalista con atributos como `gloryDiv`, `gloryDivSecundario` o `gloryContentRender="post"`. En tiempo de ejecución, GBN normaliza esos atributos a la versión válida `data-gbn-*` y asigna IDs estables (`data-gbn-id`). Si el autor ya define atributos `data-gbn-*`, se respetan. Cada nodo administrado necesita:

- `data-gbnId`: identificador estable por sección, generado de forma determinista.
- `data-gbnConfig`: JSON serializado con configuración editable.
- `data-gbnSchema`: describe los controles disponibles para el panel (inputs, sliders, etc.).

Ejemplo mínimo:

```
    $opciones = "publicacionesPorPagina: 3, claseContenedor: 'gbn-content-grid', claseItem: 'gbn-content-card', forzarSinCache: true";

    ?>
    <div gloryDiv class="divPrincipal" style="padding: 40px 20px; gap: 24px;">
        <div gloryDivSecundario class="divSecundario" style="display: grid; gap: 20px;">
            <div gloryContentRender="post" opciones="<?php echo esc_attr($opciones); ?>">
            </div>
        </div>
    </div>
```

Para `gloryContentRender="post"`, el builder detecta el tipo de contenido y ejecuta la carga AJAX usando `gloryAjax`, inyectando el HTML recibido dentro del bloque. Los componentes agnósticos (por ejemplo `ContentRender`) deben aceptar atributos `data-gbn` sin interferir con su salida predeterminada. Asi los demás componente deben comportarse igual.

## Estilos renderizados
- GBN genera estilos en un `<style data-gbn-style="layoutHero">` cercano a cada nodo principal. Así se conserva legibilidad y permite rehacer estilos sin mezclar con atributos `style` manuales.
- Las declaraciones `style="..."` escritas por el autor se registran como baseline. Mientras no existan cambios desde el panel, GBN respeta esa prioridad; cuando se editen opciones desde el front (o se marque `data-gbnSync="locked"`) se refleja en el `<style>` asociado.
- El panel actualiza simultáneamente el nodo `<style>` y el valor serializado en `data-gbnConfig`. Si el HTML se edita a mano y se cambia el config, GBN puede reconstruir la hoja sin perder cambios.

## Arquitectura JS (refactor)
1. `core/utils`: helpers agnósticos (logger, DOM, parsing de estilos, detección de builder).
2. `core/state`: registra nodos, crea `data-gbn-id`, persiste config y expone getters.
3. `render/styleManager`: sincroniza estilos base en `<style data-gbn-style="*">` reutilizando los inline originales.
4. `services/content`: Facade que orquesta sub-módulos (`roles`, `config`, `dom`, `builder`, `scanner`, `hydrator`) para la gestión de contenido.
5. `ui/panel-core`: núcleo del panel lateral (montaje, títulos, estado, summary y aplicación de estilos vía `styleManager`).
6. `ui/panel-fields/`: módulos refactorizados para campos del panel, organizados por tipo (spacing, slider, select, toggle, color, text, typography, icon-group, fraction, rich-text, header). Cada módulo exporta una función `build()` y el dispatcher en `index.js` delega según el tipo de campo.
7. `ui/inspector`: toggle “Open GBN”, botones secundarios, persistencia local y activación/desactivación; abre paneles de tema/página/restauración.
8. `gbn.js`: orquesta el arranque (omite ejecución cuando el builder externo está activo).

## Experiencia de edición actual
- Usuarios con permisos ven un botón flotante `Open GBN` (UI en inglés). El estado se guarda por usuario/página en `localStorage`.
- Al activar el constructor se añade `gbn-active`, cada `gloryDiv`/`gloryDivSecundario` recibe `min-height: 40px`, outline azul permanente y brillo en hover.
- El `<main>` desplaza el contenido `100px` hacia abajo mientras GBN está activo para evitar que el panel lo cubra; el padding original se restaura al cerrar.
- Cada bloque genera un botón contextual `Config` que abre un panel lateral (placeholder) con resumen del bloque; el panel se cierra al desactivar el modo o con `Esc`.
- `gloryContentRender` se hidrata vía `gloryAjax` y emite `gbn:contentHydrated` para volver a enganchar los controles sin perder interactividad.

## Roadmap Fase 1

### Etapa 1 · Fundamentos de bloques
- [x] Definir esquemas base por rol (principal/secundario/content) y almacenarlos en una carpeta de configuraciones reutilizable.
- [x] Centralizar la definición de contenedores (`gloryDiv`, `gloryDivSecundario`, `gloryContentRender`) en un registro único consumido por el builder y por los componentes.
- [ ] Ajustar cada componente agnóstico para que exponga su configuración y esquema desde su propio archivo, evitando duplicados. (Aun no, para el final)

### Etapa 2 · Panel interactivo y UX
- [x] Construir el panel real (inputs, tabs) reutilizando `data-gbn-schema` y conectándolo a `state` + `styleManager`.
- [x] Añadir feedback visual (loading, hover) para botones `Config` y estados de bloque.
- [x] Reordenar los botones flotantes (`Open GBN`, `Config tema`, `Config página`, `Restaurar`) para que vivan bajo el panel y respeten el modo activo.
- [x] Resolver problema de estilos inline vs GBN: modificar `styleManager` para aplicar estilos directamente al atributo `style` del elemento en lugar de usar reglas CSS que compiten con estilos inline.
- [x] Sincronizar estilos inline con configuración inicial: cuando un elemento tiene estilos inline (ej: `style="padding-top: 100px"`), GBN ahora carga estos valores en los controles del panel automáticamente.
- [x] Implementar reset inteligente a valores por defecto: cuando se borra un valor en los controles, regresa al valor inline original (del HTML) o al valor por defecto del schema, no se queda con el último valor editado.
- [x] Agregar opciones de altura (auto, mínimo, altura completa) a divs primarios y secundarios.
- [x] Implementar selector flex/grid con opciones específicas condicionales para cada layout (flex-direction, flex-wrap, justify-content, align-items para flex; grid-columns, grid-gap para grid).

### Etapa 3 · Persistencia y sincronización
- [x] Implementar el dispatcher AJAX (`gbn_save_config`) que reciba la estructura de bloques, valide permisos y escriba los metadatos `gbn_config`/`gbn_styles` por página.
- [x] Cliente JS de persistencia y botón Guardar conectado al dispatcher.
- [x] Restauración básica: endpoint `gbn_restore_page` que limpia `gbn_config`/`gbn_styles` y regenera `post_content` cuando el modo es `editor`.

### Etapa 4 · Manipulación del DOM y Estructura (PRIORIDAD ACTUAL)
- [x] **Rediseño Drag & Drop**: Implementar sistema moderno con indicadores visuales (líneas de inserción), mejor UX y soporte para reordenamiento fluido.
- [x] **Inserción de Bloques**: Crear modal/panel "Biblioteca" para insertar nuevos contenedores y componentes. *(Implementado con ui/library.js)*.
- [x] **Eliminación de Bloques**: Agregar opción para eliminar nodos desde la UI del constructor. *(Implementado con botón directo)*.
- [x] **Sincronización Bidireccional**: Asegurar que los cambios en el DOM (movimientos, inserciones) se reflejen en `Gbn.state` y viceversa en tiempo real. *(Cubierto por persistence.js que lee el DOM y eventos de sincronización)*.

### Etapa 5 · Configuraciones globales
- [x] Implementar el panel de configuración del tema (colores, fuentes, `init.css`) con almacenamiento centralizado.
- [x] Implementar el panel de configuración de la página (fondo, padding del `main`, overrides locales).


### Etapa 6 · Adaptación de Componentes (Pospuesto)
- [ ] Ajustar componentes agnósticos (`TermRender`, `GloryImage`, etc.) para exponer `gbnDefaults`.
- [ ] Documentar cómo registrar nuevos elementos/controles.

### Etapa 7 · Nuevos Requerimientos (Noviembre)

#### Configuración de Tema y Página
- [x] **Valores por defecto**:
    - `gloryDiv`: Padding 20px, display flex, clase `primario`.
    - `gloryDivSecundario`: Padding 20px, clase `secundario`.
    - Las clases deben agregarse automáticamente aunque no estén en el código.
- [x] **Configuración de Página**:
    - Implementar `data-gbn-root` (solo visible con GBN activo).
    - Panel de configuración de página: Background color del main, padding (default 20px), ancho máximo (default 100%), clase específica por página (ej: `gbnPage-{id}`).
- [x] **Panel de Configuración del Tema**:
    - Apartados: Texto, Color, Páginas, Componentes.
    - **Texto**: Configurar fuentes, tamaños, colores por defecto para p, h1, h2, etc.
    - **Colores**: Selector de color personalizado con paleta de colores por defecto (editable por usuario).
    - **Componentes**: Configuración global de defaults para cada rol (incluyendo Ancho Máximo).

#### Componentes y UI Avanzada
- [x] **Componente `gloryTexto`**:
    - Capacidad de cambiar etiqueta (p, h1, h2, etc.).
    - Panel de configuración específico (color morado).
    - Opciones: Fuente, color, alineación (heredando defaults del tema).
- [x] **Mejoras de UI**:
    - **Layout**: Reemplazar selects por iconos para opciones de flexbox/grid (dirección, wrap, etc.).
    - **Tamaños de Bloques Secundarios**: Selector visual de fracciones (1/1, 1/2, 1/3, etc.) al agregar o configurar.
    - **Colores de Panel**: Diferenciar paneles por tipo (Azul: Primario, Naranja: Secundario, Morado: Componentes).
- [x] **Gestión Avanzada de Estilos Globales**:
    - **Placeholders Dinámicos**: Los campos vacíos muestran el valor heredado del tema en tiempo real.
    - **Distinción Visual**: Indicadores visuales (flecha ↓, texto gris/itálica) para diferenciar valores heredados de overrides manuales.
    - **Sincronización en Tiempo Real**: Cambios en defaults del tema se propagan instantáneamente a todos los bloques sin recargar.



---

## 🚨 PRIORIDAD CRÍTICA: Sincronización de Estilos Computados con Panel

### Problema Identificado

Cuando se abre el panel de configuración de un elemento, **no se leen los estilos actuales del elemento**. Esto causa:

1. **Estilos inline ignorados**: Un `<div gloryDiv style="padding: 50px; background-color: #f0f0f0;">` muestra "20" (del tema) en el panel en lugar de "50".

2. **Estilos de clase ignorados**: Si `.miClase { padding: 50px }` está aplicada al elemento, el panel no lo refleja.

3. **Afecta TODOS los campos**: padding, background, gap, y cualquier otro estilo configurable.

4. **El placeholder debería ser condicional**: Solo mostrar placeholder cuando el valor computado coincide con el default del tema.

### Comportamiento Actual (Incorrecto)

```
Elemento: <div gloryDiv style="padding: 50px">
Panel muestra:
  - input.value = "" (vacío)
  - input.placeholder = "20" (del tema)
  
El usuario ve "20" y piensa que el padding es 20px, cuando en realidad es 50px.
```

### Comportamiento Esperado

```
Elemento: <div gloryDiv style="padding: 50px">
Panel muestra:
  - input.value = "50" (leído del computed style)
  - input.placeholder = "20" (del tema, solo visible si se borra el valor)
  
El usuario ve "50" y sabe exactamente qué tiene el elemento.
```

### Jerarquía de Lectura de Valores (Prioridad)

1. **`block.config`**: Valores guardados en GBN (post_meta) - máxima prioridad
2. **`getComputedStyle(element)`**: Estilos actuales del DOM (inline + clases + CSS)
3. **`themeSettings.components[role]`**: Valores del Panel de Tema
4. **`cssSync.readDefaults()`**: Variables CSS de `:root` en gbn.css

### Archivos Afectados

| Archivo | Rol | Cambio Necesario |
|---------|-----|------------------|
| `ui/panel-fields/spacing.js` | Campo de padding/margin | Leer `getComputedStyle` del elemento |
| `ui/panel-fields/color.js` | Campo de color | Leer `backgroundColor` computado |
| `ui/panel-fields/slider.js` | Campo numérico (gap, etc.) | Leer propiedad computada |
| `ui/panel-fields/utils.js` | Utilidades compartidas | Nueva función `getComputedValue(element, cssProperty)` |
| `ui/panel-core.js` | Apertura del panel | Pasar referencia al elemento DOM al builder de campos |

### Solución Propuesta

1. **Nueva utilidad en `utils.js`**:
   ```javascript
   function getComputedValue(element, cssProperty) {
       if (!element) return undefined;
       var computed = window.getComputedStyle(element);
       return computed[cssProperty];
   }
   ```

2. **Modificar cada campo** para:
   - Recibir `element` (el nodo DOM real)
   - Leer `getComputedStyle(element)` para obtener valores actuales
   - Comparar con defaults del tema para decidir si mostrar como value o placeholder

3. **Lógica de decisión**:
   ```javascript
   var computedValue = getComputedValue(element, 'paddingTop');
   var themeDefault = getThemeDefault(role, 'padding.superior');
   var savedValue = getDeepValue(block.config, 'padding.superior');
   
   if (savedValue) {
       input.value = parseSpacingValue(savedValue).valor;
   } else if (computedValue !== themeDefault) {
       // Tiene valor inline o de clase diferente al tema
       input.value = parseSpacingValue(computedValue).valor;
   } else {
       // Usa el default del tema
       input.value = '';
       input.placeholder = parseSpacingValue(themeDefault).valor;
   }
   ```

### Mapeo CSS ↔ Config

| Campo Config | Propiedad CSS | Notas |
|--------------|---------------|-------|
| `padding.superior` | `paddingTop` | En px |
| `padding.derecha` | `paddingRight` | En px |
| `padding.inferior` | `paddingBottom` | En px |
| `padding.izquierda` | `paddingLeft` | En px |
| `background` | `backgroundColor` | rgb() o hex |
| `gap` | `gap` o `rowGap` | En px |
| `layout` | `display` | flex, grid, block |
| `flexDirection` | `flexDirection` | row, column |
| `flexWrap` | `flexWrap` | wrap, nowrap |
| `flexJustify` | `justifyContent` | flex-start, center, etc. |
| `flexAlign` | `alignItems` | stretch, center, etc. |

### Estado Actual

- [x] Función `getComputedValue` en utils.js
- [x] `spacing.js` lee estilos computados
- [x] `color.js` lee backgroundColor computado
- [x] `slider.js` lee propiedades numéricas
- [x] `select.js` / `icon-group.js` leen propiedades de layout
- [x] Panel pasa referencia al elemento DOM (ya disponible en block.element)
- [x] Tests manuales con inline, clases y defaults

### Correcciones Intentadas (Diciembre 2025)

| Problema | Estado | Notas |
|----------|--------|-------|
| Altura no cargaba 'Automática' por defecto | ✅ OK | Corregido (fallback a defaultValue en select.js) |
| Padding no cambiaba visualmente al borrar | ✅ OK | Corregido (agregado shorthands a styleManager.js) |
| Alineación mostraba vacía | ✅ OK | Corregido (cambiado a icon-group y defaults) |
| Color leía incorrectamente de clases CSS | ✅ OK | Corregido (mapeo correcto de background-color en config.js) |
| Ancho máximo mostraba NaN | ✅ OK | Corregido y mejorado con input de texto flexible |
| Layout no mostraba opciones condicionales | ✅ OK | Funciona correctamente (defaults en panel-render.js) |
| Color no mostraba placeholder del valor original | ✅ OK | Corregido (sincronización correcta en config.js) |

### Revisión de Usuario (Diciembre 2025)

**Prueba realizada con:**
```html
<div gloryDiv style="padding: 50px; background-color: #f0f0f0;">
```

**Problemas encontrados:**

1. **Background color incorrecto**: 
   - El panel muestra #1d8ff1 en lugar de #f0f0f0
   - El placeholder también muestra #1d8ff1 al borrar
   - El color no se muestra visualmente en el elemento

2. **Selects aparecen vacíos**:
   - Altura y Alineación del contenido aparecen sin selección
   - No se debe forzar un valor, pero si hay un default real definido, debe cargarlo
   - Solo mostrar seleccionado si realmente hay un valor (config, inline, o default del tema)

3. **Alineación del contenido**:
   - Actualmente es un `select`, debería ser `icon-group` para consistencia visual

4. **Padding no cambia visualmente al borrar**:
   - Al borrar el padding en el panel, visualmente se queda en 50px
   - Solo cambia el placeholder a 20px pero el elemento mantiene el estilo inline

5. **Layout**: ✅ Funciona correctamente


---

## Estructura de Archivos y Arquitectura Actual

### Backend (PHP)

#### 📁 Raíz (`Glory/src/Gbn/`)
- **`GbnManager.php`**: Gestor principal que registra assets CSS/JS con versionado dinámico, maneja enqueuing condicional (solo para usuarios con permisos), y proporciona configuración al frontend vía `gloryGbnCfg`
- **`GbnAjaxHandler.php`**: Facade para compatibilidad, delega a `Ajax\Registrar`
- **`Logger.php`**: Utilidad para logging de depuración en `gbn.log`

#### 📁 `Ajax/` - Gestión de Endpoints
- **`Registrar.php`**: Registra todos los endpoints AJAX (`wp_ajax_*`) y conecta con handlers específicos
- **`ContentHandler.php`**: Facade que delega operaciones de contenido a handlers especializados
- **`DeleteHandler.php`**: Maneja eliminación de bloques (`gbn_delete_item`)
- **`OrderHandler.php`**: Maneja reordenamiento de bloques (`gbn_save_order`)
- **`PageSettingsHandler.php`**: GET/POST de configuraciones por página (`gbn_get_page_settings`, `gbn_save_page_settings`)
- **`ThemeSettingsHandler.php`**: GET/POST de configuraciones globales del tema (`gbn_get_theme_settings`, `gbn_save_theme_settings`)
- **`LibraryHandler.php`**: Creación/actualización de elementos desde la biblioteca (`create_glory_link`, `update_glory_link`, `create_glory_header`, etc.)

##### 📁 `Ajax/Handlers/` - Lógica de Negocio
- **`ConfigHandler.php`**: Procesa y persiste configuraciones de bloques, aplica estilos al DOM, maneja sincronización con `PageManager`
- **`OptionsHandler.php`**: Maneja actualización de opciones de bloques (`gbn_save_options`)
- **`PreviewHandler.php`**: Genera vistas previas sin persistir cambios (`gbn_preview_block`)

##### 📁 `Ajax/Services/` - Utilidades Compartidas
- **`DomProcessor.php`**: Manipulación del DOM, generación de IDs deterministas, parsing de HTML, aplicación de estilos y clases, limpieza de nodos inválidos

#### 📁 `Config/` - Esquemas y Configuración
- **`ContainerRegistry.php`**: Define schemas y defaults para todos los tipos de contenedores (`principal`, `secundario`, `content`). Especifica controles del panel (spacing, layout, width, etc.)
- **`RoleConfig.php`**: Mapeo de roles a clases CSS y configuraciones básicas

#### 📁 `assets/css/` - Estilos Modulares
- **`gbn.css`**: Imports y configuración base
- **`variables.css`**: Variables CSS globales (colores, espaciado)
- **`layout.css`**: Estilos del panel, dock, y layout general de GBN
- **`forms.css`**: Inputs, selectores, controles del panel
- **`components.css`**: Componentes específicos (color picker, typography field, etc.)
- **`interactive.css`**: Estados hover, drag & drop, feedback visual
- **`modals.css`**: Modales y overlays (biblioteca, confirmaciones)
- **`theme-styles.css`**: Aplica variables CSS del tema a contenido dentro de `[data-gbn-root]`

### Frontend (JavaScript)

#### 📁 `assets/js/`
- **`gbn.js`**: Punto de entrada principal, inicializa GBN solo cuando no hay builders externos activos
- **`gbn-front.js`**: Versión simplificada para frontend público (usuarios no logueados)

#### 📁 `core/` - Fundamentos
- **`utils.js`**: Helpers agnósticos (logger, parsing de estilos, detección de builder, manipulación DOM, generación de IDs deterministas)
- **`state.js`**: Gestión de estado global (`Gbn.state`), registro de nodos, almacenamiento de config/schema, getters/setters

#### 📁 `render/` - Aplicación de Estilos
- **`styleManager.js`**: Sincroniza estilos entre `data-gbn-config` y atributos inline del DOM, maneja prioridad inline vs panel
- **`theme-applicator.js`**: Aplica configuraciones del tema como variables CSS globales en `[data-gbn-root]`

#### 📁 `services/` - Servicios de Negocio
- **`persistence.js`**: Maneja serialización y guardado AJAX del estado, dispara eventos de sincronización
- **`css-sync.js`**: Sincronización entre CSS defaults y panel de configuración. Lee estilos computados de elementos temporales para poblar defaults en el panel.
- **`content.js`**: Facade que orquesta normalización de atributos y carga dinámica de contenido

##### 📁 `services/content/` - Módulos de Contenido
- **`roles.js`**: Define roles (`principal`, `secundario`, `content`, `text`), detecta elementos con atributos `glory*`, asigna roles y defaults
- **`config.js`**: Parsing de atributos `opciones`, construcción de configuración inicial, merge de configs
- **`dom.js`**: Normalización de atributos (`glorydiv` → `data-gbn-*`), inyección de clases y estilos por defecto (padding, flex) si faltan
- **`builder.js`**: Construcción de objetos de bloque (`Gbn.state.register`), integración de estilos inline con configuración
- **`scanner.js`**: Escaneo del DOM inicial, detección de elementos GBN, reconciliación con configuración guardada
- **`hydrator.js`**: Inyecta HTML recibido vía AJAX para bloques de contenido dinámico, emite eventos de hidratación

#### 📁 `ui/` - Interfaz de Usuario
- **`dock.js`**: Barra flotante minimalista (botones Guardar, Config Tema, Config Página, Restaurar), detecta cambios pendientes via eventos
- **`inspector.js`**: Maneja interacción con bloques (hover, click, selección), muestra botones contextuales (Config, Add, Delete)
- **`drag-drop.js`**: Sistema drag & drop nativo HTML5 con indicadores visuales (líneas de inserción)
- **`library.js`**: Modal de biblioteca para insertar nuevos bloques/componentes
- **`panel-core.js`**: Núcleo del panel lateral (apertura, cierre, navegación, estructura base)
- **`panel-fields.js`**: Wrapper de compatibilidad que delega a módulos en `panel-fields/`
- **`panel-fields/`**: Módulos refactorizados para construcción de campos del panel
  - **`utils.js`**: Utilidades compartidas (`getDeepValue`, `getThemeDefault`, `getConfigValue`, `parseSpacingValue`)
  - **`sync.js`**: Indicadores de sincronización CSS y actualización de placeholders
  - **`spacing.js`**: Campo de spacing (padding/margin) con 4 direcciones
  - **`slider.js`**: Campo slider/range con badge de valor
  - **`select.js`**: Campo select/dropdown
  - **`toggle.js`**: Campo toggle on/off con íconos
  - **`text.js`**: Campo de texto simple
  - **`color.js`**: Campo de color con picker y paleta global
  - **`typography.js`**: Campo compuesto (font, size, lineHeight, spacing, transform)
  - **`icon-group.js`**: Grupo de botones con íconos para selección única
  - **`fraction.js`**: Selector de fracciones de ancho (1/2, 1/3, etc.)
  - **`rich-text.js`**: Editor de texto enriquecido básico
  - **`header.js`**: Separador/header de sección
  - **`index.js`**: Dispatcher principal que delega según tipo de campo
- **`panel-render.js`**: Renderiza contenido del panel basado en schema, aplica cambios en tiempo real
- **`ui/theme/`**: Módulo refactorizado para configuraciones de tema
  - **`applicator.js`**: Lógica de aplicación de estilos (CSS variables)
  - **`render.js`**: Renderizado de formularios de configuración
  - **`index.js`**: Punto de entrada y compatibilidad

---

## Contexto Técnico Detallado

### Sistema de Eventos
La comunicación entre módulos se realiza a través de eventos globales en `window`:
- `gbn:layoutChanged`: Se dispara cuando cambia la estructura (mover, insertar, borrar).
- `gbn:contentHydrated`: Se dispara cuando el contenido se carga o recarga.
- `gbn:configChanged`: Se dispara cuando se edita la configuración de un bloque en el panel.
- `gbn:themeDefaultsChanged`: Se dispara cuando cambian los valores por defecto en el Panel de Tema. Permite actualización en tiempo real de todos los bloques afectados.
- **Uso**: El módulo `dock.js` escucha estos eventos para habilitar el botón de "Guardar".

### UI Components
- **Dock (`dock.js`)**: Barra flotante minimalista. Usa iconos SVG. Centraliza las acciones de guardar y configuración global.
- **Panel (`panel-core.js`)**: Panel lateral derecho. Se abre al seleccionar un bloque.
  - Usa `panel-fields.js` para renderizar controles basados en el esquema del bloque.
  - Estilos minimalistas definidos en `gbn.css` (ancho 280px, inputs compactos).
- **Inspector (`inspector.js`)**: Maneja la interacción directa con el DOM (hover, click en bloques).

### Persistencia
- Los datos se guardan como un JSON en la base de datos (o meta post).
- `persistence.js` maneja las llamadas AJAX.
- Al guardar, se envía el estado actual (`Gbn.state.all()`).

### Estilos y CSS
- **Glassmorphism**: Usado en el Dock (`backdrop-filter: blur`).
- **Variables CSS**: Se usan para colores y fuentes (`--bg`, `--text`, `--primary-bg`).
- **Selectores**: `#gbn-panel` para el panel, `.gbn-dock` para la barra inferior.
- **Inputs**: Personalizados para ser compactos (`height: 28px`). El selector de color es un círculo (`.gbn-color-picker`) + texto hexadecimal.

### Drag & Drop
- Implementado nativamente con HTML5 Drag and Drop API.
- `drag-drop.js` gestiona `dragstart`, `dragover`, `drop`.
- Usa `Gbn.state.move()` para actualizar el modelo de datos.

### Inserción de Bloques
- Se realiza a través de `Gbn.library` (no visible en este resumen pero parte del sistema).
- `inspector.js` coordina la inserción y llama a `ensureBlockSetup`.

---

## Historial de Desarrollo Reciente (basado en contructor.php)

### ✅ Problemas Resueltos

#### Persistencia de Eliminación de Bloques
- **Problema**: Al borrar un bloque y guardar, no persistía al recargar
- **Causa**: Desajuste en generación de IDs entre cliente (incluía `<main>`) y servidor (no lo incluía)
- **Solución**: Se modificó `utils.js` y `ContentHandler.php` para ignorar `<main>`, se actualizó prefijo a `gbn-v3-`

#### Persistencia de Orden de Bloques
- **Problema**: El orden visual no se guardaba correctamente
- **Causa**: Cliente ordenaba por creación/índice de array, no por posición DOM real
- **Solución**: `persistence.js` ahora usa `element.compareDocumentPosition` antes de asignar `order`

#### Función Restaurar
- **Objetivo**: Devolver contenido al estado original del código PHP/HTML
- **Implementación**: Limpia `gbn_config` y `gbn_styles`, resetea modo de página a `code`

#### Configuración de Tema y Página en Tiempo Real
- **Problema**: Cambios no se guardaban ni reflejaban en tiempo real, aparecía "Connection Error"
- **Solución**: 
  - Centralización en `panel-render.js` y `dock.js`
  - Botón Guardar ahora guarda concurrentemente PageConfig, PageSettings, ThemeSettings
  - Aplicación de estilos vía variables CSS (`--gbn-*`) con helper `toCssValue`

#### Herencia de Padding y Valores Default
- **Problema**: `gloryDiv` y `gloryDivSecundario` cargaban padding de 20px hardcodeado, sobreescribiendo theme defaults
- **Contexto**: Múltiples iteraciones en conversaciones recientes (41a2ca99, ff5abd2a, 83c31b39, 39ffecf4)
- **Estado**: Solucionado con sistema de fallback correcto a Theme Panel defaults
- **Mejora**: Restauración efectiva de valores vía panel

#### CSS Defaults y Sincronización
- **Objetivo**: Defaults CSS automáticamente en panel, modificaciones del usuario rompen sync, función "Restore" re-sincroniza
- **Estado**: Sistema implementado con sync parcial, trabajo continuo en optimización

#### Experiencia de Usuarios Deslogeados
- **Problema**: Páginas no renderizaban correctamente para usuarios no logueados
- **Áreas afectadas**: `data-gbn-root` sin estilos inline, `gloryContentRender` no renderizaba
- **Estado**: Resuelto - estilos de página y componentes se aplican correctamente para ambos tipos de usuarios

### 🔄 Refactorizaciones Recientes

#### Panel Fields Modularizado
- **Problema**: `panel-fields.js` creció a ~970 líneas, dificultando mantenimiento
- **Solución**: Refactorización en módulos independientes dentro de `ui/panel-fields/`
- **Estructura**:
  - `utils.js` - Utilidades compartidas (getDeepValue, parseSpacingValue, etc.)
  - `sync.js` - Sincronización con CSS y actualización de placeholders
  - Un archivo por tipo de campo (spacing, slider, select, toggle, text, color, typography, icon-group, fraction, rich-text, header)
  - `index.js` - Dispatcher que delega al módulo correcto según `field.tipo`
- **Compatibilidad**: `panel-fields.js` original se mantiene como wrapper que re-exporta la API
- **Beneficios**: Código más mantenible, ~80 líneas por módulo en promedio, facilita testing y extensión

### 🔄 Características Implementadas Recientemente

#### Mejoras de UI/UX
- **Typography Field**: Control compuesto con Familia, Grid (Size/LineHeight/Spacing) y Transform (iconos)
- **Color Picker**: Toggle para mostrar/ocultar paleta global (icono mundo), lista de colores por defecto editables
- **Layout Options**: Selectores con iconos para flex/grid (reemplazando selects simples)
- **Width Options**: Opciones fraccionarias para divs secundarios (1/2, 1/3, 3/4, etc.)
- **Panel Diferenciado por Color**: Azul (primario), Naranja (secundario), Morado (componentes)

#### Componente gloryTexto
- **Funcionalidad**: Capacidad de cambiar etiqueta (p, h1, h2, etc.) dinámicamente
- **Configuraciones**: Fuente, color, alineación, tamaño con unidades flexibles (px, rem)
- **Herencia**: Valores default heredan de configuración de tema

#### gloryContentRender
- **Template Discovery**: `TemplateManager` para detección dinámica de plantillas
- **Post Types**: Soporte para tipos personalizados (libro, post, etc.)
- **Layout Options**: Display mode, flex-direction, flex-wrap, justify-content con iconos
- **Opciones Display**: Mostrar/ocultar imagen, título, con persistencia correcta
- **Plantillas**: Sistema de plantillas dinámicas (plantillaPosts, plantillaLibro, etc.)

#### Configuraciones Globales
- **Theme Panel**: Apartados para Texto, Color, Páginas
  - Configuración de fuentes por defecto para p, h1, h2, etc.
  - Paleta de colores globales (5 defaults + capacidad de añadir/eliminar)
  - Variables CSS aplicadas globalmente
- **Page Panel**: Background color, padding, clase específica por página (`gbnPage-{id}`)

#### Drag & Drop y Manipulación DOM
- **Sistema D&D**: HTML5 nativo con indicadores visuales (líneas de inserción)
- **Biblioteca**: Modal para insertar nuevos bloques y componentes
- **Eliminación**: Botón directo para eliminar nodos
- **Sincronización Bidireccional**: Cambios DOM ↔ `Gbn.state` en tiempo real

### 🚧 Áreas en Desarrollo

#### gloryContentRender Avanzado
- **Objetivo**: Replicar funcionalidad compleja de integración Avada
- **Pendiente**: Opciones avanzadas de alineación, layout, efectos
- **Referencia**: `Glory/Integrations/` contiene integración Avada original

#### Adaptación de Componentes
- **Pendiente**: Ajustar componentes agnósticos (`TermRender`, `GloryImage`) para exponer `gbnDefaults()`
- **Prioridad**: Pospuesta hasta tener base sólida de constructor

#### CSS Sync Avanzado
- **Estado**: Implementación parcial en `css-sync.js`
- **Objetivo**: Sincronización perfecta entre CSS y panel con detección de cambios manuales

---

## Roadmap y Estado de Desarrollo

### ✅ Fase 1 - Fundamentos (COMPLETADO)
- [x] Esquemas base por rol (principal/secundario/content)
- [x] Centralización de definición de contenedores en `ContainerRegistry.php`
- [x] Panel interactivo con inputs, tabs basados en `data-gbn-schema`
- [x] Feedback visual (loading, hover) en botones y estados de bloques
- [x] Sincronización de estilos inline con configuración inicial
- [x] Reset inteligente a valores por defecto
- [x] Opciones de altura para divs (auto, mínimo, altura completa)
- [x] Selector flex/grid con opciones condicionales
- [x] Sistema de persistencia AJAX (`gbn_save_config`)
- [x] Typography field compuesto
- [x] Color picker con paleta global editable
- [x] Template discovery para `gloryContentRender`
- [/] gloryContentRender avanzado (opciones completas de Avada)
- [ ] Layout grid con opciones completas (columns, gap, auto-flow)
- [ ] Editor de texto enriquecido para componente texto

- [ ] Editor de texto enriquecido para componente texto

---

## ✅ PRIORIDADES CRÍTICAS RESUELTAS (Diciembre 2025)

### ✅ Problema 1: Theme Settings > Componentes > Principal - RESUELTO

**Solución aplicada:**
- [x] Extendido `ui/theme/applicator.js` → `applyThemeSettings()` ahora aplica todas las propiedades de componentes
- [x] Nueva función `applyLayoutProperties()` para manejar: layout, direction, wrap, justify, align, gridColumns, gridGap, maxAncho, height
- [x] Variables CSS creadas: `--gbn-{role}-layout`, `--gbn-{role}-direction`, `--gbn-{role}-wrap`, `--gbn-{role}-justify`, `--gbn-{role}-align`, `--gbn-{role}-max-width`, etc.

---

### ✅ Problema 2: Page Settings - Valores no se mantienen al reabrir - RESUELTO

**Solución aplicada:**
- [x] Modificado `ui/panel-core.js` → `openPage()` y `openTheme()` ahora verifican primero si existe estado local
- [x] Si hay `Gbn.config.pageSettings` o `Gbn.config.themeSettings` (cambios no guardados), se usa ese estado
- [x] Solo va al servidor si no hay estado local
- [x] Al cargar del servidor, se guarda en `Gbn.config` para uso futuro

---

### ✅ Problema 3: Herencia de valores Theme → Bloques individuales - RESUELTO

**Solución aplicada:**
- [x] Creadas nuevas funciones en `ui/panel-render.js`:
  - `getThemeSettingsValue(role, path)`: Obtiene valor de Theme Settings para un rol
  - `getConfigWithThemeFallback(config, role, path)`: Obtiene valor con fallback a Theme Settings
- [x] Modificados `styleResolvers.principal` y `styleResolvers.secundario` para usar fallback a Theme Settings
- [x] Ahora cuando un bloque no tiene configuración propia, hereda del Theme Panel
- [x] Actualizado `ui/panel-fields/utils.js` → `getThemeDefault()` ahora prioriza:
  1. `Gbn.config.themeSettings` (estado local, puede tener cambios no guardados)
  2. `gloryGbnCfg.themeSettings` (valores del servidor)
  3. `cssSync.readDefaults()` (fallback CSS)

**Jerarquía implementada:**
```
CSS defaults (gbn.css) 
    ↓ hereda si no hay override
Theme Panel (components.principal.*) - Gbn.config.themeSettings.components[role]
    ↓ hereda si no hay override  
Bloque individual (block.config.*)
```

**Funciones expuestas para debugging:**
- `Gbn.ui.panelRender.getThemeSettingsValue(role, path)`
- `Gbn.ui.panelRender.getConfigWithThemeFallback(config, role, path)`
- `Gbn.ui.panelRender.applyThemeStylesToAllBlocks()`

---

### Mejoras pendientes (futuras)

#### 🟡 Refactorización: Automatización de Opciones de Componentes (SOLID)
- [ ] **Problema**: La lógica actual en `renderThemeSettingsForm` define manualmente qué campos mostrar para cada componente, lo cual es repetitivo y propenso a errores.
- [ ] **Propuesta**: Refactorizar para iterar automáticamente sobre `ContainerRegistry.getRoleDefaults(role).schema`.
- [ ] **Objetivo**: Que cualquier nuevo campo añadido al schema del componente aparezca automáticamente en la configuración global del tema sin tocar `render.js`.

### 📋 Fase 3 - Expansión (PLANIFICADO)
- [ ] Adaptación de componentes agnósticos (`GloryImage`, etc.)
- [ ] Sistema de plantillas robusto y extensible
- [ ] Historial de cambios (undo/redo)
- [ ] Responsive breakpoints (mobile, tablet, desktop)
- [ ] Export/import de configuraciones
- [ ] Biblioteca de presets y bloques predefinidos

---

## Marcado Base y Atributos

### Atributos HTML Personalizados

GBN utiliza atributos personalizados para identificar y configurar elementos:

**Atributos de Autor (simplificados)**:
- `gloryDiv`: Define un contenedor principal
- `gloryDivSecundario`: Define un contenedor secundario (hijo de principal)
- `gloryContentRender="{postType}"`: Renderiza contenido dinámico del tipo especificado
- `gloryTexto`: Elemento de texto editable
- `opciones="{config}"`: Configuraciones adicionales parseadas

**Atributos GBN Normalizados** (generados automáticamente):
- `data-gbn-id`: ID estable generado determinísticamente basado en posición DOM
- `data-gbn-role`: Rol del elemento (`principal`, `secundario`, `content`, `texto`)
- `data-gbn-config`: JSON con configuración editable del elemento
- `data-gbn-schema`: JSON con definición de controles del panel
- `data-gbn-root`: Contenedor raíz de página (solo visible con GBN activo)

### Ejemplo de Markup Mínimo

```php
<?php $opciones = "publicacionesPorPagina: 3, plantilla: 'plantillaPosts'"; ?>

<div gloryDiv style="padding: 40px 20px; gap: 24px;">
    <div gloryDivSecundario style="display: grid; gap: 20px;">
        <p gloryTexto>Título de sección</p>
        <div gloryContentRender="libro" opciones="<?php echo esc_attr($opciones); ?>"></div>
    </div>
</div>
```

### Normalización en Runtime

1. `services/content/scanner.js` detecta atributos `glory*`
2. `services/content/roles.js` asigna roles apropiados
3. `services/content/dom.js` normaliza a atributos `data-gbn-*`
4. `core/utils.js` genera IDs deterministas basados en ruta DOM
5. `services/content/config.js` parsea `opciones` y construye config inicial
6. `core/state.js` registra el bloque en estado global

---

## Sistema de Persistencia

### Flujo de Guardado

1. **Captura**: Usuario modifica elemento en panel → `panel-fields.js` actualiza `Gbn.state`
2. **Evento**: Se dispara `gbn:configChanged` → `dock.js` habilita botón Guardar
3. **Serialización**: `persistence.js` ordena bloques por posición DOM real
4. **Payload**: Se construye JSON con estructura:
   ```json
   {
     "pageId": 123,
     "blocks": [
       {
         "id": "gbn-v3-xyz",
         "role": "principal",
         "config": {"padding": {"top": "40px", ...}},
         "order": 0
       }
     ]
   }
   ```
5. **AJAX**: Se envía a `gbn_save_options` con nonce de seguridad
6. **Backend**: `OptionsHandler.php` valida y delega a `ConfigHandler.php`
7. **DOM Processing**: `DomProcessor.php` aplica cambios al HTML
8. **Persistencia**: Se guarda en `post_meta` como `gbn_config` y se actualiza `post_content`

### Metadatos de WordPress

- `gbn_config`: Array serializado con configuraciones por bloque
- `gbn_styles`: CSS instanciado para regenerar `<style>` tags
- `gbn_page_settings`: Configuraciones específicas de página
- `gbn_theme_settings`: Configuraciones globales del tema (option, no post_meta)
- `_glory_content_mode`: `code` o `editor` (controla sincronización)
- `_glory_content_hash`: Hash para detectar ediciones manuales

### Sincronización con PageManager

- Si modo = `code`: Solo persiste metadatos, respeta HTML original
- Si modo = `editor`: Actualiza `post_content` con HTML regenerado
- Hash cambiado: GBN muestra aviso, deja de sincronizar automáticamente

---

## Notas Técnicas Importantes

### Generación de IDs Deterministas
- **Formato**: `gbn-v3-{hash}` (v3 indica versión de algoritmo)
- **Algoritmo**: Hash de ruta DOM excluyendo `<main>` (ej: `div:0>div:1>p:0`)
- **Estabilidad**: El mismo markup siempre genera el mismo ID
- **Sincronización**: Garantiza que cliente y servidor usen IDs idénticos

### Prioridad de Estilos
1. **Estilos inline del autor** (baseline en HTML)
2. **Configuración GBN** (si existe en `data-gbn-config`)
3. **Theme defaults** (si no hay inline ni config)
4. **Fallback hard-coded** (último recurso, evitado cuando es posible)

### Variables CSS Globales
Aplicadas en `[data-gbn-root]`:
- `--gbn-text-font`: Fuente de párrafos
- `--gbn-text-size`: Tamaño de texto base
- `--gbn-text-color`: Color de texto base
- `--gbn-custom-{n}`: Colores personalizados del tema

### Eventos del Sistema
- `gbn:layoutChanged`: Estructura DOM cambió (mover/insertar/eliminar)
- `gbn:contentHydrated`: Contenido dinámico cargado vía AJAX
- `gbn:configChanged`: Configuración de bloque modificada
- `gbn:styleApplied`: Estilos aplicados al DOM
- `gbn:saved`: Guardado completado exitosamente

---

## Próximos Pasos Inmediatos

1. **gloryContentRender Completo**: Implementar todas las opciones de la integración Avada
2. **Layout Grid**: Opciones completas de CSS Grid (columns, rows, areas, auto-flow)
3. **Editor de Texto Rico**: Implementar editor minimalista para gloryTexto (bold, italic, links)
4. **Optimización de Performance**: Debouncing en actualizaciones, lazy loading de assets
5. **Testing Cross-browser**: Validar en Chrome, Firefox, Safari, Edge
6. **Documentación de API**: Documentar cómo extender GBN con componentes personalizados

