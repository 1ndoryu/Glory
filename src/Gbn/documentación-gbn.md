# Documentación GBN: Uso y Configuración

> [!IMPORTANT]
> Guía práctica para trabajar con GBN. Para principios arquitectónicos ver `reglas.md`. Para crear componentes ver `guia-crear-componente.md`.

---

## 1. Ciclo de Vida de los Estilos

**GBN gestiona el atributo `style` sincronizándolo con su configuración interna.**

### Flujo de Procesamiento

| Paso | Acción                             | Resultado                                   |
| :--- | :--------------------------------- | :------------------------------------------ |
| 1    | GBN escanea DOM (`gloryDiv`, etc.) | Detecta elementos editables                 |
| 2    | Lee estilos inline existentes      | `style="background: red; padding: 20px;"`   |
| 3    | Sincroniza a config del bloque     | `config = { fondo: "red", padding: {...} }` |
| 4    | Aplica config guardada (si existe) | Preset **sobrescribe** lo leído del HTML    |

### ¿Cuándo se "pierden" los estilos inline?

| Escenario              | Qué pasa                                     |
| :--------------------- | :------------------------------------------- |
| Hay config guardada    | Preset sobrescribe HTML                      |
| Usuario edita en panel | Solo propiedades editadas se aplican         |
| Usuario limpia valor   | `styleManager` elimina propiedad del `style` |

### Opciones para Estilos Iniciales

**A. Estilos Inline (Editables desde el Panel)**
```html
<div gloryDiv style="background-color: red; padding: 20px;">...</div>
```
✅ Editable desde panel  
⚠️ Si hay preset guardado, este tiene prioridad

**B. Atributo `opciones` (Recomendado)**
```html
<div gloryDiv opciones="fondo: 'red', padding: { superior: '20px' }">...</div>
```
✅ Explícito y legible  
✅ Integración directa con config

**C. Clases CSS (Para defaults del tema)**
```html
<div gloryDiv class="hero-section">...</div>
<style>
    .hero-section { background-color: red; }
</style>
```
✅ Actúa como "default" visual  
✅ Panel puede sobrescribir  
✅ No se pierde nunca

---

## 2. Jerarquía de Estilos

De menor a mayor prioridad:

1. **Variables CSS del Tema** (`theme-settings`) → Globales
2. **Clases CSS Base** → Hojas de estilo
3. **Panel GBN** → `style="..."` inline (sobrescribe clases, excepto `!important`)

---

## 3. Elementos Decorativos y Posicionamiento

> [!WARNING]
> **NO** agregues `gloryDiv` a elementos puramente decorativos con `position: absolute`.

**¿Por qué?**  
El editor inyecta estilos de control (`position: relative`, pseudo-elementos) que rompen layouts absolutos.

```html
<!-- ❌ MAL: Editor romperá position: absolute -->
<div gloryDivSecundario class="overlay-absoluto"></div>

<!-- ✅ BIEN: Elemento estático -->
<div class="overlay-absoluto"></div>
```

---

## 4. Especificidad CSS (`:where()`)

GBN usa `:where()` en selectores base (especificidad 0) para que tus clases personalizadas siempre ganen.

```css
/* GBN Base (Especificidad 0) */
:where([data-gbn-root]) p {
    color: var(--gbn-text-color);
}

/* Tu Clase (Especificidad 1) - GANA SIEMPRE */
.mi-clase {
    color: red;
}
```

---

## 5. Uso Manual de Componentes en PHP

### Inferencia Automática (v6.5+)

GBN infiere automáticamente:
- `texto` → Del `innerHTML`
- `tag` → Del `tagName` (h1, p, div)
- `url` (botones) → Del atributo `href`
- `target` (botones) → Del atributo `target`

**Ejemplos simplificados:**

```html
<!-- Texto: GBN detecta H1 y contenido -->
<h1 gloryTexto>Mi Título</h1>

<!-- Botón: Solo especifica lo que NO es contenido -->
<a href="#contacto" gloryButton class="btn" opciones="variant: 'primary'">Click</a>

<!-- Imagen -->
<img gloryImagen src="imagen.jpg" alt="Descripción">
```

