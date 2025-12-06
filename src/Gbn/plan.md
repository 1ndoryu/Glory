# GBN - Plan de Trabajo

> [!TIP] LEER SIEMPRE CUANDO SE RECIBA ESTE MD LOS SIGUIENTES MD: 
> *   **Reglas y Arquitectura:** Ver `reglas.md`.
> *   **Documentación:** Ver `documentación-gbn.md`.

---

## 1. Estado Actual del Sistema (Resumen Ejecutivo)

### ✅ Infraestructura Completada
- **Core:** ComponentLoader, SchemaBuilder, AbstractComponent, ContainerRegistry
- **Traits PHP:** HasFlexbox, HasSpacing, HasTypography, HasGrid, HasBorder, HasBackground, HasPositioning, HasCustomCSS  
- **Traits JS:** `renderer-traits.js` con funciones reutilizables para eliminar código duplicado
- **Store Centralizado:** Redux-lite con flujo unidireccional (`state.js`, `store-subscriber.js`)
- **Sistema de Paneles:** Modular con tabs, campos auto-registrables (`registry.js`)
- **Responsive:** Breakpoints, herencia mobile→tablet→desktop
- **Estados Hover/Focus:** Lectura/escritura de pseudo-clases CSS, simulación visual en editor
- **Diagnóstico:** Remote Logger, Debug Overlay (`Ctrl+Alt+D`), GBN Control Center (API + UI)

### 🎯 Componentes Activos
| Componente          | Role         | Selector               | Traits Usados                                   | Estado |
| :------------------ | :----------- | :--------------------- | :---------------------------------------------- | :----- |
| PrincipalComponent  | `principal`  | `[gloryDiv]`           | Flexbox, Grid, Spacing, Background, Positioning | ✅      |
| SecundarioComponent | `secundario` | `[gloryDivSecundario]` | Flexbox, Spacing, Background, Positioning       | ✅      |
| TextComponent       | `text`       | `[gloryTexto]`         | Typography, Spacing, Border, Background         | ✅      |
| ButtonComponent     | `button`     | `[gloryButton]`        | Typography, Spacing, Border, Background         | ✅      |
| ImageComponent      | `image`      | `[gloryImagen]`        | Spacing, Border, Dimensions                     | ✅      |
| PostRenderComponent | `postRender` | `[gloryPostRender]`    | Flexbox, Grid, Spacing, Background              | ✅      |
| PostItemComponent   | `postItem`   | `[gloryPostItem]`      | Flexbox, Spacing, Background, Border            | ✅      |
| PostFieldComponent  | `postField`  | `[gloryPostField]`     | N/A (Semantic)                                  | ✅      |
| FormComponent       | `form`       | `[gloryForm]`          | Spacing, Background, Border                     | ✅      |
| InputComponent      | `input`      | `[gloryInput]`         | Spacing, Border, Typography                     | ✅      |
| TextareaComponent   | `textarea`   | `[gloryTextarea]`      | Spacing, Border, Typography                     | ✅      |
| SelectComponent     | `select`     | `[glorySelect]`        | Spacing, Border                                 | ✅      |
| SubmitComponent     | `submit`     | `[glorySubmit]`        | Spacing, Border, Typography                     | ✅      |

---

## 2. Bugs Críticos Resueltos (Referencia Rápida)

> [!NOTE]
> Estos bugs han sido **erradicados** y documentados para evitar regresiones. Ver secciones detalladas en historial.

### Defensa Arquitectónica Implementada

| ID   | Bug Histórico                           | Defensa Aplicada                                                 | Archivo Clave                           |
| :--- | :-------------------------------------- | :--------------------------------------------------------------- | :-------------------------------------- |
| 🛡️ 1  | Layout Delay/Flash                      | Defaults en CSS vars, no JS hardcoded                            | `style-composer.js`, `theme-styles.css` |
| 🛡️ 2  | Data Leak (Atributos internos visibles) | Solo limpiar `data-gbn-schema/config`, preservar `glory*`        | `GbnManager.php`                        |
| 🛡️ 3  | Estilos rotos en frontend               | Atributos `glory*` preservados para selectores CSS               | `theme-styles.css`, `GbnManager.php`    |
| 🛡️ 4  | Race Condition (Async)                  | Async Guards en callbacks AJAX                                   | `panel-core.js`, `post-render.js`       |
| 🛡️ 5  | Memory Leak (Listeners)                 | Cleanup explícito + Map de handlers activos                      | `theme/render.js`                       |
| 🛡️ 6  | Persistencia Responsive                 | Breakpoint explícito en `updateBlock()`                          | `panel-render.js`                       |
| 🛡️ 7  | Especificidad CSS                       | Prefijo `body` en CSS generado                                   | `style-generator.js`                    |
| 🛡️ 8  | Defaults vs Computed                    | Detección de browser defaults                                    | `utils.js`, `fraction.js`               |
| 🛡️ 10 | Crash JSON Circular                     | Excluir DOM de serialización                                     | `persistence.js`                        |
| 🛡️ 11 | Grid Columns sin unidades               | Lista `UNITLESS_PROPERTIES`                                      | `applicator.js`                         |
| 🛡️ 15 | MockBlocks sin elemento DOM             | Verificar `block.element` antes de `getComputedStyle`            | `color.js`                              |
| 🛡️ 16 | Stale Block Reference (Pérdida datos)   | `state.get(block.id)` antes de `cloneConfig()`                   | `panel-render.js`                       |
| 🛡️ 17 | Estados sin propiedades CSS             | Agregar props a `cssDirectProps` en `style-generator.js`         | `style-generator.js`                    |
| 🛡️ 18 | Elementos editables bloqueados por CSS  | NUNCA `pointer-events: none` en elementos editables              | `interactive.css`                       |
| 🛡️ 19 | Elementos AJAX no interactivos          | Re-escanear elementos cargados por AJAX con `Gbn.content.scan()` | `post-render.js`                        |
| 🛡️ 20 | Badges cortados por overflow            | `overflow: visible` en contenedores con badges absolutos         | `interactive.css`                       |

