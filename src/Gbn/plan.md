# GBN - Plan de Trabajo

> [!TIP] LEER SIEMPRE CUANDO SE RECIBA ESTE MD LOS SIGUIENTES MD: 
> *   **Reglas y Arquitectura:** Ver `reglas.md`.
> *   **Contexto y Estado:** Ver `contexto.md`.

## 1. Roadmap de Refactorización y Hardening

Este roadmap está diseñado para asegurar que GBN sea modular, SOLID y fácil de mantener antes de escalar la biblioteca de componentes.

### Fase 1: Infraestructura y Core (COMPLETADO)
**Objetivo:** Establecer los cimientos arquitectónicos para el nuevo sistema de componentes.

- [x] **Definición de Contratos e Interfaces**
    -   **Acción:** Crear `Glory/src/Gbn/Components/ComponentInterface.php` y `AbstractComponent.php`.
    -   **Estado:** Completado. Define el contrato estricto para componentes.

- [x] **Sistema de Carga Dinámica**
    -   **Acción:** Crear `Glory/src/Gbn/Components/ComponentLoader.php`.
    -   **Estado:** Completado. Escanea y registra componentes automáticamente.

- [x] **SchemaBuilder (Fluent Interface)**
    -   **Acción:** Crear `Glory/src/Gbn/Schema/SchemaBuilder.php` y `Option.php`.
    -   **Estado:** Completado. API fluida implementada.

- [x] **Traits de Configuración Compartida (DRY)**
    -   **Acción:** Crear Traits reutilizables (`HasFlexbox`, `HasSpacing`, `HasTypography`, `HasGrid`).
    -   **Estado:** Completado.

### Fase 2: Migración de Componentes Base (COMPLETADO)
**Objetivo:** Migrar los componentes esenciales a la nueva arquitectura.

- [x] **Componente Principal (Root)**
    -   **Acción:** Migrado a `Glory/src/Gbn/Components/Principal/PrincipalComponent.php`.
    -   **Estado:** Usa Traits y SchemaBuilder.

- [x] **Componente Secundario (Layout)**
    -   **Acción:** Migrado a `Glory/src/Gbn/Components/Secundario/SecundarioComponent.php`.
    -   **Estado:** Implementado con opciones de Layout y Grid.

- [x] **Componente Texto**
    -   **Acción:** Migrado a `Glory/src/Gbn/Components/Text/TextComponent.php`.
    -   **Estado:** Implementado con opciones de tipografía y contenido.

### Fase 3: Limpieza y Finalización (EN PROGRESO)
**Objetivo:** Eliminar deuda técnica y asegurar estabilidad.

- [x] **Desactivación de Legacy**
    -   **Acción:** Eliminadas referencias a componentes antiguos en `ContainerRegistry.php`.
    -   **Estado:** Completado.

- [x] **Limpieza de JS (Roles Legacy)**
    -   **Acción:** Eliminar definiciones hardcoded en `roles.js` que ya no son necesarias gracias a la inyección dinámica desde PHP.
    -   **Prioridad:** Media (No bloqueante, el JS prioriza la config inyectada).
    -   **Estado:** Completado. Definiciones hardcoded eliminadas.

- [x] **Tests de Regresión**
    -   **Acción:** Verificar manualmente que los componentes migrados funcionan idénticamente a sus versiones anteriores. (Esto lo hara el usuario)

### Fase 4: Refactorización JS (SOLID & DRY) (COMPLETADO)
**Objetivo:** Alinear la arquitectura Frontend con los principios de modularidad y automatización del Backend.

- [x] **Implementación de `StyleComposer` (DRY)**
    -   **Acción:** Creado `style-composer.js` y refactorizados `principal.js` y `secundario.js`.
    -   **Estado:** Completado.

- [x] **Automatización de Tema (`applicator.js`)**
    -   **Acción:** Refactorizado `applicator.js` para iterar dinámicamente sobre el esquema.
    -   **Estado:** Completado.

- [x] **Registro de Campos (Field Registry - OCP)**
    -   **Acción:** Implementado `registry.js` y actualizado `index.js` y campos clave.
    -   **Estado:** Completado.

### Fase 5: Futuro y Escalabilidad
- [ ] **Tests Automatizados:** Implementar tests unitarios para `SchemaBuilder` y `ComponentLoader`.
- [ ] **API de Terceros:** Documentar cómo registrar componentes externos.