> [!NOTE]
> Puedes forzar valores con `opciones="texto: 'Nuevo Valor'"` si necesitas sobrescribir.

---

## 6. Sistema de Traits JS (Fase 11)

### Funciones Reutilizables

| Trait      | Genera Estilos                                | Aplica al DOM                                  |
| :--------- | :-------------------------------------------- | :--------------------------------------------- |
| Typography | `getTypographyStyles(config.typography)`      | `applyTypography(el, prop, val)`               |
| Spacing    | `getSpacingStyles(config.padding, 'padding')` | `applySpacing(el, 'padding', 'superior', val)` |
| Border     | `getBorderStyles(config)`                     | `applyBorder(el, 'borderRadius', val)`         |
| Background | `getBackgroundStyles(config)`                 | `applyBackground(el, 'backgroundColor', val)`  |

### Uso en Renderers

```javascript
var traits = Gbn.ui.renderers.traits;

function handleUpdate(block, path, value) {
    // Propiedades específicas
    if (path === 'miPropiedad') {
        block.element.dataset.valor = value;
        return true;
    }
    
    // Delegar todo lo demás a traits
    return traits.handleCommonUpdate(block.element, path, value);
}
```

### Factory para Componentes Simples

```javascript
Gbn.ui.renderers.miComponente = traits.createRenderer({
    getExtraStyles: function(config) {
        return { 'custom-prop': config.miValor };
    }
});
```

---

## 7. Sincronización CSS → Panel

### Componentes del Sistema

```
Campo del Panel (fraction.js, text.js, etc.)
    ↓
getEffectiveValue(block, path)
    ↓
getComputedValueForPath(element, path)
    ↓
CONFIG_TO_CSS_MAP[path]
    ↓
getComputedStyle(element)[cssProperty]
```

### Reglas para Nuevas Propiedades

**1. Agregar mapeo en `CONFIG_TO_CSS_MAP` (`utils.js`)**
```javascript
var CONFIG_TO_CSS_MAP = {
    'miNuevaPropiedad': 'cssPropertyName',  // ← OBLIGATORIO
};
```

**2. Detectar browser defaults**
```javascript
// Ejemplo: 'position: static' es default del navegador
if (path === 'position' && computedValue === 'static') {
    isBrowserDefault = true;
}
```

**Browser defaults comunes:**
| Propiedad        | Default         |
| :--------------- | :-------------- |
| `position`       | `'static'`      |
| `z-index`        | `'auto'`        |
| `overflow`       | `'visible'`     |
| `width/height`   | `'auto'`        |
| `padding/margin` | `'0px'`         |
| `background`     | `'transparent'` |

**3. Cuidado con unidades**

`getComputedStyle()` siempre devuelve píxeles para dimensiones. Para mostrar porcentajes:

```javascript
// Calcular % basándose en el padre
var parentWidth = element.parentElement.offsetWidth;
var percentValue = (pxValue / parentWidth) * 100;
```

**4. Defaults del tema vs valores computados**

```javascript
// Tratar default como "sin valor explícito"
var hasExplicitValue = current !== undefined && current !== null && current !== '';
if (field.id === 'height' && current === 'auto') {
    hasExplicitValue = false; // 'auto' es default, no cuenta
}
```

### Checklist para Nuevas Propiedades

- [ ] Agregada a `CONFIG_TO_CSS_MAP` en `utils.js`
- [ ] Browser default detectado en `getValueSource()` 
- [ ] Browser default detectado en `getEffectiveValue()`
- [ ] Si usa unidades especiales (%, vh), implementar conversión
- [ ] Si tiene default en PHP, verificar que no bloquee lectura del DOM

---

## 8. Registro de Nuevos Componentes

> [!CAUTION]
> Sin registro correcto, el panel no funcionará.

### Archivos a Modificar