**Checklist Obligatorio (Pre-Código):**
- [ ] No defaults duros en JS
- [ ] Limpiar atributos internos
- [ ] Clases persistentes para CSS
- [ ] Async guards en AJAX
- [ ] Cleanup de listeners
- [ ] Hidratación explícita completa
- [ ] Especificidad con `body` prefix
- [ ] Detectar defaults vs computed
- [ ] Breakpoint en updateBlock
- [ ] Excluir DOM de JSON
- [ ] `UNITLESS_PROPERTIES` configuradas
- [ ] Placeholders locales
- [ ] `overflow:hidden` con border-radius
- [ ] Estado solo en memoria
- [ ] Verificar `block.element`
- [ ] Usar `state.get()` antes de clonar config
- [ ] Nuevas props CSS en estados → agregar a `cssDirectProps`
- [ ] **NUNCA `pointer-events: none` en elementos editables**
- [ ] **Re-escanear elementos cargados por AJAX**
- [ ] **`overflow: visible` en contenedores con badges**

---

## 3. Cambios Recientes (Últimos 30 Días)

### ✅ Diciembre 2025 - Highlights

#### Fase 14: Componentes de Formulario
**Funcionalidad:** Sistema de formularios editable visualmente con validación HTML5 y AJAX.

**Componentes:**
- ✅ `FormComponent` → Contenedor `<form>` con AJAX submit y honeypot anti-spam
- ✅ `InputComponent` → Tipos: text, email, tel, number, password, url
- ✅ `TextareaComponent` → Área de texto con filas configurables
- ✅ `SelectComponent` → Dropdown con opciones en formato simple
- ✅ `SubmitComponent` → Botón con estado loading

**Archivos Clave:**
- `Components/Form/FormComponent.php`, `InputComponent.php`, `TextareaComponent.php`, `SelectComponent.php`, `SubmitComponent.php`
- `assets/js/ui/renderers/form.js`, `input.js`, `textarea.js`, `select.js`, `submit.js`
- `assets/css/components.css` (estilos de formularios)

#### Fase 14.5: Notificación por Correo para Formularios
**Funcionalidad:** Sistema automático de envío de correos al administrador cuando se envía un formulario GBN.

**Características:**
- ✅ Envío automático de correo HTML al admin con datos del formulario
- ✅ Validación honeypot anti-spam
- ✅ Rate limiting básico por IP (5 segundos entre envíos)
- ✅ Asunto de email configurable con placeholders (`{{formId}}`, `{{siteName}}`)
- ✅ Estados de carga en botón submit (texto "Enviando...")
- ✅ Mensajes de éxito/error estilizados en el formulario
- ✅ Auto-inicialización y observador DOM para formularios dinámicos

**Archivos Clave:**
- `Ajax/Handlers/FormSubmitHandler.php` → Procesa envío y envía email
- `assets/js/frontend/form-submit.js` → Manejo AJAX desde frontend

#### Fase 13: PostRender - Componente de Contenido Dinámico
**Funcionalidad:** Renderiza listados de posts/CPTs con plantillas visuales editables.

**Características:**
- ✅ WP_Query con opciones configurables (postType, order, categorías, taxonomías)
- ✅ Sistema de template único que se replica por cada post
- ✅ Campos semánticos: `title`, `featuredImage`, `excerpt`, `date`, `author`, `categories`, `tags`, `meta:*`, `acf:*`
- ✅ Preview AJAX en tiempo real en el editor
- ✅ Filtrado por categorías (frontend sin recarga)
- ✅ Paginación AJAX para usuarios anónimos
- ✅ Cache por instancia con transients
- ✅ Layout Pattern `alternado_lr` con responsive
- ✅ Hover Effects: lift, scale, glow

**Archivos Clave:**
- `components/PostRender/PostRenderComponent.php`
- `components/PostRender/PostItemComponent.php`
- `components/PostRender/PostFieldComponent.php`
- `components/PostRender/PostRenderProcessor.php`
- `Services/PostRenderService.php`
- `Ajax/Handlers/PostRenderHandler.php`
- `assets/js/ui/renderers/post-render.js`
- `assets/js/frontend/post-render-frontend.js`

#### Fase 11: Refactorización SOLID de Componentes
**Objetivo:** Centralizar lógica reutilizable para reducir código duplicado.

**Resultados:**
- ✅ Traits JS (`renderer-traits.js`): ~350 líneas de funciones compartidas
- ✅ `text.js`: Reducido 60% (236→110 líneas)
- ✅ `button.js`: Reducido 50% (344→175 líneas)
- ✅ `image.js`: Refactorizado con mejor estructura
- ✅ Trait PHP `HasBorder` centralizado
- ✅ Documentación: `guia-crear-componente.md`

#### Fase 10: Estados Hover/Focus
**Funcionalidad:** Editar estilos de pseudo-clases desde el panel.

**Arquitectura:**
- ✅ `config._states` para almacenar hover/focus/active
- ✅ `state-styles.js`: Parser de hojas de estilo para leer pseudo-clases
- ✅ `style-generator.js`: Genera CSS con reglas `:hover`, `:focus`, `:active`
- ✅ Simulación visual con clases `.gbn-simulated-*`
- ✅ Selector de estados en panel con iconos SVG

#### Mejoras UI/UX
- ✅ **Smart Dimension Control**: Input libre + presets para ancho/alto
- ✅ **Transparencia en colores**: RGBA + slider de opacidad
- ✅ **Editor Dual (Visual/Code)**: Rich-text con vista HTML raw
- ✅ **Context Menu Avanzado**: Selección de elementos anidados con `elementsFromPoint()`
- ✅ **Dimensions Field UI**: Grid 2x2 con iconos SVG
- ✅ **Border Colapsable**: Grupo con toggle y condiciones
- ✅ **Persistencia de pestaña activa** y scroll en panel
- ✅ **Iconos en estado selector** (Normal, Hover, Focus)
- ✅ **Font-weight en Typography**
- ✅ **CSS Specificity Normalizada**: `:where()` en `init.css`

