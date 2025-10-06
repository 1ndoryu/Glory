## Guía práctica: extender un elemento/shortcode nativo de Avada (Front + Live Builder)

Este documento es una guía reusable para extender un elemento nativo de Avada (ejemplo real: `Title` con "Fit To Width (No Wrap)") y hacerlo funcionar correctamente en el front y en el Live Builder. Incluye estructura, eventos, encolado de scripts y patrones de compatibilidad.

### Objetivo

- Ajustar automáticamente el tamaño de fuente del heading del `Title` para que el texto ocupe el ancho disponible en una sola línea (sin saltos), preservando la apariencia en front y habilitando un comportamiento coherente en el Live Builder.

### Archivos clave (puntos de extensión)

- `plugins/fusion-builder/shortcodes/fusion-title.php`
  - Encolado del script `fusion-title-fit.js`.
  - Nuevos parámetros y atributos: `fit_to_width` (Yes/No) y estilos asociados.

- `plugins/fusion-builder/front-end/views/elements/view-title.js`
  - Vista del Live Builder para Title: añade clase `awb-fit-title` cuando `fit_to_width=yes` y desactiva tipografía responsiva para evitar conflictos.

- `plugins/fusion-builder/front-end/fusion-frontend-combined.js`
  - Bundle del Live Builder donde se sincronizaron los cambios de la vista para asegurar el comportamiento sin recargar el bundle por herramientas externas.

- `plugins/fusion-builder/assets/js/min/general/*.js`
  - Scripts propios para la nueva funcionalidad (lógica, detección y re-cálculo; compatibilidad con Live Builder).

### Flujo del marcado

- Wrapper del título: `.fusion-title`
- Heading interno: `.fusion-title-heading`
- Clase de activación del ajuste: `.awb-fit-title` (se añade cuando `fit_to_width=yes`).

### Algoritmo de ajuste (binario)

1. Garantizar condiciones de medida:
   - `white-space: nowrap; display: block; width: 100%` sobre el heading durante la medición.
2. Determinar ancho de contenedor: máximo entre el ancho del wrapper y el de su padre. En Live Builder se descuenta padding/margen/borde horizontales del heading para evitar saltos por unos pocos píxeles.
3. Búsqueda binaria de 14 iteraciones entre `[4px, max(3*fontSize, 600px)]` para encontrar el `font-size` máximo que no exceda el ancho disponible.
4. Aplicar `font-size` resultante y asegurar `opacity:1`.

### Live Builder: eventos y detección

- Señales del builder: `FusionPageBuilder`/toolbar/iframe `#fb-preview`.
- Evento de render por elemento (patrón): `fusion-element-render-{shortcode}` (se dispara en el preview dentro del iframe). Lo escuchamos y:
  - Si `fit_to_width=yes`, garantizamos `awb-fit-title` y recalculamos.
  - Si `fit_to_width=off`, limpiamos estilos y evitamos recalcular (unfit).
- Eventos generales observados: `fusion-preview-updated`, `fusion-element-updated`, etc., además de `resize`, `load`, `fonts.ready` y `mutation/resize observers`.

### Lectura del estado (params) en Live Builder

- Se implementó `getBuilderParams(cid)` para obtener `params` desde los ViewManagers disponibles:
  - `FusionPageBuilderViewManager` o `FusionPageBuilderApp.ViewManager`.
- Se extrae `cid` desde la clase `fusion-title-cid{cid}` o `data-cid` del contenedor `.fusion-builder-live-element`.
- Con esto, `shouldApplyFit(wrapper)` decide si aplicar el ajuste:
  - ON: aplicar (y mantener nowrap en sesión builder).
  - OFF: limpiar estilos (`unfitWrapper`) y omitir recalcular.

### Limpieza cuando está OFF

- `unfitWrapper(wrapper)`:
  - Remueve `awb-fit-title`.
  - Resetea estilos directos del heading (`font-size`, `white-space`, `display`, `width`).
  - Limpia datasets internos y clase `awb-responsive-type__disable`.

### Observadores y robustez

- `ResizeObserver` sobre wrappers para re-calcular ante cambios de tamaño.
- `MutationObserver` sobre el documento para detectar inserciones/cambios de clase.
- `IntersectionObserver` como fallback cuando el elemento no es medible (invisible/0 ancho).
- Re-cálculos adicionales en `window load`, `document.fonts.ready`, y pequeños timeouts (300/800ms) para el front.

### Consideraciones de UX y rendimiento

- Se desactiva tipografía responsiva (`.awb-responsive-type__disable`) cuando el ajuste está ON para evitar que estilos responsivos reescalen el heading tras nuestro cálculo.
- En Live Builder, conservamos `white-space:nowrap` mientras esté ON para eliminar saltos transitorios.
- Se evitó el auto-marcado masivo de `.awb-fit-title` en builder; ahora el ajuste depende de `params.fit_to_width`.

### Problemas y soluciones

- El Live Builder no aplicaba la clase `awb-fit-title`: se añadió en la vista JS y en el evento `fusion-element-render-fusion_title` solo cuando la opción está en ON.
- El front no recalculaba en todos los casos: se añadieron recálculos tras `load`, `fonts.ready` y con timeouts.
- En builder, al desactivar (OFF) seguía activo: ahora `shouldApplyFit` corta el flujo, limpia estilos y previene nuevos cálculos.
- Saltos de línea por diferencias de ancho en builder: se ajustó el ancho efectivo restando paddings/márgenes/bordes del heading en el cálculo.

### Pasos para replicar/ampliar en otros elementos (checklist)

1. Añadir parámetro en el shortcode/PHP si procede y mapearlo al builder.
2. En la vista del Live Builder, componer clases/atributos cuando el parámetro esté activo.
3. Escribir un JS de comportamiento:
   - Detección robusta de builder (iframe, eventos, managers).
   - Re-cálculo idempotente y escalonado.
   - Limpieza cuando esté OFF.
4. Escuchar el evento de render específico del elemento (`fusion-element-render-{shortcode}`) dentro del preview.
5. Cache-busting al encolar el script cuando se itera en desarrollo.
6. Validar compatibilidad con tipografía responsiva y estilos globales (añadir/remover clases tipo `awb-responsive-type__disable`).

---

Última actualización: mantener esta guía si Avada cambia eventos internos o la estructura del preview. Si un evento deja de disparar, usar `MutationObserver` como red de seguridad y rastrear el nuevo evento equivalente.


