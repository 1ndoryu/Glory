# GBN - Plan de Trabajo

> [!TIP] LEER SIEMPRE CUANDO SE RECIBA ESTE MD LOS SIGUIENTES MD: 
> *   **Reglas y Arquitectura:** Ver `reglas.md`.
> *   **Contexto y Estado:** Ver `contexto.md`.
> *   **Documentación:** Ver `documentación-gbn.md`.


## 2. Fases Completadas (Hitos Alcanzados)

### Fase 1: Infraestructura y Core
**Objetivo:** Cimientos arquitectónicos.
- [x] **Definición de Contratos:** `ComponentInterface` y `AbstractComponent` definidos.
- [x] **Carga Dinámica:** `ComponentLoader` implementado.
- [x] **SchemaBuilder:** API fluida (`SchemaBuilder.php`, `Option.php`) implementada.
- [x] **Traits:** `HasFlexbox`, `HasSpacing`, `HasTypography`, `HasGrid` creados.

### Fase 2: Migración de Componentes Base
**Objetivo:** Migración a nueva arquitectura.
- [x] **Componente Principal (Root):** Migrado a `PrincipalComponent.php`.
- [x] **Componente Secundario (Layout):** Migrado a `SecundarioComponent.php`.
- [x] **Componente Texto:** Migrado a `TextComponent.php`.

### Fase 3: Limpieza y Finalización
**Objetivo:** Eliminar deuda técnica.
- [x] **Desactivación de Legacy:** Referencias antiguas eliminadas en `ContainerRegistry.php`.
- [x] **Limpieza de JS:** Definiciones hardcoded eliminadas de `roles.js`.
- [x] **Tests de Regresión:** Verificación manual completada.

### Fase 4: Refactorización JS (SOLID & DRY)
**Objetivo:** Arquitectura Frontend modular.
- [x] **StyleComposer:** Centralización de estilos en `style-composer.js`.
- [x] **Automatización de Tema:** `applicator.js` refactorizado.
- [x] **Field Registry:** Implementado `registry.js` (OCP).

### Roadmap de Estabilidad (Defensa contra Zombie Bugs)
**Objetivo:** Blindar el sistema.
- [x] **Fase A: Arquitectura de Estado:** Store Centralizado (Redux-lite) y Pipeline de Renderizado Puro.
- [x] **Fase B: Programación Defensiva:** Validación de Esquemas (Zod-lite) y Error Boundaries.
- [x] **Fase C: Herramientas de Diagnóstico:** Remote Logger y Debug Overlay (Modo Dios).
- [x] **Fase E: Documentación Viva:** Actualización de `reglas.md`.

### Fase 6: Expansión de Biblioteca y UX
**Objetivo:** Escalar biblioteca y mejorar UX.
- [x] **6.1 Integración Diagnóstico:** Validación automática de nuevos componentes.
- [x] **6.2 Refactorización Biblioteca:** Nueva lógica de inserción inteligente y componentes atómicos (Imagen, Botón).
- [x] **6.2.2 Imágenes de Fondo:** Trait `HasBackground` integrado en contenedores.
- [x] **6.2.1 Hotfix UX/Persistencia:** Persistencia de nuevos bloques y zonas de inserción.
- [x] **6.3 Mejoras Componentes Base:** Custom CSS, Estandarización de Spacing y Tabs en paneles.
- [x] **6.4 Mejoras Visuales:** Acceso rápido de ancho, Docking de panel, Configuración de página refactorizada y Reposicionamiento de controles.

---

## 3. Historial de Cambios y Resoluciones (Detallado)

### Diciembre 2025 (Reciente)

#### ✅ Refinamiento de Dimensiones (Element Sizing)
-   **Problema:** `min-height` y `min-width` aplicados directamente a los `div` causaban problemas de persistencia y dificultad en la interacción con el ratón.
-   **Solución:** Se eliminó la aplicación directa de estas propiedades en los elementos y se movió el control de tamaño mínimo al inspector/editor (`interactive.css`), asegurando que la estructura guardada sea limpia.

#### ✅ Fix Unidades REM
-   **Problema:** Las unidades `rem` no funcionaban correctamente en el CSS generado.
-   **Solución:** Corregida la gestión de unidades en el generador de estilos.

#### ✅ Bug 10: Persistencia de Layout Options (Flexbox)
-   **Solución:** Actualizada lógica de hidratación para incluir `direction`, `wrap`, `justify`, `align`.

#### ✅ Bug 11: Salida HTML Limpia
-   **Solución:** `data-gbn-schema` solo se inyecta si `config.isEditor` es true.

#### ✅ Bug 8: Persistencia de Valores Responsive en Tema
-   **Solución:** Implementado "burbujeo" (Mobile -> Tablet -> Desktop) en `themeSettings`.

#### ✅ Refactorización SOLID de Paneles
-   **Cambio:** Descomposición de `panel-fields.js` en módulos (`spacing.js`, `typography.js`).

#### ✅ Bug 24, 25, 26: Corrección de Parsing de Esquema
-   **Solución:** `style-composer.js` y `applicator.js` ahora iteran correctamente sobre arrays de campos.

#### ✅ Bug 30: Crash en Inspector (JSON Serialization)
-   **Solución:** Uso de `Object.assign` en Store para preservar referencias DOM.

#### ✅ Bug 29: Persistencia Responsive en Theme Settings
-   **Solución:** Escritura en ruta anidada correcta (`themeSettings.components.principal._responsive`).

#### ✅ Bug 28 & 27: Herencia de Flexbox y Layout Delay
-   **Solución:** Inyección explícita de `var(--gbn-role-prop)` cuando no hay configuración local.

#### ✅ Bug 33: Flash de Contenido Flex (Fix V8)
-   **Solución:** Eliminado fallback forzado a `flex` en `style-composer.js`.

#### ✅ Bug 32: Grid Defaults (Fix V13)
-   **Solución:** Excepción en `applicator.js` para `gridColumns` (sin unidades).

#### ✅ Bug 34: Margen sin efecto real
-   **Solución:** Refactorizado `extractSpacingStyles` y soporte en `style-composer.js`.

#### ✅ Bug 35: Iconos faltantes en Theme Settings
-   **Solución:** Propagación de propiedad `icon` desde PHP y limpieza de `roles.js`.

#### ✅ Mejora UX: Unificación de Paneles
-   **Solución:** Implementación de pestañas en `render.js` (Theme Settings).

#### ✅ Implementación: Página de Prueba (Constructor)
-   **Cambio:** Lógica condicional en `header.php` y diseño Toyota Supra en `contructor.php`.

#### ✅ Mejoras Visuales (Fase 6.4)
-   **Acceso Rápido de Ancho:** Dropdown responsive en controles secundarios.
-   **Docking:** Reducción visual del ancho de página al abrir panel.
-   **Configuración Página:** Tabs y Custom CSS.
-   **GlobalControls:** Refactorización completa de controles de interacción (Singleton) para eliminar bugs de "controles congelados".

#### ✅ Refactorización Hero Section (Constructor)
-   **Cambio:** Implementación de `TextComponent` y `ButtonComponent` en la sección Hero de `contructor.php` para reemplazar HTML hardcodeado.

#### ✅ Mejora DX: Inferencia de Contenido
-   **Problema:** Redundancia al declarar `texto` y `tag` en `opciones` cuando ya existen en el HTML.
-   **Solución:** Actualizado `builder.js` para inferir automáticamente `texto` (de `innerHTML`) y `tag` (de `tagName`) si no se especifican explícitamente.

#### ✅ Fix: Tipografía en Tiempo Real (TextComponent)
-   **Problema:** Los cambios de tipografía (fuente, tamaño, line-height, etc.) no se aplicaban en tiempo real. La fuente mostraba "Default" y el tamaño aparecía vacío. Padding/Margin tampoco funcionaban.
-   **Solución Multi-parte:**
    1. **`text.js` (Renderer):** Refactorizado `handleUpdate` para manejar paths anidados (`typography.*`, `padding.*`, `margin.*`). Ahora aplica cambios directamente al DOM.
    2. **`typography.js` (Panel Field):** Implementada sincronización bidireccional CSS→Panel. Lee valores computados del DOM (`getComputedStyle`) cuando no hay configuración explícita.
    3. **`TextComponent.php`:** Agregadas opciones de `backgroundColor`, `borderWidth`, `borderStyle`, `borderColor` y `borderRadius`.
    4. **`utils.js`:** Expandido `CONFIG_TO_CSS_MAP` con typography, margin y border properties.

#### ✅ Mejoras Panel: Border, Fuentes y Fondo (UX)
-   **Problema:** Valores de borde vacíos en panel, fuentes detectadas como "Default", imagen de fondo no editable desde panel y opciones de fondo visibles innecesariamente.
-   **Solución Integral:**
    -   **Border Sync:** `text.js` ahora lee valores computados (`borderWidth`, `borderRadius`) del DOM si no hay config.
    -   **Fuentes Inteligentes:** `typography.js` soporta fuentes genéricas (Monospace, Serif) y detecta fuentes personalizadas dinámicamente.
    -   **Imagen de Fondo:** Implementado soporte para **Galería WordPress** (`wp.media`) en `image.js`, lectura de `backgroundImage` desde CSS y parsing de URLs.
    -   **Condiciones y UX:** `HasBackground.php` ahora usa condiciones (`->condition()`) para ocultar opciones de tamaño/posición si no hay imagen. `Option.php` actualizado para soportar lógica condicional.
    -   **Iconos:** Mejorada UX de `backgroundSize` con iconos visuales (Cover, Contain, etc.).

