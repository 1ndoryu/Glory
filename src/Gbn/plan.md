# GBN - Plan de Trabajo

> [!TIP] LEER SIEMPRE CUANDO SE RECIBA ESTE MD LOS SIGUIENTES MD: 
> *   **Reglas y Arquitectura:** Ver `reglas.md`.
> *   **Documentacion:** Ver `documentacion-gbn.md`.
> *   **Refactor Activo (Dic 2025):** Ver `refactor-transparencia-css.md` - Roadmap de limpieza CSS.

---

## 1. Estado Actual del Sistema

### Infraestructura Completada
- **Core:** ComponentLoader, SchemaBuilder, AbstractComponent, ContainerRegistry
- **Traits PHP:** HasFlexbox, HasSpacing, HasTypography, HasGrid, HasBorder, HasBackground, HasPositioning, HasCustomCSS  
- **Traits JS:** `renderer-traits.js` con funciones reutilizables
- **Store Centralizado:** Redux-lite con flujo unidireccional (`state.js`, `store-subscriber.js`)
- **Sistema de Paneles:** Modular con tabs, campos auto-registrables (`registry.js`)
- **Responsive:** Breakpoints, herencia mobile-tablet-desktop
- **Estados Hover/Focus:** Lectura/escritura de pseudo-clases CSS
- **Diagnostico:** Remote Logger, Debug Overlay (`Ctrl+Alt+D`), GBN Control Center

### Componentes Activos (18 total)

| Componente          | Selector               | Traits Principales                              |
| :------------------ | :--------------------- | :---------------------------------------------- |
| PrincipalComponent  | `[gloryDiv]`           | Flexbox, Grid, Spacing, Background, Positioning |
| SecundarioComponent | `[gloryDivSecundario]` | Flexbox, Spacing, Background, Positioning       |
| TextComponent       | `[gloryTexto]`         | Typography, Spacing, Border, Background         |
| ButtonComponent     | `[gloryButton]`        | Typography, Spacing, Border, Background         |
| ImageComponent      | `[gloryImagen]`        | Spacing, Border, Dimensions                     |
| PostRenderComponent | `[gloryPostRender]`    | Flexbox, Grid, Spacing, Background              |
| PostItemComponent   | `[gloryPostItem]`      | Flexbox, Spacing, Background, Border            |
| PostFieldComponent  | `[gloryPostField]`     | N/A (Semantic)                                  |
| FormComponent       | `[gloryForm]`          | Spacing, Background, Border                     |
| InputComponent      | `[gloryInput]`         | Spacing, Border, Typography                     |
| TextareaComponent   | `[gloryTextarea]`      | Spacing, Border, Typography                     |
| SelectComponent     | `[glorySelect]`        | Spacing, Border                                 |
| SubmitComponent     | `[glorySubmit]`        | Spacing, Border, Typography                     |
| HeaderComponent     | `[gloryHeader]`        | Spacing, Background, Positioning                |
| LogoComponent       | `[gloryLogo]`          | Spacing, Typography                             |
| MenuComponent       | `[gloryMenu]`          | Spacing, Typography                             |
| FooterComponent     | `[gloryFooter]`        | Spacing, Background, Grid                       |
| MenuItemComponent   | `[gloryMenuItem]`      | Spacing, Typography                             |
| TarjetaComponent    | `[gloryTarjeta]`       | Flexbox, Grid, Spacing, Background, Positioning |

---

## 2. Defensas Anti-Regresion (Reference Rapida)

> [!CAUTION]
> Bugs criticos erradicados. Documentados para evitar regresiones.

