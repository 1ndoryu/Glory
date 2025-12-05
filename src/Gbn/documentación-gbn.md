# Documentación GBN: Manejo de Estilos y Construcción de Páginas

> [!IMPORTANT]
> Esta documentación resume los puntos clave para trabajar con estilos en GBN y evitar conflictos entre el código manual y el constructor visual. Esta en construcción, cada nuevo aprendizaje importante debe documentarse aca.

## 1. Ciclo de Vida de los Estilos en GBN

**GBN gestiona el atributo `style` de los elementos editables, sincronizándolo con su configuración interna.**

### Flujo Real de Procesamiento

```
┌──────────────────────────────────────────────────────────────────┐
│                    AL CARGAR LA PÁGINA                           │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. GBN escanea el DOM buscando elementos con gloryDiv, etc.     │
│                                                                  │
│  2. Por cada elemento, LEE los estilos inline existentes:        │
│     <div gloryDiv style="background: red; padding: 20px;">       │
│                                                                  │
│  3. SINCRONIZA esos valores a la configuración del bloque:       │
│     block.config = { fondo: "red", padding: {...} }              │
│                                                                  │
│  4. Si hay configuración GUARDADA (presets), esta SOBRESCRIBE    │   
│     lo que se leyó del HTML (la versión guardada tiene prioridad)│
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

### ¿Cuándo se "pierden" los estilos inline?

Los estilos inline del HTML **NO se borran al iniciar**. Se pierden en estos casos:

| Escenario                                  | ¿Qué pasa?                                         |
| ------------------------------------------ | -------------------------------------------------- |
| Hay configuración guardada para ese bloque | El preset **sobrescribe** la config leída del HTML |
| El usuario edita una propiedad en el panel | Solo las propiedades en la nueva config se aplican |
| El usuario "limpia" un valor en el panel   | `styleManager` elimina esa propiedad del `style`   |

### Opciones para Estilos Iniciales

**Opción A: Estilos Inline (Editables desde el Panel)**
```html
<!-- GBN lee esto y lo sincroniza a la config del bloque -->
<div gloryDiv style="background-color: red; padding: 20px;">...</div>
```
- ✅ El usuario puede editarlo desde el panel
- ⚠️ Si hay versión guardada, esta tiene prioridad

**Opción B: Atributo `opciones` (Recomendado para valores iniciales)**
```html
<!-- Configuración explícita que GBN entiende directamente -->
<div gloryDiv opciones="fondo: 'red', padding: { superior: '20px' }">...</div>
```
- ✅ Más explícito y legible
- ✅ Se integra directamente con la config del bloque

**Opción C: Clases CSS (Para defaults del tema)**
```html
<!-- Estilos base que el panel puede sobrescribir -->
<div gloryDiv class="hero-section">...</div>

<style>
    .hero-section {
        background-color: red;
    }
</style>
```
- ✅ Actúa como "default" visual
- ✅ El panel (estilos inline) tiene prioridad y puede sobrescribirlo
- ✅ No se pierde nunca (solo se sobrescribe visualmente)

## 2. Jerarquía de Estilos en GBN

GBN sigue una jerarquía estricta para decidir qué estilo mostrar. De menor a mayor prioridad:

1.  **Variables CSS del Tema (`theme-settings`)**: Definidas globalmente.
2.  **Clases CSS Base**: Las clases que pones en tu hoja de estilos o `<style>`.
3.  **Configuración del Panel (GBN)**: Lo que el usuario edita en el constructor. Esto se aplica como `style="..."` inline y **sobrescribe** a las clases base (a menos que uses `!important`).

## 3. Cómo agregar imágenes de fondo (Caso Práctico)

Si necesitas una imagen de fondo fija que no se pierda al cargar el editor:

**Opción A: Vía CSS (Recomendada para plantillas)**
Define una clase con la imagen y asígnala al div.

```css
.hero-bg-custom {
    background-image: url('ruta/imagen.jpg');
    background-size: cover;
    background-position: center;
}
```

**Opción B: Vía Configuración (Para contenido editable)**
Si quieres que la imagen sea editable, debe estar en la configuración del bloque (`data-gbn-schema` o inyectada vía JS), no hardcodeada en el HTML.

## 4. Elementos Decorativos y Posicionamiento Absoluto

**IMPORTANTE:** No agregues `gloryDiv` o `gloryDivSecundario` a elementos puramente decorativos o estructurales que dependen de `position: absolute` (como overlays, efectos de fondo, iconos flotantes).

**¿Por qué?**
El editor de GBN inyecta estilos de control (como `position: relative` y pseudo-elementos `::after` para asegurar áreas de click) que pueden romper tu layout absoluto, haciendo que los overlays colapsen (height: 0) o se muevan de lugar.

```html
<!-- MAL: El editor romperá el position: absolute -->
<div gloryDivSecundario class="overlay-absoluto"></div>

