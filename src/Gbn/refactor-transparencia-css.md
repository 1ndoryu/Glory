# Refactor: Transparencia CSS - Roadmap

> [!IMPORTANT]
> **Fecha de inicio:** 7 Diciembre 2025
> **Objetivo:** Restaurar GBN a su proposito original: un editor WYSIWYG transparente que NO inyecta estilos propios.

---

## Instrucciones para la IA

> [!TIP]
> Este documento es **vivo**. La IA tiene permiso explicito para:
> - Actualizar el estado de las tareas
> - Agregar notas y descubrimientos durante la investigacion
> - Proponer cambios al roadmap basado en hallazgos
> - Replanificar si encuentra dependencias o bloqueadores
> - Profundizar en areas que requieran mas analisis
> - Documentar decisiones arquitectonicas tomadas
> - Marcar tareas como completadas, bloqueadas o descartadas
>
> **Regla:** Cada vez que la IA trabaje en este refactor, debe actualizar este documento.

---

## 1. Contexto y Motivacion

### El Problema
GBN se desvio de su proposito original. En lugar de ser un editor transparente que permite al cliente editar lo que el programador construyo, se convirtio en:

1. **Un inyector de estilos propios** - Variables CSS en `:root`, estilos base para componentes
2. **Un competidor del CSS del desarrollador** - Definiendo como deben verse los elementos
3. **Un sistema opaco** - El desarrollador no puede predecir como se vera su pagina sin conocer los internals de GBN
4. **Dependiente de CSS ocultos** - Si desactivas GBN, algunos estilos desaparecen

### La Filosofia Correcta
GBN debe ser **invisible** al CSS:

```
[Desarrollador escribe HTML/CSS]
        |
        v
[Agrega atributos glory* a elementos editables]
        |
        v
[Cliente edita visualmente con GBN]
        |
        v
[GBN aplica style="..." inline]
        |
        v
[Pagina guardada funciona IDENTICAMENTE con o sin GBN]
```

### Regla de Oro
```
Si desactivas GBN y el sitio se ve diferente (roto, sin estilos),
entonces GBN esta haciendo algo mal.
```

---

## 2. Inventario de CSS Actuales (AUDITADO)

> [!NOTE]
> Auditoria completada el 7 de Diciembre 2025. Ver detalles en Fase 0.

### Archivos CSS en `/assets/css/`

| Archivo              | Proposito Actual                    | Veredicto     | Accion Requerida                                             |
| :------------------- | :---------------------------------- | :------------ | :----------------------------------------------------------- |
| `gbn.css`            | Variables globales, debug overlay   | LIMPIAR       | Eliminar vars `:root` (lineas 7-19), conservar debug overlay |
| `theme-styles.css`   | Estilos base para componentes glory | ELIMINAR ~90% | Solo conservar estados del editor (loading, empty)           |
| `interactive.css`    | Estilos del editor (hover, select)  | CONSERVAR     | OK - Solo estilos de modo edicion                            |
| `components.css`     | Estilos varios                      | LIMPIAR       | Eliminar lineas 716-979 (Header/Menu/Logo base)              |
| `layout.css`         | Layout del panel                    | CONSERVAR     | OK - UI del panel lateral                                    |
| `modals.css`         | Modales y biblioteca                | CONSERVAR     | OK - UI del editor                                           |
| `variables.css`      | Variables de UI del editor          | LIMPIAR       | Eliminar lineas 9-19 (vars del tema)                         |
| `forms.css`          | Estilos de formularios              | CONSERVAR     | OK - UI del panel (campos del panel)                         |
| `formComponents.css` | Componentes de formulario           | CONSERVAR     | OK - UI del panel                                            |

### Criterio de Decision
- **CONSERVAR:** CSS que afecta elementos del EDITOR (panel, dock, overlay, biblioteca)
- **ELIMINAR:** CSS que afecta elementos del DESARROLLADOR (glory*, componentes del tema)
- **LIMPIAR:** Archivo mixto, requiere eliminacion selectiva

---

## 3. Roadmap de Implementacion

### Fase 0: Investigacion y Auditoria
**Estado:** COMPLETADA
**Fecha:** 7 Diciembre 2025

- [x] **0.1** Auditar `gbn.css` - Identificar que variables son del editor vs del tema
- [x] **0.2** Auditar `theme-styles.css` - Listar todos los selectores `[glory*]`
- [x] **0.3** Auditar `components.css` - Separar CSS de editor vs CSS de componentes
- [x] **0.4** Documentar hallazgos en la tabla de inventario (arriba)
- [x] **0.5** Identificar posibles regresiones (que podria romperse)

**Notas de la investigacion:**

#### 0.1 Auditoria de `gbn.css` (65 lineas)

| Lineas | Contenido                                        | Veredicto | Razon                                           |
| ------ | ------------------------------------------------ | --------- | ----------------------------------------------- |
| 1      | Import Google Fonts                              | ELIMINAR  | Deberia estar en CSS del tema, no de GBN        |
| 2-65   | `@layer gbn { ... }`                             | REVISAR   | Mezcla editor + tema                            |
| 7-19   | Variables `:root` (principal/secundario padding) | ELIMINAR  | Defaults que compiten con CSS del desarrollador |
| 24-30  | `[gloryContentRender], [data-gbnContent]`        | CONSERVAR | Selector de contenedor root de GBN              |
| 32-63  | `#gbn-debug-overlay`                             | CONSERVAR | Estilos del editor (debug)                      |

