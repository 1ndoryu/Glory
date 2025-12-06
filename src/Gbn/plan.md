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
| HeaderComponent     | `header`     | `[gloryHeader]`        | Spacing, Background, Positioning                | ✅      |
| LogoComponent       | `logo`       | `[gloryLogo]`          | Spacing, Typography                             | ✅      |
| MenuComponent       | `menu`       | `[gloryMenu]`          | Spacing, Typography                             | ✅      |
| FooterComponent     | `footer`     | `[gloryFooter]`        | Spacing, Background, Grid                       | ✅      |
| MenuItemComponent   | `menuItem`   | `[gloryMenuItem]`      | Spacing, Typography                             | ✅      |

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
| 🛡️ 21 | Scope Global (Shared References)        | Deep clone en `getRoleDefaults()` Y en `ADD_BLOCK` action        | `roles.js`, `store.js`                  |

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
- [ ] **Deep clone completo en ADD_BLOCK (store): config, styles, meta**

---

## 3. Cambios Recientes (Diciembre 2025) - Resumen Compacto

### ✅ Fases Completadas

| Fase      | Funcionalidad                  | Componentes / Archivos Clave                                                                                                                       |
| :-------- | :----------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------- |
| **15**    | Header/Footer Editables        | `HeaderComponent`, `LogoComponent`, `MenuComponent`, `FooterComponent`, `MenuItemComponent` + renderers JS + `TemplateService.php` + páginas admin |
| **14.5**  | Notificación Email Formularios | `FormSubmitHandler.php`, `form-submit.js` (honeypot, rate-limit, AJAX)                                                                             |
| **14**    | Componentes de Formulario      | `FormComponent`, `InputComponent`, `TextareaComponent`, `SelectComponent`, `SubmitComponent` + renderers JS                                        |
| **13**    | PostRender Dinámico            | `PostRenderComponent`, `PostItemComponent`, `PostFieldComponent`, `PostRenderProcessor`, `PostRenderService`                                       |
| **12**    | Modernización MenuComponent    | Refactor completo: SchemaBuilder, `HasTypography`, iconGroups, nombres canónicos (`layout`), `MenuWalker` compatible.                              |
| **10-11** | Estandarización Schema         | `Option::gap()`, `Option::condition()` canónico `[field, op, val]`, normalización de tipos y operadores.                                           |
| **7-9**   | Refactor Icons & Layout        | `IconRegistry` (PHP/JS), `HasLayoutOptions`, `HasDimensions`, eliminación de SVGs inline duplicados.                                               |

### ✅ Mejoras UI/UX Implementadas
- Smart Dimension Control (input + presets)
- Transparencia RGBA + slider opacidad
- Editor Dual Visual/Code
- Context Menu con `elementsFromPoint()`
- Dimensions Grid 2x2 con iconos SVG
- Border Colapsable con toggle
- Persistencia de pestaña activa y scroll
- Font-weight en Typography
- CSS Specificity con `:where()` en `init.css`
- Variables CSS en `interactive.css`
- **Sistema de Iconos Centralizado**: +50 SVGs únicos gestionados por `IconRegistry` y `Icons` JS.
- **Layout Unificado**: Opciones de Grid/Flex/Block consistentes en todos los componentes.

### ✅ Integraciones Completadas

| Feature                 | Descripción                                        | Archivos Clave                                                                             |
| :---------------------- | :------------------------------------------------- | :----------------------------------------------------------------------------------------- |
| **Componentes Hijos**   | `getAllowedChildren()` en PHP expuesto a JS        | `ComponentInterface`, `AbstractComponent`, `ContainerRegistry`, `inspector.js`, `utils.js` |
| **Menú Contextual +**   | Botón "+" inteligente según relaciones padre-hijo  | `context-menu.js`, `utils.js`                                                              |
| **PostRender WYSIWYG**  | Preview con clones, MutationObserver inteligente   | `post-render.js`, `interactive.css`                                                        |
| **PostRender Dinámico** | `isEditorMode()` para preservar template en editor | `PostRenderProcessor.php`, `persistence.js`                                                |
| **IconRegistry**        | Single Source of Truth para iconos SVG             | `IconRegistry.php`, `ui/icons/index.js`                                                    |

