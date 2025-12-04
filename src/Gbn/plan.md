# GBN - Plan de Trabajo

> [!TIP] LEER SIEMPRE CUANDO SE RECIBA ESTE MD LOS SIGUIENTES MD: 
> *   **Reglas y Arquitectura:** Ver `reglas.md`.
> *   **Contexto y Estado:** Ver `contexto.md`.
> *   **Documentación:** Ver `documentación-gbn.md`.


## 2. Fases Completadas (Hitos Alcanzados)

### Fase 1: Infraestructura y Core
**Objetivo:** Cimientos arquitectónicos.
- [x] **Definición de Contratos:** `ComponentInterface` y `AbstractComponent` definidos.
- [x] **Carga Dinámica:** `ComponentLoader` implementado.
- [x] **SchemaBuilder:** API fluida (`SchemaBuilder.php`, `Option.php`) implementada.
- [x] **Traits:** `HasFlexbox`, `HasSpacing`, `HasTypography`, `HasGrid` creados.

### Fase 2: Migración de Componentes Base
**Objetivo:** Migración a nueva arquitectura.
- [x] **Componente Principal (Root):** Migrado a `PrincipalComponent.php`.
- [x] **Componente Secundario (Layout):** Migrado a `SecundarioComponent.php`.
- [x] **Componente Texto:** Migrado a `TextComponent.php`.

### Fase 3: Limpieza y Finalización
**Objetivo:** Eliminar deuda técnica.
- [x] **Desactivación de Legacy:** Referencias antiguas eliminadas en `ContainerRegistry.php`.
- [x] **Limpieza de JS:** Definiciones hardcoded eliminadas de `roles.js`.
- [x] **Tests de Regresión:** Verificación manual completada.

### Fase 4: Refactorización JS (SOLID & DRY)
**Objetivo:** Arquitectura Frontend modular.
- [x] **StyleComposer:** Centralización de estilos en `style-composer.js`.
- [x] **Automatización de Tema:** `applicator.js` refactorizado.
- [x] **Field Registry:** Implementado `registry.js` (OCP).

### Roadmap de Estabilidad (Defensa contra Zombie Bugs)
**Objetivo:** Blindar el sistema.
- [x] **Fase A: Arquitectura de Estado:** Store Centralizado (Redux-lite) y Pipeline de Renderizado Puro.
- [x] **Fase B: Programación Defensiva:** Validación de Esquemas (Zod-lite) y Error Boundaries.
- [x] **Fase C: Herramientas de Diagnóstico:** Remote Logger y Debug Overlay (Modo Dios).
- [x] **Fase E: Documentación Viva:** Actualización de `reglas.md`.

### Fase 6: Expansión de Biblioteca y UX
**Objetivo:** Escalar biblioteca y mejorar UX.
- [x] **6.1 Integración Diagnóstico:** Validación automática de nuevos componentes.
- [x] **6.2 Refactorización Biblioteca:** Nueva lógica de inserción inteligente y componentes atómicos (Imagen, Botón).
- [x] **6.2.2 Imágenes de Fondo:** Trait `HasBackground` integrado en contenedores.
- [x] **6.2.1 Hotfix UX/Persistencia:** Persistencia de nuevos bloques y zonas de inserción.
- [x] **6.3 Mejoras Componentes Base:** Custom CSS, Estandarización de Spacing y Tabs en paneles.
- [x] **6.4 Mejoras Visuales:** Acceso rápido de ancho, Docking de panel, Configuración de página refactorizada y Reposicionamiento de controles.

---

## 3. Historial de Cambios y Resoluciones (Detallado)

### Diciembre 2025 (Reciente)

#### ✅ Refinamiento de Dimensiones (Element Sizing)
-   **Problema:** `min-height` y `min-width` aplicados directamente a los `div` causaban problemas de persistencia y dificultad en la interacción con el ratón.
-   **Solución:** Se eliminó la aplicación directa de estas propiedades en los elementos y se movió el control de tamaño mínimo al inspector/editor (`interactive.css`), asegurando que la estructura guardada sea limpia.

#### ✅ Fix Unidades REM
-   **Problema:** Las unidades `rem` no funcionaban correctamente en el CSS generado.
-   **Solución:** Corregida la gestión de unidades en el generador de estilos.

#### ✅ Bug 10: Persistencia de Layout Options (Flexbox)
-   **Solución:** Actualizada lógica de hidratación para incluir `direction`, `wrap`, `justify`, `align`.