---

## 2. Tarea Prioritaria: Sincronización de Theme Settings

**Objetivo:** Implementar la arquitectura canónica definida en `reglas.md` (Sección 3).

- [x] **Auditoría de `applicator.js`**
    -   Confirmar que solo gestiona Variables CSS y no inyecta estilos directos que compitan.
- [x] **Auditoría de `style-composer.js`**
    -   Asegurar que **NO emita estilos** si no hay configuración explícita del usuario.
    -   Eliminar cualquier lógica de "default hardcoded" que rompa la herencia de variables.
- [x] **Verificación de Herencia**
    -   Comprobar que al borrar un valor en el componente, este hereda visualmente el valor definido en Theme Settings.

---

## 3. Bugs Conocidos y Regresiones

### Regresiones Críticas (Zombie Bugs)
> [!CAUTION]
> Estos errores han reaparecido múltiples veces. NO INTENTAR ARREGLAR CON PARCHES RÁPIDOS. Se requiere revisión estructural.

- [x] **Bug 27: Retraso en Layout (Flexbox) (FIX V4)**
    -   **Síntoma:** Doble click requerido.
    -   **Solución V4:** Añadido CSS base en `theme-styles.css` con selectores `:where()` que consumen las variables del tema. Esto aplica los defaults (como `display: flex`) inmediatamente sin necesidad de interacción ni estilos inline forzados.

- [x] **Bug 28: Theme Settings Flex Global (FIX V4)**
    -   **Síntoma:** Valores no aplicaban o rompían clases externas.
    -   **Solución V4:** Implementada arquitectura de "Nivel 2" en CSS. `theme-styles.css` define `:where([gloryDiv]) { ... var(--gbn-...) }`. Esto asegura que los defaults del tema se apliquen con **especificidad 0**, permitiendo que clases externas (`.mi-clase`) ganen, pero funcionando perfectamente si el elemento está "limpio".

- [x] **Bug 29: Persistencia Responsive (Padding) (FIX V4)**
    -   **Síntoma:** Valores responsive se perdían.
    -   **Solución V4:** Confirmada la corrección de prioridad en `panel-render.js` (V3) que permite a `theme-settings.js` gestionar correctamente la estructura de datos anidada.

- [x] **Bug 30: Flex Click Bug (SOLUCIONADO)**
    -   **Estado:** COMPLETADO (V6.1).

- [x] **Bug 31: Alineación de Contenido (SOLUCIONADO)**
    -   **Estado:** COMPLETADO (V6.1).

- [x] **Bug 32 & 27: Grid/Flex Conflict (SOLUCIONADO)**
    -   **Estado:** COMPLETADO (V13).
    -   **Síntoma:** El Grid en Theme Settings no actualiza las columnas visualmente.
    -   **Causa Real:** `applicator.js` añadía automáticamente `px` a `gridColumns` (ej: `2px`), generando CSS inválido `repeat(2px, 1fr)`.
    -   **Solución V13:** Se modificó `applicator.js` para excluir `gridColumns` de la conversión de unidades (`toCssValue`), aplicando el valor crudo (entero) a la variable CSS.

- [x] **Bug 33: Flash de Contenido Flex (SOLUCIONADO)**
    -   **Estado:** COMPLETADO (V8).
    -   **Causa Real:** `style-composer.js` forzaba un "Security Fallback" a `flex` e inyectaba estilos inline (`display: flex`) incluso cuando no había configuración explícita, sobrescribiendo el CSS base y causando un repintado visible (Flash).
    -   **Solución V8:** Se eliminó el fallback forzado en `style-composer.js`. Ahora, si no hay configuración local ni en Theme Settings, el componente no emite estilos inline y respeta totalmente el CSS base (y sus variables), eliminando el conflicto y el flash.



- [x] **Tarea Crítica: Documentación y Contexto (COMPLETADO)**
    -   **Requerimiento:** Agregar comentarios explicativos detallados en `style-composer.js`, `panel-render.js` y `theme-settings.js` explicando el POR QUÉ de las soluciones (especificidad, orden de ejecución, herencia).
    -   **Objetivo:** Evitar regresiones futuras por falta de contexto sobre decisiones arquitectónicas críticas (como el uso de `:where()` o la prioridad de renderers).
    -   **Estado:** Documentación añadida en `style-composer.js` explicando la jerarquía de herencia.