**Accion:** Eliminar lineas 1 y 7-19. Conservar el resto.

---

#### 0.2 Auditoria de `theme-styles.css` (573 lineas)

| Lineas  | Contenido                                                | Veredicto    | Razon                                                                |
| ------- | -------------------------------------------------------- | ------------ | -------------------------------------------------------------------- |
| 1-100   | Bloque comentado (data-gbn-root, h1-h6)                  | YA COMENTADO | Correctamente deshabilitado                                          |
| 108-135 | `[gloryDiv], .primario` estilos base                     | ELIMINAR     | Define display, flex, padding - compite con desarrollador            |
| 137-162 | `[gloryDivSecundario], .secundario` estilos              | ELIMINAR     | Misma razon                                                          |
| 165-177 | `[gloryImagen], .gbn-image` estilos                      | ELIMINAR     | Define display, position, max-width                                  |
| 184-204 | `[gloryButton], .gbn-button` estilos                     | ELIMINAR     | Define display, padding, colors                                      |
| 211-245 | `[gloryPostRender], .gbn-post-render` estilos            | PARCIAL      | Loading state es del editor (CONSERVAR), grid es del tema (ELIMINAR) |
| 248-278 | `[gloryPostItem], .gbn-post-item` estilos                | ELIMINAR     | Estilos de contenido                                                 |
| 260-271 | `.gbn-hover-lift`, `.gbn-hover-scale`, `.gbn-hover-glow` | ELIMINAR     | Efectos hover del tema                                               |
| 283-338 | `[gloryPostField='*']` estilos                           | ELIMINAR     | Estilos para campos de post                                          |
| 341-348 | `.gbn-post-render-empty`                                 | CONSERVAR    | Estado vacio (feedback del editor)                                   |
| 354-456 | Filter UI, Pagination UI, Responsive                     | REVISAR      | Algunos son UI del editor, otros del tema                            |
| 464-498 | Pattern `alternado_lr`                                   | ELIMINAR     | Layout pattern del tema                                              |
| 504-520 | `[gloryLogo], .gbn-logo`                                 | ELIMINAR     | Estilos base del logo                                                |
| 527-572 | `[gloryTarjeta], .gbn-tarjeta`                           | ELIMINAR     | Estilos base de tarjeta                                              |

**Resumen:** ~90% de este archivo deberia ELIMINARSE. Solo conservar estados del editor (loading, empty).

---

#### 0.3 Auditoria de `components.css` (1048 lineas)

| Lineas    | Contenido                                                  | Veredicto | Razon                                           |
| --------- | ---------------------------------------------------------- | --------- | ----------------------------------------------- |
| 1-79      | Toggle buttons (`.gbn-toggle-*`)                           | CONSERVAR | UI del editor                                   |
| 81-229    | Controls group (`.gbn-controls-*`, `.gbn-config-btn`)      | CONSERVAR | UI del editor                                   |
| 231-331   | Floating buttons (`.gbn-floating-*`, `.gbn-title-actions`) | CONSERVAR | UI del editor                                   |
| 333-361   | Tabs nav (`.gbn-tabs-nav`, `.gbn-tab-btn`)                 | CONSERVAR | UI del panel                                    |
| 363-391   | Botones y utilidades (`.gbn-btn`, `.is-hidden`)            | CONSERVAR | UI del editor                                   |
| 383-392   | Header styles `.glory-split__*`                            | REVISAR   | Parece componente split del tema                |
| 394-482   | Theme Panel Styles (`.gbn-theme-*`)                        | CONSERVAR | UI del panel de tema                            |
| 484-582   | Rich Text Editor (`.gbn-rich-text-*`)                      | CONSERVAR | UI del editor                                   |
| 585-714   | Custom Colors UI                                           | CONSERVAR | UI del panel                                    |
| 716-793   | Layout Components Header/Logo                              | ELIMINAR  | Estilos base para componentes del desarrollador |
| 794-979   | Menu Component + Mobile Responsive                         | ELIMINAR  | Estilos base/responsive del menu                |
| 981-1004  | Editor highlights (`.gbn-active [glory*]:hover`)           | CONSERVAR | Solo activo en modo edicion                     |
| 1006-1048 | Control group colors                                       | CONSERVAR | UI del editor                                   |

**Resumen:** Lineas 716-979 son estilos de componentes del tema. El resto es UI del editor.

---

#### 0.4 Auditoria de `variables.css` (29 lineas)

| Lineas | Contenido                                           | Veredicto | Razon                             |
| ------ | --------------------------------------------------- | --------- | --------------------------------- |
| 1-8    | Variables base panel (--bg, --text, --border-color) | CONSERVAR | Usadas por el panel lateral       |
| 9-19   | Variables tema (--gbn-text-color, --gbn-h1-*, etc)  | ELIMINAR  | Defaults que compiten con el tema |
| 21-28  | Variables mapeadas (--primary-bg, --overlay-bg)     | CONSERVAR | UI del editor                     |