**1. `GbnManager.php` - Registrar script:**
```php
'glory-gbn-ui-renderers-NOMBRE' => [
    'file' => '/js/ui/renderers/NOMBRE.js',
    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
],
```

**2. `GbnManager.php` - Agregar como dependencia:**
```php
'glory-gbn-ui-panel-render' => [
    'deps' => [
        // ... otros renderers ...
        'glory-gbn-ui-renderers-NOMBRE',
    ],
],
```

**3. `panel-render.js` - Agregar resolver:**
```javascript
var styleResolvers = {
    NOMBRE: function(config, block) {
        return Gbn.ui.renderers.NOMBRE 
            ? Gbn.ui.renderers.NOMBRE.getStyles(config, block) 
            : {};
    }
};
```

**4. `roles.js` (opcional) - Fallback selector:**
```javascript
var FALLBACK_SELECTORS = {
    NOMBRE: { attribute: 'gloryNOMBRE', dataAttribute: 'data-gbn-NOMBRE' }
};
```

### Sin estos pasos:
- ❌ No aplicará estilos en tiempo real
- ❌ Panel no responderá a cambios
- ❌ `handleUpdate()` nunca será llamado

---

## 9. Sistema de Estados Hover/Focus

### Arquitectura

Estados se almacenan en `config._states`:

```javascript
{
    // Estilos base
    backgroundColor: '#ffffff',
    
    // Estilos por estado
    _states: {
        hover: {
            backgroundColor: '#e5e5e5',
            transform: 'scale(1.02)'
        },
        focus: {
            borderColor: '#1d8ff1'
        }
    }
}
```

### API de Lectura

```javascript
// Leer estilos hover
var hoverStyles = Gbn.services.stateStyles.getStateStyles(element, 'hover');

// Leer todos los estados
var allStates = Gbn.services.stateStyles.getAllStatesFromCSS(element);

// Leer estados combinados (CSS + config)
var blockStates = Gbn.services.stateStyles.getBlockStates(block);
```

### API de Escritura

```javascript
// Guardar estilos de un estado
Gbn.services.stateStyles.setStateStyles(block, 'hover', {
    backgroundColor: '#e5e5e5'
});

// Actualizar propiedad específica
Gbn.services.stateStyles.setStateProperty(block, 'hover', 'backgroundColor', '#e5e5e5');

// Eliminar propiedad (pasar null)
Gbn.services.stateStyles.setStateProperty(block, 'hover', 'backgroundColor', null);
```

### Aplicación en Tiempo Real

```javascript
// Aplicar un estado
Gbn.styleManager.applyStateCss(block, 'hover', { backgroundColor: '#e5e5e5' });

// Aplicar todos los estados
Gbn.styleManager.applyAllStates(block);

// Limpiar estados
Gbn.styleManager.clearAllStates(block);
```

### Estados Soportados

- `hover` → Mouse sobre elemento
- `focus` → Elemento enfocado
- `active` → Elemento siendo presionado
- `visited` → Enlace visitado
- `focus-visible` → Focus visible (teclado)
- `focus-within` → Contiene elemento enfocado

### Limitación Conocida

`getComputedStyle()` no puede leer pseudo-clases directamente. El servicio `state-styles.js` parsea hojas de estilo cargadas.

**Implicaciones:**
1. **Cross-origin stylesheets**: CDNs no accesibles (CORS)
2. **Cache**: Resultados cacheados 5 segundos
3. **Rendimiento**: Parsing inicial toma algunos ms en páginas grandes

### Persistencia de Estados (CRÍTICO)

> [!CAUTION]
> **Bug frecuente:** Una propiedad funciona en tiempo real en hover/focus pero NO persiste después de guardar.

**Causa:** La propiedad CSS no está en la lista `cssDirectProps` de `style-generator.js`.

**Cómo funciona la persistencia:**
1. Al editar en hover/focus, `panel-render.js` guarda el valor en `config._states.hover.{cssProp}`
2. Al guardar, `style-generator.js` genera CSS usando `extractStyles()`
3. `extractStyles()` solo procesa propiedades listadas en `cssDirectProps`
4. Si la propiedad no está en la lista → No se genera CSS → No persiste