#### ✅ Bug 11: Salida HTML Limpia
-   **Solución:** `data-gbn-schema` solo se inyecta si `config.isEditor` es true.

#### ✅ Bug 8: Persistencia de Valores Responsive en Tema
-   **Solución:** Implementado "burbujeo" (Mobile -> Tablet -> Desktop) en `themeSettings`.

#### ✅ Refactorización SOLID de Paneles
-   **Cambio:** Descomposición de `panel-fields.js` en módulos (`spacing.js`, `typography.js`).

#### ✅ Bug 24, 25, 26: Corrección de Parsing de Esquema
-   **Solución:** `style-composer.js` y `applicator.js` ahora iteran correctamente sobre arrays de campos.

#### ✅ Bug 30: Crash en Inspector (JSON Serialization)
-   **Solución:** Uso de `Object.assign` en Store para preservar referencias DOM.

#### ✅ Bug 29: Persistencia Responsive en Theme Settings
-   **Solución:** Escritura en ruta anidada correcta (`themeSettings.components.principal._responsive`).

#### ✅ Bug 28 & 27: Herencia de Flexbox y Layout Delay
-   **Solución:** Inyección explícita de `var(--gbn-role-prop)` cuando no hay configuración local.

#### ✅ Bug 33: Flash de Contenido Flex (Fix V8)
-   **Solución:** Eliminado fallback forzado a `flex` en `style-composer.js`.

#### ✅ Bug 32: Grid Defaults (Fix V13)
-   **Solución:** Excepción en `applicator.js` para `gridColumns` (sin unidades).

#### ✅ Bug 34: Margen sin efecto real
-   **Solución:** Refactorizado `extractSpacingStyles` y soporte en `style-composer.js`.

#### ✅ Bug 35: Iconos faltantes en Theme Settings
-   **Solución:** Propagación de propiedad `icon` desde PHP y limpieza de `roles.js`.

#### ✅ Mejora UX: Unificación de Paneles
-   **Solución:** Implementación de pestañas en `render.js` (Theme Settings).

#### ✅ Implementación: Página de Prueba (Constructor)
-   **Cambio:** Lógica condicional en `header.php` y diseño Toyota Supra en `contructor.php`.

#### ✅ Mejoras Visuales (Fase 6.4)
-   **Acceso Rápido de Ancho:** Dropdown responsive en controles secundarios.
-   **Docking:** Reducción visual del ancho de página al abrir panel.
-   **Configuración Página:** Tabs y Custom CSS.
-   **GlobalControls:** Refactorización completa de controles de interacción (Singleton) para eliminar bugs de "controles congelados".

#### ✅ Refactorización Hero Section (Constructor)
-   **Cambio:** Implementación de `TextComponent` y `ButtonComponent` en la sección Hero de `contructor.php` para reemplazar HTML hardcodeado.

#### ✅ Mejora DX: Inferencia de Contenido
-   **Problema:** Redundancia al declarar `texto` y `tag` en `opciones` cuando ya existen en el HTML.
-   **Solución:** Actualizado `builder.js` para inferir automáticamente `texto` (de `innerHTML`) y `tag` (de `tagName`) si no se especifican explícitamente.

#### ✅ Fix: Tipografía en Tiempo Real (TextComponent)
-   **Problema:** Los cambios de tipografía (fuente, tamaño, line-height, etc.) no se aplicaban en tiempo real. La fuente mostraba "Default" y el tamaño aparecía vacío. Padding/Margin tampoco funcionaban.
-   **Solución Multi-parte:**
    1. **`text.js` (Renderer):** Refactorizado `handleUpdate` para manejar paths anidados (`typography.*`, `padding.*`, `margin.*`). Ahora aplica cambios directamente al DOM.
    2. **`typography.js` (Panel Field):** Implementada sincronización bidireccional CSS→Panel. Lee valores computados del DOM (`getComputedStyle`) cuando no hay configuración explícita.
    3. **`TextComponent.php`:** Agregadas opciones de `backgroundColor`, `borderWidth`, `borderStyle`, `borderColor` y `borderRadius`.
    4. **`utils.js`:** Expandido `CONFIG_TO_CSS_MAP` con typography, margin y border properties.

