GBN será un constructor visual con compatibilidad con el front y el back (código). Debe permitir construir sitios desde el HTML plano y, opcionalmente, desde la interfaz del front. Mantiene una UI en inglés, pero todo el código queda en español. El núcleo en `Glory/` se mantiene agnóstico; cualquier personalización específica vive en el tema. 

Los componentes de Glory deberan de mantener su propias configuaciones compatibles en el codigo y el constructor en el front, se adaptara los componentes poco a poco. Seguir los principios solid.

## Principios
- HTML limpio sin shortcodes.
- Al apagar GBN (no cargar scripts ni estilos) la página debe comportarse igual gracias a que el marcado mantiene estilos renderizados.
- Código minimalista, ordenado y fácil de escalar.
- Los componentes de Glory deben aceptar progresivamente la capa GBN sin romper su uso independiente.
- Evitar repetir el error de Avada Constructor, donde el codigo se repite una y otra vez, no, aquí hay que seguir los principios solid a como de lugar, centralizar cosas y no de repetir nada.

## Contexto Glory, tema y PageManager
- `Glory/` actúa como micro–framework agnóstico: provee managers (assets, páginas, menús), componentes reutilizables y utilidades base. No debe incluir lógica específica del proyecto.
- El tema (carpeta `App/`) es quien orquesta la implementación concreta del sitio y registra los assets propios mediante `AssetManager`. GBN vive como paquete dentro de Glory pero sólo expone herramientas agnósticas para que cada tema active o personalice el builder.
- `PageManager` sincroniza las páginas gestionadas desde código. Define slugs, plantillas, modo de contenido (`code` vs `editor`) y mantiene metadatos (`_glory_content_hash`, `_glory_content_mode`) para detectar cambios manuales.
- GBN se apoya en `PageManager` para saber cuándo una página está en modo `editor` y así persistir configuraciones sin pisar contenido escrito a mano. El objetivo es que los cambios realizados en el front queden almacenados en metadatos propios y, opcionalmente, en el campo `post_content` sólo cuando el modo `editor` esté habilitado.

## Flujo de persistencia y sincronización planificado
1. **Captura de estado en el navegador**: `Gbn.state` mantiene un registro por bloque con `config`, `schema`, estilos y metadatos (`role`, `postType`, etc.). Cada interacción del panel actualiza el estado y refleja cambios inmediatos en el DOM.
2. **Serialización por bloque**: al guardar, se construye un payload JSON con la estructura:
   ```json
   {
     "pageId": 123,
     "blocks": [
       {
         "id": "gbn-xyz",
         "role": "principal",
         "config": {"padding": {...}},
         "styles": {"padding-top": "40px"},
         "order": 0,
         "children": [ ... ]
       }
     ]
   }
   ```
   El orden y las relaciones padre-hijo se calculan leyendo el árbol de `data-gbn-id`.
3. **Transporte AJAX**: se usarán endpoints vía `gloryAjax` con `nonce` `glory_gbn_nonce` y URL entregada en `gloryGbnCfg`:
   - `gbn_save_options` (guardar opciones/estilos por bloque)
   - `gbn_preview_block` (vista previa con overrides sin persistir)
   - `gbn_get_page_settings` / `gbn_save_page_settings` (configuraciones por página)
4. **Persistencia en WordPress**:
   - Los datos se guardarán en el metadato `gbn_config` (array por bloque) asociado al `pageId`.
   - El CSS instanciado se guarda en `gbn_styles` para poder regenerar `<style data-gbn-style="...">` sin depender del inline original.
   - Cuando la página esté en modo `editor`, `PageManager` podrá volcar un HTML renderizado a `post_content` para que el front muestre la última versión aunque GBN esté desactivado.
5. **Sincronización con PageManager**:
   - Se leerá `PageManager::getModoContenidoParaPagina($pageId)` antes de sobrescribir contenido. Si retorna `code`, se guardan sólo metadatos y se respeta el markup original.
   - Si retorna `editor`, se actualiza `post_content` con el HTML regenerado y se escribe un hash (`_glory_content_hash`) para detectar ediciones manuales. Si el hash cambia, GBN dejará de sincronizar automáticamente y mostrará un aviso.
6. **Reconstrucción al cargar**:
   - Al iniciar GBN en el front, `services/content` consultará `gloryGbnCfg.presets` (por implementar) para hidratar `data-gbn-config` desde los metadatos guardados.
   - Si no existen datos persistidos, se usan los defaults definidos por cada componente (`gbnDefaults()` en PHP o `ROLE_DEFAULTS` en JS).
7. **Rollback / Restaurar**: la opción de “Restore defaults” vacía `gbn_config`, borra `gbn_styles` y fuerza a PageManager a regenerar `post_content` desde el HTML baseline del tema.