- [x] **Bug 34: Margen sin efecto real (SOLUCIONADO)**
    -   **Síntoma:** El margen configurado en el panel no se aplica visualmente.
    -   **Estado:** COMPLETADO.
    -   **Solución:** Se actualizó `shared.js` para soportar `extractSpacingStyles` con propiedad dinámica y `style-composer.js` para procesar el campo `margin`.

- [x] **Bug 35: Iconos faltantes en paneles de configuración del tema (SOLUCIONADO)**
    -   **Síntoma:** Los paneles de configuración de componentes dentro de Theme Settings no muestran iconos para las pestañas o secciones.
    -   **Requisito:** Debe sincronizarse de forma inteligente y automática con los iconos definidos en los componentes individuales.
    -   **Estado:** COMPLETADO.
    -   **Solución:** Se realizó la limpieza de definiciones legacy en `roles.js` y se implementó la propagación de la propiedad `icon` desde PHP.

---

## 4. Historial de Cambios y Resoluciones (Resumen)

- **Bug 1-5 (Responsive):** Corregidos problemas de simulación de viewport, persistencia de breakpoints y detección de estilos computados.
- **Bug 6 (Panel State):** Implementada persistencia de estado de navegación del panel al cambiar breakpoints.
- **Bug 7 (Color Detection):** Corregida detección de color de fondo inicial.
- **Bug 8 (Responsive Persistence):** Solucionado. Los valores responsive ahora persisten y se aplican correctamente en el editor.
- **Bug 10 (Layout Persistence):** Solucionado. Las opciones de layout (Flexbox) ahora persisten y se aplican correctamente al recargar.
- **Bug 12-14 (UI Consistency):** Unificadas etiquetas, reordenadas opciones, añadido fondo a Secundario y mejorada UI de tipografía con iconos.
- **Bug 15-18 (UI/UX):** Corregido error en `roles.js`, mejorado botón Dock, centrada barra de herramientas y asegurada visibilidad de elementos vacíos.
- **Bug 19-20 (UI Refinements):** Tipografía más limpia (iconos internos) y mejor feedback visual en hover de componentes.

## 5. Historial de Cambios y Resoluciones (Detallado)

### Diciembre 2025

#### ✅ Bug 10: Persistencia de Layout Options (Flexbox)
-   **Problema**: Opciones como `flex-wrap: nowrap` configuradas en Theme Settings funcionaban en vivo pero se perdían al recargar.
-   **Causa**: `applicator.js` aplicaba los estilos en tiempo real, pero el generador de CSS estático (`style-generator.js`) o la hidratación inicial no estaban leyendo correctamente estas propiedades específicas del objeto de configuración del tema.
-   **Solución**: Se actualizó la lógica de hidratación y aplicación de estilos para incluir explícitamente propiedades de layout (`direction`, `wrap`, `justify`, `align`) al procesar los defaults del tema.

#### ✅ Bug 11: Salida HTML Limpia
-   **Problema**: El atributo `data-gbn-schema` (que contiene el JSON completo de configuración del panel) se inyectaba en el frontend para todos los usuarios, inflando el tamaño del DOM innecesariamente.
-   **Solución**: Se modificó `Glory/src/Gbn/assets/js/services/content/dom.js`.
    -   Se añadió una verificación estricta: `if (config.isEditor)`.
    -   Ahora, `data-gbn-schema` solo se inyecta si el usuario logueado tiene permisos de edición. Los visitantes ven un HTML limpio.

#### ✅ Bug 8: Persistencia de Valores Responsive en Tema
-   **Problema**: Los valores definidos para Tablet/Mobile en el Panel de Tema no se guardaban o no se aplicaban al cambiar de vista.
-   **Solución**: Se corrigió `services/responsive.js` y `ui/theme/applicator.js`.
    -   Se implementó la lógica de "burbujeo" (Mobile -> Tablet -> Desktop) específicamente para el objeto `themeSettings`.
    -   Se aseguró que `getThemeResponsiveValue` consulte correctamente la estructura `_responsive` dentro de la configuración global del tema.

#### ✅ Refactorización SOLID de Paneles
-   **Cambio**: Se descompuso el archivo monolítico `panel-fields.js` en múltiples módulos (`spacing.js`, `typography.js`, etc.) dentro de `ui/panel-fields/`.
-   **Beneficio**: Mantenibilidad drásticamente mejorada. Añadir un nuevo tipo de campo ahora solo requiere crear un archivo y registrarlo en el `index.js` dispatcher.