### ✅ Bugs Críticos Resueltos (Diciembre)

| Bug                               | Solución                                                  | Archivo                             |
| :-------------------------------- | :-------------------------------------------------------- | :---------------------------------- |
| Clones parpadean / interactivos   | `pointer-events: none`, debounce 300ms, flag `_isSyncing` | `post-render.js`, `interactive.css` |
| PostRender estático al guardar    | `isEditorMode()` retorna template sin procesar            | `PostRenderProcessor.php`           |
| Hover/Focus spacing no persiste   | Agregar props camelCase a `cssDirectProps`                | `style-generator.js`                |
| Stale block reference             | `state.get(block.id)` antes de `cloneConfig()`            | `panel-render.js`                   |
| Atributos glory* borrados         | Preservar `glory*`, limpiar solo `data-gbn-*` internos    | `GbnManager.php`, `dom.js`          |
| PostRender duplicación            | Detectar `data-post-id` existente                         | `PostRenderProcessor.php`           |
| Iconos inconsistentes             | Centralización en `IconRegistry` (PHP/JS)                 | `Traits/*.php`, `panel-fields/*.js` |
| Opciones Layout duplicadas        | Trait `HasLayoutOptions` unificado                        | `HasLayoutOptions.php`              |
| Variables CSS no leídas (BUG-003) | Refactor `css-sync.js` para leer todos roles/props        | `css-sync.js`                       |
| Tabs sin iconos (BUG-006)         | Estandarización nombres tabs + mapa iconos                | `LogoComponent.php`, `tabs.js`      |
| Logo Tipo/Estilos (BUG-016)       | Refactor CSS Variables + Fix DOM update logic             | `logo.js`, `theme-styles.css`       |

**Otros bugs menores resueltos:** PostField hidratación, categoryFilter undefined, Docking persistente, colores paleta, placeholder imagen, Data Leak, border overflow, dirty HTML, hover especificidad, layout frontend deslogeado.

---

## 4. Arquitectura de Sincronización de Estilos (Crítico)

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

## 5. Guía Rápida: Crear Nuevo Componente

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

## 6. Cumplimiento SOLID y Defensa Anti-Regresiones

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

## 7. Métricas de Salud del Sistema

**Comando de diagnóstico rápido:**
```bash
# Snapshot completo del sistema (API REST)
GET /wp-admin/admin-ajax.php?action=gbn_diagnostics_dump

# Validación de integridad
GET /wp-admin/admin-ajax.php?action=gbn_diagnostics_validate
```

**Indicadores clave:**
- **Total de Componentes Registrados:** 18 activos
- **Tamaño Payload `gloryGbnCfg`:** ~84KB (Óptimo: <100KB)
- **Trait más usado:** `HasSpacing` (presente en 15/18 componentes)
- **Health Score esperado:** >95/100 (Verde)

**Ver detalles en:**
- UI: `/wp-admin/admin.php?page=gbn-control-panel`
- API: Ver `plan_control.md` para endpoints y estructura JSON

---

## 8. Historial Comprimido (Pre-Diciembre 2025)

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

## 9. Mantenimiento del Plan

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

---

# 📋 TAREAS PENDIENTES (Consolidado)

> [!IMPORTANT]
> **SECCIÓN ÚNICA** con todas las tareas pendientes organizadas por prioridad.
> Última actualización: Diciembre 2025

---

## 🔴 BUGS CRÍTICOS (Prioridad Alta)

### BUG-001: ImageComponent No Detectado por Inspector
**Estado:** ✅ RESUELTO | **Fecha:** Diciembre 2025

~~El componente `gloryImagen` no es detectado por el inspector. Los elementos `<img gloryImagen>` no responden a clicks ni aparecen en el árbol de bloques.~~

**Causa raíz:** Inconsistencia de nomenclatura entre archivos:
- `ImageComponent.php` definía selector como `gloryImage` (sin "n")
- `roles.js` tenía fallback como `gloryImagen` (con "n")
- Esto causaba que el scanner no detectara los elementos correctamente

**Solución aplicada:**
- Se estandarizó a `gloryImagen` (con "n") en todos los archivos
- Archivos corregidos: `ImageComponent.php` (selector + template), `theme-styles.css`

