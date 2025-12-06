# GBN - Reglas y Arquitectura

> [!IMPORTANT]
> Este documento contiene las **reglas inmutables** y **principios arquitectónicos** del sistema. Cualquier implementación que contradiga este documento se considera un error.

---

## 1. Filosofía y Principios

1. **HTML Limpio**: Sin shortcodes, markup semántico y válido.
2. **Independencia de GBN**: Las páginas funcionan correctamente aunque GBN esté desactivado.
3. **Código Minimalista**: Principios SOLID, evitar duplicación.
4. **Componentes Agnósticos**: Los componentes aceptan progresivamente GBN sin romper su uso independiente.
5. **Sincronización Bidireccional**: Cambios en código o interfaz se reflejan en ambos lados.

---

## 2. Arquitectura del Sistema

### Backend (PHP)

#### Core (`src/Gbn/`)
| Clase                   | Responsabilidad                                        |
| :---------------------- | :----------------------------------------------------- |
| `GbnManager.php`        | Bootstrap, assets, localización (`gloryGbnCfg`)        |
| `GbnAjaxHandler.php`    | Proxy de endpoints AJAX                                |
| `Logger.php`            | Sistema de logs dedicado (`gbn.log`)                   |
| `ComponentLoader.php`   | Escaneo y registro automático de componentes           |
| `AbstractComponent.php` | Clase base con funcionalidad común                     |
| `SchemaBuilder.php`     | API fluida para definir campos del panel               |
| `ContainerRegistry.php` | Registro central (fuente de verdad para `roleSchemas`) |

#### Traits (`src/Gbn/Traits/`)
- `HasFlexbox`, `HasGrid` → Layout
- `HasSpacing`, `HasTypography` → Estilo
- `HasBorder`, `HasBackground` → Decoración
- `HasPositioning` → Posición y z-index
- `HasCustomCSS` → CSS personalizado

#### Sistema AJAX (`src/Gbn/Ajax/`)
| Handler                           | Función                                      |
| :-------------------------------- | :------------------------------------------- |
| `ContentHandler`                  | Facade que redirige a Options/Config/Preview |
| `PageSettingsHandler`             | Configuración de página (`post_meta`)        |
| `ThemeSettingsHandler`            | Configuración global (`wp_options`)          |
| `LibraryHandler`                  | Elementos reutilizables (Headers, Links)     |
| `PostRenderHandler`               | Preview/paginación de PostRender             |
| `DiagnosticsHandler`              | Dump de sistema y validación                 |
| `ConfigHandler`, `OptionsHandler` | Guardado de configuración                    |
| `DomProcessor`                    | Normalización HTML, limpieza de atributos    |

### Frontend (JavaScript)

#### Core (`assets/js/core/`)
- **`state.js`**: Store Central (Redux-lite) - Single Source of Truth
- **`store-subscriber.js`**: Conecta Store con DOM
- **`utils.js`**: Utilidades generales y acceso a configuración

#### Servicios (`assets/js/services/`)
| Servicio             | Función                                      |
| :------------------- | :------------------------------------------- |
| `content.js`         | Facade principal - API pública `Gbn.content` |
| `css-sync.js`        | Sincronización de estilos en tiempo real     |
| `persistence.js`     | Recolección y envío de estado al backend     |
| `responsive.js`      | Breakpoints, herencia, simulación viewport   |
| `style-generator.js` | Genera cadenas CSS para persistencia         |
| `state-styles.js`    | Lectura de pseudo-clases CSS (hover/focus)   |
| `logger.js`          | Remote Logger (desactivado por defecto)      |

**Servicios de Contenido (`content/`):**
- `builder.js` → Construye bloques desde JSON
- `config.js` → Motor de configuración
- `dom.js` → Manipulación directa del DOM
- `hydrator.js` → Convierte HTML estático en bloques interactivos
- `roles.js` → Detecta roles en HTML existente
- `scanner.js` → Escanea DOM buscando elementos editables