<!-- BIEN: Elemento estático, el editor no lo toca -->
<div class="overlay-absoluto"></div>
```

## 5. Especificidad de Estilos del Tema (Theme Settings)

**Problema:** Los estilos globales del tema (color de texto, fuentes, etc.) pueden sobrescribir tus clases personalizadas si tienen mayor especificidad.

**Solución:** GBN utiliza `:where()` en sus selectores base para reducir su especificidad a 0. Esto permite que tus clases CSS personalizadas siempre ganen sin necesidad de usar `!important`.

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

Si notas que un estilo del tema está "ganando" indebidamente, verifica que el selector en `theme-styles.css` esté envuelto en `:where()`.

## 6. Diagnóstico de Problemas Comunes

-   **El estilo inline desaparece al recargar:** Hay una configuración guardada (preset) que sobrescribe lo leído del HTML. Limpia los presets del bloque o usa clases CSS.
-   **El estilo del HTML se ignora:** GBN lee el preset guardado primero. Si quieres resetear, edita el bloque en el panel y guarda de nuevo.
-   **El estilo no se aplica:** Verifica si hay una regla `!important` o si el panel de GBN tiene un valor configurado que está sobrescribiendo tu clase.
-   **Overlays o elementos absolutos rotos en el editor:** Verifica si tienen `gloryDivSecundario` innecesariamente. Quítalo si no es contenido editable.
-   **Flash de contenido sin estilo:** Asegúrate de que tus clases críticas estén cargadas en el `<head>` o en el bloque `<style>` del template.
-   **El panel no muestra el valor actual:** GBN intenta sincronizar desde `getComputedStyle`. Si el valor viene de una clase CSS, el panel lo mostrará pero al editarlo se guarda como inline.

## 7. Uso Manual de Componentes en PHP

Para utilizar componentes de GBN directamente en archivos PHP (fuera del editor visual), se deben usar los atributos correspondientes.

**Mejora de Inferencia (v6.5):**
Ya no es necesario duplicar el contenido del texto ni la etiqueta en el atributo `opciones`. GBN ahora infiere automáticamente:
- `texto`: Del `innerHTML` del elemento.
- `tag`: Del `tagName` del elemento (ej: `h1`, `p`, `div`).

**Ejemplo Texto (Simplificado):**
```html
<!-- GBN detectará automáticamente que es un H1 y que el texto es "Mi Título" -->
<h1 gloryTexto>Mi Título</h1>
```

**Ejemplo Botón (Simplificado):**
```html
<!-- Solo necesitas especificar las opciones que NO son contenido -->
<a href="#" gloryButton class="btn" opciones="variant: 'primary'">Click</a>
```

> [!NOTE]
> Si necesitas forzar un valor diferente al del HTML inicial, puedes seguir usando `opciones="texto: 'Nuevo Valor'"`.

## 7.5 Sistema de Traits JS para Renderers (Fase 11)

> [!TIP]
> Los traits JS centralizan funciones reutilizables para eliminar código duplicado en renderers.

### ¿Qué son los Traits JS?

El módulo `renderer-traits.js` proporciona funciones compartidas equivalentes a los Traits PHP (`HasTypography`, `HasSpacing`, etc.) para usar en los renderers JavaScript.

### Traits Disponibles

| Trait      | Genera Estilos                                | Aplica al DOM                                  |
| ---------- | --------------------------------------------- | ---------------------------------------------- |
| Typography | `getTypographyStyles(config.typography)`      | `applyTypography(el, prop, val)`               |
| Spacing    | `getSpacingStyles(config.padding, 'padding')` | `applySpacing(el, 'padding', 'superior', val)` |
| Border     | `getBorderStyles(config)`                     | `applyBorder(el, 'borderRadius', val)`         |
| Background | `getBackgroundStyles(config)`                 | `applyBackground(el, 'backgroundColor', val)`  |

### Uso Rápido

```javascript
// En tu renderer, usa el handler universal
var traits = Gbn.ui.renderers.traits;