**Archivos:** `ImageComponent.php`, `roles.js`, `theme-styles.css`

---

### BUG-002: Tabs Duplicados en Panel de Configuración del Tema
**Estado:** ✅ RESUELTO | **Fecha:** Diciembre 2025

~~Los tabs del panel "Configuración del Tema" aparecen duplicados **después** de haber abierto el panel de un componente.~~

**Causa raíz:**
- La función `cleanupCurrentMode()` no limpiaba el área de tabs `.gbn-panel-header-tabs-area` del header
- Cuando cambiabas de un bloque (que renderiza tabs en el header) a Theme Settings (que no usa tabs), las tabs del bloque anterior persistían visualmente

**Solución aplicada:**
- Se agregó limpieza explícita del área de tabs y estados en `cleanupCurrentMode()`
- Ahora la transición entre modos siempre limpia: tabs del header, estados del footer, clases de simulación, y bloque activo

**Archivos:** `panel-core.js`

---

### BUG-003: Padding Reset al Abrir Panel (Variables CSS No Leídas)
**Estado:** ✅ RESUELTO | **Fecha:** Diciembre 2025

~~Al definir defaults via variables CSS en `:root`, los valores se aplican visualmente pero el panel de configuración **no los carga**.~~

**Causa raíz:**
El módulo `css-sync.js` estaba muy limitado:
- Solo leía 2 roles (`principal`, `secundario`)
- Solo leía 3 propiedades (`padding`, `background`, `gap`)

**Solución aplicada (Refactorización completa css-sync.js):**
1. **Lectura dinámica de roles:** Ahora lee TODOS los roles de `gloryGbnCfg.roleSchemas` (~18 componentes)
2. **Lectura dinámica de propiedades:** Usa `CONFIG_TO_CSS_MAP` para leer ~40+ propiedades CSS
3. **Detección de browser defaults:** Usa `isBrowserDefault()` centralizado de `css-map.js`
4. **Soporte para propiedades anidadas:** Maneja correctamente `padding.superior`, `margin.derecha`, etc.
5. **API de debugging:** Expone `getAllRoles()` y `getCssMap()` para diagnóstico

**Archivos modificados:**
- `css-sync.js` (refactorización completa)
- `effective-value.js` (fix de placeholder previo mantiene compatibilidad)

---

### BUG-015: LogoComponent Selector de Modo No Funciona (CONDITIONAL REFRESH)
**Estado:** ✅ RESUELTO | **Fecha:** Diciembre 2025

Selector `logoMode` (Imagen/Texto/SVG) visible pero no actualiza el panel para mostrar los campos condicionales.

**Causa raíz:** `logoMode` faltaba en la lista `conditionalTriggers` de `config-updater.js`.
**Solución:** Se agregó `logoMode` a la lista de triggers.

---

### BUG-011: Scope Global en Padding de Botones
**Estado:** ✅ RESUELTO (DEFINITIVO) | **Fecha:** Diciembre 2025

~~Al cambiar el padding de un botón, el cambio se refleja en **todos** los botones.~~

**Causa raíz (Investigación completa):**
Este bug tenía **DOS puntos de fallo** en la cadena de clonación de datos:

1. **roles.js (getRoleDefaults):** Usaba `utils.assign()` (shallow copy) ✅ YA CORREGIDO
   - Los defaults de componentes compartían objetos anidados como `padding: {}`
   - Fix aplicado: Deep clone con `JSON.parse(JSON.stringify())`

2. **store.js (ADD_BLOCK):** ⚠️ **VERDADERO CULPABLE**
   - Cuando se agregaba un bloque al store, se asignaba directamente sin clonar
   - Línea 73: `nextState.blocks[newBlock.id] = newBlock;` ← REFERENCIA COMPARTIDA
   - Todos los bloques del mismo role compartían el MISMO objeto config en memoria
   - Al editar padding de un botón, se mutaba el objeto compartido → afectaba a todos

**Por qué el fix anterior fue insuficiente:**
- El deep clone en `getRoleDefaults()` creaba copias independientes AL MOMENTO DE LLAMARLO
- Pero cuando el bloque se agregaba al store con `ADD_BLOCK`, se asignaba por referencia
- Resultado: Los bloques creados en la misma "oleada" compartían el config clonado