#### ✅ Fase 4: Refactorización JS Completa
-   **StyleComposer**: Implementado para centralizar la generación de estilos.
-   **FieldRegistry**: Implementado para registro dinámico de campos.
-   **Theme Automation**: `applicator.js` ahora es agnóstico y basado en esquemas.

#### ✅ Bug 24, 25, 26: Corrección de Parsing de Esquema (Critical)
-   **Problema**: Los componentes no aplicaban estilos de Padding, Flexbox ni Defaults del Tema.
-   **Causa**: `style-composer.js` y `applicator.js` esperaban que el esquema fuera un objeto `{ fields: {...} }`, pero `ContainerRegistry` entregaba un array de campos `[...]`. Esto hacía que la detección de campos fallara silenciosamente.
#### ✅ Bug 24, 25, 26: Corrección de Parsing de Esquema (Critical)
-   **Problema**: Los componentes no aplicaban estilos de Padding, Flexbox ni Defaults del Tema.
-   **Causa**: `style-composer.js` y `applicator.js` esperaban que el esquema fuera un objeto `{ fields: {...} }`, pero `ContainerRegistry` entregaba un array de campos `[...]`. Esto hacía que la detección de campos fallara silenciosamente.
-   **Solución**: Se actualizó `style-composer.js` y `applicator.js` para iterar correctamente sobre el array de campos del esquema.

#### ✅ Bug 30: Crash en Inspector (JSON Serialization)
-   **Problema**: Error `Cannot read properties of undefined (reading 'add')` en `inspector.js` al iniciar.
-   **Causa**: El Store usaba `JSON.parse(JSON.stringify())` para clonar el estado, lo que eliminaba las referencias a los elementos DOM (`block.element`) almacenados en el estado, causando que `inspector.js` recibiera bloques sin elementos válidos.
-   **Solución**: Se modificó `store.js` para usar clonación superficial (`Object.assign`) en `reducer` y `getState`, preservando las referencias a objetos no serializables como nodos DOM. Se añadió validación defensiva en `inspector.js`.

#### ✅ Bug 29: Persistencia Responsive en Theme Settings
-   **Problema**: Los valores responsive (Tablet/Mobile) definidos en Theme Settings se guardaban en una estructura incorrecta (`themeSettings._responsive`) en lugar de anidarse en el componente (`themeSettings.components.principal._responsive`), causando que `applicator.js` no los encontrara.
-   **Solución**: Se actualizó `responsive.js` (`setResponsiveValue` y `clearResponsiveOverride`) para detectar cuando se edita un componente del tema y escribir en la ruta anidada correcta.

#### ✅ Bug 28 & 27: Herencia de Flexbox y Layout Delay
-   **Problema**: Los componentes no heredaban las opciones de Flex/Layout del tema si no tenían valores propios, y los cambios a veces no se reflejaban inmediatamente.
-   **Causa**: Falta de conexión entre los estilos inline generados y las variables CSS del tema. `style-composer.js` y `layout-flex.js` simplemente no emitían nada si el valor era `undefined`, dejando al elemento sin estilo explícito.
-   **Solución**: Se modificaron `layout-flex.js` y `style-composer.js` para inyectar explícitamente `var(--gbn-role-prop)` (ej: `flex-wrap: var(--gbn-principal-flex-wrap)`) cuando no hay configuración local. Esto asegura la herencia correcta y fuerza al navegador a repintar inmediatamente al cambiar la variable o el valor local.

#### ✅ Bug 33: Flash de Contenido Flex (Fix V8)
-   **Problema**: Flash of Unstyled Content (FOUC) al cargar la página. El layout saltaba visiblemente.
-   **Causa**: `style-composer.js` tenía un "Security Fallback" que forzaba `layout = 'flex'` si no encontraba configuración. Esto inyectaba `style="display: flex"` inline tardíamente, causando un repintado sobre el CSS base.
-   **Solución**: Se eliminó el fallback forzado. Ahora el composer no emite estilos si no hay configuración explícita, permitiendo que el CSS base (cargado antes) controle el layout sin interferencias.