#### Mantenimiento y Refactorización
- ✅ **interactive.css Variables**: Refactorización completa de `interactive.css` para usar Custom Properties (Variables CSS) en colores, espaciado, z-indices y transiciones. Facilita el mantenimiento y consistencia visual.

#### Bugs Resueltos (Últimos)
- ✅ **PostRender Preview WYSIWYG - Parpadeo e interacción en clones**: Los clones del preview parpadeaban al pasar el mouse y permitían interacción incorrecta. Solución: 1) `pointer-events: none` en clones, 2) MutationObserver inteligente que filtra mutaciones de hover/selección del editor, 3) Debounce aumentado a 300ms con flag anti-recursivo. Ahora el template es editable y los clones son visuales estables.
- ✅ **PostRender Estático - Contenido no dinámico**: Al guardar PostRender, el contenido quedaba estático porque `PostRenderProcessor.php` procesaba el contenido ANTES de que el editor cargara. Solución multi-capa: 1) `isEditorMode()` en PHP detecta editores y NO procesa (retorna template original), 2) `persistence.js` limpia clones de preview JS, 3) `post-render.js` genera preview temporal con clones marcados. Ahora el editor ve el template original y los cambios estructurales persisten.
- ✅ **Hover/Focus Spacing - Persistencia**: Al editar padding/margin en estados hover/focus, los valores funcionaban en tiempo real pero NO persistían después de guardar. El problema era que `extractStyles()` en `style-generator.js` no incluía las propiedades de spacing camelCase (`paddingTop`, `marginLeft`, etc.) en la lista `cssDirectProps`. Solución: agregar propiedades `paddingTop/Right/Bottom/Left` y `marginTop/Right/Bottom/Left` a `cssDirectProps`.
- ✅ **Hover/Focus Spacing - Padding/Margin no funcionaban en estados**: Los paths como `padding.superior` no se mapeaban correctamente a propiedades CSS (`paddingTop`) al editar estados hover/focus. El código usaba el último segmento del path (`superior`) en lugar de la propiedad CSS correcta. Solución: manejo explícito para paths de spacing que traduce direcciones en español a CSS camelCase.
- ✅ **Stale Block Reference - Pérdida de valores de spacing/margin**: Al editar padding-top y luego padding-bottom, el segundo valor sobrescribía el primero porque la referencia del bloque pasada a los campos del panel era una captura del momento de renderizado. Solución: usar `state.get(block.id)` antes de `cloneConfig()` para obtener el bloque fresco del store.
- ✅ **Atributos glory* - Estilos rotos en frontend**: Los atributos `glory*` se limpiaban para usuarios no editores, rompiendo selectores CSS. Solución: preservar `glory*` en el HTML final (solo limpiar `data-gbn-schema/config`). Fix adicional en `dom.js` para verificación case-insensitive de atributos existentes.
- ✅ **PostRenderProcessor - Duplicación de posts en frontend**: El procesador no detectaba contenido ya procesado, causando multiplicación de posts en cada recarga. Se añadió detección de PostItems con `data-post-id` y extracción del template original (primer PostItem) limpiando atributos de datos previos.
- ✅ PostField - Hidratación en panel (lectura de `gloryPostField`)
- ✅ PostRenderProcessor - `categoryFilter` undefined
- ✅ Docking Persistente (Theme Settings)
- ✅ Colores de paleta global en negro
- ✅ Placeholder de imagen roto y gigante
- ✅ Visibilidad de atributos internos (Data Leak)
- ✅ Estilos de borde en imagen (overflow)
- ✅ Dirty HTML & atributos malformados
- ✅ Persistencia de hover (especificidad)
- ✅ Estilos de layout rotos (frontend deslogeado)

---

## 4. Roadmap de Trabajo Futuro

### 🐛 Bugs Pendientes

#### Inconsistencia de Estilos en FormComponent (Editor vs Frontend)
**Prioridad:** Media-Baja  
**Estado:** Pendiente de solución

**Síntoma:** El formulario se ve diferente en el editor GBN (1 columna) vs frontend sin GBN (2 columnas con grid layout).

**Causa raíz:** 
- El archivo `forms.css` contiene estilos que solo se cargan en el editor GBN
- En el editor, el formulario usa `style="gap: 16px"` (solo gap, sin display ni grid-template-columns)
- En el frontend sin GBN, se genera correctamente: `style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;"`
- El componente depende de estilos CSS del editor que no están disponibles en frontend

**Evidencia:**
```html
<!-- CON GBN (Editor) -->
<form gloryform style="gap: 16px" ...>

<!-- SIN GBN (Frontend) -->
<form gloryform style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;" ...>
```

**Archivos afectados:**
- `Glory/src/Gbn/assets/css/forms.css` → Solo carga en editor
- `Components/Form/FormComponent.php` → Configuración de grid layout
- `assets/js/ui/renderers/form.js` → Aplicación de estilos inline

**Solución propuesta:**
1. Asegurar que todos los estilos de layout críticos se apliquen como inline styles
2. Que `forms.css` solo contenga estilos auxiliares del panel/editor, no estilos funcionales
3. O alternativamente, cargar selectivamente reglas de `forms.css` en el frontend

---

#### Filtro por Categoría en PostRender
**Prioridad:** Baja  
**Estado:** Para investigación

**Síntoma:** Al activar el filtro por categoría en PostRender, no hace nada.

**Archivos a investigar:**
- `post-render-frontend.js`
- `PostRenderProcessor.php`

---