---

#### 0.5 Regresiones Potenciales

| Riesgo | Descripcion                                         | Mitigacion                                                                         |
| ------ | --------------------------------------------------- | ---------------------------------------------------------------------------------- |
| ALTO   | Componentes insertados desde biblioteca sin estilos | Los componentes de biblioteca deben incluir HTML con clases CSS del tema existente |
| MEDIO  | PostRender sin grid layout                          | El desarrollador debe definir grid en su CSS para `[gloryPostRender]`              |
| MEDIO  | Botones sin estilos visibles                        | El desarrollador debe tener estilos para botones en su tema                        |
| BAJO   | Loading states no visibles                          | Conservar estilos de loading en GBN                                                |
| BAJO   | Editor highlights rotos                             | Solo eliminar estilos del tema, no del editor                                      |

---

#### Resumen Ejecutivo

| Archivo            | Lineas Total | A Eliminar | A Conservar | A Revisar |
| ------------------ | ------------ | ---------- | ----------- | --------- |
| `gbn.css`          | 65           | ~15        | ~45         | 5         |
| `theme-styles.css` | 573          | ~500       | ~50         | ~23       |
| `components.css`   | 1048         | ~264       | ~770        | ~14       |
| `variables.css`    | 29           | ~10        | ~19         | 0         |
| **TOTAL**          | 1715         | ~789       | ~884        | ~42       |

**Conclusion:** Aproximadamente 46% del CSS debe eliminarse para cumplir con la filosofia de transparencia.

---

### Fase 1: Limpieza de CSS Innecesarios
**Estado:** COMPLETADA
**Fecha:** 7 Diciembre 2025

- [x] **1.1** Eliminar variables `:root` de componentes en `gbn.css`
- [x] **1.2** Eliminar estilos `[gloryDiv]`, `[gloryTexto]`, etc. de `theme-styles.css`
- [x] **1.3** Mover cualquier CSS de editor mezclado a `interactive.css` (ya estaban separados)
- [x] **1.4** Eliminar estilos de Header/Menu/Logo de `components.css`
- [x] **1.5** Limpiar variables de tema en `variables.css`

**Archivos modificados:**
- `assets/css/gbn.css` - Eliminado import fonts, variables :root
- `assets/css/theme-styles.css` - Reducido de 573 a ~90 lineas (solo estados del editor)
- `assets/css/components.css` - Eliminadas lineas 716-979 (estilos base Layout)
- `assets/css/variables.css` - Eliminadas variables del tema

**Backup creado:** `assets/css/backup-2025-12-07/`

**Regresiones a verificar:**
- [x] El editor (panel, dock, inspector) sigue funcionando
- [x] Los elementos `glory*` siguen siendo detectables
- [x] No hay errores en consola

**Regresion encontrada y corregida:**
- `.case-card` necesitaba `width: 100%` en `landing.css` - Fix aplicado
- Esto NO era un problema del refactor, sino que el CSS del tema no tenia el width definido

**Notas de la implementacion:**

#### Cambios Realizados:

1. **gbn.css**: 
   - Eliminado `@import` de Google Fonts (debe estar en CSS del tema)
   - Eliminadas variables CSS `:root` para padding de principal/secundario
   - Conservado debug overlay y selector `[gloryContentRender]`

2. **theme-styles.css**:
   - Archivo reconstruido desde cero
   - Solo contiene: loading states, empty states, clones de preview
   - Eliminados ~500 lineas de estilos base de componentes

3. **components.css**:
   - Eliminados estilos base de Header, Logo, Menu, Footer
   - Conservados estilos del editor (toggle, controls, tabs, theme panel)
   - Reducido de 1048 a ~780 lineas

4. **variables.css**:
   - Eliminadas variables `--gbn-text-*`, `--gbn-h1-*`, `--gbn-primary`, etc.
   - Conservadas solo variables para UI del panel del editor


---

### Fase 1.6: Solucion Temporal - Container Queries
**Estado:** COMPLETADA
**Fecha:** 7 Diciembre 2025
**Prioridad:** Temporal (hasta implementar Fase 2)

> [!NOTE]
> Esta es una solucion temporal mientras se implementa la arquitectura Iframe-First (Fase 2).
> Container Queries permiten que los estilos responsive respondan al ancho del contenedor,
> no al viewport del navegador. Esto hace que el preview de GBN funcione cuando se cambia
> el ancho del contenedor root.

#### 1.6.1 Cambios Realizados

**Archivo:** `App/Assets/css/init.css`
- Agregado `container-type: inline-size` a `[data-gbn-root]`, `.landing-container`, `main`
- Definido `container-name: gbn-root` para referenciar en las queries

**Archivos convertidos de `@media` a `@container gbn-root`:**

| Archivo                | Media Queries Convertidas        |
| ---------------------- | -------------------------------- |
| `landing.css`          | 4 queries (768px, 1024px, 600px) |
| `header.css`           | 2 queries (768px, 1024px)        |
| `casos.css`            | 4 queries (768px, 1024px)        |
| `about.css`            | 2 queries (768px, 1024px)        |
| `servicios.css`        | 3 queries (768px, 1024px)        |
| `marquee.css`          | 1 query (1024px)                 |
| `single-portfolio.css` | 2 queries (768px, 900px)         |