| ID   | Bug Historico                    | Defensa Aplicada                                             | Archivo Clave                |
| :--- | :------------------------------- | :----------------------------------------------------------- | :--------------------------- |
| 1    | Layout Delay/Flash               | Defaults en CSS vars, no JS hardcoded                        | `style-composer.js`          |
| 2    | Data Leak (Atributos internos)   | Solo limpiar `data-gbn-schema/config`, preservar `glory*`    | `GbnManager.php`             |
| 3    | Estilos rotos en frontend        | Atributos `glory*` preservados para selectores CSS           | `GbnManager.php`             |
| 4    | Race Condition (Async)           | Async Guards en callbacks AJAX                               | `panel-core.js`              |
| 5    | Memory Leak (Listeners)          | Cleanup explicito + Map de handlers                          | `theme/render.js`            |
| 6    | Persistencia Responsive          | Breakpoint explicito en `updateBlock()`                      | `panel-render.js`            |
| 7    | Especificidad CSS                | Prefijo `body` en CSS generado                               | `style-generator.js`         |
| 11   | Scope Global (Shared References) | Deep clone en `getRoleDefaults()` Y en `ADD_BLOCK`           | `roles.js`, `store.js`       |
| 18   | Elementos editables bloqueados   | NUNCA `pointer-events: none` en elementos editables          | `interactive.css`            |
| 19   | Elementos AJAX no interactivos   | Re-escanear con `Gbn.content.scan()`                         | `post-render.js`             |
| 20   | Hijos GBN anidados perdidos      | Detectar hijos GBN antes de sobrescribir innerHTML           | `text.js`                    |
| 21   | Theme Panel inyecta +100 vars    | Separar savedSettings de defaults, usar `applySingleValue()` | `render.js`, `applicator.js` |

**Checklist Obligatorio (Pre-Codigo):**
- [ ] No defaults duros en JS
- [ ] Limpiar atributos internos
- [ ] Async guards en AJAX
- [ ] Cleanup de listeners
- [ ] Breakpoint en updateBlock
- [ ] Deep clone en ADD_BLOCK (store)
- [ ] Verificar `block.element` antes de `getComputedStyle`
- [ ] Verificar hijos GBN antes de sobrescribir innerHTML
- [ ] NUNCA `pointer-events: none` en elementos editables
- [ ] Re-escanear elementos cargados por AJAX
- [ ] TRANSPARENCIA CSS: No inyectar estilos que afecten elementos del desarrollador

---

## 3. Arquitectura de Sincronizacion de Estilos

### Jerarquia de Prioridad (Cascade)

```
[ NIVEL 1: ALTA PRIORIDAD ]
  A. Panel Builder (style="...")  B. Clases Personalizadas (.mi-clase)
              ↓                                  ↓
         [ NIVEL 2: BASE DEL COMPONENTE ]
                  Clase Base (.gbn-principal)
                          ↓
               Consume Variable Global (var(--gbn-color))
                          ↓
         [ NIVEL 3: CONFIGURACION DEL TEMA ]
                  Theme Settings Panel
```

### Ciclo de Vida del Estilo

```
[ CARGA ] → NAVEGADOR (HTML + CSS) → JS (Lee getComputedStyle) → PANEL (Muestra valores)
    ↑                                                                    ↓
  Vuelta                               PERSISTENCIA ← JS (Inyecta style inline)
```

---

## 4. Guia Rapida: Crear Nuevo Componente

> Ver `guia-crear-componente.md` para documentacion completa.

### Checklist Minimo

1. **PHP:** Crear clase con `getRole()`, `getLabel()`, `getIcon()`, `getSelector()`, `buildSchema()`
2. **PHP:** Usar traits para opciones comunes
3. **PHP:** Implementar `getAllowedChildren()` SI es contenedor
4. **JS:** Crear renderer con `getStyles()` y `handleUpdate()` usando traits
5. **Registro:** `GbnManager.php` + `panel-render.js` (styleResolvers)

---

## 5. Metricas de Salud

**Comando de diagnostico:**
```bash
GET /wp-admin/admin-ajax.php?action=gbn_diagnostics_dump
GET /wp-admin/admin-ajax.php?action=gbn_diagnostics_validate
```

**Indicadores clave:**
- Total Componentes: 18 activos
- Payload `gloryGbnCfg`: ~84KB (Optimo: <100KB)
- Health Score esperado: >95/100

**UI:** `/wp-admin/admin.php?page=gbn-control-panel`

---

---

# TAREAS PENDIENTES (Consolidado)

> [!IMPORTANT]
> Seccion unica con todas las tareas pendientes organizadas por prioridad.
> Ultima actualizacion: 7 Diciembre 2025

---

## BUGS CRITICOS

### BUG-022: Sistema de Vista Responsive No Refleja Comportamiento Real
**Estado:** PENDIENTE (Solucion Definida) | **Prioridad:** MAXIMA