#### ✅ RESUELTO: PostRender Preview WYSIWYG - Pulir interacciones
**Prioridad:** Media  
**Estado:** ✅ Resuelto (Diciembre 2025)

**Contexto:** Se implementó un preview WYSIWYG que muestra todos los posts reales en el editor. El primer post es el template editable y los demás son clones sincronizados con MutationObserver.

**Problemas resueltos:**
1. ~~**No se puede editar el template**~~ - CSS bloqueaba `pointer-events` en el template
2. ~~**Los clones parpadean al pasar el mouse**~~ - El MutationObserver se disparaba con eventos hover/focus
3. ~~**Interacción en clones no deshabilitada**~~ - Los clones permitían interacción incorrectamente

**Solución implementada (multi-capa):**

**Archivos modificados:**
- `post-render.js` - Lógica de clones y MutationObserver
- `interactive.css` - Reglas CSS que bloqueaban interacción

**1. Corrección CSS crítica en `interactive.css`:**
   - **ANTES (incorrecto):** `[data-gbn-is-template] { pointer-events: none }` bloqueaba el template editable
   - **AHORA:** Template editable, solo clones con `pointer-events: none !important`
   - Badge visual "✏️ TEMPLATE (editable)" para indicar cuál es el template

**2. Clones no interactivos (en `post-render.js`):**
   - `pointer-events: none` en clones para deshabilitar toda interacción
   - `opacity: 0.85` para indicar visualmente que son clones (no editables)
   - Limpieza de `data-gbn-id` de clones para evitar conflictos con el store

**3. MutationObserver inteligente:**
   - Filtrado de mutaciones: solo sincroniza en cambios estructurales reales (`childList`)
   - Ignora cambios de atributos de hover/selección del editor (`gbn-selected`, `gbn-hovered`, `gbn-simulated`)
   - Ignora cambios de estilo inline (son temporales, hover CSS nativo)
   - Solo observa atributos relevantes: `class`, `src`, `href`, `alt`

**4. Sincronización estable:**
   - Debounce aumentado de 100ms a 300ms para dar tiempo a interacciones del usuario
   - Flag `_isSyncing` para prevenir sincronización recursiva
   - Verificación de `clone.parentNode` antes de reemplazar (safety check)

**5. Re-escaneo de elementos después del preview:**
   - Los elementos del template se re-escanean para asegurar que están en el store de GBN
   - Necesario porque el preview AJAX modifica elementos después del escaneo inicial

**Resultado:** Template editable sin parpadeo, clones visuales estables, interacción fluida.


### 🔍 Investigación Arquitectónica (SOLID / Centralización)

> [!NOTE]
> Estas tareas requieren análisis profundo antes de implementar. No resolver directamente.

#### Iconos SVG Repetidos en Componentes
**Problema:** Los iconos SVG se especifican en cada componente individualmente, causando código repetitivo e inconsistencias potenciales.

**Síntoma observado:**
- Los iconos de "Modo de Visualización" en PostRender son diferentes a los de "Layout" en DivPrincipal
- Mismo concepto (layout flex/grid) → diferentes iconos

**Preguntas a resolver:**
- ¿Existe un registro centralizado de iconos?
- ¿Se pueden compartir iconos entre componentes?
- ¿Deberíamos crear un `IconRegistry` similar a `registry.js` de campos?

**Propuesta inicial:**
```
assets/js/ui/icons/
├── index.js          → Registro centralizado
├── layout-icons.js   → Iconos de layout (flex, grid, columns)
├── action-icons.js   → Iconos de acciones (edit, delete, move)
└── state-icons.js    → Iconos de estados (normal, hover, focus)
```

**Archivos afectados:**
- `components/Principal/PrincipalComponent.php`
- `components/PostRender/PostRenderComponent.php`
- `panel-render.js` (state selector)
- Todos los renderers con iconos

---

#### Opciones de Layout No Centralizadas
**Problema:** Las opciones de "Modo de Visualización" (PostRender) y "Layout" (DivPrincipal) no comparten la misma fuente, aunque conceptualmente son lo mismo.

**Preguntas a resolver:**
- ¿Por qué PostRender tiene opciones diferentes?
- ¿Debería existir un trait `HasLayoutOptions` compartido?
- ¿Los esquemas de layout deberían definirse en un solo lugar?

**Hipótesis:**
- PostRender fue desarrollado con opciones específicas para su caso de uso
- No se reutilizó `HasFlexbox` o `HasGrid` correctamente

**Acción requerida:** Auditar diferencias entre:
- `PrincipalComponent::buildSchema()` → opciones de layout
- `PostRenderComponent::buildSchema()` → opciones de display_mode

---

### ⚠️ Tareas Prioritarias (Roadmap Inmediato)

#### ✅ RESUELTO: Integración de Componentes Hijos en Biblioteca y Constructor
**Prioridad:** Alta  
**Estado:** ✅ Implementado (Diciembre 2025)

**Problema original:**
1. El botón "+" no mostraba componentes hijos específicos al hacer clic dentro de FormComponent o PostRender.
2. Los componentes hijos (Input, Textarea, Select, Submit, PostItem, PostField) no aparecían en la Biblioteca.

**Solución implementada:**

1. **Método `getAllowedChildren()` en la Interfaz y Clase Base:**
   - `ComponentInterface.php`: Agregado método a la interfaz
   - `AbstractComponent.php`: Implementación base que devuelve `[]`

2. **Implementación en Componentes Contenedores:**
   - `FormComponent.php`: `['input', 'textarea', 'select', 'submit', 'secundario']`
   - `PostRenderComponent.php`: `['postItem']`
   - `PostItemComponent.php`: `['postField', 'text', 'image', 'secundario', 'button']`
   - `PrincipalComponent.php`: `['secundario']`
   - `SecundarioComponent.php`: `['secundario', 'text', 'image', 'button', 'form', 'postRender']`