**Solución aplicada (DEFINITIVA):**
- **store.js líneas 70-108:** Deep clone completo en `ADD_BLOCK` action
  - Config: `JSON.parse(JSON.stringify(config))` - copia profunda
  - Styles: Clonación manual de `inline` y `current`
  - Meta: Shallow clone (solo valores simples)
  - Element y Schema: Mantener referencias (no deben clonarse)

**Validación:**
1. Crear 3 botones: `<a href="#" gloryButton>Botón 1</a>`, `<a href="#" gloryButton>Botón 2</a>`, etc.
2. Editar padding del Botón 1
3. Verificar que Botón 2 y 3 NO se vean afectados ✅

**Archivos modificados:** 
- `roles.js` (Fix parcial - líneas 133-142)
- `store.js` (Fix definitivo - líneas 70-108)

---

### BUG-004: Iconos Inconsistentes en Biblioteca de Componentes
**Estado:** ✅ RESUELTO | **Fecha:** Diciembre 2025

~~Algunos iconos se ven más grandes que otros (ej: Logo, Menu, Menu Item vs Área de Texto, Header, Imagen).~~

**Causa raíz:**
- Algunos iconos SVG usaban solo `viewBox="0 0 24 24"` sin atributos `width/height`
- Otros iconos tenían `width="24" height="24" viewBox="0 0 24 24"`
- Esta inconsistencia causaba que el navegador renderizara los SVGs de manera diferente

**Solución aplicada:**
1. Se estandarizaron TODOS los iconos para incluir `width="24" height="24" viewBox="0 0 24 24"`
2. Los estilos CSS en `modals.css` ya forzaban `32px !important` para uniformidad
3. Componentes corregidos: `LogoComponent`, `HeaderComponent`, `MenuComponent`, `MenuItemComponent`, `FooterComponent`

**Archivos modificados:** 
- `LogoComponent.php`, `HeaderComponent.php`, `MenuComponent.php`, `MenuItemComponent.php`, `FooterComponent.php`
- `modals.css` (fix CSS previo, líneas 157-174)

---

### BUG-005: ButtonComponent Sin Estilos Default
**Estado:** ✅ RESUELTO | **Fecha:** Diciembre 2025

~~El componente `gloryButton` no tiene clases CSS default ni estilos base. Aparece como texto plano.~~

**Solución aplicada:**
- Se agregaron estilos base en `theme-styles.css` para `[gloryButton]` y `.gbn-button`
- Variables CSS disponibles: `--gbn-button-*` (display, padding, font, colors, border, etc.)
- Estilos para estados `:hover`, `:focus`, `:active`
- Variante secundaria `.btn-secondary` con estilos de outline
- Usa `:where()` para mantener especificidad 0 y permitir override por clases personalizadas

**Archivos modificados:** `theme-styles.css`

---

### BUG-006: Logo/Header Tabs Sin Iconos y UI Poco Intuitiva
**Estado:** ✅ RESUELTO | **Fecha:** Diciembre 2025

~~Las tabs del panel de Logo/Header no tienen iconos o usan nombres inconsistentes~~

**Causa raíz:**
- Los componentes `LogoComponent.php` y `HeaderComponent.php` usaban tabs en minúscula (`'contenido'`, `'estilo'`)
- El sistema de tabs (`tabs.js`) esperaba mayúscula inicial (`'Contenido'`, `'Estilo'`)

**Solución aplicada:**
1. **Estandarización de tabs en LogoComponent.php:**
   - `'contenido'` → `'Contenido'`
   - `'estilo'` → `'Estilo'`

2. **Estandarización de tabs en HeaderComponent.php:**
   - `'configuracion'` → `'Configuración'`
   - `'estilo'` → `'Estilo'`
   - `'avanzado'` → `'Avanzado'`

3. **Mapa de iconos ya incluye `'Configuración'`** → `'tab.content'`

4. **Normalización case-insensitive mantenida** en `getTabIcon()` como fallback

