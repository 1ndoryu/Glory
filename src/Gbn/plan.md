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

---

## 2. Bugs Críticos Resueltos (Referencia Rápida)

> [!NOTE]
> Estos bugs han sido **erradicados** y documentados para evitar regresiones. Ver secciones detalladas en historial.

### Defensa Arquitectónica Implementada

| ID   | Bug Histórico                           | Defensa Aplicada                                          | Archivo Clave                           |
| :--- | :-------------------------------------- | :-------------------------------------------------------- | :-------------------------------------- |
| 🛡️ 1  | Layout Delay/Flash                      | Defaults en CSS vars, no JS hardcoded                     | `style-composer.js`, `theme-styles.css` |
| 🛡️ 2  | Data Leak (Atributos internos visibles) | Solo limpiar `data-gbn-schema/config`, preservar `glory*` | `GbnManager.php`                        |
| 🛡️ 3  | Estilos rotos en frontend               | Atributos `glory*` preservados para selectores CSS        | `theme-styles.css`, `GbnManager.php`    |
| 🛡️ 4  | Race Condition (Async)                  | Async Guards en callbacks AJAX                            | `panel-core.js`, `post-render.js`       |
| 🛡️ 5  | Memory Leak (Listeners)                 | Cleanup explícito + Map de handlers activos               | `theme/render.js`                       |
| 🛡️ 6  | Persistencia Responsive                 | Breakpoint explícito en `updateBlock()`                   | `panel-render.js`                       |
| 🛡️ 7  | Especificidad CSS                       | Prefijo `body` en CSS generado                            | `style-generator.js`                    |
| 🛡️ 8  | Defaults vs Computed                    | Detección de browser defaults                             | `utils.js`, `fraction.js`               |
| 🛡️ 10 | Crash JSON Circular                     | Excluir DOM de serialización                              | `persistence.js`                        |
| 🛡️ 11 | Grid Columns sin unidades               | Lista `UNITLESS_PROPERTIES`                               | `applicator.js`                         |
| 🛡️ 15 | MockBlocks sin elemento DOM             | Verificar `block.element` antes de `getComputedStyle`     | `color.js`                              |
| 🛡️ 16 | Stale Block Reference (Pérdida datos)   | `state.get(block.id)` antes de `cloneConfig()`            | `panel-render.js`                       |
| 🛡️ 17 | Estados sin propiedades CSS             | Agregar props a `cssDirectProps` en `style-generator.js`  | `style-generator.js`                    |

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

---

## 3. Cambios Recientes (Últimos 30 Días)

### ✅ Diciembre 2025 - Highlights

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

#### Bugs Resueltos (Últimos)
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

(Sin bugs pendientes actualmente)

---

### ⏳ Pendientes Confirmados

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