#### ✅ Mejoras Panel: Border, Fuentes y Fondo (UX)
-   **Problema:** Valores de borde vacíos en panel, fuentes detectadas como "Default", imagen de fondo no editable desde panel y opciones de fondo visibles innecesariamente.
-   **Solución Integral:**
    -   **Border Sync:** `text.js` ahora lee valores computados (`borderWidth`, `borderRadius`) del DOM si no hay config.
    -   **Fuentes Inteligentes:** `typography.js` soporta fuentes genéricas (Monospace, Serif) y detecta fuentes personalizadas dinámicamente.
    -   **Imagen de Fondo:** Implementado soporte para **Galería WordPress** (`wp.media`) en `image.js`, lectura de `backgroundImage` desde CSS y parsing de URLs.
    -   **Condiciones y UX:** `HasBackground.php` ahora usa condiciones (`->condition()`) para ocultar opciones de tamaño/posición si no hay imagen. `Option.php` actualizado para soportar lógica condicional.
    -   **Iconos:** Mejorada UX de `backgroundSize` con iconos visuales (Cover, Contain, etc.).

#### ✅ Smart Dimension Control (Ancho)
-   **Problema:** El control de ancho estaba limitado a fracciones predefinidas (`1/2`, `1/3`), impidiendo el uso de valores arbitrarios (`100%`, `500px`) o clases CSS.
-   **Solución:** Implementado `Smart Dimension Control` en `fraction.js`.
    -   Agregado input manual para valores libres.
    -   Sincronización bidireccional entre input y botones de presets.
    -   Actualizado `shared.js` para procesar cualquier valor CSS válido.
    -   Estilizado en `forms.css`.

### Fase 6.5: Posicionamiento y Dimensiones Avanzadas
**Objetivo:** Completar el control de layout y posicionamiento.
- [ ] **Trait HasPositioning:** Centralizar lógica de `position` y `z-index`.
- [ ] **Esquemas Componentes:** Integrar `HasPositioning` en `Principal`, `Secundario` y `Text`.
- [ ] **Mejora Altura:** Convertir `height` en input flexible (Smart Control) en todos los contenedores.
- [ ] **Max-Width Secundario:** Agregar opción `maxAncho` a `SecundarioComponent`.
- [ ] **Renderizado:** Actualizar `style-composer.js` para procesar nuevas propiedades.

---

## Trabajo Pendiente

### ✅ Fase 7: Soporte de Transparencia en Campos de Color

**Problema Identificado:**
Los inputs de color actuales no soportan transparencia (alpha channel). El `<input type="color">` nativo del navegador solo soporta colores sólidos HEX de 6 dígitos (#RRGGBB), no RGBA ni HEX de 8 dígitos (#RRGGBBAA).

**Solución Implementada:**
- **Refactorización `color.js`:** Implementado soporte para RGBA y slider de opacidad.
- **Nuevo Helper `color-utils.js`:** Funciones de conversión HEX<->RGBA y parsing de colores.
- **UI Update:** Añadido preview visual (checkerboard) y slider de opacidad en `forms.css`.
- **Sincronización:** El campo de color ahora lee y escribe valores RGBA correctamente.
- **Compatibilidad:** Los campos sin `permiteTransparencia` siguen funcionando solo con HEX.

**Archivos Modificados:**
- `src/Gbn/assets/js/ui/panel-fields/color.js`
- `src/Gbn/assets/js/ui/panel-fields/color-utils.js` (Nuevo)
- `src/Gbn/assets/css/forms.css`

**Criterios de Aceptación:**
- [x] El slider de opacidad aparece solo cuando `permiteTransparencia: true`.
- [x] Los colores se muestran con preview de transparencia visual.
- [x] Los cambios de opacidad se aplican en tiempo real al elemento.
- [x] Los valores RGBA persisten correctamente al guardar.
- [x] La sincronización bidireccional CSS↔Panel funciona con RGBA.
- [x] Compatibilidad hacia atrás: colores HEX existentes siguen funcionando.

---

## 4. Bugs Críticos Resueltos (Histórico)
> [!NOTE]
> Referencia de errores graves que han sido solucionados definitivamente.

- **Bug 27: Retraso en Layout (Flexbox)** -> Solucionado con `:where()` y variables CSS.
- **Bug 28: Theme Settings Flex Global** -> Solucionado con arquitectura de Nivel 2.
- **Bug 29: Persistencia Responsive (Padding)** -> Solucionado en `panel-render.js`.
- **Bug 30: Flex Click Bug** -> Solucionado (V6.1).
- **Bug 31: Alineación de Contenido** -> Solucionado (V6.1).
- **Bug 32 & 27: Grid/Flex Conflict** -> Solucionado (V13).
- **Bug 33: Flash de Contenido Flex** -> Solucionado (V8).