**Archivos modificados:**
- `LogoComponent.php` (tabs estandarizadas)
- `HeaderComponent.php` (tabs estandarizadas)
- `panel-render/tabs.js` (mapa de iconos completo)

---

### BUG-009: MenuComponent - Fuentes y Hover
**Estado:** ✅ RESUELTO | **Fecha:** 6 Diciembre 2025

**Problemas originales:**
1. ~~No se puede cambiar la fuente del menú~~ → **✅ CORREGIDO**
2. ~~Estructura de "Color Hover" inconsistente~~ → **⏩ Movido a REFACTOR-010**
3. ~~Falta documentación de hover~~ → **⏩ Movido a REFACTOR-010**

**Causa raíz:** El renderer `menu.js` no usaba `traits.getTypographyStyles()` ni `traits.applyTypography()` para la propiedad `font`, solo mapeaba manualmente size/weight/transform.

**Solución aplicada:**
- `getStyles()`: Ahora usa `traits.getTypographyStyles(config.typography)` para incluir font-family
- `handleUpdate()`: Delega propiedades `typography.*` a `traits.applyTypography()`
- Ahora maneja correctamente: **font**, size, weight, lineHeight, letterSpacing, transform

**Archivos modificados:** `assets/js/ui/renderers/menu.js`

**Nota:** La migración completa del sistema hover legacy se documentó en **REFACTOR-010**.

---

### BUG-012: Z-Index en ImageComponent
**Prioridad:** Media | **Estado:** 🔴 PENDIENTE

Las imágenes se superponen a los controles de edición (resize handles, toolbar) en el editor. Probablemente falta contexto de apilamiento (stacking context).

**Acción:** Revisar z-index en CSS del editor para component wrappers vs contenido.

---

### BUG-013: Filtros PostRender Invisibles en Constructor
**Prioridad:** Media | **Estado:** 🔴 PENDIENTE

Los filtros activados en PostRender no aparecen visualmente dentro del constructor, pero sí en el frontend.

**Causa sospechosa:** JS de inicialización de filtros no se dispara o el DOM del editor no coincide con lo esperado por el script.

---

### BUG-014: PostRender Layout (Flex/Gap)
**Prioridad:** Media | **Estado:** 🔴 PENDIENTE

1. Modo de visualización Flex no funciona.
2. Propiedad Gap no aplica.
3. *Nota:* Patrón de Layout pospuesto por instrucción directa.

**Archivos:** `PostRenderProcessor.php`, `post-render.js`

---

## 🟡 BUGS BAJA PRIORIDAD

### BUG-007: Inconsistencia de Estilos en FormComponent (Editor vs Frontend)
**Estado:** ✅ RESUELTO | **Fecha:** Diciembre 2025

~~El formulario usa `gap: 16px` en editor pero necesita `display: grid; grid-template-columns: 1fr 1fr` en frontend.~~

**Causa raíz:**
- El FormComponent no tenía opciones de layout configurables
- Solo tenía estilos fijos `display: flex; flex-direction: column; gap: 16px`
- No había forma de configurar formularios de 2 columnas (grid)

**Solución aplicada:**
1. Agregado trait `HasLayoutOptions` al `FormComponent.php`
2. Implementadas opciones de layout completas: flex (column/row) y grid con columnas configurables
3. Actualizado `formComponents.css` para usar variables CSS (`--gbn-form-*`)
4. Actualizado `form.js` renderer para generar estilos de flex/grid dinámicamente
5. Ahora el usuario puede elegir:
   - **Flex column** (default): Campos apilados verticalmente
   - **Flex row**: Campos en línea horizontal
   - **Grid con N columnas**: Layout de grid con columnas configurables (ej: 2 columnas = `1fr 1fr`)

**Archivos modificados:** `FormComponent.php`, `formComponents.css`, `form.js`

---

### BUG-008: Filtro por Categoría en PostRender No Funciona
**Estado:** 🔒 POSPUESTO | **Razón:** Ignorar hasta que el usuario indique trabajar directamente en esto

El filtro no hace nada al activarse.

**Archivos:** `post-render-frontend.js`, `PostRenderProcessor.php`

---

### BUG-010: MenuComponent Responsive en Constructor
**Estado:** 🔴 PENDIENTE