function handleUpdate(block, path, value) {
    // Propiedades específicas de tu componente
    if (path === 'miPropiedad') {
        block.element.dataset.valor = value;
        return true;
    }
    
    // ¡Delegar todo lo demás a traits!
    return traits.handleCommonUpdate(block.element, path, value);
}
```

### Factory para Componentes Simples

```javascript
// Crea un renderer con una línea usando la factory
Gbn.ui.renderers.miComponente = traits.createRenderer({
    getExtraStyles: function(config) {
        return { 'custom-prop': config.miValor };
    }
});
```

## 8. Arquitectura de Sincronización de Estilos Computados (CSS → Panel)

> [!IMPORTANT]
> Esta sección documenta las lecciones aprendidas del bug **BUG-SYNC** (Dic 2025) para evitar errores similares en el futuro.

### El Problema

Cuando un elemento tiene estilos definidos en clases CSS (ej: `.mi-clase { width: 30%; height: 400px; }`), el panel debe mostrar esos valores como estado inicial. Esto requiere leer `getComputedStyle()` del DOM.

### Componentes Clave del Sistema

```
┌─────────────────────────────────────────────────────────────────┐
│                    FLUJO DE LECTURA DE ESTILOS                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. Campo del Panel (fraction.js, text.js, icon-group.js)       │
│     │                                                           │
│     ▼                                                           │
│  2. getEffectiveValue(block, path) → utils.js                   │
│     │                                                           │
│     ▼                                                           │
│  3. getComputedValueForPath(element, path) → utils.js           │
│     │                                                           │
│     ▼                                                           │
│  4. CONFIG_TO_CSS_MAP[path] → Mapeo config→CSS                  │
│     │                                                           │
│     ▼                                                           │
│  5. getComputedStyle(element)[cssProperty] → Valor real         │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Reglas Críticas para Nuevas Propiedades

**1. Siempre agregar mapeo en `CONFIG_TO_CSS_MAP`**

Si agregas una nueva propiedad de estilo al sistema, **DEBES** agregarla al mapeo en `utils.js`:

```javascript
var CONFIG_TO_CSS_MAP = {
    // ... propiedades existentes ...
    'miNuevaPropiedad': 'cssPropertyName',  // ← OBLIGATORIO
};
```

Sin este mapeo, `getComputedValueForPath()` devolverá `undefined` y el panel mostrará vacío.

**2. Detectar valores por defecto del navegador**

En `getEffectiveValue()` y `getValueSource()`, debes agregar la detección del valor default del navegador para evitar mostrarlo como "computed":

```javascript
// Ejemplo: 'position: static' es el default del navegador
else if (path === 'position' && computedValue === 'static') {
    isBrowserDefault = true;
}
```

Valores defaults comunes:
| Propiedad        | Default del navegador                  |
| ---------------- | -------------------------------------- |
| `position`       | `'static'`                             |
| `z-index`        | `'auto'`                               |
| `overflow`       | `'visible'`                            |
| `width/height`   | `'auto'`                               |
| `padding/margin` | `'0px'`                                |
| `background`     | `'rgba(0, 0, 0, 0)'` o `'transparent'` |

**3. Cuidado con `getComputedStyle` y unidades**

`getComputedStyle()` **siempre devuelve valores en píxeles** para dimensiones, incluso si el CSS dice `width: 30%`. Para mostrar el porcentaje original:

```javascript
// En fraction.js: calcular % basándose en el padre
var parentWidth = element.parentElement.offsetWidth;
var percentValue = (pxValue / parentWidth) * 100;
```

**4. Defaults del tema vs valores computados**

Si una propiedad tiene un default en el esquema PHP (ej: `->default('auto')`), `getConfigValue()` lo devolverá aunque el usuario no haya configurado nada. Esto puede bloquear la lectura del DOM.

**Solución:** Tratar el default como "sin valor explícito":