## Endpoints y estructura de handlers (PHP)
- Registrador: `Glory/src/Gbn/Ajax/Registrar.php` centraliza los `add_action('wp_ajax_*', ...)`.
- Handlers atómicos:
  - `OrderHandler`: `gbn_save_order`.
  - `ContentHandler`: `gbn_save_options`, `gbn_preview_block`.
  - `PageSettingsHandler`: `gbn_get_page_settings`, `gbn_save_page_settings`.
  - `LibraryHandler`: `create_glory_link`, `update_glory_link`, `create_glory_header`, `update_glory_header`.
  - `DeleteHandler`: `gbn_delete_item`.
- Compatibilidad: `GbnAjaxHandler::register()` delega en `Ajax\\Registrar::register()`.

## Marcado base
Los autores pueden escribir HTML minimalista con atributos como `gloryDiv`, `gloryDivSecundario` o `gloryContentRender="post"`. En tiempo de ejecución, GBN normaliza esos atributos a la versión válida `data-gbn-*` y asigna IDs estables (`data-gbn-id`). Si el autor ya define atributos `data-gbn-*`, se respetan. Cada nodo administrado necesita:

- `data-gbnId`: identificador estable por sección, generado de forma determinista.
- `data-gbnConfig`: JSON serializado con configuración editable.
- `data-gbnSchema`: describe los controles disponibles para el panel (inputs, sliders, etc.).

Ejemplo mínimo:

```
    $opciones = "publicacionesPorPagina: 3, claseContenedor: 'gbn-content-grid', claseItem: 'gbn-content-card', forzarSinCache: true";

    ?>
    <div gloryDiv class="divPrincipal" style="padding: 40px 20px; gap: 24px;">
        <div gloryDivSecundario class="divSecundario" style="display: grid; gap: 20px;">
            <div gloryContentRender="post" opciones="<?php echo esc_attr($opciones); ?>">
            </div>
        </div>
    </div>
```

Para `gloryContentRender="post"`, el builder detecta el tipo de contenido y ejecuta la carga AJAX usando `gloryAjax`, inyectando el HTML recibido dentro del bloque. Los componentes agnósticos (por ejemplo `ContentRender`) deben aceptar atributos `data-gbn` sin interferir con su salida predeterminada. Asi los demás componente deben comportarse igual.

## Estilos renderizados
- GBN genera estilos en un `<style data-gbn-style="layoutHero">` cercano a cada nodo principal. Así se conserva legibilidad y permite rehacer estilos sin mezclar con atributos `style` manuales.
- Las declaraciones `style="..."` escritas por el autor se registran como baseline. Mientras no existan cambios desde el panel, GBN respeta esa prioridad; cuando se editen opciones desde el front (o se marque `data-gbnSync="locked"`) se refleja en el `<style>` asociado.
- El panel actualiza simultáneamente el nodo `<style>` y el valor serializado en `data-gbnConfig`. Si el HTML se edita a mano y se cambia el config, GBN puede reconstruir la hoja sin perder cambios.

## Arquitectura JS (refactor)
1. `core/utils`: helpers agnósticos (logger, DOM, parsing de estilos, detección de builder).
2. `core/state`: registra nodos, crea `data-gbn-id`, persiste config y expone getters.
3. `render/styleManager`: sincroniza estilos base en `<style data-gbn-style="*">` reutilizando los inline originales.
4. `services/content`: normaliza atributos `glory*`, parsea `opciones`, define meta y lanza AJAX (`gloryAjax`) para hidratar `gloryContentRender`.
5. `ui/panel`: núcleo del panel lateral (montaje, títulos, estado, summary y aplicación de estilos vía `styleManager`).
6. `ui/panel-fields`: constructores de campos (spacing, slider, select, toggle, color, text) que delegan en `Gbn.ui.panelApi` para actualizar config.
7. `ui/inspector`: toggle “Open GBN”, botones secundarios, persistencia local y activación/desactivación; abre paneles de tema/página/restauración.
8. `gbn.js`: orquesta el arranque (omite ejecución cuando el builder externo está activo).

## Experiencia de edición actual
- Usuarios con permisos ven un botón flotante `Open GBN` (UI en inglés). El estado se guarda por usuario/página en `localStorage`.
- Al activar el constructor se añade `gbn-active`, cada `gloryDiv`/`gloryDivSecundario` recibe `min-height: 40px`, outline azul permanente y brillo en hover.
- El `<main>` desplaza el contenido `100px` hacia abajo mientras GBN está activo para evitar que el panel lo cubra; el padding original se restaura al cerrar.
- Cada bloque genera un botón contextual `Config` que abre un panel lateral (placeholder) con resumen del bloque; el panel se cierra al desactivar el modo o con `Esc`.
- `gloryContentRender` se hidrata vía `gloryAjax` y emite `gbn:contentHydrated` para volver a enganchar los controles sin perder interactividad.