El tamaño del menú no se actualiza dinámicamente al cambiar la vista (mobile/tablet) en el constructor. ()

---

### BUG-017: Campos del Panel No Muestran Valor Preseleccionado
**Estado:** ✅ RESUELTO | **Fecha:** 6 Diciembre 2025

~~Al abrir el panel de configuración de un componente (ej: LogoComponent), los campos como `logoMode` (iconGroup) no muestran el valor actual del componente.~~

**Causa raíz:**
- `getEffectiveValue()` en `effective-value.js` buscaba valores en:
  1. `block.config` (valor guardado)
  2. `computedStyle` (de clases CSS)
  3. `themeDefault` (valores del tema)
- **FALTABA**: Buscar en los **defaults del schema PHP** (`getDefaults()`)
- Para campos no-CSS como `logoMode`, `fieldType`, etc., el valor nunca se encontraba

**Solución aplicada:**
- **`effective-value.js`** (líneas 116-130): Nueva búsqueda en `gloryGbnCfg.roleSchemas[role].config`
- Si no hay valor en config ni computed, buscar en los defaults del schema PHP
- Nueva fuente de valor: `source: 'schema-default'`

**Archivos modificados:**
- `assets/js/ui/panel-fields/effective-value.js`

---

---

## 🔧 REFACTORIZACIONES ARQUITECTÓNICAS (Fase 17)

### REFACTOR-001: Centralización de Defaults CSS
**Prioridad:** Alta | **Estado:** Diseño conceptual

**Problema:** 
- Los defaults CSS están dispersos en múltiples archivos (`gbn.css`, `theme-styles.css`)
- No es claro dónde agregar defaults para nuevos componentes
- La lógica de lectura de defaults no sigue principios SOLID

**Propuesta:**
1. Mover defaults `:where()` a carpeta de cada componente:
   ```
   components/
   ├── Button/
   │   ├── ButtonComponent.php
   │   └── button-defaults.css  ← NUEVO
   ```

2. Cargar CSS de defaults automáticamente desde ComponentLoader

3. Todos los componentes deben tener acceso directo a defaults desde Theme Settings

4. **Visibility de fuente de valor:**
   - TODOS los inputs del panel deben mostrar si el valor viene de CSS
   - Si no hay valor CSS, mostrar placeholder diferenciado

---

### REFACTOR-002: Centralización de Lógica de Tabs
**Prioridad:** Alta | **Estado:** ✅ COMPLETADO
**Fecha:** Diciembre 2025

**Problema:** La lógica de tabs estaba duplicada/dispersa causando bugs de duplicación.

**Solución Implementada:**
- Creado módulo `ui/tabs-manager.js` con patrón Singleton.
- Unificada lógica de `panel-render/tabs.js` y `theme/utils.js` delegando al manager.
- Centralizado mapa de iconos y normalización de nombres.
- Simplificado mantenimineto: un solo lugar para cambiar orden o iconos de tabs.

---

### REFACTOR-003: Archivos Grandes (Fase 16)
**Prioridad:** Media | **Estado:** ✅ COMPLETADO

Refactorizar archivos que superan las 600 líneas:

- [x] `post-render.js` (802→210 líneas) → Dividido en 5 módulos (Dic 2025)
- [x] `inspector.js` (676→170 líneas) → Dividido en 5 módulos (Dic 2025)
- [x] `panel-core.js` (644→150 líneas) → Dividido en 8 módulos (Dic 2025):
  - `panel-core/state.js` - Estado privado del panel
  - `panel-core/status.js` - Funciones de notificación
  - `panel-core/active-block.js` - Manejo de bloque activo
  - `panel-core/mode-manager.js` - Transiciones de modo
  - `panel-core/dom.js` - Montaje y listeners
  - `panel-core/renderers/block.js` - Render de bloque
  - `panel-core/renderers/theme.js` - Render de tema
  - `panel-core/renderers/page.js` - Render de página
  - `panel-core/renderers/restore.js` - Render de restauración
- [x] `PostRenderProcessor.php` (768→280 líneas) → Dividido en 4 módulos (Dic 2025):
  - `PostFieldProcessor.php` - Procesamiento de campos semánticos [gloryPostField]
  - `PostItemRenderer.php` - Renderizado de items individuales
  - `PostRenderStyles.php` - Generación de CSS scoped y estilos
  - `PostRenderUI.php` - Componentes UI (filtros, paginación, mensajes)