**Solución:** Agregar la propiedad CSS (camelCase) a `cssDirectProps`:

```javascript
// style-generator.js - Lista de propiedades que persisten en estados
var cssDirectProps = [
    'backgroundColor', 'color', 'borderColor', 'borderWidth', 'borderStyle', 
    'borderRadius', 'transform', 'transition', 'opacity', 'boxShadow',
    'textDecoration', 'cursor', 'fontWeight', 'fontSize', 'fontFamily',
    'lineHeight', 'letterSpacing', 'textTransform', 'textShadow',
    'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
    'marginTop', 'marginRight', 'marginBottom', 'marginLeft'
];
```

**Checklist para nuevas propiedades en estados:**
- [ ] Agregar a `cssDirectProps` en `style-generator.js`
- [ ] Verificar mapeo en `panel-render.js` (líneas 36-51)
- [ ] Probar ciclo completo: editar → guardar → recargar → verificar

---

## 10. Colores Globales

### Variables Generadas

| Opción Panel | Variable CSS      | Uso Común                    |
| :----------- | :---------------- | :--------------------------- |
| Primario     | `--gbn-primary`   | Botones principales, enlaces |
| Secundario   | `--gbn-secondary` | Botones secundarios, bordes  |
| Acento       | `--gbn-accent`    | CTAs, alertas                |
| Fondo Body   | `--gbn-bg`        | Fondo general                |
| Custom [0-9] | `--gbn-custom-N`  | Colores personalizados       |

### Uso en CSS

```css
.mi-boton-especial {
    background-color: var(--gbn-primary);
    border: 2px solid var(--gbn-accent);
}

.mi-tarjeta:hover {
    background-color: var(--gbn-custom-0);
}
```

### Agregar Colores

El flujo es **unidireccional** (Panel → CSS). Para usar un color de tu CSS en la paleta:

1. Ir a Theme Settings → Colores → Paleta Global
2. Agregar nuevo color con el valor deseado
3. Ahora estará disponible en todos los componentes

---

## 11. Limpieza Automática de HTML

**Dos niveles de limpieza:**

1. **Persistencia (DB):** `DomProcessor` elimina `data-gbn-schema` y `data-gbn-config` antes de guardar
2. **Frontend (Visualización):** Filtro en `the_content` elimina atributos `glory*` y `data-gbn*` para usuarios no logueados

> [!WARNING]
> No dependas de atributos como `glorydiv` para selectores CSS/JS en frontend. Usa clases CSS o `data-gbn-id`.

---

## 12. Estandarización UI del Panel

### Principios Visuales

1. **Grid Layout**: Agrupación en rejillas (2x2) para optimizar espacio
2. **Iconografía SVG**: Iconos inline, no texto ni Unicode
3. **Inputs Compactos**: Sin etiquetas de texto visibles (solo `title`)
4. **Source Feedback**:
   - Heredado/Default: Borde sutil
   - Sobreescrito: Borde de color de acento
5. **Unidades Flexibles**: Input libre (px, %, vh, auto) con parseo inteligente

### Campos de Referencia

- `spacing.js` → Grupos de 4 valores (Padding, Margin)
- `dimensions.js` → Pares (Ancho/Alto, Min/Max)
- `typography.js` → Grupos complejos con tipos mixtos

---

## 13. Diagnóstico de Problemas

| Problema                 | Causa                           | Solución                           |
| :----------------------- | :------------------------------ | :--------------------------------- |
| Estilo inline desaparece | Hay preset guardado             | Limpia presets o usa clases CSS    |
| Estilo HTML ignorado     | Preset tiene prioridad          | Edita en panel y guarda            |
| Estilo no se aplica      | `!important` o valor en panel   | Verifica especificidad             |
| Overlays/absolutos rotos | Atributo `gloryDiv` innecesario | Quítalo si no es editable          |
| Flash sin estilo         | Clases no en `<head>`           | Carga críticas en bloque `<style>` |
| Panel no muestra valor   | Problema de sync                | Verifica `CONFIG_TO_CSS_MAP`       |