3. **Exposición al Frontend:**
   - `ComponentLoader.php`: Incluye `allowedChildren` en el registro
   - `ContainerRegistry.php`: Expone `allowedChildren` en `resolveSingle()` y `rolePayload()`
   - `gloryGbnCfg.containers[role].allowedChildren` disponible en JS

4. **JavaScript Dinámico:**
   - `inspector.js`: Nueva función `getAllowedChildrenForRole(role)` que consulta la configuración del PHP
   - Fallback a defaults razonables para compatibilidad

**Archivos modificados:**
- `Components/ComponentInterface.php`
- `Components/AbstractComponent.php`
- `Components/Form/FormComponent.php`
- `Components/PostRender/PostRenderComponent.php`
- `Components/PostRender/PostItemComponent.php`
- `Components/Principal/PrincipalComponent.php`
- `Components/Secundario/SecundarioComponent.php`
- `Components/ComponentLoader.php`
- `Config/ContainerRegistry.php`
- `assets/js/ui/inspector.js`

**Uso:**
Ahora al hacer clic en "+" dentro de un FormComponent, la biblioteca muestra solo: Input, Textarea, Select, Submit, Secundario.
Al hacer clic en "+" dentro de un PostRender, muestra solo: PostItem.
Al hacer clic en "+" dentro de un PostItem, muestra: PostField, Text, Image, Secundario, Button.

**Notas futuras:**
- El drag-drop aún no valida si el destino acepta el componente (pendiente)
- Considerar agregar validación visual al arrastrar sobre contenedores inválidos

---

#### ✅ RESUELTO: Integración del Menú Contextual con Componentes Hijos
**Prioridad:** Alta  
**Estado:** ✅ Implementado (Diciembre 2025)

**Problema original:**
Al hacer click derecho sobre un componente, el menú contextual no mostraba un botón "+" inteligente que respetara las relaciones padre-hijo definidas en `getAllowedChildren()`.

**Solución implementada:**

1. **Centralización de `getAllowedChildrenForRole()` en `utils.js`:**
   - Movida la función de `inspector.js` a `core/utils.js` para evitar duplicación
   - Consulta `gloryGbnCfg.containers[role].allowedChildren` del PHP
   - Fallbacks razonables para compatibilidad

2. **Refactorización de `inspector.js`:**
   - Ahora delega a `utils.getAllowedChildrenForRole(role)`
   - Código más limpio y centralizado

3. **Modificación de `context-menu.js`:**
   - Añadido método `getAllowedChildrenForRole()` que delega a utils
   - Añadido botón "+" en el header de cada bloque del menú contextual
   - El botón solo aparece si `getAllowedChildren().length > 0`
   - Al hacer clic, abre `Gbn.ui.library.open()` con posición `'append'` y roles filtrados
   - Estilos CSS para el nuevo botón (`.gbn-ctx-add`)

**Archivos modificados:**
- `assets/js/core/utils.js` → Nueva función `getAllowedChildrenForRole()`
- `assets/js/ui/inspector.js` → Refactorizado para usar `utils.getAllowedChildrenForRole()`
- `assets/js/ui/context-menu.js` → Nuevo botón "+" con lógica de filtrado

**Comportamiento implementado:**

| Click derecho en...   | Botón "+" muestra...                                              |
| :-------------------- | :---------------------------------------------------------------- |
| Principal             | `['secundario']`                                                  |
| Secundario            | `['secundario', 'text', 'image', 'button', 'form', 'postRender']` |
| FormComponent         | `['input', 'textarea', 'select', 'submit', 'secundario']`         |
| PostRender            | `['postItem']`                                                    |
| PostItem              | `['postField', 'text', 'image', 'secundario', 'button']`          |
| Input/Text/Image/etc. | Sin botón "+" (no aceptan hijos)                                  |

**Notas:**
- El drag-drop aún no valida si el destino acepta el componente (pendiente futuro)
- El sistema es extensible: cualquier nuevo componente que implemente `getAllowedChildren()` funcionará automáticamente

> [!WARNING]
> **Bug conocido (Baja prioridad):** El botón "+" en componentes hoja (text, image, input, postField, etc.) no aparece, pero debería mostrar los hijos permitidos del **padre**. Por ejemplo, al hacer clic derecho en un título (text) dentro de un PostItem, debería mostrar el "+" con los componentes permitidos del PostItem padre. Ver tarea pendiente: "Mejora del Botón '+' en Menú Contextual para Componentes Hoja".

---

#### 🟡 Mejora del Botón '+' en Menú Contextual para Componentes Hoja
**Prioridad:** Baja  
**Estado:** Pendiente

**Problema identificado:**
Al hacer clic derecho en componentes que no aceptan hijos (text, image, input, postField, button, etc.), el botón "+" no aparece. Sin embargo, **debería aparecer mostrando los hijos permitidos del componente padre**.

**Ejemplo del bug:**
- Usuario hace clic derecho en un título (text) dentro de un PostItem
- El menú NO muestra botón "+" porque `text` no tiene `allowedChildren`
- **Comportamiento esperado:** Debería mostrar "+" con los hijos del padre (PostItem): `['postField', 'text', 'image', 'secundario', 'button']`

**Solución propuesta:**

1. En `context-menu.js`, si `allowedChildren.length === 0`:
   - Buscar el padre más cercano que SÍ tenga `allowedChildren`
   - Usar `block.element.parentElement.closest('[data-gbn-id]')` para encontrar el padre
   - Obtener el bloque padre del store y usar sus `allowedChildren`

2. Cambiar la posición de inserción de `'append'` a `'after'` cuando se usa el contexto del padre

**Archivos a modificar:**
- `assets/js/ui/context-menu.js` → `renderContent()` sección del botón "+"

---


#### ✅ RESUELTO: Sistema de Notificación por Correo para Formularios GBN
**Prioridad:** Media  
**Estado:** ✅ Implementado (Diciembre 2025)

**Objetivo:** Sistema automático de envío de correos al administrador cuando se envía un formulario GBN.