---

### REFACTOR-004: LogoComponent (Compliance)
**Prioridad:** Alta | **Estado:** ✅ COMPLETADO
**Fecha:** Diciembre 2025

Se ha refactorizado el `LogoComponent.php` y `logo.js` para cumplir con los estándares SOLID y utilizar los Traits (`HasSpacing`, `HasTypography`, `HasDimensions`) y `SchemaBuilder`. El renderer JS ahora utiliza `traits.getCommonStyles()` para una gestión de estilos consistente.

---

### REFACTOR-005: FooterComponent (Compliance)
**Prioridad:** Alta | **Estado:** � EN PROGRESO

**ESTADO FINAL:** ✅ COMPLETADO (Diciembre 2025) - Se ha reescrito el `FooterComponent` para implementar `SchemaBuilder` y utilizar los Traits estándar. Se añadieron opciones para variables CSS y se estandarizó `textColor` a `color`.

El componente `FooterComponent` ha sido refactorizado completamente.

---

### REFACTOR-006: Iconos de Formulario
**Prioridad:** Media | **Estado:** 🔴 PENDIENTE

Rehacer iconos para:
- Campo de Texto
- Selector
- Botón Enviar

Están mal diseñados o incorrectos. Usar `IconRegistry`.

---

---

## ⏳ FEATURES PENDIENTES

### FEATURE-001: Mejora Botón '+' en Menú Contextual
**Prioridad:** Baja | **Estado:** Pendiente

Al hacer clic derecho en componentes atómicos (text, image, input), el "+" debería mostrar hijos del **padre**.

**Archivo:** `context-menu.js`

---

### FEATURE-002: Plantillas SinglePage para PostTypes
**Prioridad:** Media | **Estado:** Diseño conceptual

Editar plantillas `single-post.php` y `single-{cpt}.php` visualmente.

**Dependencia:** Header/Footer implementados ✅

---

### FEATURE-003: Transform con Iconos para Botones (Fase 9)
**Prioridad:** Baja | **Estado:** 🔴 Pendiente

- [ ] Crear `iconGroup` para transforms comunes (`skewX`, `scale`, `rotate`)
- [ ] Implementar en `ButtonComponent.php`
- [ ] Sincronización CSS: detectar transforms de clases existentes

---

### REFACTOR-008: Detección Automática de Triggers Condicionales
**Prioridad:** Media | **Estado:** ✅ RESUELTO | **Fecha:** 6 Diciembre 2025

**Problema:** La lista `conditionalTriggers` en `config-updater.js` estaba hardcodeada (violación OCP). Cada nuevo campo condicional requería editar este archivo central.

**Solución Implementada:**

1. **ContainerRegistry.php** - Nuevo método `extractConditionalTriggers(array $schema)`:
   - Analiza el array del schema ya generado
   - Extrae los campos usados como triggers en condiciones (`condicion`)
   - Se ejecuta automáticamente en `rolePayload()`
   - Los triggers se exponen en `gloryGbnCfg.roleSchemas[role].conditionalTriggers`

2. **config-updater.js** - Función `getConditionalTriggers(role)`:
   - Lee triggers desde `gloryGbnCfg.roleSchemas[role].conditionalTriggers`
   - Fallback a lista legacy para compatibilidad durante transición
   - Expuesta en API pública para debugging: `Gbn.ui.panelRender.configUpdater.getConditionalTriggers('logo')`

3. **SchemaBuilder.php** - Método auxiliar `extractConditionalTriggers()`:
   - Permite extraer triggers antes de llamar `toArray()` si se necesita

**Beneficios:**
- ✅ Principio OCP: Agregar campos condicionales en PHP → auto-propagación a JS
- ✅ Sin modificación manual de `conditionalTriggers`
- ✅ Retrocompatible con fallback legacy
- ✅ Debugging: `Gbn.ui.panelRender.configUpdater.getConditionalTriggers('role')`

