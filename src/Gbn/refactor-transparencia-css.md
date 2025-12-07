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

### Fase 2: Sistema de Preview Responsive (Iframe Real)
**Estado:** PENDIENTE
**Dependencia:** Fase 1 completada (o en paralelo)
**Estimacion:** 4-6 horas
**Relacionado:** BUG-022

#### 2.1 Diseno del Sistema
- [ ] **2.1.1** Definir estructura del iframe container
- [ ] **2.1.2** Definir parametro URL para modo preview (`?gbn-preview=1`)
- [ ] **2.1.3** Definir protocolo de comunicacion (postMessage)
- [ ] **2.1.4** Documentar flujo de sincronizacion bidireccional

**Diagrama de Arquitectura:**
```
+-------------------+                    +-------------------+
|   GBN Editor      |                    |   Preview Iframe  |
|   (Pagina real)   |                    |   (375px/768px)   |
+-------------------+                    +-------------------+
        |                                        |
        |  1. Usuario cambia breakpoint          |
        |--------------------------------------->|
        |                                        |
        |  2. Iframe carga pagina con            |
        |     ?gbn-preview=1&width=375           |
        |                                        |
        |  3. Usuario edita en editor            |
        |--------------------------------------->|
        |  postMessage: {type:'update',          |
        |                blockId, styles}        |
        |                                        |
        |  4. Iframe aplica cambios              |
        |                                        |
        |  5. Usuario guarda                     |
        |<---------------------------------------|
        |  Editor tiene la fuente de verdad     |
        |                                        |
+-------------------+                    +-------------------+
```

#### 2.2 Implementacion Backend (PHP)
- [ ] **2.2.1** Detectar parametro `?gbn-preview=1` en PHP
- [ ] **2.2.2** En modo preview: no cargar scripts de edicion, solo estilos del tema
- [ ] **2.2.3** Inyectar script minimo de sincronizacion (`preview-receiver.js`)

#### 2.3 Implementacion Frontend (JS)
- [ ] **2.3.1** Crear `services/preview-frame.js` - Maneja creacion y comunicacion del iframe
- [ ] **2.3.2** Modificar `services/responsive.js` - Delegar a preview-frame en tablet/mobile
- [ ] **2.3.3** Modificar `ui/dock.js` - Conectar botones de breakpoint
- [ ] **2.3.4** Crear `preview-receiver.js` - Script que corre dentro del iframe

#### 2.4 CSS del Contenedor
- [ ] **2.4.1** Estilos para el contenedor del iframe (centrado, sombra, marco de dispositivo)
- [ ] **2.4.2** Transiciones suaves entre breakpoints

#### 2.5 Sincronizacion
- [ ] **2.5.1** Editor -> Iframe: Enviar cambios de estilos via postMessage
- [ ] **2.5.2** Iframe -> Editor: (Opcional) Feedback visual de que se aplico
- [ ] **2.5.3** Manejar caso de recarga del iframe (re-enviar estado actual)

**Notas de la implementacion:**
<!-- La IA agregara notas aqui -->

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
**Estado:** PENDIENTE
**Prioridad:** ALTA (Complemento de Fase 1)
**Estimacion:** 2-3 horas

#### 1.5.1 Backend PHP (Tema)
- [ ] Crear clase `ThemeSettingsRenderer` en el tema (no en GBN)
- [ ] Hook en `wp_head` para generar `<style>` con Theme Settings guardados
- [ ] Leer de `wp_options` (key: `gbn_theme_settings`)
- [ ] Usar selectores `:where(h1)`, `:where(h2)`, `:where(p)`, etc.

**Archivo a crear:** `Glory/App/Theme/ThemeSettingsRenderer.php`

```php
// Ejemplo de implementacion
class ThemeSettingsRenderer {
    public function render(): void {
        $settings = get_option('gbn_theme_settings', []);
        if (empty($settings)) return;
        
        echo '<style id="glory-theme-settings">';
        // Solo emitir valores que el usuario explicitamente guardo
        if (!empty($settings['typography']['h1']['fontSize'])) {
            echo ':where(h1) { font-size: ' . esc_attr($settings['typography']['h1']['fontSize']) . '; }';
        }
        // ... etc
        echo '</style>';
    }
}
```

#### 1.5.2 Frontend JS (GBN)
- [ ] Modificar `theme-settings.js` para leer valores computados del DOM
- [ ] Crear elementos de referencia temporales si no existen (h1, h2, p, etc.)
- [ ] Mostrar valores reales en el panel, no defaults hardcodeados

#### 1.5.3 Limpieza
- [ ] Eliminar variables `--gbn-h1-*`, `--gbn-text-*` que quedaron en el JS
- [ ] Actualizar `applicator.js` para trabajar con el nuevo sistema

---

## 5. Notas y Observaciones

> [!TIP]
> La IA puede agregar notas aqui durante cualquier fase del trabajo.

### 7 Diciembre 2025 - Inicio del Refactor
- Se identifico que GBN se habia desviado de su filosofia original
- Se decidio que la solucion para BUG-022 debe ser iframe real, no inyeccion CSS
- Se creo este roadmap para guiar el trabajo
- **Fase 0 y 1 completadas** - Limpieza de ~776 lineas de CSS
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

| Fecha      | Cambio                                      | Autor |
| ---------- | ------------------------------------------- | ----- |
| 2025-12-07 | Creacion del documento                      | IA    |
| 2025-12-07 | Fase 0 y 1 completadas, ADR-001 documentada | IA    |
| 2025-12-07 | Agregada Fase 1.5 para Theme Settings       | IA    |