**Problema:** El sistema de cambio de vista (desktop/tablet/mobile) solo cambia `max-width` del contenedor, NO activa las media queries CSS del desarrollador.

**Solucion aprobada: IFRAME REAL**
1. Crear iframe con dimensiones reales del dispositivo
2. Cargar misma pagina con `?gbn-preview=1`
3. Las media queries funcionan nativamente dentro del iframe
4. Sincronizar cambios via postMessage

**Archivos a modificar:** `responsive.js`, `dock.js`, nuevo `preview-frame.js`

**Ver detalles en:** `refactor-transparencia-css.md`

---

### BUG-013: Filtros PostRender Invisibles en Constructor
**Estado:** PENDIENTE | **Prioridad:** Media

Los filtros activados en PostRender no aparecen visualmente en el constructor, pero si en frontend.

---

### BUG-008: Filtro por Categoria en PostRender No Funciona
**Estado:** POSPUESTO | **Razon:** Ignorar hasta que el usuario indique trabajar en esto

---

## REFACTORIZACIONES

### REFACTOR-001: Eliminacion de CSS Innecesarios
**Estado:** PENDIENTE | **Prioridad:** CRITICA

> [!CAUTION]
> Este refactor implementa la filosofia de Transparencia CSS documentada en `reglas.md`.

**Objetivo:**
1. ELIMINAR estilos de componentes de `theme-styles.css`
2. ELIMINAR variables CSS globales en `:root` de `gbn.css`
3. CONSERVAR SOLO estilos del editor (panel, dock, overlay)

**Ver detalles en:** `refactor-transparencia-css.md`

---

### REFACTOR-011: Sistema de Preview Responsive con Iframe Real
**Estado:** PENDIENTE | **Prioridad:** ALTA

Resuelve BUG-022. Ver `refactor-transparencia-css.md` para roadmap completo.

---

### REFACTOR-006: Iconos de Formulario
**Estado:** PENDIENTE | **Prioridad:** Media

Rehacer iconos para: Campo de Texto, Selector, Boton Enviar. Usar `IconRegistry`.

---

### REFACTOR-009: Auditoria y Centralizacion de Iconos (DRY)
**Estado:** PENDIENTE | **Prioridad:** Media

Auditar componentes que usan SVGs hardcodeados y migrar a `IconRegistry`.

---

### REFACTOR-011 (Duplicado): Auditoria Nombres de Campos JS vs PHP
**Estado:** PENDIENTE | **Prioridad:** Media

Auditar renderers JS y comparar nombres de campos con `SchemaConstants.php`.

---

### REFACTOR-010: Migracion de Campos Hover Legacy
**Estado:** BLOQUEADO | **Prioridad:** Baja

**Bloqueador:** El sistema de estados actual solo aplica estilos al elemento principal del bloque, no a elementos internos (ej: links dentro de menu).

---

## FEATURES PENDIENTES

### FEATURE-001: Mejora Boton '+' en Menu Contextual
**Estado:** Pendiente | **Prioridad:** Baja

El "+" en componentes atomicos deberia mostrar hijos del padre.

---

### FEATURE-002: Plantillas SinglePage para PostTypes
**Estado:** Diseno conceptual | **Prioridad:** Media

Editar plantillas `single-post.php` visualmente.

---

### FEATURE-003: Transform con Iconos para Botones
**Estado:** Pendiente | **Prioridad:** Baja

Crear `iconGroup` para transforms comunes (`skewX`, `scale`, `rotate`).

---

### FEATURE-004: Refactorizacion UI Dimensions Panel
**Estado:** Pendiente | **Prioridad:** Baja

---

### FEATURE-005: Bordes Avanzados
**Estado:** PENDIENTE | **Prioridad:** Media

Control direccional independiente (Top, Right, Bottom, Left).

---

### FEATURE-006: Estilo Especifico para Input (Inner)
**Estado:** PENDIENTE | **Prioridad:** Media

Estilizar el elemento `<input>` interno separado del contenedor.

---

### FEATURE-007: Herencia Tipografia Formulario
**Estado:** PENDIENTE | **Prioridad:** Media

El padre FormComponent maneja fuentes, inputs heredan por defecto.

---

### FEATURE-008: Rediseno UI Field Dimensions
**Estado:** PENDIENTE | **Prioridad:** Baja

---

---