**Archivo NO convertido:**
- `init.css` - La media query de scrollbar (640px) permanece como `@media` ya que afecta el viewport real, no el contenedor

#### 1.6.2 Como Funciona

```css
/* Antes: respondia al viewport del navegador */
@media (max-width: 768px) { ... }

/* Ahora: responde al ancho del contenedor [data-gbn-root] */
@container gbn-root (max-width: 768px) { ... }
```

Cuando GBN cambia `max-width` del contenedor root a 768px (modo tablet) o 375px (modo mobile),
las container queries se activan automaticamente.

#### 1.6.3 Limitaciones

- Solo funciona para elementos DENTRO del container (no para el body, html o elementos fixed)
- El header fixed puede no responder correctamente (ya se maneja con JS en `responsive.js`)
- Requiere navegadores modernos (Chrome 105+, Firefox 110+, Safari 16+)

#### 1.6.4 Fallback

Para navegadores antiguos, los estilos base (desktop) se aplicaran siempre.
Considerar agregar `@supports` check si es necesario.

---

### Fase 2: Arquitectura Iframe-First (Opcion G)
**Estado:** EN PLANIFICACION
**Dependencia:** Fase 1 completada
**Estimacion:** 8-12 horas (es un cambio arquitectonico mayor)
**Relacionado:** BUG-022

> [!CAUTION]
> Esta es una refactorizacion arquitectonica significativa. El contenido editable vivira SIEMPRE dentro de un iframe. La pagina padre sera solo el "shell" del editor.

#### Problemas del Primer Intento (Documentados)

| Problema                   | Causa Raiz                                                     | Solucion en Opcion G                                                 |
| -------------------------- | -------------------------------------------------------------- | -------------------------------------------------------------------- |
| Inspector no visible       | El inspector vivia en la pagina padre, no dentro del iframe    | Inspector como overlay ENCIMA del iframe, con coordenadas calculadas |
| Header/Footer duplicados   | El iframe cargaba TODO el template WordPress                   | PHP detecta `?gbn-iframe=1` y NO carga header/footer del tema        |
| Cambios no se reflejaban   | El iframe cargaba desde servidor (estado guardado), no memoria | El contenido se inyecta via postMessage, NO se recarga la pagina     |
| Sincronizacion de guardado | Dos instancias de GBN compitiendo                              | UNA sola instancia de GBN en el padre, iframe es solo renderer       |

---

#### 2.1 Arquitectura General

**Diagrama de Componentes:**
```
+============================================================================+
|  PAGINA PADRE (Shell del Editor)                                           |
|  - GBN Core (state.js, store.js, persistence.js)                           |
|  - Panel lateral (panel-core.js, panel-render.js)                          |
|  - Dock de herramientas (dock.js)                                          |
|  - Inspector OVERLAY (se dibuja encima del iframe)                         |
|  - Theme Settings, Library, Context Menu                                   |
+============================================================================+
|                                                                            |
|  +----------------------------------------------------------------------+  |
|  |  IFRAME CONTAINER (posicion relativa para el overlay)               |  |
|  |  - CSS: position: relative; overflow: hidden;                       |  |
|  |  - Redimensionable: 100% | 768px | 375px                            |  |
|  |                                                                      |  |
|  |  +----------------------------------------------------------------+  |  |
|  |  |  IFRAME (contenido editable)                                  |  |  |
|  |  |  - Carga con ?gbn-iframe=1                                    |  |  |
|  |  |  - NO carga: header.php, footer.php, GBN scripts              |  |  |
|  |  |  - SI carga: CSS del tema, contenido de la pagina             |  |  |
|  |  |  - Contiene: gbn-iframe-bridge.js (receptor de mensajes)      |  |  |
|  |  +----------------------------------------------------------------+  |  |
|  |                                                                      |  |
|  |  +----------------------------------------------------------------+  |  |
|  |  |  INSPECTOR OVERLAY (position: absolute sobre el iframe)       |  |  |
|  |  |  - Hover highlight, selection highlight                       |  |  |
|  |  |  - Botones de control (+, delete, move)                       |  |  |
|  |  |  - Calcula posiciones usando iframe.getBoundingClientRect()   |  |  |
|  |  +----------------------------------------------------------------+  |  |
|  +----------------------------------------------------------------------+  |
|                                                                            |
+============================================================================+
```

**Flujo de Datos Unidireccional:**
```
Usuario hace click en elemento dentro del iframe
    |
    v
[gbn-iframe-bridge.js] Detecta click, envia postMessage al padre
    |
    v
[inspector-overlay.js] Recibe blockId, dibuja highlight sobre el iframe
    |
    v
[panel-core.js] Abre panel con config del bloque (desde state.js del padre)
    |
    v
Usuario edita valor en panel
    |
    v
[state.js] Actualiza estado en memoria (PADRE es fuente de verdad)
    |
    v
[postMessage] Envia cambio al iframe: {type: 'updateStyle', blockId, css}
    |
    v
[gbn-iframe-bridge.js] Aplica style inline al elemento
    |
    v
[persistence.js] Guarda desde el PADRE (serializa estado + DOM del iframe)
```

