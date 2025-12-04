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

## Trabajo Pendiente

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

#### ✅ Bug Crítico: Docking Persistente (Theme Settings) - RESUELTO
- **Problema:** Al cerrar el panel de configuración de tema, el ancho de la página no regresaba a su normalidad (se quedaba contraído).
- **Causa Raíz:** El estado de Theme Settings (variables globales `componentState` y `currentView`) no se reseteaba al cerrar el panel, causando conflictos en la gestión del docking.
- **Solución Implementada:**
    1. Agregada función `resetThemeSettingsState()` en `render.js` que resetea el estado global.
    2. Modificada función `close()` en `panel-core.js` para llamar a `resetState()` cuando el panel estaba en modo `theme`.
    3. Verificación adicional para asegurar que la clase `gbn-panel-open` siempre se remueva del body.
- **Archivos Modificados:**
    - `Glory/src/Gbn/assets/js/ui/theme/render.js`
    - `Glory/src/Gbn/assets/js/ui/panel-core.js`

#### ✅ Bug: Colores de Paleta Global en Negro - RESUELTO
- **Problema:** En Theme Settings > Colores > Paleta Global, todos los colores aparecían en negro.
- **Causa Raíz:** La refactorización del campo `color` (soporte transparencia) no consideraba que los `mockBlock` de Theme Settings no tienen un `element` DOM asociado. El código intentaba leer `computedStyles` de un elemento inexistente, fallando y usando el fallback `#000000`.
- **Solución Implementada:**
    1. Agregada lectura directa de `block.config` usando `getDeepValue()` como prioridad para bloques sin elemento DOM.
    2. La cadena de prioridad ahora es: `configValue > effective.value > computedColor > field.defecto > '#000000'`.
- **Archivos Modificados:**
    - `Glory/src/Gbn/assets/js/ui/panel-fields/color.js`

#### 📝 Tarea: Documentación de Colores Globales (para opus, no geminis)
- **Objetivo:** Documentar cómo agregar colores globales desde CSS directamente.

#### ⚙️ Tarea: Opción Faltante en Defaults de Página
- **Objetivo:** Agregar opción "Ancho Máximo" en la sección Defaults de Página. Revisar si esto requiere refactorizacion debido a todos los cambios.