# HISTORIAL DE BUGS RESUELTOS (Compacto)

> [!NOTE]
> Referencia rapida de bugs criticos resueltos con causa y solucion. Para detalles ver Git history.

| ID      | Bug                             | Causa Raiz                                                                            | Solucion                                            |
| :------ | :------------------------------ | :------------------------------------------------------------------------------------ | :-------------------------------------------------- |
| BUG-001 | ImageComponent no detectado     | Inconsistencia nomenclatura `gloryImage` vs `gloryImagen`                             | Estandarizar a `gloryImagen`                        |
| BUG-002 | Tabs duplicados en Theme Panel  | `cleanupCurrentMode()` no limpiaba area de tabs                                       | Agregar limpieza explicita                          |
| BUG-003 | Padding reset al abrir panel    | `css-sync.js` muy limitado                                                            | Refactorizar para leer todos roles/props            |
| BUG-004 | Iconos inconsistentes           | SVGs sin width/height estandar                                                        | Estandarizar todos a 24x24                          |
| BUG-005 | ButtonComponent sin estilos     | Faltaban estilos base                                                                 | Agregar en `theme-styles.css`                       |
| BUG-006 | Tabs sin iconos                 | Minusculas vs mayusculas en nombres                                                   | Estandarizar tabs                                   |
| BUG-007 | FormComponent inconsistente     | Sin opciones layout                                                                   | Agregar trait `HasLayoutOptions`                    |
| BUG-009 | MenuComponent fuentes           | Renderer no usaba traits tipografia                                                   | Usar `traits.getTypographyStyles()`                 |
| BUG-010 | Menu responsive en constructor  | Header fixed ignora root simulado                                                     | Nueva funcion `applyViewportToLayoutComponents()`   |
| BUG-011 | Scope global en padding botones | **DOS puntos de fallo**: shallow copy en roles.js + referencia compartida en store.js | Deep clone en AMBOS lugares                         |
| BUG-012 | Z-index ImageComponent          | Void elements no pueden tener hijos                                                   | Cambiar estructura a `<div gloryImagen><img></div>` |
| BUG-014 | PostRender Layout Flex/Gap      | Inconsistencia nombres campos PHP vs JS                                               | Usar nombres canonicos de SchemaConstants           |
| BUG-015 | LogoComponent selector modo     | `logoMode` faltaba en conditionalTriggers                                             | Agregar a lista de triggers                         |
| BUG-017 | Campos panel sin valor          | Faltaba buscar en defaults del schema PHP                                             | Nueva busqueda en `effective-value.js`              |
| BUG-018 | Serializacion DOM corrompe HTML | Logica de reordenamiento movia solo nodos GBN                                         | Eliminar reordenamiento innecesario                 |
| BUG-019 | Imagenes PostRender perdidas    | Solo se guardaba innerHTML, no style                                                  | Guardar/restaurar `data-gbn-original-style`         |
| BUG-020 | gloryTexto hijos perdidos       | innerHTML sobrescrito sin verificar hijos                                             | Detectar hijos GBN antes de sobrescribir            |
| BUG-021 | Theme Panel inyecta +100 vars   | Merge de defaults tratado como valores guardados                                      | Separar savedSettings, usar `applySingleValue()`    |

---

# REFACTORIZACIONES COMPLETADAS (Compacto)

| Refactor     | Descripcion                                 | Beneficio                                                                    |
| :----------- | :------------------------------------------ | :--------------------------------------------------------------------------- |
| REFACTOR-002 | Centralizacion logica tabs                  | `tabs-manager.js` Singleton                                                  |
| REFACTOR-003 | Archivos grandes divididos                  | `post-render.js`, `inspector.js`, `panel-core.js`, `PostRenderProcessor.php` |
| REFACTOR-004 | LogoComponent compliance                    | Uso de traits y SchemaBuilder                                                |
| REFACTOR-005 | FooterComponent compliance                  | SchemaBuilder + traits estandar                                              |
| REFACTOR-008 | Deteccion automatica triggers condicionales | PHP extrae triggers, JS lee dinamicamente                                    |

---

**Ultima actualizacion:** 7 Diciembre 2025  
**Version del plan:** 5.0 (Compactado)  
**Mantenedor:** Ver `reglas.md` para protocolo de cambios