#### ✅ Smart Dimension Control (Ancho)
-   **Problema:** El control de ancho estaba limitado a fracciones predefinidas (`1/2`, `1/3`), impidiendo el uso de valores arbitrarios (`100%`, `500px`) o clases CSS.
-   **Solución:** Implementado `Smart Dimension Control` en `fraction.js`.
    -   Agregado input manual para valores libres.
    -   Sincronización bidireccional entre input y botones de presets.
    -   Actualizado `shared.js` para procesar cualquier valor CSS válido.
    -   Estilizado en `forms.css`.

### Fase 6.5: Posicionamiento y Dimensiones Avanzadas
**Objetivo:** Completar el control de layout y posicionamiento.
- [x] **Trait HasPositioning:** Centralizar lógica de `position` y `z-index`.
- [x] **Esquemas Componentes:** Integrar `HasPositioning` en `Principal`, `Secundario` y `Text`.
- [x] **Mejora Altura:** Convertir `height` en input flexible (Smart Control) en todos los contenedores.
- [x] **Max-Width Secundario:** Agregar opción `maxAncho` a `SecundarioComponent`.
- [x] **Renderizado:** Actualizar `style-composer.js` para procesar nuevas propiedades.

---

### Trabajo Pendiente

#### ⏳ Refactorización UI Dimensions Panel
- **Problema:** El diseño de `dimensions.js` no encaja con el resto del panel (iconos, espaciado).
- **Tarea:** Actualizar `dimensions.js` para usar iconos SVG, grid layout y inputs visuales consistentes con `spacing.js`.
- **Estado:** Pendiente (Solicitado por usuario).

### ✅ [COMPLETADA] Fase 10: Soporte para Estados Hover/Focus

**Problema Resuelto:**
El sistema ahora **diferencia correctamente** entre estilos base y estilos de pseudo-clases como `:hover`, `:focus`, `:active`.

**Funcionalidad:**
- ✅ Editar colores y estilos de hover desde el panel
- ✅ Los estilos hover/focus se aplican en tiempo real con clases de simulación
- ✅ Los estados se persisten correctamente en `config._states`
- ✅ CSS generado incluye reglas para pseudo-clases

**Tareas de LÓGICA (COMPLETADAS Dic 2025):**
- [x] **Arquitectura de Estados:** Implementado `config._states` para almacenar configuración por estado
    - Estructura: `{ _states: { hover: {...}, focus: {...}, active: {...} } }`
- [x] **Lectura de Pseudo-clases:** Creado servicio `state-styles.js` que parsea hojas de estilo
    - `parseStylesheets()` - Escanea todas las CSS del documento
    - `getStateStyles(element, state)` - Obtiene estilos de un estado específico
    - `getAllStatesFromCSS(element)` - Lee todos los estados de un elemento
    - Cache con TTL de 5 segundos para evitar re-parseo
- [x] **Generación CSS:** Actualizado `style-generator.js` con método `generateBlockStates()`
    - Genera reglas CSS separadas por pseudo-clase: `[data-gbn-id="..."]:hover { ... }`
    - Integrado en el método principal `generateCss()`
- [x] **Persistencia:** Los estados se guardan automáticamente en `config._states`
    - El sistema de persistencia existente los incluye sin modificaciones

**Tareas de DISEÑO (COMPLETADAS Dic 2025):**
- [x] **Toggle de Estado en Panel:** Selector visual para cambiar entre "Normal", "Hover", "Focus"
- [x] **Indicador Visual:** Mostrar qué estado se está editando actualmente
- [x] **Preview de Hover:** Simular hover en el editor con clases `.gbn-simulated-*`

**Archivos Modificados:**
- `Glory/src/Gbn/assets/js/services/state-styles.js` (Nuevo)
- `Glory/src/Gbn/assets/js/services/style-generator.js` (Modificado - soporte camelCase→kebab-case)
- `Glory/src/Gbn/assets/js/render/styleManager.js` (Modificado - conversión CSS en applyStateCss)
- `Glory/src/Gbn/assets/js/ui/panel-render.js` (Modificado - flujo de estados, evento configChanged)
- `Glory/src/Gbn/assets/js/ui/panel-fields/utils.js` (Modificado)
- `Glory/src/Gbn/assets/js/services/persistence.js` (Fix línea duplicada)
- `Glory/src/Gbn/GbnManager.php` (Modificado)

**Bugs Resueltos (Dic 2025):**
1. ✅ **Estilos en tiempo real:** Arreglado flujo en `panel-render.js` - construcción directa de estilos y clase de simulación
2. ✅ **Conversión CSS:** `styleManager.js` convierte `backgroundColor` → `background-color`
3. ✅ **Mapeo de Path:** Si no hay mapeo en `CONFIG_TO_CSS_MAP`, usa el último segmento del path
4. ✅ **Botón Guardar:** Evento `gbn:configChanged` ahora se dispara correctamente
5. ✅ **CORS Warning:** Silenciado en `state-styles.js`

---


### ✅ Fase 11: Refactorización SOLID de Componentes (COMPLETADO Dic 2025)

**Objetivo:** Revisar y refactorizar los componentes existentes aplicando principios SOLID para reducir código repetitivo y facilitar la creación de nuevos componentes.

**Análisis Realizado:**
- Identificado código duplicado entre renderers (`button.js`, `text.js`, `image.js`)
- Funciones como `normalizeSize()`, manejo de `typography.*`, `padding.*`, `margin.*`, y border estaban repetidas
- El proceso de crear un nuevo componente requería mucho boilerplate

**Solución Implementada:**

**Tareas de LÓGICA (COMPLETADAS):**
- [x] **Auditoría de Código:** Identificados patrones repetitivos en 3+ renderers
- [x] **Trait System en JS:** Creado `renderer-traits.js` con funciones compartidas:
    - `normalizeSize()` - Normalización de valores CSS
    - `getTypographyStyles()` / `applyTypography()` - Manejo de tipografía
    - `getSpacingStyles()` / `applySpacing()` - Manejo de padding/margin
    - `getBorderStyles()` / `applyBorder()` - Manejo de bordes
    - `getBackgroundStyles()` / `applyBackground()` - Manejo de fondos
    - `handleCommonUpdate()` - Handler universal para paths comunes
    - `getCommonStyles()` - Genera estilos desde config completa
    - `createRenderer()` - Factory para crear renderers simples
- [x] **Refactorización de Renderers:** 
    - `text.js` - Reducido ~60% de código usando traits
    - `button.js` - Reducido ~50% de código usando traits
    - `image.js` - Reducido ~40% de código usando traits
- [x] **Centralizar Border:** Creado trait `HasBorder.php` reutilizable
- [x] **Registro en GbnManager:** Nuevo script registrado con dependencias correctas

**Tareas de DISEÑO (COMPLETADAS):**
- [x] **Documentar patrón:** Creada `guia-crear-componente.md` paso a paso

**Archivos Creados/Modificados:**
- `Glory/src/Gbn/assets/js/ui/renderers/renderer-traits.js` (Nuevo - 350+ líneas)
- `Glory/src/Gbn/assets/js/ui/renderers/text.js` (Refactorizado - 236→110 líneas)
- `Glory/src/Gbn/assets/js/ui/renderers/button.js` (Refactorizado - 344→175 líneas)
- `Glory/src/Gbn/assets/js/ui/renderers/image.js` (Refactorizado - 62→72 líneas con mejor estructura)
- `Glory/src/Gbn/Traits/HasBorder.php` (Nuevo)
- `Glory/src/Gbn/GbnManager.php` (Actualizado - nuevas dependencias)
- `Glory/src/Gbn/guia-crear-componente.md` (Nuevo - documentación)

**Beneficios:**
1. **DRY:** Código duplicado eliminado, una sola fuente de verdad
2. **Mantenibilidad:** Cambios en traits afectan todos los componentes
3. **Onboarding:** Guía clara para crear nuevos componentes
4. **Consistencia:** Todos los renderers usan la misma lógica base

---

### ✅ Fase 12: Mejoras Visuales del Panel (COMPLETADO Dic 2025)

**Objetivo:** Mejorar la UX del panel con opciones más visuales e intuitivas.

**Problemas Identificados:**
1. "Abrir en" (target) debería usar iconos (pestaña actual vs nueva pestaña)
2. "Ancho" es ambiguo - debería ser "Ancho del Botón" o similar
3. Opciones de borde (Radio, Ancho, Estilo, Color) siempre visibles aunque no haya borde activo
4. Opciones de borde podrían usar iconos en lugar de dropdowns/text

**Solución Implementada:**
- **Target con Iconos:** Implementado `Option::iconGroup` para el campo `target` en `ButtonComponent`.
- **Labels Claros:** Renombrado "Ancho" a "Ancho del Botón".
- **Border Colapsable y Visual:**
    - Actualizado `HasBorder` trait para usar un grupo colapsable con toggle (`hasBorder`).
    - Implementado `Option::iconGroup` para `borderStyle` con iconos SVG.
    - Agregada lógica condicional (`condition`) para ocultar opciones cuando el borde está desactivado.