## Roadmap Fase 1

### Etapa 1 · Fundamentos de bloques
- [x] Definir esquemas base por rol (principal/secundario/content) y almacenarlos en una carpeta de configuraciones reutilizable.
- [x] Centralizar la definición de contenedores (`gloryDiv`, `gloryDivSecundario`, `gloryContentRender`) en un registro único consumido por el builder y por los componentes.
- [ ] Ajustar cada componente agnóstico para que exponga su configuración y esquema desde su propio archivo, evitando duplicados. (Aun no, para el final)

### Etapa 2 · Panel interactivo y UX
- [x] Construir el panel real (inputs, tabs) reutilizando `data-gbn-schema` y conectándolo a `state` + `styleManager`.
- [x] Añadir feedback visual (loading, hover) para botones `Config` y estados de bloque.
- [x] Reordenar los botones flotantes (`Open GBN`, `Config tema`, `Config página`, `Restaurar`) para que vivan bajo el panel y respeten el modo activo.
- [x] Resolver problema de estilos inline vs GBN: modificar `styleManager` para aplicar estilos directamente al atributo `style` del elemento en lugar de usar reglas CSS que compiten con estilos inline.
- [x] Sincronizar estilos inline con configuración inicial: cuando un elemento tiene estilos inline (ej: `style="padding-top: 100px"`), GBN ahora carga estos valores en los controles del panel automáticamente.
- [x] Implementar reset inteligente a valores por defecto: cuando se borra un valor en los controles, regresa al valor inline original (del HTML) o al valor por defecto del schema, no se queda con el último valor editado.
- [x] Agregar opciones de altura (auto, mínimo, altura completa) a divs primarios y secundarios.
- [x] Implementar selector flex/grid con opciones específicas condicionales para cada layout (flex-direction, flex-wrap, justify-content, align-items para flex; grid-columns, grid-gap para grid).

### Etapa 3 · Persistencia y sincronización
- [x] Implementar el dispatcher AJAX (`gbn_save_config`) que reciba la estructura de bloques, valide permisos y escriba los metadatos `gbn_config`/`gbn_styles` por página.
- [x] Cliente JS de persistencia y botón Guardar conectado al dispatcher.
- [x] Restauración básica: endpoint `gbn_restore_page` que limpia `gbn_config`/`gbn_styles` y regenera `post_content` cuando el modo es `editor`.
- [ ] (no se si esto ya esta resuelto hay revisar primero) Integrar la lectura/escritura con `PageManager`: respetar `content_mode = code`, actualizar `post_content` sólo cuando el modo sea `editor` y mantener el hash `_glory_content_hash` para detectar ediciones manuales.
- [ ] (no se si esto ya esta resuelto hay revisar primero) Definir el flujo de restauración que recupere el markup baseline, limpie metadatos y vuelva a sincronizar `data-gbnConfig` con la versión guardada o la del tema.

### Etapa 4 · Manipulación del DOM y Estructura (PRIORIDAD ACTUAL)
- [x] **Drag & Drop**: Implementar sistema para mover `divPrincipal` (secciones) y `divSecundario` (columnas/contenedores). *(Funcional, mejorado con UX y validación)*.
- [x] **Inserción de Bloques**: Crear modal/panel "Biblioteca" para insertar nuevos contenedores y componentes. *(Implementado con ui/library.js)*.
- [x] **Eliminación de Bloques**: Agregar opción para eliminar nodos desde la UI del constructor. *(Implementado con botón directo)*.
- [x] **Sincronización Bidireccional**: Asegurar que los cambios en el DOM (movimientos, inserciones) se reflejen en `Gbn.state` y viceversa en tiempo real. *(Cubierto por persistence.js que lee el DOM y eventos de sincronización)*.

### Etapa 5 · Configuraciones globales
- [ ] Implementar el panel de configuración del tema (colores, fuentes, `init.css`) con almacenamiento centralizado.
- [ ] Implementar el panel de configuración de la página (fondo, padding del `main`, overrides locales).
- [ ] Conectar la opción de restaurar valores por defecto con las configuraciones de tema y página.

### Etapa 6 · Adaptación de Componentes (Pospuesto)
- [ ] Ajustar componentes agnósticos (`TermRender`, `GloryImage`, etc.) para exponer `gbnDefaults`.
- [ ] Documentar cómo registrar nuevos elementos/controles.

### Comentarios del usuario 