**Archivos modificados:**
- `Config/ContainerRegistry.php` (nuevo método + rolePayload actualizado)
- `Schema/SchemaBuilder.php` (método auxiliar)
- `assets/js/ui/panel-render/config-updater.js` (lectura dinámica)

**¿Qué revisar para confirmar la corrección?**
1. Abrir el constructor y agregar un componente con campos condicionales (ej: Logo)
2. Cambiar el campo trigger (ej: logoMode de "text" a "image")
3. VERIFICAR: El panel debe refrescarse y mostrar los campos condicionales correctos
4. Abrir consola JS y ejecutar: `Gbn.ui.panelRender.configUpdater.getConditionalTriggers('logo')`
5. VERIFICAR: Debe retornar `['logoMode']` (el trigger extraído del schema)

---

### REFACTOR-010: Migración de Campos Hover Legacy a Sistema de Estados
**Prioridad:** Baja | **Estado:** � EN PROGRESO | **Fecha inicio:** 6 Diciembre 2025
**Origen:** Identificado durante análisis de BUG-009

**Problema:** 
Algunos componentes usan campos hover legacy (ej: `linkColorHover` en MenuComponent) con event listeners manuales en vez del sistema de estados estándar `_states.hover`.

**Componentes afectados:**
- `MenuComponent`: Usa `linkColorHover` + `applyHoverStyles()` manual
- (Auditar otros componentes para campos `*Hover`)

**Migración propuesta:**
1. **PHP:** Eliminar campos `*Hover` del schema
2. **PHP:** Agregar soporte de estados en el componente (ver ButtonComponent como referencia)
3. **JS:** Usar `Gbn.styleManager.applyStateCss(block, 'hover', styles)` en vez de event listeners
4. **Panel:** Los campos de estado ya existen (selector Normal/Hover/Focus en footer)

**Beneficios:**
- Consistencia arquitectónica
- Menos código duplicado
- Estados persisten correctamente en CSS generado
- UI unificada para todos los componentes

**Complejidad:** Media - requiere cambios en PHP y JS, pero el sistema ya existe

---

### FEATURE-004: Refactorización UI Dimensions Panel
**Prioridad:** Baja | **Estado:** 🔴 Pendiente

- [ ] Actualizar `dimensions.js` con iconos SVG, grid layout y estilo consistente con `spacing.js`

---

### FEATURE-005: Bordes Avanzados
**Prioridad:** Media | **Estado:** 🔴 PENDIENTE

Agregar modo avanzado a las opciones de borde para permitir control direccional independiente (Top, Right, Bottom, Left).

---

### FEATURE-006: Estilo Específico para Input (Inner)
**Prioridad:** Media | **Estado:** 🔴 PENDIENTE

En el `InputComponent`, agregar una pestaña/opción específica para estilizar el elemento `<input>` interno (donde se escribe), separado del contenedor.

---

### FEATURE-007: Herencia Tipografía Formulario
**Prioridad:** Media | **Estado:** 🔴 PENDIENTE

Unificar tipografía en `FormComponent`. El padre debe manejar la configuración de fuentes y los inputs hijos deben heredarla por defecto.

---

### FEATURE-008: Rediseño UI Field Dimensions
**Prioridad:** Baja | **Estado:** 🔴 PENDIENTE

El campo `gbn-field-dimensions` no sigue los patrones de diseño UI del resto del panel. Estandarizar visualmente.

---

### REFACTOR-009: Auditoría y Centralización de Iconos (DRY)
**Prioridad:** Media | **Estado:** 🔴 PENDIENTE

**Problema:** Se detectó que algunos componentes (ej: FooterComponent -> columnsLayout) siguen usando SVGs hardcodeados en lugar de `IconRegistry`.
**Acción:**
1. Auditar TODOS los componentes (`components/**/*.php`).
2. Identificar opciones con `icon` o `options` que contengan SVG string.
3. Migrar esos iconos a clases en `Icons/` y usarlos vía `IconRegistry::get()` o `IconRegistry::getGroup()`.

---

---

**Última actualización:** 6 Diciembre 2025  
**Versión del plan:** 4.0 (BUG-017 RESUELTO: Fix para campos sin preselección - defaults del schema)  
**Mantenedor:** Ver `reglas.md` para protocolo de cambios

