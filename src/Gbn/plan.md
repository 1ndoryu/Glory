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

- [ ] **Limpieza de JS (Roles Legacy)**
    -   **Acción:** Eliminar definiciones hardcoded en `roles.js` que ya no son necesarias gracias a la inyección dinámica desde PHP.
    -   **Prioridad:** Media (No bloqueante, el JS prioriza la config inyectada).

- [ ] **Tests de Regresión**
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

- [ ] **Bug 27: Retraso en Layout (Flexbox)**
    -   **Síntoma:** Al modificar opciones de layout en un Div Principal, el cambio no es visualmente inmediato. Requiere re-click o interacción adicional.
    -   **Posible Causa:** Desincronización entre el evento `change` del UI y el trigger del `StyleComposer`.

- [ ] **Bug 28: Theme Settings Flex Global**
    -   **Síntoma:** Las opciones de Flex definidas en Theme Settings no se aplican a los componentes nuevos o existentes que deberían heredarlas.
    -   **Posible Causa:** Falta de consumo de variables CSS de layout en los componentes base o especificidad CSS incorrecta.

- [ ] **Bug 29: Persistencia Responsive (Padding)**
    -   **Síntoma:** Valores asignados en Tablet/Mobile se pierden visualmente o en datos al cambiar de vista y volver.
    -   **Posible Causa:** Fallo en la lectura/escritura del objeto `_responsive` o en la lógica de "burbujeo" inversa al leer el estado para el UI.

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
    -   **Feature:** Un panel flotante activable que muestre para el bloque seleccionado:
        -   Estado Crudo (JSON).
        -   Variables CSS heredadas (Computed).
        -   Fuente del estilo (¿Viene de Theme, de Page o Local?).
    -   **Uso:** Permite al desarrollador (tú) ver instantáneamente por qué un padding no se aplica (ej: "Ah, está siendo sobrescrito por una clase !important").

- [ ] **Logger Estructurado en Consola**
    -   **Acción:** Mejorar los logs para que no sean ruido.
    -   **Formato:** `[GBN:Action] Update Block #123 -> { padding: 20 }`.

### Fase D: Tests Automatizados (The Safety Net)
**Objetivo:** Evitar que un fix rompa otra cosa (Regresiones).

- [ ] **Tests Unitarios para Lógica Core**
    -   **Scope:** Testear `style-composer.js` y `utils.js` con Jest/Vitest.
    -   **Caso de Prueba:** "Dado un config `{ padding: 20 }` y un Theme Setting `{ padding: 10 }`, el composer debe retornar `padding: 20px`".
    -   **Caso de Prueba:** "Dado un config vacio, debe retornar `undefined` (para que actúen las variables)".

---