**Arquitectura implementada:**

1. **AJAX Handler:** `Gbn/Ajax/Handlers/FormSubmitHandler.php`
   - Recibe submissions de formularios GBN vía AJAX
   - Valida honeypot anti-spam
   - Rate limiting básico por IP (5 segundos entre envíos)
   - Sanitiza datos del formulario
   - Formatea contenido en HTML con diseño moderno
   - Usa `EmailUtility::sendToAdmins()` para enviar correo

2. **Frontend Script:** `assets/js/frontend/form-submit.js`
   - Intercepta submit de formularios con `data-ajax-submit="true"`
   - Valida formulario (HTML5 nativo)
   - Inyecta campo honeypot automáticamente
   - Muestra estados de carga en botón submit
   - Muestra mensajes de éxito/error estilizados
   - Auto-inicializa y observa DOM para formularios dinámicos

3. **Configuración en FormComponent:**
   - Campo: "Asunto del Email" con placeholders (`{{formId}}`, `{{siteName}}`)
   - Los mensajes de éxito/error ya existían

**Flujo implementado:**
```
[Frontend] Usuario envía form con data-ajax-submit="true"
     ↓
[JS] form-submit.js intercepta, valida, muestra loading
     ↓
[JS] fetch a wp-ajax endpoint 'gbn_form_submit'
     ↓
[PHP] FormSubmitHandler::handle()
     ↓
[PHP] Validar honeypot + rate limit + sanitizar datos
     ↓
[PHP] Formatear HTML → EmailUtility::sendToAdmins($subject, $htmlBody)
     ↓
[PHP] wp_send_json_success(['message' => '¡Formulario enviado!'])
     ↓
[JS] Mostrar mensaje de éxito, resetear formulario
```

**Archivos creados:**
- `Gbn/Ajax/Handlers/FormSubmitHandler.php`
- `assets/js/frontend/form-submit.js`

**Archivos modificados:**
- `Ajax/Registrar.php` → Registrar endpoint AJAX (wp_ajax + nopriv)
- `GbnManager.php` → Cargar script frontend para todos los usuarios
- `Components/Form/FormComponent.php` → Agregar campo emailSubject
- `assets/js/ui/renderers/form.js` → Manejar data attribute emailSubject

**Uso automático:**
```html
<!-- El formulario envía correo automáticamente al admin -->
<form gloryForm data-form-id="contacto" data-ajax-submit="true">
    <div gloryInput><input type="text" name="nombre" required></div>
    <div gloryInput><input type="email" name="email" required></div>
    <div gloryTextarea><textarea name="mensaje"></textarea></div>
    <button type="submit" glorySubmit>Enviar</button>
</form>
```

---

#### ✅ RESUELTO: PostRender Dinámico (No Estático)
**Prioridad:** CRÍTICA  
**Estado:** ✅ Implementado (Diciembre 2025)

**Problema original:**
Al guardar cambios en PostRender, el contenido se volvía estático. Los posts no se actualizaban cuando había nuevos posts. Los cambios en la estructura del template (mover título, etc.) no se persistían.

**Causa raíz:**
`PostRenderProcessor.php` procesaba el contenido ANTES de que el editor GBN cargara, por lo que el editor veía HTML con posts clonados en lugar del template original.

**Solución implementada (Multi-capa):**

1. **`PostRenderProcessor.php`** - Detección de modo editor:
   - Nuevo método `isEditorMode()` que detecta si el usuario puede editar
   - Si está en modo editor, `processContent()` retorna el HTML SIN procesar
   - El editor ve el template original (PostItem), no los posts clonados
   - El procesamiento dinámico solo ocurre en frontend para usuarios no-editores

2. **`persistence.js`** - Limpieza antes de guardar:
   - Nueva función `cleanPostRenderClones()` que limpia clones de preview JS
   - Elimina banners informativos y badges del editor
   - Limpia atributos de procesamiento previo

3. **`post-render.js`** - Preview en editor:
   - `requestPreview()` genera clones temporales para vista previa
   - Los clones tienen `data-gbn-pr-clone` para identificarlos
   - Banner informativo: "Modo Plantilla: Los cambios afectan a todos los posts"
   - Selectores case-insensitive para compatibilidad DOM

**Flujo corregido:**
```
[PHP] Template original (sin procesar para editores) →
[Editor GBN] Ve el template, JS genera preview con clones →
[Usuario edita] Cambios afectan el template original →
[Guardar] persistence.js limpia clones JS →
[DB] Solo template guardado →
[Frontend público] PostRenderProcessor ejecuta query dinámica
```

---

#### ✅ RESUELTO: Componentes de Formulario
**Prioridad:** Alta  
**Estado:** ✅ Implementado (Diciembre 2025) - Fase 14

**Objetivo:** Versión sencilla y minimalista pero funcional.

**Componentes implementados:**
- [x] `FormComponent` → Contenedor `<form>` con action/method, AJAX submit, honeypot anti-spam
- [x] `InputComponent` → Input text, email, tel, number, password, url con validación HTML5
- [x] `TextareaComponent` → Área de texto con filas configurables y maxlength
- [x] `SelectComponent` → Dropdown con opciones en formato simple (valor:etiqueta)
- [x] `SubmitComponent` → Botón submit con texto de loading configurable

**Archivos creados:**

**PHP (Components):**
- `Components/Form/FormComponent.php`
- `Components/Form/InputComponent.php`
- `Components/Form/TextareaComponent.php`
- `Components/Form/SelectComponent.php`
- `Components/Form/SubmitComponent.php`

**JS (Renderers):**
- `assets/js/ui/renderers/form.js`
- `assets/js/ui/renderers/input.js`
- `assets/js/ui/renderers/textarea.js`
- `assets/js/ui/renderers/select.js`
- `assets/js/ui/renderers/submit.js`