```javascript
// En text.js para height
var hasExplicitValue = current !== undefined && current !== null && current !== '';
if (field.id === 'height' && current === 'auto') {
    hasExplicitValue = false; // 'auto' es el default, no cuenta
}
```

### Checklist para Nuevas Propiedades de Estilo

- [ ] Agregada a `CONFIG_TO_CSS_MAP` en `utils.js`
- [ ] Browser default detectado en `getValueSource()` 
- [ ] Browser default detectado en `getEffectiveValue()`
- [ ] Si usa unidades especiales (%, vh, etc.), implementar conversión en el campo
- [ ] Si tiene default en PHP, verificar que no bloquee lectura del DOM

### Checklist para Nuevos Componentes (CRÍTICO)

> [!CAUTION]
> Si creas un nuevo componente (ej: `ButtonComponent.php`), **DEBES** registrar su renderer en el sistema de scripts o el panel no funcionará.

> [!TIP]
> **Fase 11:** Consulta la guía completa en `Glory/src/Gbn/guia-crear-componente.md` para instrucciones detalladas y uso de traits.

**Archivos a modificar:**

1. **`GbnManager.php`** - Registrar el script del renderer:
```php
// En $builderScripts, agregar:
'glory-gbn-ui-renderers-NOMBRE' => [
    'file' => '/js/ui/renderers/NOMBRE.js',
    // Fase 11: Incluir traits en dependencias para usar funciones compartidas
    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
],
```


2. **`GbnManager.php`** - Agregarlo como dependencia de `panel-render.js`:
```php
'glory-gbn-ui-panel-render' => [
    'deps' => [
        // ... otros renderers ...
        'glory-gbn-ui-renderers-NOMBRE',  // ← AGREGAR
    ],
],
```

3. **`panel-render.js`** - Agregar a `styleResolvers`:
```javascript
var styleResolvers = {
    // ... otros resolvers ...
    NOMBRE: function(config, block) {
        return Gbn.ui.renderers.NOMBRE ? Gbn.ui.renderers.NOMBRE.getStyles(config, block) : {};
    }
};
```

4. **`roles.js`** (opcional) - Agregar fallback selector si es necesario:
```javascript
var FALLBACK_SELECTORS = {
    // ...
    NOMBRE: { attribute: 'gloryNOMBRE', dataAttribute: 'data-gbn-NOMBRE' }
};
```

**Sin estos pasos, el componente:**
- ❌ No aplicará estilos en tiempo real
- ❌ El panel no responderá a cambios
- ❌ `handleUpdate()` nunca será llamado

## 9. Sistema de Estados Hover/Focus (Fase 10)

> [!NOTE]
> Esta funcionalidad permite leer y editar estilos de pseudo-clases CSS como `:hover`, `:focus`, `:active`.

### Arquitectura de Estados

Los estados se almacenan en `config._states` dentro de cada bloque:

```javascript
{
    // Estilos normales (base)
    backgroundColor: '#ffffff',
    color: '#333',
    
    // Estilos por estado (pseudo-clases)
    _states: {
        hover: {
            backgroundColor: '#e5e5e5',
            transform: 'scale(1.02)'
        },
        focus: {
            borderColor: '#1d8ff1',
            boxShadow: '0 0 0 2px rgba(29, 143, 241, 0.2)'
        },
        active: {
            transform: 'scale(0.98)'
        }
    }
}
```

### Lectura de Estados desde CSS

El servicio `Gbn.services.stateStyles` permite leer estilos de pseudo-clases de las hojas de estilo:

```javascript
// Leer estilos hover de un elemento
var hoverStyles = Gbn.services.stateStyles.getStateStyles(element, 'hover');
// { backgroundColor: 'rgb(229, 229, 229)', transform: 'scale(1.02)' }

// Leer todos los estados de un elemento
var allStates = Gbn.services.stateStyles.getAllStatesFromCSS(element);
// { hover: {...}, focus: {...} }

// Leer estados combinados (CSS + config guardada)
var blockStates = Gbn.services.stateStyles.getBlockStates(block);
```

### Escritura de Estados

```javascript
// Guardar estilos de un estado completo
Gbn.services.stateStyles.setStateStyles(block, 'hover', {
    backgroundColor: '#e5e5e5'
});

// Actualizar una propiedad específica
Gbn.services.stateStyles.setStateProperty(block, 'hover', 'backgroundColor', '#e5e5e5');

// Eliminar una propiedad (pasar null)
Gbn.services.stateStyles.setStateProperty(block, 'hover', 'backgroundColor', null);
```