#### UI (`assets/js/ui/`)

**Core UI:**
- `panel-core.js` → Ciclo de vida del panel
- `panel-render.js` → Renderiza controles del panel
- `drag-drop.js` → Sistema de arrastrar y soltar
- `library.js` → Biblioteca de componentes
- `inspector.js` → Inspector visual (hover, selección)
- `context-menu.js` → Menú contextual avanzado

**Debug:**
- `debug/overlay.js` → Debug Overlay (`Ctrl+Alt+D`)

**Theme:**
- `theme/applicator.js` → Aplica configuraciones globales (Variables CSS)
- `theme/render.js` → Panel de configuración del tema

**Panel Fields (`panel-fields/`):**
- `registry.js` → Registro auto-registrable (OCP)
- `utils.js` → Utilidades compartidas, `CONFIG_TO_CSS_MAP`
- Campos: `color.js`, `fraction.js`, `icon-group.js`, `rich-text.js`, `select.js`, `slider.js`, `spacing.js`, `text.js`, `toggle.js`, `typography.js`, `dimensions.js`, `image.js`

**Renderers (`renderers/`):**
- **`renderer-traits.js`**: Sistema de traits JS (funciones reutilizables)
- **`style-composer.js`**: Función pura `(config, schema) => css`
- **`shared.js`**: Utilidades compartidas
- Renderers por componente: `principal.js`, `secundario.js`, `text.js`, `button.js`, `image.js`, `post-render.js`, `post-item.js`, `post-field.js`
- Especiales: `page-settings.js`, `theme-settings.js`

---

## 3. Jerarquía de Estilos (Cascade)

> [!IMPORTANT]
> **FUENTE ÚNICA DE VERDAD** para sincronización de estilos.

### Prioridad de Especificidad

```
[ NIVEL 1: ALTA PRIORIDAD (Overrides) ]

A. Panel Builder               B. Clases Personalizadas
   style="color: red;"            .mi-clase { color: blue; }
   (Gana casi siempre)            (Gana a la base por especificidad)
              ↓                              ↓
         [ NIVEL 2: BASE DEL COMPONENTE ]
                  Clase Base del Sistema
                  (.gbn-principal, .gbn-texto)
                          ↓
                  Consume Variable Global
                  (color: var(--gbn-color))
                          ↓
         [ NIVEL 3: CONFIGURACIÓN DEL TEMA ]
                  Theme Settings Panel
                  Define: --gbn-color: black
```

### Reglas de Oro

1. **Sincronización Universal**: TODAS las opciones default deben leer su variante en CSS.
2. **Fallback Limpio**: Si no tiene valor en CSS, usar `undefined` o `var(--gbn-*)`, nunca valores duros.
3. **Herencia Global**: Theme Settings modifica componentes sin valor fijo.
4. **Defaults en CSS, NO en JS**: `style-composer.js` solo emite si hay configuración explícita.

### Ciclo de Vida del Estilo

```
    [ 1. CARGA ]
         ↓
    NAVEGADOR (Renderiza HTML + CSS)
         ↓
    JS (GBN) ← Lee getComputedStyle() [Verdad Visual]
         ↓
    PANEL (Muestra valores reales)
         ↓
    Usuario Edita → "80px"
         ↓
    JS (GBN) → Inyecta style="padding: 80px" [Gana al CSS]
         ↓
    PERSISTENCIA (Guarda HTML)
         ↓
    (Vuelta al inicio)
```

---

## 4. Prevención de Regresiones (Anti-Zombie Bugs)

> [!CAUTION]
> **LEER ANTES DE TOCAR LÓGICA DE ESTADO O UI.**

### Principios Arquitectónicos MANDATORIOS

#### 1. Single Source of Truth
**PROHIBIDO:** Lógica de sincronización dispersa en eventos `change` individuales.  
**MANDATORIO:** Todo cambio debe mutar el **Estado Central (`state.js`)**.