**Archivos modificados:**
- `GbnManager.php` → Registro de scripts y dependencias
- `panel-render.js` → Resolvers de estilos y supportedRoles
- `roles.js` → Fallback selectors para detección de elementos
- `components.css` → Estilos base para campos de formulario

**Uso en HTML:**
```html
<form gloryForm method="post" data-form-id="contacto">
    <div gloryInput><label>Nombre</label><input type="text" name="nombre" required></div>
    <div gloryInput><label>Email</label><input type="email" name="email" required></div>
    <div gloryTextarea><label>Mensaje</label><textarea name="mensaje" rows="4"></textarea></div>
    <button type="submit" glorySubmit>Enviar</button>
</form>
```

**Características:**
- Validación frontend HTML5 (required, type, pattern)
- Soporte para AJAX submit (configurable)
- Honeypot anti-spam integrado
- Estilos consistentes con otros componentes GBN
- Estados hover/focus editables desde el panel
- Responsive por defecto

---

#### 🟠 Plantillas de Layout: Header y Footer
**Prioridad:** Alta  
**Estado:** Requiere diseño arquitectónico

**Objetivo:** Header y Footer editables como "páginas especiales" siguiendo lógica similar a PostRender.

**Características deseadas:**
- Modificables desde panel Y desde código (como PostRender)
- Página especial dedicada para editar Header
- Página especial dedicada para editar Footer
- Acceso directo desde Theme Settings

**Arquitectura propuesta:**
```
/wp-admin/admin.php?page=gbn-edit-header  → Editar Header
/wp-admin/admin.php?page=gbn-edit-footer  → Editar Footer
```

**Flujo:**
1. Usuario accede a página especial de Header
2. GBN carga el HTML del header como contenido editable
3. Cambios se guardan en `wp_options` (gbn_header_template)
4. Frontend renderiza header desde template guardado

**Referencia:** Evaluar lógica útil de `Glory/src/Components/Header/`

**Archivos nuevos estimados:**
- `pages/HeaderEditorPage.php`
- `pages/FooterEditorPage.php`
- `services/TemplateService.php` → Guardar/cargar templates

---

#### 🟡 Plantillas SinglePage para PostTypes
**Prioridad:** Media (después de Header/Footer)  
**Estado:** Diseño conceptual

**Objetivo:** Siguiendo principios de PostRender, poder editar plantillas para páginas individuales de posts (single-post.php, single-{cpt}.php).

**Concepto:**
- Página especial para editar plantilla de "Single Post"
- Página especial para editar plantilla de "Single {CPT}"
- Campos dinámicos similares a PostField (`[title]`, `[content]`, `[featured_image]`, etc.)

**Arquitectura similar a PostRender:**
```
/wp-admin/admin.php?page=gbn-edit-single&post_type=post
/wp-admin/admin.php?page=gbn-edit-single&post_type=producto
```

**Diferencia con PostRender:**
- PostRender = Listado de posts (archive)
- SinglePage = Vista individual de UN post (single)

**Dependencias:**
- Requiere que Header/Footer estén implementados
- Reutilizar `PostFieldComponent` para campos dinámicos

---

### ⏳ Pendientes Confirmados (Backlog)

#### Fase 9: Transform con Iconos para Botones
**Objetivo:** Exponer transformaciones CSS con presets visuales.

**Tareas:**
- [ ] Crear `iconGroup` para transforms comunes (`skewX`, `scale`, `rotate`)
- [ ] Implementar en `ButtonComponent.php`
- [ ] Sincronización CSS: detectar transforms de clases existentes
- [ ] Preview en panel con iconos representativos

#### Refactorización UI Dimensions Panel
**Problema:** Diseño inconsistente con el resto del panel.

**Tarea:**
- [ ] Actualizar `dimensions.js` con iconos SVG, grid layout y estilo consistente con `spacing.js`

---

## 5. Arquitectura de Sincronización de Estilos (Crítico)

### Jerarquía de Prioridad (Cascade)

```
[ NIVEL 1: ALTA PRIORIDAD ]
  A. Panel Builder (style="...")  B. Clases Personalizadas (.mi-clase)
              ↓                                  ↓
         [ NIVEL 2: BASE DEL COMPONENTE ]
                  Clase Base (.gbn-principal)
                          ↓
               Consume Variable Global
                  (color: var(--gbn-color))
                          ↓
         [ NIVEL 3: CONFIGURACIÓN DEL TEMA ]
                  Theme Settings Panel
                  Define: --gbn-color: black
```

### Reglas de Oro (Ver `reglas.md`)

1. **Sincronización Universal:** TODAS las opciones default deben leer su variante en CSS y sincronizarse.
2. **Fallback Limpio:** Si no tiene valor en CSS, dejar que la cascada actúe (usar `undefined` o `var(--gbn-*)`).
3. **Herencia Global:** Theme Settings modifica componentes sin valor fijo.
4. **Defaults en CSS, NO en JS:** `style-composer.js` solo emite si hay config explícita.

### Ciclo de Vida del Estilo

```
    [ CARGA / RECARGA ]
           ↓
    +------+------+
    |  NAVEGADOR  | → Renderiza HTML + CSS
    +------+------+
           ↓
    +------+------+
    |   JS (GBN)  | → Lee getComputedStyle() (Verdad Visual)
    +------+------+
           ↓
    +------+------+
    |    PANEL    | → Muestra valores reales
    +------+------+
           ↓ (Usuario edita)
    +------+------+
    |   JS (GBN)  | → Inyecta style="..." (Gana a CSS)
    +------+------+
           ↓
    [ PERSISTENCIA ] → Guarda HTML
           ↓
    (Vuelta al inicio)
```

---

## 6. Guía Rápida: Crear Nuevo Componente

> [!TIP]
> Consulta `guia-crear-componente.md` para la documentación completa paso a paso.

### Checklist Mínimo