### Aplicación de Estados en Tiempo Real

El `styleManager` aplica automáticamente los estados cuando se actualiza un bloque:

```javascript
// Aplicar un estado específico
Gbn.styleManager.applyStateCss(block, 'hover', { backgroundColor: '#e5e5e5' });

// Aplicar todos los estados de un bloque
Gbn.styleManager.applyAllStates(block);

// Limpiar todos los estados
Gbn.styleManager.clearAllStates(block);
```

### Generación CSS para Persistencia

El `style-generator` genera CSS con pseudo-clases:

```javascript
// Generar CSS incluyendo estados
var css = Gbn.services.styleGenerator.generateCss(blocksMap);
// Resultado:
// /* Estados (hover/focus/active) */
// [data-gbn-id="btn-123"]:hover { background-color: #e5e5e5; }
// [data-gbn-id="btn-123"]:focus { border-color: #1d8ff1; }
// @media (max-width: 1024px) { ... }
```

### Estados Soportados

Los siguientes estados están soportados:
- `hover` - Mouse sobre el elemento
- `focus` - Elemento enfocado (input, botón)
- `active` - Elemento siendo presionado
- `visited` - Enlace visitado
- `focus-visible` - Focus visible (keyboard navigation)
- `focus-within` - Contiene elemento enfocado

### Implementación UI (Panel)

El panel ahora incluye un selector de estados ("Normal", "Hover", "Focus") para bloques soportados.

**Comportamiento:**
1.  **Normal:** Edita `block.config` directamente.
2.  **Hover/Focus:** 
    -   Edita `block.config._states[state]`.
    -   Muestra valores computados del estado (leídos de CSS).
    -   Activa **Simulación Visual**: Agrega la clase `.gbn-simulated-hover` al elemento para ver los cambios sin mover el mouse.

**Reset de Estado:**
Al cambiar de bloque, el selector vuelve automáticamente a "Normal" y se limpian las clases de simulación.


### ✅ Bugs Resueltos (Fase 10 - Dic 2025)

> [!NOTE]
> Todos los bugs de la funcionalidad de estados hover/focus fueron resueltos:

1.  ✅ **Estilos en tiempo real:** Arreglado en `panel-render.js`. Se construyen los estilos directamente y se asegura que la clase de simulación esté presente antes de aplicar el CSS.

2.  ✅ **Conversión CSS camelCase→kebab-case:** Arreglado en `styleManager.js`. La función `applyStateCss()` ahora convierte `backgroundColor` → `background-color`.

3.  ✅ **Mapeo de Path:** Arreglado en `panel-render.js`. Si no hay mapeo explícito en `CONFIG_TO_CSS_MAP`, usa el último segmento del path.

4.  ✅ **Botón Guardar:** Arreglado en `panel-render.js`. El evento `gbn:configChanged` ahora se dispara correctamente cuando se editan estados.

5.  ✅ **CORS en stylesheets:** Silenciado en `state-styles.js`. El warning era esperado para hojas de estilo cross-origin.

6.  ✅ **Persistencia:** Los estados se guardan correctamente en `config._states` y se incluyen automáticamente en el payload.

### Limitación Conocida

`getComputedStyle()` **NO puede leer pseudo-clases directamente**. El servicio `state-styles.js` resuelve esto parseando las hojas de estilo cargadas en el documento. Esto tiene algunas implicaciones:

1. **Cross-origin stylesheets**: Hojas de estilo de otros dominios (CDN, Google Fonts) no son accesibles por razones de seguridad (CORS).
2. **Cache**: Los resultados se cachean por 5 segundos. Llamar `Gbn.services.stateStyles.invalidateCache()` para forzar re-lectura.
3. **Rendimiento**: El parsing inicial puede tomar algunos milisegundos en páginas con muchas reglas CSS.

---
*Basado en `Glory/src/Gbn/reglas.md` y `plan.md`.*

## 10. Gestión de Colores Globales

> [!TIP]
> GBN utiliza variables CSS para propagar los colores globales a todo el sitio.

### Variables Generadas