- **Refactorización:** `ButtonComponent` y `TextComponent` ahora usan el trait `HasBorder` actualizado.
- **Lógica JS:** Actualizado `renderer-traits.js` para manejar el toggle `hasBorder` y limpiar estilos cuando se desactiva.

**Archivos Modificados:**
- `Glory/src/Gbn/Schema/Option.php` (Agregado método `toggle`)
- `Glory/src/Gbn/Traits/HasBorder.php` (Actualizado con nueva UI)
- `Glory/src/Gbn/components/Button/ButtonComponent.php` (Refactorizado)
- `Glory/src/Gbn/components/Text/TextComponent.php` (Refactorizado)
- `Glory/src/Gbn/assets/js/ui/renderers/renderer-traits.js` (Lógica `hasBorder`)

**Tareas de DISEÑO (COMPLETADAS):**
- [x] **Target con Iconos:** Cambiar select "Abrir en" a `iconGroup` con iconos de ventana
- [x] **Labels Específicos:** Renombrar campos ambiguos (width → "Ancho del Botón", etc.)
- [x] **Border Colapsable:** Crear grupo colapsable tipo "Tipografía" para opciones de borde
- [x] **Organización por Grupos:** Agrupar opciones relacionadas visualmente

**Tareas de LÓGICA (COMPLETADAS):**
- [x] **Campo `border` Compuesto:** Implementado vía Trait `HasBorder` con toggle y condiciones.
- [x] **Condición de Visibilidad:** Usado `Option::condition()` para ocultar opciones dependientes.

**Tareas Pendientes (Solicitud Usuario):**
- [ ] **Iconos en Estado:** El selector de estado (Normal, Hover, Focus) debe usar iconos.
- [ ] **Borde en Contenedores:** Agregar opciones de borde a `PrincipalComponent` y `SecundarioComponent`.

---

### ⏳ Fase 9: Transform con Iconos para Botones

**Objetivo:** Agregar opciones de transform preestablecidas con iconos visuales al componente Button.

**Contexto:**
Los botones como `.btnRacing` usan `transform: skewX(-10deg)` para crear efectos visuales dinámicos. Se necesita exponer estas transformaciones de manera visual en el panel GBN.

**Tareas:**
- [ ] **Crear grupo de iconos visuales** para transforms comunes:
    - `none` - Sin transformación
    - `skewX(-10deg)` - Inclinación izquierda
    - `skewX(10deg)` - Inclinación derecha
    - `scale(1.05)` - Agrandar
    - `rotate(5deg)` - Rotación
- [ ] **Implementar presets** en `ButtonComponent.php` usando `Option::iconGroup()`.
- [ ] **Sincronización CSS:** Detectar transforms existentes aplicados por clases CSS.
- [ ] **Preview en panel:** Mostrar el efecto visual del transform en el icono del preset.

**Notas de Implementación:**
- Debe ser un `iconGroup` con iconos SVG que representen visualmente cada transformación.
- Si el botón ya tiene un transform por clase CSS (como `.btnRacing`), el panel debe detectarlo.
- Un input de texto adicional permite valores personalizados (ya implementado como `transform` text input).

---

### ✅ Fase 9.1: Refactorización del Componente Botón (COMPLETADO Dic 2025)

**Problema Identificado:**
1. El componente usaba `opciones="variant: 'primary', url: '#...'` que iba en contra del diseño nativo.
2. La URL debía leerse del atributo `href` nativo de HTML.
3. El campo `typography` no tenía soporte para `font-weight`.

**Solución Implementada:**
- **Diseño Nativo:** `ButtonComponent.php` reescrito desde cero:
    - El atributo `opciones=` ya **NO ES NECESARIO**.
    - `texto` se infiere desde `innerHTML`.
    - `url` se infiere desde el atributo `href`.
    - `target` se infiere desde el atributo `target`.
- **Font-Weight en Typography:** `typography.js` ahora incluye un grupo de iconos para seleccionar peso de fuente (400, 500, 600, 700/Bold).
- **Nuevas Opciones Button:**
    - Display (inline-block, block, inline-flex)
    - Text align
    - Background color
    - Color
    - Border completo (width, style, color, radius)
    - Cursor
    - Transition
    - Transform (texto libre)
    - Custom CSS
- **Renderer Actualizado:** `button.js` reescrito para manejar todas las nuevas propiedades con sincronización bidireccional.
- **Mapeo CSS Ampliado:** `utils.js` `CONFIG_TO_CSS_MAP` incluye `typography.weight`, `display`, `textAlign`, `cursor`, `transition`, `transform`.

**Archivos Modificados:**
- `Glory/src/Gbn/components/Button/ButtonComponent.php`
- `Glory/src/Gbn/assets/js/ui/panel-fields/typography.js`
- `Glory/src/Gbn/assets/js/ui/renderers/button.js`
- `Glory/src/Gbn/assets/js/services/content/builder.js`
- `Glory/src/Gbn/assets/js/ui/panel-fields/utils.js`
- `App/Templates/pages/contructor.php`

**Criterios de Aceptación:**
- [x] Los botones funcionan sin el atributo `opciones=`.
- [x] URL se lee desde el atributo `href` del elemento.
- [x] Texto se lee desde el `innerHTML` del elemento.
- [x] Campo font-weight disponible en el panel de tipografía.
- [x] Sincronización bidireccional CSS↔Panel para todas las propiedades.

**Bugs Pendientes (Reportados Dic 2025):**
- [x] **Bug [REGRESIÓN]: Botón Guardar Inactivo:** El botón de guardar no se activa al realizar cambios en el panel. (RESUELTO)
- [x] **Bug: Visibilidad Opciones de Borde:** Las opciones de borde no se muestran correctamente al activar el toggle. (RESUELTO)
- [x] **Bug UX: Salto de Pestaña:** Al refrescar el panel (ej. al activar borde), el panel volvía a la primera pestaña. Se implementó persistencia de pestaña activa. (RESUELTO)
- [x] **Bug UX: Persistencia de Scroll:** Al refrescar el panel, el scroll volvía arriba. Se implementó persistencia de `scrollTop`. (RESUELTO)
- [x] **Mejora UI: Selector de Estados:** Reemplazados botones de texto por iconos SVG (Normal, Hover, Focus).
- [x] **Limpieza UI:** Eliminada visualización de ID y Rol en el encabezado del panel.

---

### ✅ [BUG-SYNC] Falla en Lectura de Estilos Computados (RESUELTO Dic 2025)
- **Problema:** El panel no recogía correctamente los valores de estilos definidos en clases CSS (`width`, `height`, `position`, `overflow`, `z-index`) para mostrarlos como estado inicial.
- **Causa Raíz:** El mapeo `CONFIG_TO_CSS_MAP` en `utils.js` no incluía las propiedades `ancho`→`width`, `position`, `zIndex`, `overflow`. Esto causaba que `getComputedValueForPath()` devolviera `undefined`.
- **Solución Implementada:**
    1. **Ampliación de `CONFIG_TO_CSS_MAP`:** Agregadas las propiedades faltantes (`ancho`, `width`, `position`, `zIndex`, `overflow`, `overflowX`, `overflowY`, `minHeight`, `minWidth`).
    2. **Mejora de `getValueSource`:** Agregada detección de valores por defecto del navegador (`static`, `auto`, `visible`, `0px`) para evitar mostrarlos como valores "computados".
    3. **Refactorización de `getEffectiveValue`:** Implementada lógica de exclusión de browser defaults antes de comparar con theme defaults.
    4. **[FIX] Ancho en porcentaje:** `fraction.js` ahora calcula el % basándose en el padre cuando `getComputedStyle` devuelve px. Detecta fracciones comunes (30%, 50%, etc.) y muestra el porcentaje en lugar de píxeles.
    5. **[FIX] Altura definida en CSS:** `text.js` ahora trata `'auto'` (el default del tema) como "sin valor explícito", permitiendo leer el valor computado del DOM. Esto soluciona que `height: 400px` en CSS no se mostraba porque `getConfigValue` devolvía el default `'auto'` del tema.
- **Archivos Modificados:** 
    - `Glory/src/Gbn/assets/js/ui/panel-fields/utils.js`
    - `Glory/src/Gbn/assets/js/ui/panel-fields/fraction.js`
    - `Glory/src/Gbn/assets/js/ui/panel-fields/text.js`

### ✅ Fase 7: Soporte de Transparencia en Campos de Color