---

## 14. Arquitectura Modular JS (Diciembre 2025)

### Principio: Archivos Pequeños y Enfocados

Los archivos JS grandes (>500 líneas) se han refactorizado en módulos más pequeños siguiendo **Single Responsibility Principle**.

### Módulos Refactorizados

**`ui/theme/render.js` → Módulos de Theme Settings:**
```
ui/theme/
├── render.js          → Orquestador (~225 líneas)
├── state.js           → Estado global del módulo
├── utils.js           → Utilidades de tabs
└── renderers/
    ├── page-settings.js
    ├── menu.js
    ├── section-text.js
    ├── section-colors.js
    ├── section-pages.js
    └── section-components.js
```

**`ui/panel-render.js` → Módulos del Panel:**
```
ui/panel-render/
├── state.js           → Estado del panel (editing state, active tab)
├── style-resolvers.js → Mapa de resolvers por rol
├── state-selector.js  → UI selector Normal/Hover/Focus
├── tabs.js            → Utilidades de tabs
├── config-updater.js  → Lógica de updateConfigValue
└── theme-propagation.js → Propagación de cambios del tema
```

### Beneficios

| Métrica                       | Antes      | Después     | Mejora |
| ----------------------------- | ---------- | ----------- | ------ |
| `render.js`                   | 702 líneas | ~225 líneas | -68%   |
| `panel-render.js`             | 796 líneas | ~220 líneas | -72%   |
| Responsabilidades por archivo | 6+         | 1           | ✓ SRP  |
| Iconos hardcodeados           | ~100 SVGs  | 0           | ✓ DRY  |

### Uso de IconRegistry

Los iconos SVG ahora se obtienen del registro centralizado:

```javascript
// Antes (hardcodeado)
var icon = '<svg width="16" height="16" viewBox="0 0 24 24"...';

// Después (centralizado)
var icon = GbnIcons.get('tab.style');
```

---

## 15. Refactorización utils.js y GbnManager.php (Diciembre 2025)

### utils.js → Módulos Especializados

El archivo `panel-fields/utils.js` (758 líneas) se dividió en módulos más pequeños:

```
ui/panel-fields/
├── utils.js              → Orquestador (~75 líneas)
├── deep-access.js        → getDeepValue, setDeepValue
├── theme-defaults.js     → getThemeDefault
├── css-map.js            → CONFIG_TO_CSS_MAP, BROWSER_DEFAULTS
├── computed-styles.js    → getComputedValue, getComputedValueForPath
├── config-values.js      → getConfigValue, getValueSource
├── effective-value.js    → getEffectiveValue (lógica de valor efectivo)
├── condition-handler.js  → shouldShowField
├── state-utils.js        → SUPPORTED_STATES, getStateConfig
└── helpers.js            → parseSpacingValue, ICONS
```

### GbnManager.php → ScriptManifest

Las definiciones de scripts (~560 líneas) se extrajeron a `Config/ScriptManifest.php`:

```php
// Antes (inline en GbnManager.php)
$builderScripts = [
    'glory-gbn-icons-index' => [...],
    'glory-gbn-icons-layout' => [...],
    // ~150 entradas más...
];

// Después (ScriptManifest)
$frontendScripts = ScriptManifest::getFrontendScripts();
$builderScripts = ScriptManifest::getBuilderScripts();
```

### Métricas de Mejora

| Archivo          | Antes      | Después     | Reducción |
| ---------------- | ---------- | ----------- | --------- |
| `utils.js`       | 758 líneas | ~75 líneas  | -90%      |
| `GbnManager.php` | 855 líneas | ~296 líneas | -65%      |

---

**Versión:** 2.2 (Refactorización utils.js y GbnManager - Diciembre 2025)  
**Relacionado:** `reglas.md`, `plan.md`, `guia-crear-componente.md`, `plan_refactoring_icons_layout.md`
