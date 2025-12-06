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

## 3. Cambios Recientes (Diciembre 2025) - Resumen Compacto

### ✅ Fases Completadas

| Fase     | Funcionalidad                  | Componentes / Archivos Clave                                                                                                                       |
| :------- | :----------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------- |
| **15**   | Header/Footer Editables        | `HeaderComponent`, `LogoComponent`, `MenuComponent`, `FooterComponent`, `MenuItemComponent` + renderers JS + `TemplateService.php` + páginas admin |
| **14.5** | Notificación Email Formularios | `FormSubmitHandler.php`, `form-submit.js` (honeypot, rate-limit, AJAX)                                                                             |
| **14**   | Componentes de Formulario      | `FormComponent`, `InputComponent`, `TextareaComponent`, `SelectComponent`, `SubmitComponent` + renderers JS                                        |
| **13**   | PostRender Dinámico            | `PostRenderComponent`, `PostItemComponent`, `PostFieldComponent`, `PostRenderProcessor`, `PostRenderService`                                       |
| **11**   | Refactorización SOLID          | `renderer-traits.js` (~350 líneas compartidas), reducción 50-60% en text/button.js                                                                 |
| **10**   | Estados Hover/Focus            | `config._states`, `state-styles.js`, `style-generator.js`, simulación visual                                                                       |

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

### ✅ Integraciones Completadas

| Feature                 | Descripción                                        | Archivos Clave                                                                             |
| :---------------------- | :------------------------------------------------- | :----------------------------------------------------------------------------------------- |
| **Componentes Hijos**   | `getAllowedChildren()` en PHP expuesto a JS        | `ComponentInterface`, `AbstractComponent`, `ContainerRegistry`, `inspector.js`, `utils.js` |
| **Menú Contextual +**   | Botón "+" inteligente según relaciones padre-hijo  | `context-menu.js`, `utils.js`                                                              |
| **PostRender WYSIWYG**  | Preview con clones, MutationObserver inteligente   | `post-render.js`, `interactive.css`                                                        |
| **PostRender Dinámico** | `isEditorMode()` para preservar template en editor | `PostRenderProcessor.php`, `persistence.js`                                                |

### ✅ Bugs Críticos Resueltos (Diciembre)

| Bug                             | Solución                                                  | Archivo                             |
| :------------------------------ | :-------------------------------------------------------- | :---------------------------------- |
| Clones parpadean / interactivos | `pointer-events: none`, debounce 300ms, flag `_isSyncing` | `post-render.js`, `interactive.css` |
| PostRender estático al guardar  | `isEditorMode()` retorna template sin procesar            | `PostRenderProcessor.php`           |
| Hover/Focus spacing no persiste | Agregar props camelCase a `cssDirectProps`                | `style-generator.js`                |
| Stale block reference           | `state.get(block.id)` antes de `cloneConfig()`            | `panel-render.js`                   |
| Atributos glory* borrados       | Preservar `glory*`, limpiar solo `data-gbn-*` internos    | `GbnManager.php`, `dom.js`          |
| PostRender duplicación          | Detectar `data-post-id` existente                         | `PostRenderProcessor.php`           |

**Otros bugs menores resueltos:** PostField hidratación, categoryFilter undefined, Docking persistente, colores paleta, placeholder imagen, Data Leak, border overflow, dirty HTML, hover especificidad, layout frontend deslogeado.

---

## 4. Roadmap de Trabajo Futuro

### 🐛 Bugs Pendientes

#### Inconsistencia de Estilos en FormComponent (Editor vs Frontend)
**Prioridad:** Media-Baja | **Estado:** Pendiente

El formulario usa `gap: 16px` en editor pero necesita `display: grid; grid-template-columns: 1fr 1fr` en frontend. **Solución:** Asegurar estilos de layout como inline styles o cargar `forms.css` selectivamente.

**Archivos:** `forms.css`, `FormComponent.php`, `form.js`

---

#### Filtro por Categoría en PostRender
**Prioridad:** Baja | **Estado:** Para investigación

El filtro no hace nada al activarse. **Archivos:** `post-render-frontend.js`, `PostRenderProcessor.php`

---

### 🔍 Investigación Arquitectónica

#### Iconos SVG Repetidos
**Problema:** Iconos definidos en cada componente individualmente. **Propuesta:** Crear `IconRegistry` centralizado en `assets/js/ui/icons/`.

#### Layout Options No Centralizadas
**Problema:** PostRender "Modo de Visualización" vs DivPrincipal "Layout" no comparten fuente. **Acción:** Auditar `buildSchema()` de ambos componentes.

---

### ⚠️ Tareas Pendientes

#### 🟡 Mejora Botón '+' en Menú Contextual para Componentes Hoja
**Prioridad:** Baja | **Estado:** Pendiente

Al hacer clic derecho en componentes atómicos (text, image, input), el "+" debería mostrar hijos del **padre**. **Solución:** Buscar padre con `allowedChildren` y usar posición `'after'`.

**Archivo:** `context-menu.js`

---

#### 🟡 Plantillas SinglePage para PostTypes
**Prioridad:** Media | **Estado:** Diseño conceptual

Editar plantillas `single-post.php` y `single-{cpt}.php` visualmente. Similar a PostRender pero para vista individual. Reutilizar `PostFieldComponent`.

**Dependencia:** Header/Footer implementados ✅

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