#### ✅ Bug 32: Grid Defaults (Fix V13)
-   **Problema**: El layout Grid no se aplicaba correctamente desde Theme Settings.
-   **Causa**: `applicator.js` convertía el número de columnas (ej: 2) a pixeles (2px), invalidando la función `repeat()`.
-   **Solución**: Se añadió una excepción en `applicator.js` para que `gridColumns` se aplique sin unidades.

#### ✅ Bug 34: Margen sin efecto real
-   **Problema**: El margen configurado en el panel no se aplicaba visualmente.
-   **Causa**: `style-composer.js` solo procesaba el campo `padding` y `extractSpacingStyles` en `shared.js` tenía hardcoded las propiedades de padding.
-   **Solución**: Se refactorizó `extractSpacingStyles` para aceptar una propiedad dinámica y se añadió la lógica de procesamiento de `margin` en `style-composer.js`.

#### ✅ Bug 35: Iconos faltantes en Theme Settings
-   **Problema**: Los componentes en la lista de Theme Settings mostraban un icono genérico en lugar de su icono específico.
-   **Causa**: `roles.js` no copiaba la propiedad `icon` de la definición del contenedor (PHP) a los defaults del rol en JS. Además, `getRoleDefaults` devolvía un objeto nuevo sin la propiedad `icon`.
-   **Solución**: 
    1. Se limpiaron las definiciones legacy en `roles.js`.
    2. Se actualizó `getRoleDefaults` en `roles.js` para incluir explícitamente la propiedad `icon`.
    3. Se actualizó `ContainerRegistry.php` para incluir `icon` y `label` en el payload global `roleSchemas`.

#### ✅ Mejora UX: Unificación de Paneles (Theme Settings)
-   **Problema**: El panel de configuración de componentes en Theme Settings era plano y diferente al panel de edición directa.
-   **Solución**: Se implementó la lógica de pestañas (Contenido, Estilo, Avanzado) en `render.js` (Theme Settings) replicando la estructura y clases CSS de `panel-render.js`. Ahora ambos paneles tienen la misma organización visual y funcional.

#### ✅ Implementación: Página de Prueba (Constructor)
-   **Objetivo**: Validar la lógica del constructor con un diseño complejo (Toyota Supra Landing).
-   **Cambios**:
    -   `header.php`: Se añadió lógica condicional para ocultar el header y toggle por defecto en la página `contructor`.
    -   `contructor.php`: Se adaptó el diseño HTML proporcionado (Tailwind, Lucide, Fuentes) dentro de la estructura `gloryDiv > gloryDivSecundario`.


---

## 6. Roadmap de Estabilidad y Prevención (Defensa contra Zombie Bugs)

> [!IMPORTANT]
> Este plan tiene prioridad sobre cualquier nueva funcionalidad. El objetivo es blindar el sistema contra errores recurrentes mediante arquitectura estricta y herramientas de diagnóstico.

### Fase A: Arquitectura de Estado Blindada (COMPLETADO)
**Objetivo:** Eliminar la desincronización UI <-> DOM <-> Estado.

- [x] **Refactorización a Store Centralizado (Redux-lite)**
    -   **Acción:** Transformar `state.js` en un Store real con `dispatch`, `actions` y `subscribers`.
    -   **Regla:** El UI (inputs) **NUNCA** escribe en el objeto de configuración directamente. Solo despacha acciones (`UPDATE_BLOCK_PROP`).
    -   **Beneficio:** Trazabilidad total de quién cambió qué y cuándo.

- [x] **Pipeline de Renderizado Puro**
    -   **Acción:** Convertir `style-composer.js` en una función pura: `(config, schema, context) => cssString`.
    -   **Eliminación de Efectos Secundarios:** El composer no debe leer del DOM ni consultar variables globales ocultas. Todo se le pasa por argumentos.

### Fase B: Programación Defensiva (COMPLETADO)
**Objetivo:** Que el sistema falle ruidosamente en desarrollo y silenciosamente en producción, pero nunca corrompa datos.

- [x] **Validación de Esquemas en Runtime (Zod-lite)**
    -   **Acción:** Implementar validador ligero que verifique la integridad de `gloryGbnCfg` y los esquemas de componentes al cargar.
    -   **Alerta:** Si un campo requerido falta o tiene el tipo incorrecto, mostrar un `console.error` gigante o un aviso visual en el editor.

- [x] **Error Boundaries en Renderers**
    -   **Acción:** Envolver la ejecución de `render.js` y `applicator.js` en bloques `try/catch`.
    -   **Fallback:** Si un componente falla al renderizar, mostrar un placeholder "Error Visual" en el editor en lugar de romper toda la página.