---

#### 2.2 Componentes a Crear/Modificar

##### 2.2.1 PHP: Modo Iframe
**Archivo:** `GbnManager.php` (modificar)

```php
// Detectar modo iframe
public static function isIframeMode(): bool {
    return isset($_GET['gbn-iframe']) && $_GET['gbn-iframe'] === '1';
}

// En wp_enqueue_scripts:
if (self::isIframeMode()) {
    // NO cargar scripts del editor
    // Solo cargar: gbn-iframe-bridge.js (minimo)
    wp_enqueue_script('gbn-iframe-bridge', ...);
    return;
}
```

**Archivo:** `header.php` / `footer.php` del TEMA (modificar)

```php
<?php
// Al inicio de header.php y footer.php
if (isset($_GET['gbn-iframe']) && $_GET['gbn-iframe'] === '1') {
    // En modo iframe: NO renderizar header/footer
    // Solo continuar si es el content area
    return;
}
?>
```

**Nota:** Alternativa mejor - usar un template especial `iframe-content.php`:
```php
// En functions.php del tema
add_filter('template_include', function($template) {
    if (isset($_GET['gbn-iframe'])) {
        return get_template_directory() . '/iframe-content.php';
    }
    return $template;
});
```

##### 2.2.2 JS: Iframe Container Manager
**Archivo nuevo:** `assets/js/services/iframe-manager.js`

Responsabilidades:
- Crear el iframe y el contenedor overlay
- Manejar redimensionamiento (desktop/tablet/mobile)
- Comunicacion bidireccional via postMessage
- Sincronizar estado inicial al cargar

```javascript
// API Publica
Gbn.iframeManager = {
    init: function(containerId) { ... },
    setViewport: function(breakpoint) { ... }, // 'desktop' | 'tablet' | 'mobile'
    sendUpdate: function(blockId, styles) { ... },
    getIframeDocument: function() { ... },
    destroy: function() { ... }
};
```

##### 2.2.3 JS: Iframe Bridge (corre DENTRO del iframe)
**Archivo nuevo:** `assets/js/gbn-iframe-bridge.js`

Responsabilidades:
- Escuchar postMessage del padre
- Aplicar cambios de estilo al DOM
- Detectar clicks y enviar blockId al padre
- Detectar hover y enviar posiciones al padre

```javascript
// NO depende de Gbn.* (es independiente)
(function() {
    window.addEventListener('message', function(event) {
        if (event.data.type === 'gbn:updateStyle') {
            var el = document.querySelector('[data-gbn-id="' + event.data.blockId + '"]');
            if (el) el.style.cssText = event.data.cssText;
        }
        if (event.data.type === 'gbn:injectState') {
            // Inyectar HTML inicial desde el padre
        }
    });
    
    document.addEventListener('click', function(e) {
        var block = e.target.closest('[data-gbn-id]');
        if (block) {
            parent.postMessage({
                type: 'gbn:blockClicked',
                blockId: block.getAttribute('data-gbn-id'),
                rect: block.getBoundingClientRect()
            }, '*');
        }
    });
})();
```

##### 2.2.4 JS: Inspector Overlay (se dibuja SOBRE el iframe)
**Archivo nuevo:** `assets/js/ui/inspector/overlay-renderer.js`

Responsabilidades:
- Crear elemento overlay (div con position: absolute)
- Recibir coordenadas del iframe y dibujar highlight
- CUIDADO: Las coordenadas del iframe son relativas a su viewport, hay que sumar el offset del iframe container

```javascript
// Calculo de posiciones
function translateRectFromIframe(iframeRect, elementRect) {
    return {
        top: iframeRect.top + elementRect.top,
        left: iframeRect.left + elementRect.left,
        width: elementRect.width,
        height: elementRect.height
    };
}
```

##### 2.2.5 Modificar: responsive.js
**Archivo:** `assets/js/services/responsive.js` (modificar)

```javascript
// En lugar de:
function applyViewportSimulation(breakpoint) {
    var root = document.querySelector('[data-gbn-root]');
    root.style.maxWidth = widths[breakpoint];
}

// Ahora:
function applyViewportSimulation(breakpoint) {
    // Delegar al iframe manager
    if (Gbn.iframeManager) {
        Gbn.iframeManager.setViewport(breakpoint);
    }
}
```

##### 2.2.6 Modificar: persistence.js
**Archivo:** `assets/js/services/persistence.js` (modificar)

Al guardar, serializar el contenido del IFRAME, no de la pagina padre:

```javascript
function collectContent() {
    var iframeDoc = Gbn.iframeManager.getIframeDocument();
    var contentRoot = iframeDoc.querySelector('[data-gbn-root]');
    // Serializar contentRoot...
}
```

---

#### 2.3 Flujos Detallados

##### 2.3.1 Flujo: Carga Inicial

