GBN será un constructor visual con compatibilidad con el front y el back (código). Debe permitir construir sitios desde el HTML plano y, opcionalmente, desde la interfaz del front. Mantiene una UI en inglés, pero todo el código queda en español. El núcleo en `Glory/` se mantiene agnóstico; cualquier personalización específica vive en el tema. 

Los componentes de Glory deberan de mantener su propias configuaciones compatibles en el codigo y el constructor en el front, se adaptara los componentes poco a poco. Seguir los principios solid.

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
5. `ui/overlay`: prepara nodos inspectables y provee un stub de panel para futuras configuraciones.
6. `gbn.js`: orquesta el arranque (omite ejecución cuando el builder externo está activo).

## Experiencia de edición actual
- Usuarios con permisos ven un botón flotante `Open GBN` (UI en inglés). El estado se guarda por usuario/página en `localStorage`.
- Al activar el constructor se añade `gbn-active`, cada `gloryDiv`/`gloryDivSecundario` recibe `min-height: 40px`, outline azul permanente y brillo en hover.
- El `<main>` desplaza el contenido `100px` hacia abajo mientras GBN está activo para evitar que el panel lo cubra; el padding original se restaura al cerrar.
- Cada bloque genera un botón contextual `Config` que abre un panel lateral (placeholder) con resumen del bloque; el panel se cierra al desactivar el modo o con `Esc`.
- `gloryContentRender` se hidrata vía `gloryAjax` y emite `gbn:contentHydrated` para volver a enganchar los controles sin perder interactividad.

## Roadmap Fase 1

### Etapa 1 · Fundamentos de bloques
- [x] Definir esquemas base por rol (principal/secundario/content) y almacenarlos en una carpeta de configuraciones reutilizable.
- [ ] Centralizar la definición de contenedores (`gloryDiv`, `gloryDivSecundario`, `gloryContentRender`) en un registro único consumido por el builder y por los componentes.
- [ ] Ajustar cada componente agnóstico para que exponga su configuración y esquema desde su propio archivo, evitando duplicados.

### Etapa 2 · Panel interactivo y UX
- [ ] Construir el panel real (inputs, tabs) reutilizando `data-gbn-schema` y conectándolo a `state` + `styleManager`.
- [ ] Añadir feedback visual (loading, hover) para botones `Config` y estados de bloque.
- [ ] Reordenar los botones flotantes (`Open GBN`, `Config tema`, `Config página`, `Restaurar`) para que vivan bajo el panel y respeten el modo activo.

### Etapa 3 · Persistencia y sincronización
- [ ] Persistir los estilos actualizados mediante AJAX (`glory_gbn_guardar_config`) y recargar bloques sin perder controles.
- [ ] Definir el flujo de restauración que recupere el markup baseline y sincronice `data-gbnConfig` con la versión persistida.

### Etapa 4 · Configuraciones globales
- [ ] Implementar el panel de configuración del tema (colores, fuentes, `init.css`) con almacenamiento centralizado.
- [ ] Implementar el panel de configuración de la página (fondo, padding del `main`, overrides locales).
- [ ] Conectar la opción de restaurar valores por defecto con las configuraciones de tema y página.

### Etapa 5 · Documentación y extensión
- [ ] Documentar cómo registrar nuevos elementos/controles y cómo extender las opciones por proyecto.
- [ ] Documentar el flujo de configuraciones globales, restauración y dependencias entre builder y componentes.

###
Comentarios del usuario 

[solucionado] Actualmente al entrar a la pagina "constructor.php" donde estamos haciendo los test, no veo el boton, tampoco veo que algún script de gbn este cargando. Al activivar el constructor los elementos editables deben ser identificables facilmente al pasar el mouse.

[solucionado] El boton aparece pero no hace nada, los script aparecen con version 1.0, deben de tener el versionado de assetmanager de cuando el modo local o dev este activo, supongo yo que hay que preparar las configuraciones para los divs? y adaptar gloryContentRender el primer componente que va a soportar gbn

[solucionado] Al activar GBN el panel lateral debe mostrarse y el contenido principal debe desplazarse (padding-top: 100px) para que sea visible.

[pendiente] Mantener centralizada la configuración de contenedores y dejar que cada componente defina la suya; añadir botones para tema, página y restaurar por defecto (resuelto dentro de las Etapas 1, 2 y 4 del roadmap). También considerar configuraciones del tema (colores, fuentes, `init.css`) y de la página (fondo, padding del `main`) para futuras iteraciones.