### Fase C: Herramientas de Diagnóstico (The Watchdog)
**Objetivo:** Ver lo invisible. Hacer explícito lo que está pasando "por debajo".

- [x] **Remote Logger (Log Físico)**
    -   **Problema:** `console.log` consume mucha memoria en equipos lentos y se pierde al recargar.
    -   **Solución:** Servicio JS (`logger.js`) que captura eventos y los envía por AJAX al servidor.
    -   **Output:** Se guarda en `Glory/src/Gbn/logs/client-{date}.log`.
    -   **Features:** Batching (agrupar logs y enviar cada 5s), captura de `window.onerror`.
    -   **TODO:** Logs movidos exitosamente a `Glory/src/Gbn/logs/`.

- [x] **Debug Overlay (Modo Dios)**
    -   **Feature:** Un panel flotante activable (`Ctrl + Alt + D`) que muestra para el bloque seleccionado:
        -   Estado Crudo (JSON).
        -   Variables CSS heredadas (Computed).
        -   Fuente del estilo (Theme Context).
    -   **Estado:** Completado y funcional. Permite verificar la herencia de variables CSS.

- [x] **Desactivar Remote Logger (Log Físico)**
    -   **Motivo:** Ya no son necesarios para el diagnóstico actual y consumen recursos.
    -   **Acción:** Desactivado el envío de logs al servidor en `logger.js` (`ENABLED = false`).

### Fase E: Documentación Viva (Mantenimiento)
**Objetivo:** Mantener la documentación sincronizada con la realidad del código.

- [x] **Actualizar `reglas.md`**
    -   **Acción:** Actualizada la lista de archivos y descripciones en `reglas.md` para reflejar la refactorización reciente (Store Centralizado, Pure Render Pipeline, Logger, Overlay).
    -   **Estado:** Completado. La documentación ahora coincide con la implementación actual.
---

## 7. Fase 6: Expansión de Biblioteca y UX (Roadmap)

**Objetivo:** Escalar la biblioteca de componentes y mejorar drásticamente la experiencia de usuario (UX) en el constructor.

### 6.1 Integración Profunda con API de Diagnóstico
**Regla:** Antes de registrar cualquier *nuevo* componente, el sistema debe validar su integridad usando la API de Diagnóstico.
- [x] **Validación Automática de Componentes:**
    -   Crear un hook o script que, al detectar un nuevo componente registrado en PHP, consulte `gbn_diagnostics_validate`.
    -   Si la API devuelve errores (ej: falta selector, schema inválido), el componente debe marcarse como "Inestable" o no cargarse en el UI.
    -   **Actualizar `reglas.md`:** Añadir regla explícita sobre esta validación obligatoria.

### 6.2 Refactorización de la Biblioteca de Componentes (UI/UX)
**Problema Actual:** El modal de "Añadir bloque" es legacy, solo muestra `divSecundario` y carece de jerarquía visual.
- [x] **Nueva Lógica de Inserción:**
    -   **Contexto Inteligente:**
        -   Si el usuario está en la raíz -> Mostrar solo Contenedores Principales (Div Principal).
        -   Si está dentro de un Div Principal -> Mostrar Contenedores Secundarios (Div Secundario).
        -   Si está dentro de un Div Secundario -> Mostrar Componentes Atómicos (Texto, Botón, Imagen) Y Divs Secundarios anidados.
    -   **Iconografía:** Todos los componentes deben tener iconos SVG representativos y minimalistas.
    -   **Carga Dinámica:** La lista de componentes disponibles debe poblarse automáticamente leyendo `gloryGbnCfg.components` (filtrando por rol/tipo), sin hardcodear nada en JS.

- [x] **Nuevos Componentes Atómicos:**
    -   **Imagen:** Implementado `ImageComponent.php` con soporte para URL, Alt, Dimensiones y Object-Fit.
    -   **Botón:** Implementado `ButtonComponent.php` con soporte para Texto, URL, Target, Variantes y Estilos.