[solucionado] Actualmente al entrar a la pagina "constructor.php" donde estamos haciendo los test, no veo el boton, tampoco veo que algún script de gbn este cargando. Al activivar el constructor los elementos editables deben ser identificables facilmente al pasar el mouse.

[solucionado] El boton aparece pero no hace nada, los script aparecen con version 1.0, deben de tener el versionado de assetmanager de cuando el modo local o dev este activo, supongo yo que hay que preparar las configuraciones para los divs? y adaptar gloryContentRender el primer componente que va a soportar gbn

[solucionado] Al activar GBN el panel lateral debe mostrarse y el contenido principal debe desplazarse (padding-top: 100px) para que sea visible.

[solucionado] Refactor UI: `overlay.js` dividido en `ui/panel.js`, `ui/panel-fields.js` y `ui/inspector.js`. `overlay.js` eliminado.

[solucionado] Refactor AJAX: `GbnAjaxHandler` dividido en `Ajax/Registrar` + `OrderHandler`, `ContentHandler`, `PageSettingsHandler`, `LibraryHandler`, `DeleteHandler`.

[pendiente] Repasar los componentes agnósticos sin `gbnDefaults()` y conectar la persistencia antes de habilitar la inserción/reordenamiento de bloques (Etapa 6). -> **POSPUESTO**

[solucionado] Problema de estilos inline vs GBN: ahora los estilos escritos en HTML (style="padding-top: 100px") se cargan automáticamente en el panel la primera vez, y al borrar valores regresan al valor inline original. ----> Actualización, la primera vez no carga los estilos escrito en el html, los carga dejar vacío la opcion en el panel o al restaurar default, no representa un problema grave pero si se puede ajustar en el futuro sería lo ideal, no es urgente resolverlo ahora, solo tenerlo en cuenta para el futuro.

[NUEVO] Prioridad: Hacer un constructor esencial con bases sólidas que permita mover contenedores, columnas entre columnas, agregar más componentes, eliminar componentes y actualizar el código en tiempo real.

[NOTA] El Drag & Drop es funcional pero "complicado" de usar. Se debe mejorar la UX para que sea más intuitivo (mejor feedback visual, zonas de drop más claras).
- [ ] Implementar el panel de configuración del tema (colores, fuentes, `init.css`) con almacenamiento centralizado.
- [ ] Implementar el panel de configuración de la página (fondo, padding del `main`, overrides locales).
- [ ] Conectar la opción de restaurar valores por defecto con las configuraciones de tema y página.

### Etapa 6 · Adaptación de Componentes (Pospuesto)
- [ ] Ajustar componentes agnósticos (`TermRender`, `GloryImage`, etc.) para exponer `gbnDefaults`.
- [ ] Documentar cómo registrar nuevos elementos/controles.

### Comentarios del usuario 

[solucionado] Actualmente al entrar a la pagina "constructor.php" donde estamos haciendo los test, no veo el boton, tampoco veo que algún script de gbn este cargando. Al activivar el constructor los elementos editables deben ser identificables facilmente al pasar el mouse.

[solucionado] El boton aparece pero no hace nada, los script aparecen con version 1.0, deben de tener el versionado de assetmanager de cuando el modo local o dev este activo, supongo yo que hay que preparar las configuraciones para los divs? y adaptar gloryContentRender el primer componente que va a soportar gbn

[solucionado] Al activar GBN el panel lateral debe mostrarse y el contenido principal debe desplazarse (padding-top: 100px) para que sea visible.

[solucionado] Refactor UI: `overlay.js` dividido en `ui/panel.js`, `ui/panel-fields.js` y `ui/inspector.js`. `overlay.js` eliminado.

[solucionado] Refactor AJAX: `GbnAjaxHandler` dividido en `Ajax/Registrar` + `OrderHandler`, `ContentHandler`, `PageSettingsHandler`, `LibraryHandler`, `DeleteHandler`.

[pendiente] Repasar los componentes agnósticos sin `gbnDefaults()` y conectar la persistencia antes de habilitar la inserción/reordenamiento de bloques (Etapa 6). -> **POSPUESTO**

[solucionado] Problema de estilos inline vs GBN: ahora los estilos escritos en HTML (style="padding-top: 100px") se cargan automáticamente en el panel la primera vez, y al borrar valores regresan al valor inline original. ----> Actualización, la primera vez no carga los estilos escrito en el html, los carga dejar vacío la opcion en el panel o al restaurar default, no representa un problema grave pero si se puede ajustar en el futuro sería lo ideal, no es urgente resolverlo ahora, solo tenerlo en cuenta para el futuro.

[NUEVO] Prioridad: Hacer un constructor esencial con bases sólidas que permita mover contenedores, columnas entre columnas, agregar más componentes, eliminar componentes y actualizar el código en tiempo real.