1. **PHP:**
   - [ ] Crear clase en `components/MiComponente/MiComponenteComponent.php`
   - [ ] Implementar `getRole()`, `getLabel()`, `getIcon()`, `getSelector()`, `buildSchema()`
   - [ ] Usar traits para opciones comunes (`HasSpacing`, `HasBorder`, etc.)

2. **JS:**
   - [ ] Crear renderer en `assets/js/ui/renderers/mi-componente.js`
   - [ ] Implementar `getStyles(config, block)` usando `traits.getCommonStyles()`
   - [ ] Implementar `handleUpdate(block, path, value)` usando `traits.handleCommonUpdate()`

3. **Registro:**
   - [ ] Agregar script en `GbnManager.php` (`$builderScripts`)
   - [ ] Agregar como dependencia de `panel-render`
   - [ ] Agregar resolver en `panel-render.js` (`styleResolvers`)
   - [ ] [Opcional] Agregar fallback selector en `roles.js`

4. **Validación:**
   - [ ] Panel muestra campos correctamente
   - [ ] Cambios se aplican en tiempo real
   - [ ] Cambios persisten al guardar
   - [ ] Funciona en frontend sin GBN activo

---

## 7. Mantenimiento del Plan

### Protocolo de Actualización

**Cuando agregar secciones:**
- Nueva fase arquitectónica (ej: Fase 14)
- Bug crítico resuelto con lección aprendida
- Cambio de reglas o principios (actualizar `reglas.md` también)

**Cuando condensar:**
- Detalles de bugs antiguos (>3 meses) movidos a historial comprimido
- Tareas completadas con detalles excesivos → resumir en tabla
- Diagramas de flujo repetitivos → unificar en uno solo

**Archivos hermanos obligatorios:**
- `reglas.md`: Principios inmutables, arquitectura core
- `documentación-gbn.md`: Guías de uso, configuración, troubleshooting
- `guia-crear-componente.md`: Tutorial paso a paso
- `plan_control.md`: Roadmap del GBN Control Center (diagnóstico interno)

---

## 8. Cumplimiento SOLID y Defensa Anti-Regresiones

### Principios SOLID Aplicados

| Principio | Implementación                       | Ejemplo                                                      |
| :-------- | :----------------------------------- | :----------------------------------------------------------- |
| **SRP**   | Service/Component/Renderer separados | `PostRenderService`, `PostRenderComponent`, `post-render.js` |
| **OCP**   | Campos extensibles vía `registry.js` | `panel-fields/registry.js`                                   |
| **LSP**   | Strategy Pattern para PostField      | `TitleStrategy`, `MetaStrategy`, `AcfStrategy`               |
| **ISP**   | Traits específicos                   | `HasBorder`, `HasTypography` (no un mega-trait)              |
| **DIP**   | Abstracciones (`ComponentInterface`) | `AbstractComponent`, `FieldStrategyInterface`                |

### Flujo de Datos Unidireccional (MANDATORIO)

```
 Usuario cambia valor en Panel
         ↓
 Gbn.actions.updateBlock(id, { key: value }, breakpoint)
         ↓
 Store actualiza config en memoria
         ↓
 Subscriber detecta cambio → renderer.handleUpdate()
         ↓
 Renderer solicita recalc de estilos
         ↓
 StyleComposer genera CSS completo
         ↓
 styleManager aplica al DOM

 ⚠️ PROHIBIDO: Modificar DOM directamente desde Panel
```

---

## 9. Métricas de Salud del Sistema

**Comando de diagnóstico rápido:**
```bash
# Snapshot completo del sistema (API REST)
GET /wp-admin/admin-ajax.php?action=gbn_diagnostics_dump

# Validación de integridad
GET /wp-admin/admin-ajax.php?action=gbn_diagnostics_validate
```

**Indicadores clave:**
- **Total de Componentes Registrados:** 8 activos
- **Tamaño Payload `gloryGbnCfg`:** ~84KB (Óptimo: <100KB)
- **Trait más usado:** `HasSpacing` (presente en 7/8 componentes)
- **Health Score esperado:** >95/100 (Verde)

**Ver detalles en:**
- UI: `/wp-admin/admin.php?page=gbn-control-panel`
- API: Ver `plan_control.md` para endpoints y estructura JSON

---

## 10. Historial Comprimido (Pre-Diciembre 2025)

### Fases Fundacionales (Completadas)

- **Fase 1-3:** Infraestructura, migración de componentes base, limpieza legacy
- **Fase 4:** Refactorización JS (StyleComposer, applicator, registry)
- **Fase 5:** Roadmap de Estabilidad (Store centralizado, validación, error boundaries, remote logger, debug overlay)
- **Fase 6:** Expansión de biblioteca (Imagen, Botón, Biblioteca inteligente, Custom CSS, Docking, Configuración de página)
- **Fase 6.5:** Posicionamiento y dimensiones avanzadas (HasPositioning, Smart Controls)
- **Fase 7:** Transparencia en colores (RGBA + slider)
- **Fase 8:** Editor de texto dual (Visual/Code) + text-shadow
- **Fase 9.1:** Refactorización ButtonComponent (diseño nativo, sin `opciones` redundante)

### Bugs Históricos Erradicados (Resumen)

- Bug 8-11, 24-35: Persistencia responsive, parsing de esquemas, JSON serialization, flexbox herencia, grid defaults, margin, iconos, flash de contenido
- Refinamiento de dimensiones (min-height/min-width)
- Fix unidades REM
- Salida HTML limpia (data-gbn-schema solo en editor)
- Página de prueba (constructor)
- GlobalControls refactorizado (Singleton anti-freeze)
- Tipografía en tiempo real (TextComponent)

**Ver detalles históricos completos en versiones anteriores de este archivo (Git).**

---

**Última actualización:** Diciembre 2025  
**Versión del plan:** 2.0 (Resumido)  
**Mantenedor:** Ver `reglas.md` para protocolo de cambios