### 6.2.2 Imágenes de Fondo (Prioridad)
**Objetivo:** Permitir añadir imágenes de fondo a contenedores (Principal y Secundario) para crear secciones Hero y tarjetas con fondo.
- [x] **Trait `HasBackground`:** Crear trait reutilizable con opciones de Imagen, Posición, Tamaño, Repetición y Attachment.
- [x] **Integración:** Añadir `HasBackground` a `PrincipalComponent` y `SecundarioComponent`.
- [x] **Frontend:** Actualizar `style-composer.js` para procesar propiedades de fondo (`background-image`, `background-size`, etc.).
- [x] **Demo:** Actualizar `contructor.php` para usar esta funcionalidad en la sección Hero.

### 6.2.1 Hotfix Críticos (UX y Persistencia)
**Problema Crítico:** Los cambios no perduran al recargar y falta una forma intuitiva de agregar contenedores principales.
- [x] **Persistencia de Nuevos Bloques:**
    -   **Diagnóstico:** `ConfigHandler` regeneraba el HTML desde el código PHP original, ignorando los bloques añadidos dinámicamente en el frontend.
    -   **Solución:** Modificado `persistence.js` para enviar el HTML del contenedor raíz (`htmlContent`). Modificado `ConfigHandler.php` para usar este HTML como fuente de verdad en modo editor.
- [x] **Inserción de Contenedores Principales (Root UX):**
    -   Implementado "Zonas de Inserción" en `inspector.js` que inyectan botones "+" al hacer hover en bloques principales.
    -   Soporte añadido en `library.js` para inserción `before`.

### 6.3 Mejoras en Componentes Base
**Objetivo:** Estandarizar capacidades de estilo en todos los componentes.
- [x] **Campo "Estilos Personalizados" (Custom CSS):**
    -   Añadir un campo `textarea` (o editor de código) al final de *todos* los paneles de configuración.
    -   Debe permitir escribir CSS libre que se aplique al selector único del componente.
    -   **Requisito:** Debe soportar breakpoints (Responsive) y aparecer en los Defaults del Tema.
- [x] **Estandarización de Spacing:**
    -   Asegurar que *todos* los componentes (sin excepción) implementen el Trait `HasSpacing` (Margin/Padding).
- [x] **Organización por Tabs:**
    -   Refactorizar el renderizado del panel (`panel-render.js`) para soportar pestañas (ej: Contenido | Estilo | Avanzado).
    -   Usar iconos para las pestañas para ahorrar espacio y mejorar la estética.




### 6.4 Mejoras Visuales y Detalles (Solicitud Usuario)
**Objetivo:** Refinar la experiencia de usuario y la interacción del panel con la página.

- [x] **Acceso Rápido de Ancho (Controles Secundarios):**
    -   **Ubicación:** En `gbn-controls-group gbn-controls-secundario` de los controles secundarios.
    -   **Funcionalidad:** Mostrar el valor actual del ancho (ej: "1/1", "1/2"). Si no tiene valor, asumir "1/1" (sin forzar estilos).
    -   **Interacción:** Al hacer clic, desplegar opciones para seleccionar y aplicar el ancho rápidamente.
    -   **Requisito:** Debe ser totalmente responsive y actualizarse dinámicamente al cambiar de vista (Mobile/Tablet/Desktop).
    -   **Estado:** COMPLETADO. Implementado en `inspector.js` con dropdown y soporte responsive.
- [x] **Acceso Rápido de Ancho (Controles Secundarios):**
    -   **Ubicación:** En `gbn-controls-group gbn-controls-secundario` de los controles secundarios.
    -   **Funcionalidad:** Mostrar el valor actual del ancho (ej: "1/1", "1/2"). Si no tiene valor, asumir "1/1" (sin forzar estilos).
    -   **Interacción:** Al hacer clic, desplegar opciones para seleccionar y aplicar el ancho rápidamente.
    -   **Requisito:** Debe ser totalmente responsive y actualizarse dinámicamente al cambiar de vista (Mobile/Tablet/Desktop).
    -   **Estado:** COMPLETADO. Implementado en `inspector.js` con dropdown y soporte responsive.
    -   **Fix (Feedback):** Mejorada la integración visual (CSS classes), ampliada la lista de opciones (12 fracciones) y corregida la actualización del valor en tiempo real (`state.get`).
    -   **Fix (Feedback 2):** Ajustado estilo del dropdown (Grid 6 cols), corregido cierre inesperado por gap (CSS bridge) y problema de controles fijos.
    -   **Fix (Feedback 3):** Implementado ocultamiento automático de controles (`gbn-show-controls`) tras seleccionar un ancho.