**Problema Identificado:**
Los inputs de color actuales no soportan transparencia (alpha channel). El `<input type="color">` nativo del navegador solo soporta colores sólidos HEX de 6 dígitos (#RRGGBB), no RGBA ni HEX de 8 dígitos (#RRGGBBAA).

**Solución Implementada:**
- **Refactorización `color.js`:** Implementado soporte para RGBA y slider de opacidad.
- **Nuevo Helper `color-utils.js`:** Funciones de conversión HEX<->RGBA y parsing de colores.
- **UI Update:** Añadido preview visual (checkerboard) y slider de opacidad en `forms.css`.
- **Sincronización:** El campo de color ahora lee y escribe valores RGBA correctamente.
- **Compatibilidad:** Los campos sin `permiteTransparencia` siguen funcionando solo con HEX.

**Archivos Modificados:**
- `src/Gbn/assets/js/ui/panel-fields/color.js`
- `src/Gbn/assets/js/ui/panel-fields/color-utils.js` (Nuevo)
- `src/Gbn/assets/css/forms.css`

**Criterios de Aceptación:**
- [x] El slider de opacidad aparece solo cuando `permiteTransparencia: true`.
- [x] Los colores se muestran con preview de transparencia visual.
- [x] Los cambios de opacidad se aplican en tiempo real al elemento.
- [x] Los valores RGBA persisten correctamente al guardar.
- [x] La sincronización bidireccional CSS↔Panel funciona con RGBA.
- [x] Compatibilidad hacia atrás: colores HEX existentes siguen funcionando.

### ✅ Fase 8: Mejora del Editor de Texto (TextComponent)

**Problema Identificado:**
1. El editor rich-text no mostraba el código HTML (`<br/>`, `<span class="...">`, etc.), solo la versión renderizada.
2. No había control de `text-shadow`, impidiendo trabajar con clases CSS como `.textGlow { text-shadow: ... }`.

**Solución Implementada:**
- **Editor Dual (Visual/Code):** El campo `rich-text` ahora tiene dos vistas:
    - **Visual:** Editor WYSIWYG con bold/italic (comportamiento original mejorado).
    - **Code:** Textarea monoespacio para ver/editar HTML raw directamente.
- **Toggle Minimalista:** Botones "Visual" y "</>" en la toolbar para cambiar de vista.
- **Opción `textShadow`:** Nueva opción en el schema del TextComponent para controlar text-shadow.
- **Sincronización Bidireccional:** El mapeo `CONFIG_TO_CSS_MAP` incluye ahora `textShadow` para leer valores de clases CSS.

**Archivos Modificados:**
- `src/Gbn/assets/js/ui/panel-fields/rich-text.js`
- `src/Gbn/assets/css/components.css`
- `src/Gbn/components/Text/TextComponent.php`
- `src/Gbn/assets/js/ui/renderers/text.js`
- `src/Gbn/assets/js/ui/panel-fields/utils.js`

**Criterios de Aceptación:**
- [x] Toggle Visual/Code visible en la toolbar del editor.
- [x] En vista Code se puede ver y editar `<br/>`, `<span>`, etc.
- [x] Los cambios en cualquier vista se sincronizan correctamente.
- [x] Bold/Italic deshabilitados en vista Code.
- [x] Nueva opción `textShadow` disponible en el panel Estilo del TextComponent.
- [x] `text-shadow` se aplica en tiempo real al editar.

---

## 4. Bugs Críticos Resueltos (Histórico)
> [!NOTE]
> Referencia de errores graves que han sido solucionados definitivamente.

- **Bug 27: Retraso en Layout (Flexbox)** -> Solucionado con `:where()` y variables CSS.
- **Bug 28: Theme Settings Flex Global** -> Solucionado con arquitectura de Nivel 2.
- **Bug 29: Persistencia Responsive (Padding)** -> Solucionado en `panel-render.js`.
- **Bug 30: Flex Click Bug** -> Solucionado (V6.1).
- **Bug 31: Alineación de Contenido** -> Solucionado (V6.1).
- **Bug 32 & 27: Grid/Flex Conflict** -> Solucionado (V13).
- **Bug 33: Flash de Contenido Flex** -> Solucionado (V8).

### Bugs Pendientes y Tareas Nuevas (Dic 2025) 

#### ✅ Bug Crítico: Docking Persistente (Theme Settings) - RESUELTO (V4 - Async Guard & Memory Leak Fix)
- **Problema:** Al cerrar el panel de configuración de tema, el ancho de la página no regresaba a su normalidad (se quedaba contraído).
- **Causa Raíz:**
    1. **Race Condition:** Si el usuario cerraba el panel antes de que terminara la carga asíncrona (`getThemeSettings`), el callback se ejecutaba después del cierre, reactivando lógica indebida.
    2. **Memory Leak:** El listener `gbn:configChanged` nunca se removía, acumulando handlers zombies.
    3. **Fragilidad:** Si `cleanupCurrentMode` fallaba, el cierre se abortaba antes de limpiar la clase del body.
- **Solución V4 - Defensa en Profundidad:**
    1. **Async Guard:** `renderThemePanel` y `renderPagePanel` ahora verifican `if (panelMode !== 'theme') return;` antes de procesar respuestas asíncronas.
    2. **Memory Leak Fix:** Implementada variable `activeConfigChangeHandler` y limpieza explícita en `resetThemeSettingsState`.
    3. **Robust Close:** `cleanupCurrentMode` envuelto en `try-catch` para garantizar que `document.body.classList.remove('gbn-panel-open')` SIEMPRE se ejecute.
- **Archivos Modificados:**
    - `Glory/src/Gbn/assets/js/ui/panel-core.js` (Async Guards + Try-Catch Close)
    - `Glory/src/Gbn/assets/js/ui/theme/render.js` (Listener Cleanup)

#### ✅ Bug: Colores de Paleta Global en Negro - RESUELTO
- **Problema:** En Theme Settings > Colores > Paleta Global, todos los colores aparecían en negro.
- **Causa Raíz:** La refactorización del campo `color` (soporte transparencia) no consideraba que los `mockBlock` de Theme Settings no tienen un `element` DOM asociado. El código intentaba leer `computedStyles` de un elemento inexistente, fallando y usando el fallback `#000000`.
- **Solución Implementada:**
    1. Agregada lectura directa de `block.config` usando `getDeepValue()` como prioridad para bloques sin elemento DOM.
    2. La cadena de prioridad ahora es: `configValue > effective.value > computedColor > field.defecto > '#000000'`.
- **Archivos Modificados:**
    - `Glory/src/Gbn/assets/js/ui/panel-fields/color.js`

#### ✅ Tarea: Documentación de Colores Globales 
- **Objetivo:** Documentar cómo agregar colores globales desde CSS directamente.
- **Estado:** Completado. Se agregó la sección 10 en `documentación-gbn.md` explicando el flujo unidireccional y el uso de variables CSS.

#### ✅ Tarea: Opción Faltante en Defaults de Página
- **Objetivo:** Agregar opción "Ancho Máximo" en la sección Defaults de Página. Revisar si esto requiere refactorizacion debido a todos los cambios.
- **Solución:** 
    - Agregado campo `maxAncho` en `render.js`.
    - Actualizado `applicator.js` para manejar `maxAncho` y `padding` en defaults de página.
    - Actualizado `theme-styles.css` para consumir las variables `--gbn-page-max-width` y padding.

#### ✅ Bug: Placeholder de Imagen Roto y Gigante
- **Problema:** El placeholder del componente imagen no funcionaba, usaba URL externa y ocupaba toda la pantalla.
- **Solución:** 
    - Actualizado `ImageComponent.php` para usar el SVG local `landscape-placeholder.svg`.
    - Agregado `maxWidth` (default: 100%) y `maxHeight` al esquema y defaults de `ImageComponent`.
    - Actualizado template de imagen para incluir `style="max-width: 100%; height: auto;"` por defecto.
    - Actualizado `image.js` (panel field) para mostrar el preview del valor por defecto.
    - Actualizado `image.js` (renderer) para manejar `maxWidth` y `maxHeight`.

#### ✅ Bug: Visibilidad de Atributos Internos (Data Leak)
- **Problema:** Usuarios no logueados podían ver atributos internos como `glorydiv`, `data-gbn-schema`, `data-gbn-config` en el HTML.
- **Solución:** 
    - **Persistencia:** `DomProcessor` ahora elimina explícitamente `data-gbn-schema` y `data-gbn-config` antes de guardar en la DB.
    - **Frontend:** Implementado filtro `the_content` en `GbnManager` que elimina regex de atributos internos (`glory*`, `data-gbn*`) para usuarios sin permisos de edición.

#### ✅ Bug: Estilos de Borde en Imagen
- **Problema:** Al aplicar `border-radius` al componente de imagen, el recorte no se aplicaba visualmente porque el estilo estaba en el contenedor wrapper pero la imagen interna rectangular se desbordaba.
- **Solución:**
    - Actualizado `image.js` (renderer) para aplicar automáticamente `overflow: hidden` al wrapper cuando existe `border-radius`.
    - Esto asegura que el `border-radius` del wrapper recorte correctamente la imagen interna.

#### ✅ Mejora: Componente Imagen (Borde y UI)
- **Cambio:** Integrado trait `HasBorder` en `ImageComponent` para opciones completas de borde.
- **Cambio:** Implementado nuevo campo UI `dimensions` (`dimensions.js`) con **iconos SVG** y layout **Grid 2x2** (estilo spacing) para una experiencia visual superior.
- **Cambio:** `objectFit` actualizado a `Option::iconGroup` con iconos SVG representativos (Cover, Contain, Fill, etc.).
- **Fix:** `object-fit` ahora funciona correctamente al forzar `height: 100%` en la etiqueta `<img>` y usar variables CSS.
- **Fix:** Corregido error fatal por falta de `use HasBorder` en `ImageComponent.php`.
- **UX Borde:** `borderRadius` ahora es visible independientemente del toggle de borde.
- **Ajuste:** Tamaño por defecto de imagen reducido a 200x200px (wrapper e imagen).
- **Fix:** Evitada sobreescritura de objeto `Gbn.ui.panelFields` en `index.js` para preservar `registry`.
- **Fix:** Encolado script `dimensions.js` en `GbnManager.php` para asegurar su carga y registro.

#### ✅ Tarea: Verificación de Limpieza HTML
- **Objetivo:** Verificar y eliminar atributos innecesarios en el HTML final (`draggable`, `data-gbn-ready`) para asegurar una salida limpia, sin romper la hidratación del editor.
- **Estado:** Completado. Se eliminan `draggable` y `data-gbn-ready` en `DomProcessor`.

#### ✅ Bug: Dirty HTML & Atributos Malformados
- **Problema:** Atributos como `data-gbn-config` aparecían malformados en el DOM (`='{"texto":...`) ensuciando el HTML.
- **Solución:** Eliminada la escritura redundante de `data-gbn-config` y `data-gbn-schema` al DOM en `dom.js` y `state.js`. El estado se gestiona en memoria.

#### ✅ Bug: Persistencia de Hover (Especificidad)
- **Problema:** Los estilos de hover creados en el panel no se aplicaban en el frontend porque las clases de la plantilla (ej: `.btnPrimary:hover`) tenían mayor prioridad.
- **Solución:** Aumentada la especificidad del CSS generado por GBN en `style-generator.js` prefijando con `body` (`body [data-gbn-id="..."]`).

#### ✅ Bug: Estilos de Layout Rotos (Frontend Deslogeado)
- **Problema:** En el frontend (usuario no logueado), los estilos de layouts (flexbox centrado, columnas) se rompían porque los selectores CSS dependían de atributos internos (`gloryDivSecundario`, etc.) que son eliminados por seguridad/limpieza.
- **Solución:** 
    - Actualizados selectores en `theme-styles.css` y `gbn.css` para incluir las clases persistentes `.primario` y `.secundario` (inyectadas por `DomProcessor`).
    - Actualizado `DomProcessor.php` para inyectar automáticamente la clase `.gbn-image` en componentes de imagen (que no tenían clase persistente).
    - Actualizado CSS de imagen para soportar el selector `.gbn-image`.

#### ✅ Refactor: Limpieza de Estilos Duplicados
- **Problema:** Existía duplicidad de reglas CSS entre `gbn.css` y `theme-styles.css`. `theme-styles.css` era más completo pero competían en especificidad.
- **Solución:** Eliminadas las reglas redundantes de componentes en `gbn.css`. Ahora `theme-styles.css` es la única fuente de verdad para estilos base de componentes, mejorando la mantenibilidad y evitando conflictos.

---

## Fase 13: PostRender - Componente de Contenido Dinámico

> [!NOTE]
> Este componente permite renderizar listados de posts/CPTs directamente desde GBN con plantillas visuales editables.

### Objetivo

Crear un sistema de componentes que permita:
1. **Consultar contenido de WordPress** (posts, páginas, CPTs) con opciones configurables
2. **Diseñar visualmente** la plantilla de cada item en el editor
3. **Campos semánticos** que se llenan automáticamente con datos del post

### Análisis: Funcionalidades Rescatadas de ContentRender

El componente existente `Glory\Components\ContentRender` tiene lógicas valiosas que debemos reutilizar:

#### ✅ Rescatar para PostRender

| Funcionalidad             | Descripción                                                     | Prioridad                     |
| :------------------------ | :-------------------------------------------------------------- | :---------------------------- |
| **WP_Query Robusto**      | Manejo de post_type, paginación, orden, meta_key, sticky posts  | Alta                          |
| **Filtro por Categorías** | Sistema de filtrado frontend sin recarga (JS inline)            | Media                         |
| **Cache Inteligente**     | Transients con invalidación automática por post_type            | Media                         |
| **Layout Patterns**       | Patrones alternados (imagen izq/der), split 50/50               | Alta                          |
| **CSS por Instancia**     | Estilos scoped con clase única (glory-cr-XXXX)                  | Alta                          |
| **Modos de Interacción**  | Normal, Carousel (scroll horizontal), Toggle (acordeón)         | Media                         |
| **Opciones de Imagen**    | Aspect ratio, object-fit, dimensiones responsive                | Alta                          |
| **Control de Título**     | Show/hide, position, hover reveal, responsive                   | Alta                          |
| **Contexto Compartido**   | API `getCurrentOption()` / `setCurrentOption()` para plantillas | Media                         |
| **Sistema de Plantillas** | TemplateRegistry + TemplateManager                              | Baja (GBN usa enfoque visual) |

#### ❌ NO Rescatar (Obsoleto o innecesario)

| Funcionalidad                 | Razón                                              |
| :---------------------------- | :------------------------------------------------- |
| `gbnDefaults()`               | Desfazado, el nuevo componente usará SchemaBuilder |
| Integración Fusion/Avada      | GBN es independiente                               |
| Callbacks PHP para plantillas | GBN usa plantillas HTML visuales                   |

### Arquitectura Propuesta

#### Componentes PHP

| Archivo                                         | Responsabilidad                                                |
| :---------------------------------------------- | :------------------------------------------------------------- |
| `components/PostRender/PostRenderComponent.php` | Componente contenedor con opciones de query y layout           |
| `components/PostRender/PostItemComponent.php`   | Define la estructura del template (hijo de PostRender)         |
| `components/PostRender/PostFieldComponent.php`  | Campos semánticos (título, imagen, excerpt, etc.)              |
| `Services/PostRenderService.php`                | Lógica de WP_Query reutilizable (extraída de ContentRender)    |
| `Support/PostRenderCss.php`                     | Generador CSS por instancia (simplificado de ContentRenderCss) |

#### Componentes JS

| Archivo                    | Responsabilidad                                   |
| :------------------------- | :------------------------------------------------ |
| `renderers/post-render.js` | Renderer del contenedor, preview con datos reales |
| `renderers/post-item.js`   | Renderer del template item                        |
| `renderers/post-field.js`  | Renderer de campos semánticos                     |

### Sintaxis HTML Propuesta

```html
<!-- Contenedor: Define la query y layout del grid/lista -->
<div gloryPostRender 
     opciones="
        postType: 'libro',
        postsPerPage: 10,
        orderBy: 'date',
        order: 'desc',
        status: 'publish',
        displayMode: 'grid',
        gridColumns: 3,
        gap: '20px',
        categoryFilter: true
     ">
    
    <!-- Template: Se replica por cada post encontrado -->
    <article gloryPostItem class="card">
        
        <!-- Campos semánticos: Se llenan con datos del post actual -->
        <img gloryPostField="featuredImage" class="card__image">
        <h3 gloryPostField="title" class="card__title"></h3>
        <p gloryPostField="excerpt" class="card__excerpt"></p>
        <span gloryPostField="date" format="d M, Y"></span>
        <a gloryPostField="link" class="card__link">Leer más</a>
        
        <!-- Campos de taxonomías -->
        <div gloryPostField="categories" class="card__cats"></div>
        
        <!-- Meta field personalizado -->
        <span gloryPostField="meta:precio"></span>
        
        <!-- ACF field -->
        <div gloryPostField="acf:galeria"></div>
        
    </article>
    
</div>
```

### Campos Semánticos Disponibles

| Valor de `gloryPostField` | Fuente de Datos            | Notas                                     |
| :------------------------ | :------------------------- | :---------------------------------------- |
| `title`                   | `get_the_title()`          | Soporta tag configurable (h1-h6, p, span) |
| `featuredImage`           | `get_the_post_thumbnail()` | Soporta tamaño, aspect-ratio, lazy        |
| `excerpt`                 | `get_the_excerpt()`        | Límite de palabras configurable           |
| `content`                 | `the_content()`            | Contenido completo procesado              |
| `date`                    | `get_the_date()`           | Formato configurable                      |
| `author`                  | `get_the_author()`         | Nombre del autor                          |
| `authorAvatar`            | `get_avatar()`             | Avatar del autor                          |
| `link`                    | `get_permalink()`          | URL del post (para `<a href>`)            |
| `categories`              | `get_the_category()`       | Lista de categorías                       |
| `tags`                    | `get_the_tags()`           | Lista de etiquetas                        |
| `taxonomy:nombre`         | `get_the_terms()`          | Taxonomía personalizada                   |
| `meta:campo`              | `get_post_meta()`          | Meta field por nombre                     |
| `acf:campo`               | `get_field()`              | Campo ACF por nombre                      |
| `commentCount`            | `get_comments_number()`    | Cantidad de comentarios                   |

### Opciones del Contenedor (PostRenderComponent)

#### Tab: Query
| Campo            | Tipo      | Descripción                                            |
| :--------------- | :-------- | :----------------------------------------------------- |
| `postType`       | select    | Tipo de contenido (post, page, CPTs registrados)       |
| `postsPerPage`   | slider    | Cantidad de posts (1-50)                               |
| `orderBy`        | select    | Ordenar por: date, title, rand, menu_order, meta_value |
| `order`          | iconGroup | ASC / DESC                                             |
| `status`         | select    | publish, draft, any                                    |
| `categoryFilter` | toggle    | Mostrar filtro por categorías                          |
| `offset`         | number    | Saltar N posts                                         |
| `postIn`         | text      | IDs específicos (comma separated)                      |
| `postNotIn`      | text      | Excluir IDs                                            |
| `taxonomyQuery`  | group     | Filtrar por taxonomía/término                          |

#### Tab: Layout
| Campo            | Tipo      | Descripción                       |
| :--------------- | :-------- | :-------------------------------- |
| `displayMode`    | iconGroup | block, flex, grid                 |
| `gridColumns`    | slider    | Columnas (1-12) - responsive      |
| `gap`            | dimension | Espaciado entre items             |
| `flexDirection`  | iconGroup | row, column                       |
| `alignItems`     | iconGroup | stretch, start, center, end       |
| `justifyContent` | iconGroup | start, center, end, space-between |
| `layoutPattern`  | select    | none, alternado_lr, masonry       |

#### Tab: Estilo
| Campo                     | Tipo | Descripción                      |
| :------------------------ | :--- | :------------------------------- |
| Hereda de `HasSpacing`    | -    | padding, margin                  |
| Hereda de `HasBackground` | -    | backgroundColor, backgroundImage |
| Hereda de `HasBorder`     | -    | border completo                  |

#### Tab: Interacción
| Campo             | Tipo   | Descripción              |
| :---------------- | :----- | :----------------------- |
| `interactionMode` | select | normal, carousel, toggle |
| `pagination`      | toggle | Activar paginación AJAX  |
| `loadMore`        | toggle | Botón "cargar más"       |

### Opciones del Item (PostItemComponent)

El item hereda las opciones de layout estándar de GBN (como SecundarioComponent):
- `HasSpacing` (padding, margin)
- `HasBackground` (fondo, imagen)
- `HasBorder` (bordes)
- `HasFlexbox` (layout interno del item)

### Flujo de Renderizado

```
┌─────────────────────────────────────────────────────────────────┐
│                    FLUJO DE RENDERIZADO                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. PHP: PostRenderComponent recibe opciones                    │
│     ↓                                                           │
│  2. PHP: PostRenderService ejecuta WP_Query                     │
│     ↓                                                           │
│  3. PHP: Por cada post encontrado:                              │
│     a. Clona el template (gloryPostItem)                        │
│     b. Procesa los campos (gloryPostField)                      │
│     c. Reemplaza contenido semántico con datos reales           │
│     ↓                                                           │
│  4. PHP: Genera CSS scoped por instancia                        │
│     ↓                                                           │
│  5. HTML: Renderiza contenedor + items + filtros                │
│                                                                 │
│  ═══════════════════════════════════════════════════════════   │
│                                                                 │
│  EN EL EDITOR (GBN):                                            │
│                                                                 │
│  1. JS: Detecta [gloryPostRender] en el DOM                     │
│     ↓                                                           │
│  2. JS: Solicita preview vía AJAX con la query config           │
│     ↓                                                           │
│  3. JS: Renderiza posts reales (limitado a 3-5 para preview)    │
│     ↓                                                           │
│  4. JS: Permite editar estilos del template                     │
│     ↓                                                           │
│  5. JS: Los cambios de estilo se aplican a todos los items      │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Tareas de Implementación

#### Fase 13.1: Core PHP (✅ COMPLETADO)
- [x] **PostRenderService.php** - Extraer lógica de WP_Query de ContentRender
- [x] **PostRenderComponent.php** - Componente contenedor con SchemaBuilder
- [x] **PostItemComponent.php** - Componente template hijo
- [x] **PostFieldComponent.php** - Campos semánticos con SchemaBuilder

#### Fase 13.2: Renderizado PHP (✅ COMPLETADO)
- [x] **PostRenderProcessor.php** - Implementado. Maneja lógica de renderizado, clonación, reemplazo de campos y CSS scoped.
- [x] **Clonación de template** - Sistema para replicar gloryPostItem por post (Parte de PostRenderProcessor)
- [x] **PostFieldComponent::renderField()** - Método estático para renderizar campos semánticos desde PostRenderProcessor

#### Fase 13.3: Frontend JS (✅ COMPLETADO)
- [x] **post-render.js** - Renderer del contenedor
- [x] **post-item.js** - Renderer del item
- [x] **post-field.js** - Renderer de campos semánticos
- [x] **Registro en GbnManager.php** - Scripts y dependencias

#### Fase 13.4: Panel GBN (✅ COMPLETADO Dic 2025)
- [x] **Agregar a styleResolvers** en panel-render.js - Agregados resolvers para postRender, postItem, postField
- [x] **Preview AJAX** - Endpoint `gbn_post_render_preview` implementado en `PostRenderHandler.php`
- [x] **Sincronización** - Cambios en layout aplican a todos los items vía clonación del template

**Archivos Creados/Modificados:**
- `Glory/src/Gbn/Ajax/Handlers/PostRenderHandler.php` (Nuevo - endpoints AJAX)
- `Glory/src/Gbn/Ajax/Registrar.php` (Registro de endpoints)
- `Glory/src/Gbn/assets/js/ui/panel-render.js` (styleResolvers + supportedRoles)
- `Glory/src/Gbn/assets/js/ui/renderers/post-render.js` (Implementación AJAX real)

#### Fase 13.5: Funcionalidades Avanzadas (✅ COMPLETADO Dic 2025)
- [x] **Filtro por categorías** - Implementado en `post-render-frontend.js` con animaciones suaves
- [x] **Paginación AJAX** - Endpoint `gbn_post_render_paginate` con soporte para usuarios anónimos
- [x] **Cache por instancia** - Sistema de transients en `PostRenderService` con invalidación automática
- [x] **Layout Pattern alternado_lr** - CSS implementado en `theme-styles.css` con responsive
- [x] **Hover Effects** - Opciones lift, scale, glow vía clases CSS

**Archivos Creados/Modificados:**
- `Glory/src/Gbn/assets/js/frontend/post-render-frontend.js` (Nuevo - filtros y paginación frontend)
- `Glory/src/Gbn/Ajax/Handlers/PostRenderHandler.php` (Agregado método paginate())
- `Glory/src/Gbn/Ajax/Registrar.php` (Agregados endpoints nopriv para paginación)
- `Glory/src/Gbn/Services/PostRenderService.php` (Agregado sistema de cache con transients)
- `Glory/src/Gbn/assets/css/theme-styles.css` (Agregados estilos PostRender completos)
- `Glory/src/Gbn/components/PostRender/PostRenderComponent.php` (Agregados layoutPattern, hoverEffect)
- `Glory/src/Gbn/components/PostRender/PostRenderProcessor.php` (Agregados data-pattern, data-categories)
- `Glory/src/Gbn/assets/js/ui/renderers/post-render.js` (Agregado manejo de pattern y hover)
- `Glory/src/Gbn/GbnManager.php` (Agregado registro del script frontend y hooks de cache)

### Criterios de Aceptación

- [x] Crear un PostRender en el editor muestra preview con posts reales
- [x] Los campos semánticos se llenan correctamente con datos del post
- [x] Los estilos del template se aplican a todos los items (via CSS scoped)
- [x] El componente funciona sin GBN activo (HTML limpio + CSS inline)
- [x] El filtro por categorías funciona en frontend (sin recarga)
- [x] Los cambios de query (postType, order, etc.) actualizan el preview
- [x] Soporte responsive en el layout (columnas, gap) - media queries implementadas

### Dependencias

- Requiere: `ComponentLoader`, `SchemaBuilder`, `HasSpacing`, `HasFlexbox`, `HasBackground`, `HasBorder`
- Opcional: `ContentRender` (para reusar plantillas registradas vía TemplateRegistry)

### Notas de Diseño

1. **El template es el primer hijo** - `gloryPostItem` debe ser hijo directo de `gloryPostRender`
2. **Los campos son declarativos** - `gloryPostField="title"` indica QUÉ mostrar, no CÓMO
3. **Los estilos van en clases** - Los PostField no tienen opciones de estilo propias, se estilizan con CSS/clases
4. **Preview limitado** - En el editor, mostrar máximo 3-5 posts para rendimiento
5. **Cache inteligente** - No cachear en editor, sí cachear en frontend

### Cumplimiento SOLID y Reglas GBN

> [!IMPORTANT]
> Esta sección documenta cómo el plan cumple (o debe cumplir) con las reglas de `reglas.md` y los principios SOLID.

#### Roles y Selectores (Regla 4.5: Protocolo de Componentes)

| Componente            | Role         | Selector            | Validación                         |
| :-------------------- | :----------- | :------------------ | :--------------------------------- |
| `PostRenderComponent` | `postRender` | `[gloryPostRender]` | Schema, ComponentLoader            |
| `PostItemComponent`   | `postItem`   | `[gloryPostItem]`   | Validar que sea hijo de postRender |
| `PostFieldComponent`  | `postField`  | `[gloryPostField]`  | Validar valor del atributo         |

#### Flujo de Datos Unidireccional (Regla 4.2)

```
┌───────────────────────────────────────────────────────────────────┐
│                    FLUJO DE CAMBIO DE QUERY                       │
├───────────────────────────────────────────────────────────────────┤
│                                                                   │
│  1. Usuario cambia `postType` en el Panel                         │
│     ↓                                                             │
│  2. Panel dispara: Gbn.actions.updateBlock(id, {postType: 'libro'})│
│     ↓                                                             │
│  3. Store actualiza config del bloque PostRender                  │
│     ↓                                                             │
│  4. Subscriber detecta cambio → Llama renderer.handleUpdate()     │
│     ↓                                                             │
│  5. Renderer solicita preview vía AJAX con nueva config           │
│     ↓                                                             │
│  6. Backend ejecuta WP_Query y devuelve HTML                      │
│     ↓                                                             │
│  7. Renderer reemplaza contenido del contenedor                   │
│                                                                   │
│  ⚠️ PROHIBIDO: Modificar DOM directamente desde el Panel          │
│                                                                   │
└───────────────────────────────────────────────────────────────────┘
```

#### Single Source of Truth: Template vs Items Clonados

**Aclaración Arquitectónica:**
- **Template (`gloryPostItem`)** → Es un bloque registrado en el Store con config editable
- **Items Clonados** → Son **visuales** generados por PHP/JS, NO son bloques individuales
- **Estilos del Template** → Se aplican vía **clase CSS** (`.postItem-XXXX`), NO inline por item
- **Editar un item** → Realmente edita el template, cambio se propaga a todos

```javascript
// ✅ CORRECTO: Un solo bloque template en el Store
registry: {
    'post-render-123': { role: 'postRender', config: {...} },
    'post-item-456': { role: 'postItem', config: {...} }  // Template único
    // Los clones NO se registran
}
```

#### Estilos y Variables CSS (Regla 4.4)

**MANDATORIO:** Los defaults NO deben emitir estilos inline duros.

```css
/* theme-styles.css - Defaults del sistema */
[gloryPostRender] {
    display: var(--gbn-post-render-display, grid);
    gap: var(--gbn-post-render-gap, 20px);
    grid-template-columns: var(--gbn-post-render-columns, repeat(3, 1fr));
}
```

```javascript
// style-composer.js - Solo emitir si hay config explícita
function getStyles(config) {
    return {
        display: config.displayMode || undefined,  // ✅ undefined = no emite
        gap: config.gap || undefined,
        // ❌ NUNCA: display: config.displayMode || 'grid'
    };
}
```

#### Strategy Pattern para PostField (LSP - Liskov)

Para evitar un "God Object", los campos semánticos usan Strategy:

```php
// PostField/FieldStrategyInterface.php
interface FieldStrategyInterface {
    public function canHandle(string $fieldType): bool;
    public function render(WP_Post $post, array $options): string;
}

// PostField/Strategies/TitleStrategy.php
class TitleStrategy implements FieldStrategyInterface {
    public function canHandle(string $fieldType): bool {
        return $fieldType === 'title';
    }
    
    public function render(WP_Post $post, array $options): string {
        return esc_html(get_the_title($post));
    }
}

// PostField/Strategies/MetaStrategy.php
class MetaStrategy implements FieldStrategyInterface {
    public function canHandle(string $fieldType): bool {
        return str_starts_with($fieldType, 'meta:');
    }
    
    public function render(WP_Post $post, array $options): string {
        $key = substr($fieldType, 5);
        return esc_html(get_post_meta($post->ID, $key, true));
    }
}

// PostFieldComponent.php usa StrategyResolver
class PostFieldComponent extends AbstractComponent {
    public function render(WP_Post $post, string $fieldType): string {
        $strategy = StrategyResolver::resolve($fieldType);
        return $strategy->render($post, $this->getConfig());
    }
}
```

#### Responsive (Regla 4.3)

**Estructura de valores responsive:**

```javascript
// Config debe soportar breakpoints explícitos
config: {
    gridColumns: {
        desktop: 4,
        tablet: 2,
        mobile: 1
    },
    gap: {
        desktop: '30px',
        tablet: '20px',
        mobile: '15px'
    }
}
```

**Al guardar desde el panel:**
```javascript
// ✅ CORRECTO: Especificar breakpoint
Gbn.actions.updateBlock(id, { gridColumns: 3 }, 'tablet');

// ❌ INCORRECTO: Sin contexto de breakpoint
Gbn.actions.updateBlock(id, { gridColumns: 3 });
```

#### Sincronización Bidireccional (Regla 5)

**Corrección:** Los atributos como `format` deben estar en `opciones`, NO como atributos HTML separados.

```html
<!-- ❌ INCORRECTO: format como atributo separado -->
<span gloryPostField="date" format="d M, Y"></span>

<!-- ✅ CORRECTO: Todo en opciones del componente -->
<span gloryPostField="date" 
      opciones="format: 'd M, Y'"></span>
```

O mejor aún, el formato se configura desde el panel del PostFieldComponent y se guarda en la config del bloque.

### Checklist de Cumplimiento (Pre-Implementación)

- [ ] **Regla 1 (HTML Limpio):** Sin shortcodes, atributos semánticos
- [ ] **Regla 2 (Independencia):** Funciona sin GBN activo
- [ ] **Regla 4.1 (Single Source):** Template único en Store, clones son visuales
- [ ] **Regla 4.2 (Unidireccional):** Cambios vía Gbn.actions, NO DOM directo
- [ ] **Regla 4.3 (Responsive):** Config con breakpoints explícitos
- [ ] **Regla 4.4 (Defaults):** Usar `undefined` o `var(--gbn-*)`, no valores duros
- [ ] **Regla 4.5 (Protocolo):** Roles únicos, selectores válidos, Schema registrado
- [ ] **Regla 5 (Bidireccional):** Opciones en `opciones=`, no atributos HTML sueltos
- [ ] **SRP:** Service (query), Component (schema), Css (estilos), Renderer (UI)
- [ ] **OCP:** Campos extensibles vía Strategy pattern
- [ ] **LSP:** FieldStrategyInterface para cada tipo de campo
- [ ] **DRY:** Reutilizar lógica de ContentRender, no duplicar

---

### Defensa contra Bugs Conocidos (Anti-Patrones)

> [!CAUTION]
> Esta sección documenta bugs históricos de GBN que **NO DEBEN REPLICARSE** en PostRender. Cada defensa incluye el bug original, la causa raíz y la protección específica.

#### 🛡️ 1. Layout Delay / Flash de Contenido (Bugs 27, 28, 33)

**Bug Original:** Los elementos aparecían sin estilos por un instante y luego "saltaban" al aplicarse flexbox/grid.

**Causa Raíz:** Defaults hardcodeados como `display: flex` en JS que competían con CSS.

**Defensa para PostRender:**
```javascript
// ❌ PROHIBIDO en post-render.js
getStyles(config) {
    return { display: config.displayMode || 'grid' }; // NO! Emite 'grid' siempre
}

// ✅ CORRECTO
getStyles(config) {
    return { display: config.displayMode || undefined }; // Solo emite si hay valor
}
```

```css
/* Defaults en theme-styles.css, NO en JS */
[gloryPostRender] {
    display: var(--gbn-post-render-display, grid);
}
```

---

#### 🛡️ 2. Atributos Internos Visibles en Frontend (Bug Data Leak)

**Bug Original:** Usuarios no logueados veían `data-gbn-schema`, `data-gbn-config` en el HTML.

**Causa Raíz:** No se limpiaban atributos internos al guardar/renderizar.

**Defensa para PostRender:**
```php
// En PostRenderComponent.php - OBLIGATORIO
public function render(): string {
    $html = $this->renderItems();
    
    // LIMPIAR antes de retornar (para frontend)
    if (!$this->isEditor()) {
        $html = DomProcessor::cleanInternalAttributes($html);
    }
    
    return $html;
}
```

**Atributos a limpiar:** `gloryPostRender`, `gloryPostItem`, `gloryPostField`, `data-gbn-*`, `opciones`

---

#### 🛡️ 3. Estilos Rotos en Frontend Deslogeado (Bug Layout Rotos)

**Bug Original:** En frontend sin login, los estilos de layout se rompían porque dependían de atributos eliminados.

**Causa Raíz:** Selectores CSS usaban `[gloryDiv]` que se elimina en frontend.

**Defensa para PostRender:**
```css
/* ❌ PROHIBIDO - Selector que se elimina */
[gloryPostRender] { display: grid; }

/* ✅ CORRECTO - Usar clase persistente inyectada por DomProcessor */
.gbn-post-render { display: grid; }
[gloryPostRender], .gbn-post-render { display: grid; }
```

```php
// DomProcessor debe inyectar clase persistente
$element->classList->add('gbn-post-render');
$itemElement->classList->add('gbn-post-item');
```

---

#### 🛡️ 4. Race Condition en Async (Bug Docking Persistente)

**Bug Original:** Al cerrar el panel antes de que termine una llamada AJAX, el callback se ejecutaba en contexto incorrecto.

**Causa Raíz:** Sin guards para verificar estado antes de procesar respuestas async.

**Defensa para PostRender:**
```javascript
// En post-render.js - handleUpdate para query changes
async function refreshPreview(blockId, config) {
    var currentPanelMode = Gbn.ui.panel.getMode();
    
    var response = await fetchPostsPreview(config);
    
    // 🛡️ ASYNC GUARD: Verificar que seguimos en contexto válido
    if (Gbn.ui.panel.getMode() !== currentPanelMode) {
        console.log('PostRender: Abortando refresh, contexto cambió');
        return; // El panel se cerró o cambió durante la espera
    }
    
    if (!document.getElementById(blockId)) {
        return; // El bloque fue eliminado durante la espera
    }
    
    applyPreview(blockId, response);
}
```

---

#### 🛡️ 5. Memory Leak en Event Listeners (Bug Theme Settings)

**Bug Original:** Listeners de `gbn:configChanged` nunca se removían, acumulando handlers zombies.

**Causa Raíz:** `addEventListener` sin cleanup correspondiente.

**Defensa para PostRender:**
```javascript
// post-render.js - Patrón de cleanup
var activeListeners = new Map();

function registerQueryChangeListener(blockId, handler) {
    // Limpiar listener anterior si existe
    if (activeListeners.has(blockId)) {
        var old = activeListeners.get(blockId);
        document.removeEventListener('gbn:configChanged', old);
    }
    
    // Registrar nuevo
    document.addEventListener('gbn:configChanged', handler);
    activeListeners.set(blockId, handler);
}

function cleanup(blockId) {
    if (activeListeners.has(blockId)) {
        document.removeEventListener('gbn:configChanged', activeListeners.get(blockId));
        activeListeners.delete(blockId);
    }
}
```

---

#### 🛡️ 6. Persistencia de Layout Options Incompleta (Bug 10)

**Bug Original:** Opciones de flexbox (direction, wrap, justify, align) no persistían.

**Causa Raíz:** Hidratación no incluía todas las propiedades de layout.

**Defensa para PostRender:**
```javascript
// Hidratación COMPLETA de PostRender
function hydratePostRender(element) {
    var config = parseOptions(element);
    
    // Lista explícita de TODAS las propiedades de layout a hidratar
    var layoutProps = [
        'displayMode', 'gridColumns', 'gap',
        'flexDirection', 'flexWrap', 'justifyContent', 'alignItems',
        'layoutPattern', 'interactionMode'
    ];
    
    layoutProps.forEach(function(prop) {
        if (config[prop] !== undefined) {
            block.config[prop] = config[prop];
        }
    });
}
```

---

#### 🛡️ 7. Especificidad de CSS Generado (Bug Hover Persistencia)

**Bug Original:** Estilos de hover del panel no se aplicaban porque clases de plantilla tenían mayor especificidad.

**Causa Raíz:** Selector `[data-gbn-id="..."]` tenía menor especificidad que `.btnPrimary:hover`.

**Defensa para PostRender:**
```javascript
// En style-generator.js para PostRender
function generatePostRenderCss(blockId, config) {
    // 🛡️ Prefijo body para mayor especificidad
    var selector = 'body [data-gbn-id="' + blockId + '"]';
    
    // Para items clonados, usar clase del template
    var itemSelector = 'body .' + config.templateClass;
    
    return css;
}
```

---

#### 🛡️ 8. Valores Computados vs Defaults del Tema (Bug BUG-SYNC)

**Bug Original:** El panel no mostraba valores de CSS (width, height) porque `getConfigValue` retornaba el default del tema.

**Causa Raíz:** Default del schema bloqueaba la lectura de `getComputedStyle`.

**Defensa para PostRender:**
```javascript
// En campos de PostRender (ej: gridColumns)
function getEffectiveValue(block, path) {
    var configValue = getConfigValue(block, path);
    var schemaDefault = getSchemaDefault(block.schema, path);
    
    // 🛡️ Si el valor es igual al default del schema, 
    // intentar leer del DOM computado
    if (configValue === schemaDefault && block.element) {
        var computedValue = getComputedValueForPath(block.element, path);
        if (computedValue && !isBrowserDefault(computedValue)) {
            return computedValue;
        }
    }
    
    return configValue;
}
```

---

#### 🛡️ 9. Responsive sin Contexto de Breakpoint (Bug 29)

**Bug Original:** Valores responsive no persistían correctamente.

**Causa Raíz:** `updateBlock` no especificaba breakpoint destino.

**Defensa para PostRender:**
```javascript
// ❌ PROHIBIDO
function onGridColumnsChange(value) {
    Gbn.actions.updateBlock(blockId, { gridColumns: value });
}

// ✅ CORRECTO - Especificar breakpoint
function onGridColumnsChange(value) {
    var currentBreakpoint = Gbn.services.responsive.getCurrentBreakpoint();
    Gbn.actions.updateBlock(blockId, { gridColumns: value }, currentBreakpoint);
}
```

---

#### 🛡️ 10. Crash por JSON Circular (Bug 30)

**Bug Original:** Inspector crasheaba al serializar objetos con referencias DOM.

**Causa Raíz:** `JSON.stringify` en objetos con `element` que contiene referencia circular.

**Defensa para PostRender:**
```javascript
// Al guardar config de PostRender
function serializeConfig(config) {
    // 🛡️ Clonar sin referencias DOM
    var safeConfig = {};
    Object.keys(config).forEach(function(key) {
        if (key !== 'element' && key !== '_dom' && key !== 'templateElement') {
            safeConfig[key] = config[key];
        }
    });
    
    return JSON.stringify(safeConfig);
}
```

---

#### 🛡️ 11. Grid Columns sin Unidades (Bug 32)

**Bug Original:** `gridColumns: 3` generaba CSS inválido `grid-template-columns: 3px`.

**Causa Raíz:** `applicator.js` añadía unidades a todos los valores numéricos.

**Defensa para PostRender:**
```javascript
// Lista de propiedades que NO llevan unidades
var UNITLESS_PROPERTIES = ['gridColumns', 'order', 'zIndex', 'opacity', 'flexGrow', 'flexShrink'];

function applyStyle(element, prop, value) {
    if (UNITLESS_PROPERTIES.includes(prop)) {
        element.style[prop] = value; // Sin unidades
    } else if (typeof value === 'number') {
        element.style[prop] = value + 'px';
    } else {
        element.style[prop] = value;
    }
}
```

---

#### 🛡️ 12. Placeholder/Imagen Rota (Bug Imagen)

**Bug Original:** Placeholder usaba URL externa y ocupaba toda la pantalla.

**Causa Raíz:** URL hardcodeada y sin max-width por defecto.

**Defensa para PostRender:**
```php
// Para gloryPostField="featuredImage"
class FeaturedImageStrategy implements FieldStrategyInterface {
    public function render(WP_Post $post, array $options): string {
        if (!has_post_thumbnail($post)) {
            // 🛡️ Placeholder LOCAL con dimensiones controladas
            return '<img src="' . GBN_ASSETS_URL . '/images/landscape-placeholder.svg" 
                        style="max-width: 100%; height: auto;" 
                        alt="Sin imagen">';
        }
        
        return get_the_post_thumbnail($post, $options['size'] ?? 'medium', [
            'style' => 'max-width: 100%; height: auto;'
        ]);
    }
}
```

---

#### 🛡️ 13. Border-Radius sin Overflow (Bug Imagen Borde)

**Bug Original:** `border-radius` en wrapper no recortaba la imagen interna.

**Causa Raíz:** Faltaba `overflow: hidden` en el contenedor.

**Defensa para PostRender:**
```javascript
// En post-item.js handleUpdate
if (path === 'borderRadius' && value) {
    // 🛡️ Forzar overflow:hidden cuando hay border-radius
    element.style.overflow = 'hidden';
}
```

---

#### 🛡️ 14. Atributos HTML Malformados (Bug Dirty HTML)

**Bug Original:** `data-gbn-config='{"texto":...'` aparecía malformado en el DOM.

**Causa Raíz:** Escritura redundante de atributos al DOM en múltiples lugares.

**Defensa para PostRender:**
```javascript
// 🛡️ PROHIBIDO escribir config/schema al DOM
// El estado vive SOLO en memoria (state.js)

// ❌ PROHIBIDO
element.setAttribute('data-gbn-config', JSON.stringify(config));

// ✅ CORRECTO - Solo en memoria
Gbn.state.updateBlock(blockId, config);
```

---

#### 🛡️ 15. MockBlocks sin Elemento DOM (Bug Colores Negro)

**Bug Original:** En Theme Settings, colores aparecían en negro.

**Causa Raíz:** Código asumía que `block.element` siempre existe.

**Defensa para PostRender:**
```javascript
// En campos que leen computed styles
function getComputedColor(block, path) {
    // 🛡️ Verificar existencia de elemento ANTES de leer
    if (!block.element) {
        // Fallback a config directa para mockBlocks
        return getDeepValue(block.config, path) || field.defecto || '#000000';
    }
    
    var computed = getComputedStyle(block.element);
    return computed[path] || field.defecto;
}
```

---

### Checklist de Defensa (Pre-Código)

- [ ] **No defaults duros en JS** (Defensa 1)
- [ ] **Limpiar atributos internos** (Defensa 2)
- [ ] **Clases persistentes para CSS** (Defensa 3)
- [ ] **Async guards en AJAX** (Defensa 4)
- [ ] **Cleanup de listeners** (Defensa 5)
- [ ] **Hidratación explícita completa** (Defensa 6)
- [ ] **Especificidad con `body` prefix** (Defensa 7)
- [ ] **Detectar defaults vs computed** (Defensa 8)
- [ ] **Breakpoint en updateBlock** (Defensa 9)
- [ ] **Excluir DOM de JSON** (Defensa 10)
- [ ] **UNITLESS_PROPERTIES** (Defensa 11)
- [ ] **Placeholders locales** (Defensa 12)
- [ ] **overflow:hidden con border-radius** (Defensa 13)
- [ ] **Estado solo en memoria** (Defensa 14)
- [ ] **Verificar block.element** (Defensa 15)

---