```javascript
// ❌ MAL: Parche rápido que causa desincronización
input.addEventListener('change', function() {
    element.style.padding = this.value;
});

// ✅ CORRECTO: Flujo unidireccional
Gbn.actions.updateBlock(id, { padding: value }, breakpoint);
```

#### 2. Flujo de Datos Unidireccional

```
Usuario cambia valor
    ↓
Gbn.actions.updateBlock(id, { key: value }, breakpoint)
    ↓
State Update en memoria
    ↓
Subscriber detecta cambio → Renderer.handleUpdate()
    ↓
StyleComposer recalcula TODOS los estilos
    ↓
Aplica al DOM
    ↓
Persiste (marca como "dirty")
```

#### 3. Integridad de Datos Responsive

- **Lectura:** Al cambiar breakpoint, pedir al Estado la configuración efectiva.
- **Escritura:** Especificar breakpoint destino: `updateBlock(id, { padding: 20 }, 'tablet')`
- **PROHIBIDO:** Suponer que el estado actual es válido sin recargar.

#### 4. Validación de Theme Settings

- Variables CSS globales (`--gbn-*`) son la **única** forma de comunicar Theme Settings.
- **REGLA:** Todo default en `style-composer.js` debe ser `undefined` o `var(--gbn-...)`.
- **NUNCA:** Valores fijos (`10px`, `flex`, `block`).

#### 5. Protocolo de Registro de Componentes

**Validación Obligatoria antes de exponer en UI:**
1. Role único
2. Selector DOM válido
3. Schema JSON válido

---

## 5. Mapeo de Propiedades (CONFIG_TO_CSS_MAP)

> [!IMPORTANT]
> **OBLIGATORIO:** Toda nueva propiedad de estilo debe agregarse a `utils.js`.

Sin este mapeo, `getComputedValueForPath()` devuelve `undefined` y el panel muestra vacío.

**Detectar Browser Defaults:**
| Propiedad        | Default     | Tratamiento                |
| :--------------- | :---------- | :------------------------- |
| `position`       | `'static'`  | No mostrar como "computed" |
| `z-index`        | `'auto'`    | Ignorar si no hay override |
| `overflow`       | `'visible'` | Ignorar                    |
| `width/height`   | `'auto'`    | Detectar y excluir         |
| `padding/margin` | `'0px'`     | Excluir si exactamente 0   |

**Propiedades sin unidades:**
```javascript
var UNITLESS_PROPERTIES = [
    'gridColumns', 'order', 'zIndex', 
    'opacity', 'flexGrow', 'flexShrink'
];
```

---

## 6. Checklist de Defensa Anti-Regresiones

Antes de escribir código nuevo:

- [ ] No defaults duros en JS (usar `undefined` o variables CSS)
- [ ] Limpiar atributos internos en frontend (`DomProcessor` + filtro)
- [ ] Clases persistentes para CSS (`.gbn-*`)
- [ ] Async guards en callbacks AJAX (verificar contexto post-await)
- [ ] Cleanup de listeners (usar Map para tracking)
- [ ] Hidratación explícita completa (todas las propiedades)
- [ ] Especificidad con prefijo `body` en CSS generado
- [ ] Detectar defaults vs computed (browser defaults)
- [ ] Breakpoint explícito en `updateBlock()`
- [ ] Excluir DOM de serialización JSON
- [ ] Configurar `UNITLESS_PROPERTIES`
- [ ] Placeholders locales (no URLs externas)
- [ ] `overflow:hidden` automático con `border-radius`
- [ ] Estado solo en memoria (`state.js`), no en atributos DOM
- [ ] Verificar `block.element` existe antes de `getComputedStyle`
- [ ] Nuevas props CSS editables en estados → agregar a `cssDirectProps`
- [ ] **NUNCA `pointer-events: none` en elementos editables** (ver Bug 🛡️18)
- [ ] **Re-escanear elementos cargados por AJAX** para registrarlos en store
- [ ] **`overflow: visible`** en contenedores con badges posicionados fuera