- [x] **Interacción Panel-Página (Docking):**
    -   **Requerimiento:** Al abrir el panel, el ancho de la página debe reducirse para que el panel no cubra el contenido.
    -   **Restricción Crítica:** Esta reducción de ancho es puramente visual para la edición y **NO** debe guardarse en los estilos persistentes de la página.
    -   **Estado:** COMPLETADO. Implementado en `panel-core.js` (clase `gbn-panel-open` en body) y `layout.css` (reducción de ancho de `main`).

- [x] **Refactorización de "Configurar Página":**
    -   **Target:** Botón `<button ... data-gbn-action="page">`.
    -   **Problema:** Funcionalidad actual no adaptada a la lógica responsive de GBN.
    -   **Acción:** Refactorizar para integrar con el sistema responsive (breakpoints).
    -   **Nueva Feature:** Añadir soporte para **Estilos Personalizados (Custom CSS)** específicos de la página (ej: `.mi-clase { ... }`).
    -   **Validación:** Asegurar que las configuraciones se apliquen y guarden individualmente por página.
    -   **Estado:** COMPLETADO. Refactorizado `renderPageSettingsForm` en `render.js` para usar pestañas (Estilo, Avanzado) y campos responsive, incluyendo Custom CSS.

- [x] **Reposicionamiento de Controles Secundarios:**
    -   **Problema:** Conflicto visual con controles principales (ambos a la derecha).
    -   **Solución:** Mover `gbn-controls-secundario` a la izquierda (`left: 8px`).
    -   **Estado:** COMPLETADO. Actualizado `components.css`.
    -   **Fix (Feedback):** Corregido el desbordamiento del dropdown de ancho (`gbn-width-dropdown`) alineándolo a la izquierda (`left: 0`) en lugar de centrarlo, para evitar que se salga de la pantalla al estar los controles en el borde izquierdo.
    -   **Fix (Feedback 2):** Solucionado problema de controles "congelados" al cambiar el ancho. Se fuerza la limpieza de la clase `gbn-show-controls` en *todos* los elementos del DOM al seleccionar un valor, asegurando que no queden estados visuales residuales tras la regeneración del bloque.
    -   **Fix (Feedback 3):** Implementado `suppressHover` (600ms) y ocultamiento forzado de `__gbnControls` al hacer clic en el ancho. Esto previene que el evento `mouseover` se dispare inmediatamente sobre el nuevo elemento renderizado bajo el cursor, evitando la reaparición instantánea (y molesta) de los controles.
    -   **Fix (Feedback 4):** Corregido problema donde los controles no volvían a aparecer nunca tras ser ocultados. Se añadió lógica en el evento `mouseover` para restaurar explícitamente `display: flex` en `__gbnControls`, revirtiendo el `display: none` aplicado al hacer clic.
    -   **Fix (Feedback 5):** Solución definitiva para controles "congelados".
        1.  **CSS:** Añadido `pointer-events: none` a `.gbn-controls-group` (y `auto` en hover) para evitar que los controles invisibles capturen eventos y bloqueen la detección de salida del mouse.
        2.  **JS:** Reemplazado `mouseout` por `mouseleave` para una detección más fiable de la salida del bloque.
        3.  **Safety:** Añadido un "limpiador global" en `mousemove` (con throttle) que elimina la clase `gbn-show-controls` de cualquier bloque que ya no tenga el mouse encima, cubriendo casos extremos donde el evento de salida se pierde.
    -   **Refactorización Mayor (Feedback 6):** Se ha reescrito completamente la lógica de interacción en `inspector.js`.
        -   **HoverManager:** Se eliminaron los listeners individuales por bloque. Ahora un único gestor global (`mousemove` en `document`) usa `document.elementFromPoint(x, y)` para determinar qué bloque está bajo el cursor.
        -   **GlobalControls (Singleton):** Se implementó un *único* conjunto de controles DOM que se mueve dinámicamente al bloque activo. Esto elimina la posibilidad de tener controles duplicados, "zombies" o congelados, ya que físicamente solo existe una instancia de controles en todo el editor.
        -   **Beneficio:** Esto elimina de raíz los problemas de "burbujeo" y "congelamiento" en estructuras anidadas, ya que solo un bloque puede estar activo a la vez (el más profundo visualmente).
        -   **Limpieza:** Se revirtieron los parches de `pointer-events` y `suppressHover` ya que el nuevo sistema es inherentemente robusto.
