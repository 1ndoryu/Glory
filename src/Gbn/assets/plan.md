GBN será un constructor visual con compatibilidad con el front y el back (código). Debe permitir construir sitios desde el HTML plano y, opcionalmente, desde la interfaz del front. Mantiene una UI en inglés, pero todo el código queda en español. El núcleo en `Glory/` se mantiene agnóstico; cualquier personalización específica vive en el tema.

## Principios
- HTML limpio sin shortcodes.
- Al apagar GBN (no cargar scripts ni estilos) la página debe comportarse igual gracias a que el marcado mantiene estilos renderizados.
- Código minimalista, ordenado y fácil de escalar.
- Los componentes de Glory deben aceptar progresivamente la capa GBN sin romper su uso independiente.

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

Para `gloryContentRender="post"`, el builder detecta el tipo de contenido y ejecuta la carga AJAX usando `gloryAjax`, inyectando el HTML recibido dentro del bloque. Los componentes agnósticos (por ejemplo `ContentRender`) deben aceptar atributos `data-gbn` sin interferir con su salida predeterminada.

## Estilos renderizados
- GBN genera estilos en un `<style data-gbn-style="layoutHero">` cercano a cada nodo principal. Así se conserva legibilidad y permite rehacer estilos sin mezclar con atributos `style` manuales.
- Las declaraciones `style="..."` escritas por el autor se registran como baseline. Mientras no existan cambios desde el panel, GBN respeta esa prioridad; cuando se editen opciones desde el front (o se marque `data-gbnSync="locked"`) se refleja en el `<style>` asociado.
- El panel actualiza simultáneamente el nodo `<style>` y el valor serializado en `data-gbnConfig`. Si el HTML se edita a mano y se cambia el config, GBN puede reconstruir la hoja sin perder cambios.

## Arquitectura JS (refactor)
1. `core/utils`: helpers agnósticos (logger, DOM, parsing de estilos, detección de builder).
2. `core/state`: registra nodos, crea `data-gbn-id`, persiste config y expone getters.
3. `render/styleManager`: sincroniza estilos base en `<style data-gbn-style="*">` reutilizando los inline originales.
4. `services/content`: normaliza atributos `glory*`, parsea `opciones`, define meta y lanza AJAX (`gloryAjax`) para hidratar `gloryContentRender`.
5. `ui/overlay`: prepara nodos inspectables y provee un stub de panel para futuras configuraciones.
6. `gbn.js`: orquesta el arranque (omite ejecución cuando el builder externo está activo).

## Experiencia de edición actual
- Usuarios con permisos ven un botón flotante `Open GBN` (UI en inglés). El estado se guarda por usuario/página en `localStorage`.
- Al activar el constructor se añade `gbn-active`, cada `gloryDiv`/`gloryDivSecundario` recibe `min-height: 40px` y outline azul en hover.
- Cada bloque genera un botón contextual `Config` (stub) que se vuelve a insertar tras cada `gbn:contentHydrated`.
- `gloryContentRender` se hidrata vía `gloryAjax` y emite `gbn:contentHydrated` para volver a enganchar los controles sin perder interactividad.

## Roadmap Fase 1
1. Definir esquemas base por rol (principal/secundario/content) y almacenarlos en una carpeta de configuraciones reutilizable.
2. Construir el panel real (inputs, tabs) reutilizando `data-gbn-schema` y conectándolo a `state` + `styleManager`.
3. Persistir los estilos actualizados mediante AJAX (`glory_gbn_guardar_config`) y recargar bloques sin perder controles.
4. Añadir feedback visual (loading, hover) para botones `Config` y estados de bloque.
5. Documentar cómo registrar nuevos elementos/controles y cómo extender las opciones por proyecto.