```
1. Usuario abre pagina.php?gbn=1 (modo editor)
   
2. PADRE carga:
   - Shell del editor (panel, dock, inspector overlay)
   - Crea iframe con src="pagina.php?gbn-iframe=1"
   
3. IFRAME carga:
   - PHP detecta gbn-iframe=1
   - NO incluye header.php / footer.php
   - SI incluye CSS del tema
   - SI incluye contenido de la pagina
   - SI incluye gbn-iframe-bridge.js
   
4. IFRAME envia postMessage: {type: 'gbn:ready'}

5. PADRE recibe 'ready':
   - Escanea DOM del iframe para construir state.js
   - O: Envia estado guardado via postMessage al iframe
   
6. Editor listo para usarse
```

##### 2.3.2 Flujo: Usuario hace click en elemento

```
1. Click dentro del IFRAME

2. [gbn-iframe-bridge.js]:
   - Detecta click en elemento con [data-gbn-id]
   - Obtiene rect via getBoundingClientRect()
   - Envia postMessage al padre:
     {type: 'gbn:blockClicked', blockId: 'xyz', rect: {...}}

3. [inspector-overlay.js] en PADRE:
   - Recibe mensaje
   - Traduce coordenadas (sumar offset del iframe container)
   - Dibuja highlight en el overlay
   - Marca bloque como seleccionado en state.js

4. [panel-core.js]:
   - Abre panel con schema del bloque seleccionado
```

##### 2.3.3 Flujo: Usuario edita valor en panel

```
1. Usuario cambia padding a 20px en el panel

2. [panel-render.js]:
   - Llama: Gbn.actions.updateBlock(blockId, {padding: {top: '20px'}}, breakpoint)

3. [state.js]:
   - Actualiza estado en memoria
   - Dispara evento 'gbn:configChanged'

4. [store-subscriber.js]:
   - Escucha evento
   - Genera nuevo CSS para el bloque
   - Envia postMessage al iframe:
     {type: 'gbn:updateStyle', blockId: 'xyz', cssText: 'padding-top: 20px;'}

5. [gbn-iframe-bridge.js] en IFRAME:
   - Recibe mensaje
   - Aplica: element.style.cssText = cssText
   - (Opcional) Envia confirmacion

6. El usuario ve el cambio reflejado en tiempo real
```

##### 2.3.4 Flujo: Cambio de Breakpoint

```
1. Usuario hace click en boton "Tablet" en el dock

2. [dock.js]:
   - Llama: Gbn.responsive.setBreakpoint('tablet')

3. [responsive.js]:
   - Actualiza currentBreakpoint
   - Llama: Gbn.iframeManager.setViewport('tablet')

4. [iframe-manager.js]:
   - Cambia width del iframe container a 768px
   - Transicion CSS suave
   - Las media queries del CSS del tema ahora funcionan nativamente

5. [panel-render.js]:
   - Recibe evento 'gbn:breakpointChanged'
   - Re-renderiza panel con valores del nuevo breakpoint
```

##### 2.3.5 Flujo: Guardar

```
1. Usuario hace click en "Guardar"

2. [persistence.js]:
   - Obtiene DOM del iframe: iframe.contentDocument
   - Serializa contenido de [data-gbn-root]
   - Combina con estado de state.js
   - Envia AJAX al backend

3. Backend:
   - Guarda HTML en post_meta o en el contenido
   - Responde OK

4. Usuario recibe confirmacion
```

---

#### 2.4 Consideraciones Criticas

##### 2.4.1 Cross-Origin
El iframe carga la misma pagina (mismo dominio), asi que NO hay problemas de cross-origin.

##### 2.4.2 Performance
- El iframe solo carga contenido, sin header/footer/scripts pesados
- El inspector overlay usa RAF para actualizaciones suaves
- Los postMessage son asincronos pero rapidos

##### 2.4.3 Header/Footer en Editor
**Pregunta:** Si el iframe no tiene header/footer, como los edita el usuario?

**Respuesta:** El header y footer se editan en una vista donde SI estan presentes. Opciones:
1. **Modo "Editar Header"**: Carga un iframe especial solo con header
2. **Vista Desktop siempre tiene header/footer**: Solo tablet/mobile usan iframe reducido

**Decision recomendada:** Opcion 2 - En desktop el iframe incluye todo, en tablet/mobile se ocultan con CSS (not header/footer PHP).

##### 2.4.4 Scroll Sync
Si el usuario hace scroll en el iframe, el overlay debe moverse junto. Solucion:
```javascript
iframe.contentWindow.addEventListener('scroll', function() {
    parent.postMessage({type: 'gbn:scroll', scrollY: this.scrollY}, '*');
});
```

##### 2.4.5 Drag and Drop entre bloques
El drag-drop actual funciona en el DOM directo. Con iframe:
- El drag se inicia en el overlay (padre)
- La posicion del drop se calcula usando coordenadas traducidas
- El drop modifica el DOM del iframe via postMessage

---

#### 2.5 Tareas de Implementacion

##### Fase 2.A: Infraestructura Base
- [ ] **2.A.1** Crear template `iframe-content.php` en el tema
- [ ] **2.A.2** Modificar `GbnManager.php` para detectar `?gbn-iframe=1`
- [ ] **2.A.3** Crear `gbn-iframe-bridge.js` (version minima)
- [ ] **2.A.4** Crear `iframe-manager.js` con init/destroy