---

## 7. Protocolo de Modificación

### Añadir Nueva Propiedad de Estilo

1. Agregar a `CONFIG_TO_CSS_MAP` en `utils.js`
2. Detectar browser default en `getValueSource()` y `getEffectiveValue()`
3. Si usa unidades especiales (%, vh), implementar conversión en el campo
4. Si tiene default en PHP, verificar que no bloquee lectura del DOM
5. Si es unitless, agregar a `UNITLESS_PROPERTIES`
6. **Si es editable en estados hover/focus**, agregar a `cssDirectProps` en `style-generator.js`

### Añadir Propiedad Editable en Estados (Hover/Focus)

> [!CAUTION]
> Sin este paso, la propiedad funcionará en tiempo real pero **NO persistirá** después de guardar.

1. Agregar la propiedad CSS (camelCase) a `cssDirectProps` en `style-generator.js`
2. Verificar que `panel-render.js` mapea correctamente el path a la propiedad CSS
3. Probar: editar propiedad en hover → guardar → recargar → verificar que persiste

**Lista actual de `cssDirectProps` (style-generator.js):**
```javascript
var cssDirectProps = [
    'backgroundColor', 'color', 'borderColor', 'borderWidth', 'borderStyle', 
    'borderRadius', 'transform', 'transition', 'opacity', 'boxShadow',
    'textDecoration', 'cursor', 'fontWeight', 'fontSize', 'fontFamily',
    'lineHeight', 'letterSpacing', 'textTransform', 'textShadow',
    'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
    'marginTop', 'marginRight', 'marginBottom', 'marginLeft'
];
```

### Crear Nuevo Componente

**Ver `guia-crear-componente.md` para detalles completos.**

**Mínimo obligatorio:**
1. Clase PHP con `ComponentInterface`
2. Renderer JS registrado en `GbnManager.php`
3. Resolver en `panel-render.js` (`styleResolvers`)
4. Dependencias correctas (incluir `renderer-traits` si usa traits JS)

### Elementos Cargados por AJAX (Preview Dinámico)

> [!CAUTION]
> Los elementos creados/modificados por AJAX después del escaneo inicial de GBN **NO están registrados en el store** y no serán interactivos (no responden a clicks, no aparecen en inspector).

**Reglas obligatorias:**

1. **Re-escanear después de AJAX:**
   ```javascript
   // Después de cargar/modificar elementos via AJAX
   if (Gbn.content && Gbn.content.scan) {
       var newBlocks = Gbn.content.scan(containerElement);
       console.log('Re-escaneados ' + newBlocks.length + ' elementos');
   }
   ```

2. **NUNCA `pointer-events: none` en elementos editables:**
   - Los clones/previews SÍ deben tener `pointer-events: none`
   - El template/elemento original NUNCA debe tener `pointer-events: none`
   - Cuidado con reglas CSS que usen atributos como `[data-gbn-is-template]`

3. **MutationObserver inteligente:**
   - Filtrar mutaciones de hover/selección del editor (`gbn-selected`, `gbn-hovered`)
   - Ignorar cambios de estilo inline (son temporales)
   - Solo sincronizar en cambios estructurales reales (`childList`)

4. **`overflow: visible` para badges:**
   - Si el componente tiene badges posicionados con `top` negativo
   - Agregar `overflow: visible` al contenedor padre

**Ejemplo de implementación correcta (PostRender):**
- Template editable: sin `pointer-events: none`, registrado en store
- Clones visuales: `pointer-events: none`, sin `data-gbn-id`, no en store
- Después de preview AJAX: re-escanear template con `Gbn.content.scan(template)`

---

**Versión:** 2.1 (Actualizada Diciembre 2025)  
**Relacionado:** `plan.md`, `documentación-gbn.md`, `guia-crear-componente.md`