Cuando defines colores en **Theme Settings > Colores**, el sistema genera automáticamente las siguientes variables CSS en el elemento raíz (`[data-gbn-root]`):

| Opción Panel | Variable CSS      | Uso Común                                   |
| :----------- | :---------------- | :------------------------------------------ |
| Primario     | `--gbn-primary`   | Botones principales, enlaces, destacados    |
| Secundario   | `--gbn-secondary` | Botones secundarios, bordes sutiles         |
| Acento       | `--gbn-accent`    | Elementos de llamada a la acción, alertas   |
| Fondo Body   | `--gbn-bg`        | Fondo general de la página                  |
| Custom [0]   | `--gbn-custom-0`  | Colores personalizados añadidos manualmente |
| Custom [1]   | `--gbn-custom-1`  | ...                                         |

### Cómo usar colores globales en tu CSS

Puedes utilizar estas variables en tus clases CSS personalizadas o en el bloque de "CSS Personalizado":

```css
.mi-boton-especial {
    background-color: var(--gbn-primary);
    color: white;
    border: 2px solid var(--gbn-accent);
}

.mi-tarjeta:hover {
    background-color: var(--gbn-custom-0); /* Usa el primer color personalizado */
}
```

### ¿Cómo agregar colores desde CSS?

Actualmente, el sistema **no escanea** tus archivos CSS buscando variables para agregarlas a la paleta. El flujo es unidireccional (Panel -> CSS).

Si tienes un color en tu CSS (ej: `$brand-color: #ff0000;` o `--brand: #ff0000`) y quieres que aparezca en el selector de colores del constructor:

3.  Ingresa el nombre (ej: "Brand Red") y selecciona el valor `#ff0000`.
4.  Ahora este color estará disponible en la paleta global de todos los componentes.

## 11. Limpieza Automática de HTML (Frontend)

Para mantener el código limpio y seguro, GBN implementa dos niveles de limpieza de atributos internos (`glorydiv`, `data-gbn-schema`, etc.):

1.  **Persistencia (Base de Datos):** `DomProcessor` elimina `data-gbn-schema` y `data-gbn-config` antes de guardar en `post_content`. Estos datos viven estrictamente en `post_meta`.
2.  **Visualización (Frontend):** Un filtro en `the_content` elimina cualquier atributo `glory*` o `data-gbn*` (excepto `data-gbn-id`) para usuarios **no logueados** o sin permisos de edición.

> [!WARNING]
> No dependas de atributos como `glorydiv` o `data-gbn-role` para selectores CSS o JS en el frontend final, ya que serán eliminados. Usa clases CSS o `data-gbn-id`.

## 12. Estandarización UI Campos del Panel

Para mantener una experiencia de usuario consistente y premium, los campos complejos del panel (Spacing, Typography, Dimensions) siguen estas guías de diseño:

### Principios Visuales
1.  **Grid Layout**: Agrupación de inputs relacionados en rejillas (2x2 o filas) para optimizar el espacio vertical. Clases como `.gbn-spacing-grid` facilitan esto.
2.  **Iconografía SVG**: Uso exclusivo de iconos SVG inline para las etiquetas de los inputs.
    -   Evitar texto ("Ancho") o caracteres Unicode ('↔').
    -   Los iconos deben ser claros y universales (flechas para dimensiones, "T" para texto, etc.).
3.  **Inputs Compactos**: Los inputs se presentan sin etiquetas de texto visibles (solo `title` o tooltip), dejando que el icono comunique la función.
4.  **Feedback de Fuente (Source Feedback)**:
    -   Heredado / Default: Borde sutil / transparente.
    -   Sobreescrito (Override): Borde de color de acento o indicador visual al editar.
5.  **Unidades Flexibles**: Permitir input libre (px, %, vh, auto) pero con soporte para parseo inteligente cuando sea posible (como en Spacing).

### Campos de Referencia
-   `spacing.js`: Modelo para grupos de 4 valores (Padding, Margin). Usa selector de unidad global.
-   `dimensions.js`: Modelo para pares de valores (Ancho/Alto, Min/Max). Usa inputs de texto libre e iconos SVG.
-   `typography.js`: Modelo para grupos complejos con tipos de input mixtos (Selects, Toggles con Iconos, Inputs Numéricos).