##### Fase 2.B: Inspector sobre Iframe
- [ ] **2.B.1** Crear `overlay-renderer.js` para dibujar highlights
- [ ] **2.B.2** Implementar traduccion de coordenadas iframe->padre
- [ ] **2.B.3** Conectar clicks del iframe con seleccion en padre
- [ ] **2.B.4** Conectar hover del iframe con highlight en overlay

##### Fase 2.C: Sincronizacion de Estilos
- [ ] **2.C.1** Modificar `store-subscriber.js` para enviar cambios al iframe
- [ ] **2.C.2** Implementar recepcion de cambios en `gbn-iframe-bridge.js`
- [ ] **2.C.3** Verificar que cambios son bidireccionales

##### Fase 2.D: Cambio de Viewport
- [ ] **2.D.1** Modificar `responsive.js` para delegar a `iframe-manager.js`
- [ ] **2.D.2** Implementar `setViewport()` con transiciones
- [ ] **2.D.3** Verificar media queries funcionan nativamente

##### Fase 2.E: Persistencia
- [ ] **2.E.1** Modificar `persistence.js` para leer DOM del iframe
- [ ] **2.E.2** Verificar que el guardado funciona igual que antes
- [ ] **2.E.3** Verificar que los cambios persisten al recargar

##### Fase 2.F: Casos Especiales
- [ ] **2.F.1** Header/Footer: Decidir estrategia y implementar
- [ ] **2.F.2** Drag-Drop: Adaptar para funcionar con iframe
- [ ] **2.F.3** Context Menu: Posicionar sobre overlay
- [ ] **2.F.4** Library: Insertar elementos en el iframe

---

#### 2.6 Riesgos y Mitigaciones

| Riesgo                    | Probabilidad | Impacto | Mitigacion                            |
| ------------------------- | ------------ | ------- | ------------------------------------- |
| Latencia en postMessage   | Baja         | Media   | Usar debounce para cambios frecuentes |
| Coordenadas incorrectas   | Media        | Alta    | Tests exhaustivos de traduccion       |
| Memory leaks en listeners | Media        | Media   | Cleanup explicito en destroy()        |
| Incompatibilidad IE11     | Baja         | Baja    | postMessage es compatible             |
| Scroll desincronizado     | Media        | Media   | Listener de scroll en iframe          |

**Notas de la implementacion:**
<!-- La IA agregara notas aqui durante la implementacion -->

---

### Fase 3: Validacion y Testing
**Estado:** PENDIENTE
**Dependencia:** Fases 1 y 2 completadas
**Estimacion:** 1-2 horas

- [ ] **3.1** Test: Desactivar GBN y verificar que pagina se ve igual
- [ ] **3.2** Test: Cambiar a mobile/tablet y verificar media queries funcionan
- [ ] **3.3** Test: Editar elemento en desktop, verificar cambio en preview mobile
- [ ] **3.4** Test: Guardar cambios y verificar persistencia
- [ ] **3.5** Test: Elementos creados desde biblioteca funcionan sin CSS de GBN

**Casos de prueba detallados:**
| #   | Caso                            | Resultado Esperado                  | Estado |
| --- | ------------------------------- | ----------------------------------- | ------ |
| 1   | Abrir pagina sin estar logueado | Pagina identica a con GBN           |        |
| 2   | Cambiar a vista mobile          | Media queries del tema se activan   |        |
| 3   | Editar padding de un div        | Cambio reflejado en preview iframe  |        |
| 4   | Guardar y recargar              | Cambios persisten, preview correcto |        |

---

### Fase 4: Documentacion y Cleanup
**Estado:** PENDIENTE
**Dependencia:** Fase 3 completada
**Estimacion:** 30 min

- [ ] **4.1** Actualizar `reglas.md` con lecciones aprendidas
- [ ] **4.2** Actualizar `plan.md` - Marcar BUG-022 y REFACTORs como resueltos
- [ ] **4.3** Actualizar `documentacion-gbn.md` si aplica
- [ ] **4.4** Eliminar este archivo o moverlo a historial

---

## 4. Decisiones Arquitectonicas

> [!NOTE]
> Documentar aqui las decisiones tomadas durante el refactor.

### ADR-001: Theme Settings Independiente de GBN
**Fecha:** 7 Diciembre 2025

**Contexto:**
El usuario final necesita poder establecer defaults globales (fuente de h1, color de texto, etc.) pero estos defaults deben funcionar SIN que GBN este activo. Esto crea una tension con la filosofia de transparencia.

**Problema:**
Si GBN inyecta variables CSS en `:root` para los defaults, entonces:
- El sitio depende de GBN para verse bien (viola transparencia)
- Si desactivas GBN, los defaults desaparecen

**Decision: Opcion C - Separacion Tema/Editor**

1. **GBN es SOLO la UI** para editar los Theme Settings
2. **El TEMA aplica los estilos** generando un `<style>` en el `<head>`
3. Los valores se guardan en `wp_options` (ya funciona asi)
4. Un hook del TEMA lee esos valores y genera CSS con selectores de baja especificidad

**Flujo:**

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Programador define CSS base:                             │
│    h1 { font-size: 32px; }                                  │
│    (Especificidad: 0,0,0,1)                                 │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. Usuario cambia h1 a 40px via Theme Settings              │
│    GBN guarda en wp_options: {h1: {fontSize: '40px'}}       │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. TEMA (no GBN) genera en <head>:                          │
│    :where(h1) { font-size: 40px; }                          │
│    (Especificidad: 0,0,0,0 - permite override del dev)      │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. Resultado:                                               │
│    - Sin GBN: Sitio usa los Theme Settings guardados        │
│    - Desarrollador puede sobrescribir con clases            │
│    - Usuario puede personalizar desde el panel              │
└─────────────────────────────────────────────────────────────┘
```

**Implementacion Requerida:**

1. **PHP (Tema):** Crear hook que genere `<style>` con Theme Settings guardados
2. **JS (GBN):** Theme Settings lee valores computados del DOM, no de defaults hardcodeados
3. **CSS:** Usar `:where()` para especificidad 0

**Consecuencias:**

Positivas:
- Cumple transparencia total
- Site funciona sin GBN
- Desarrollador puede sobrescribir
- Usuario puede personalizar

Negativas:
- Requiere cooperacion tema-GBN
- Nuevos temas deben implementar el hook

---

### Fase 1.5: Theme Settings Transparente (NUEVA)
**Estado:** COMPLETADA
**Prioridad:** ALTA (Complemento de Fase 1)
**Fecha:** 7 Diciembre 2025

#### 1.5.1 Backend PHP (Tema) - COMPLETADO
- [x] Crear `ThemeSettingsRenderer.php` en `App/Helpers/`
- [x] Hook en `wp_head` para generar `<style>` con Theme Settings guardados
- [x] Leer de `wp_options` (key: `gbn_theme_settings`)
- [x] Usar selectores `:where(h1)`, `:where(h2)`, `:where(p)`, etc.
- [x] Filtrar propiedades invalidas (contenido, posicionamiento, defaults)

**Archivo creado:** `App/Helpers/ThemeSettingsRenderer.php`

**Implementacion:**
- Hook `wp_head` con prioridad 5
- Genera `<style id="glory-theme-settings">`
- Tipografia: `:where(h1)`, etc. para especificidad 0
- Filtros inteligentes:
  - Lista de propiedades ignoradas (texto, url, position, etc.)
  - Validacion de valores CSS (ignora defaults como `block`, `none`, `0px`)
  - Ignora valores de contenido (URLs, textos largos)

#### 1.5.2 Frontend JS (GBN) - COMPLETADO
- [x] Modificar `applicator.js` para NO duplicar aplicacion de settings
- [x] Detectar existencia de `<style id="glory-theme-settings">`
- [x] `applySingleValue()` para edicion en tiempo real

#### 1.5.3 Items Opcionales (Mejoras Futuras)
- [ ] Crear elementos de referencia temporales para leer valores computados
- [ ] Mostrar valores reales en el panel, no defaults hardcodeados


---

## 5. Notas y Observaciones

> [!TIP]
> La IA puede agregar notas aqui durante cualquier fase del trabajo.

### 7 Diciembre 2025 - Inicio del Refactor
- Se identifico que GBN se habia desviado de su filosofia original
- Se decidio que la solucion para BUG-022 debe ser iframe real, no inyeccion CSS
- Se creo este roadmap para guiar el trabajo
- **Fase 0 y 1 completadas** - Limpieza de ~776 lineas de CSS
- **Fase 1.5 completada** - Theme Settings ahora generados por PHP del tema:
  - Tipografia con `:where()` para especificidad 0
  - Filtros inteligentes para ignorar defaults y propiedades invalidas
  - El sitio funciona IDENTICAMENTE con o sin GBN activo
- **ADR-001 documentada** - Theme Settings seran aplicados por el tema, no por GBN

---

## 6. Archivos Relacionados

| Archivo                | Proposito                                                 |
| ---------------------- | --------------------------------------------------------- |
| `reglas.md`            | Principios arquitectonicos (seccion 1.2 actualizada)      |
| `plan.md`              | Plan general y bugs (BUG-022, REFACTOR-001, REFACTOR-011) |
| `documentacion-gbn.md` | Documentacion de uso                                      |

---

## 7. Historial de Cambios

| Fecha      | Cambio                                                                                                                                                                             | Autor |
| ---------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----- |
| 2025-12-07 | Creacion del documento                                                                                                                                                             | IA    |
| 2025-12-07 | Fase 0 y 1 completadas, ADR-001 documentada                                                                                                                                        | IA    |
| 2025-12-07 | Agregada Fase 1.5 para Theme Settings                                                                                                                                              | IA    |
| 2025-12-07 | Fase 1.5.1 (PHP) y 1.5.2 parcial completadas                                                                                                                                       | IA    |
| 2025-12-07 | **Fase 1.5 COMPLETADA** - Filtros inteligentes de defaults                                                                                                                         | IA    |
| 2025-12-07 | **Fase 2 REPLANIFICADA** - Arquitectura Iframe-First (Opcion G) con plan exhaustivo para resolver problemas de inspector, duplicacion header/footer, sincronizacion de estado      | IA    |
| 2025-12-07 | **Fase 1.6 COMPLETADA** - Container Queries implementadas como solucion temporal. Convertidos 18 @media queries a @container en 7 archivos CSS. Fase 2 (Iframe) queda para futuro. | IA    |


