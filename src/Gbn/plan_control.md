# GBN Control Center - Roadmap de Desarrollo

> [!NOTE]
> Este documento define la hoja de ruta para la creación del **"Centro de Control GBN"**, una herramienta de observabilidad interna diseñada para mapear, visualizar y auditar el estado del sistema en tiempo real sin afectar la producción.

## 1. Visión y Objetivos

**Objetivo Principal:** Crear una "Página de Estado" (Dashboard) dentro del admin de WordPress que funcione como una radiografía completa del sistema GBN.

**Principios de Diseño:**

1.  **Aislamiento Total:** Esta herramienta vivirá en su propio ecosistema (página de admin propia, assets propios) para garantizar **CERO RIESGO** de regresiones en el constructor o el frontend.
2.  **Solo Lectura (Read-Only):** Su función principal es visualizar y auditar, no modificar datos, eliminando el riesgo de corrupción.
3.  **Minimalismo Funcional:** Diseño limpio, tipografía monoespaciada para datos técnicos, alto contraste y densidad de información optimizada.
4.  **Verdad Computada:** No solo mostrará lo que está en la base de datos, sino lo que el sistema _cree_ que son los defaults y variables.

---

## 2. Arquitectura Técnica

### A. Ubicación y Acceso

-   **Backend:** Nueva clase `Glory/src/Gbn/Diagnostics/ControlPanelManager.php` que registra la página `gbn-control-panel` usando `Glory\Manager\PageManager`.
-   **Frontend:** Nueva carpeta `Glory/src/Gbn/assets/control-panel/` con su propio `app.js` y `style.css`.
-   **Rationale:** Se usa `Diagnostics/` en lugar de `Admin/` para mantener GBN autocontenido. `Glory/src/Admin/` es del framework, no del módulo GBN.

### B. Fuente de Datos

La página recibirá un objeto JSON masivo (`gloryGbnSystemMap`) inyectado por PHP que contiene:

1.  **Registry Dump:** Todos los componentes registrados y sus esquemas completos.
2.  **Theme Settings:** La configuración global actual guardada en `wp_options`.
3.  **CSS Vars Map:** Un mapa generado de todas las variables CSS que el sistema espera usar.

---

## 3. Fases de Implementación

### Fase 0: API Foundation (Acceso Programático)

**Objetivo:** Crear endpoints REST para que la IA y herramientas externas puedan consultar el estado del sistema.

-   [x] **0.1 Handler de Diagnóstico:**
    -   Crear `Ajax/Handlers/DiagnosticsHandler.php` siguiendo el patrón existente de handlers.
    -   Registrar endpoint `wp_ajax_gbn_diagnostics_dump` en `Ajax/Registrar.php`.
    -   Implementar método `dump()` que retorne JSON con:
        ```php
        [
            'components' => ContainerRegistry::all(),
            'themeSettings' => get_option('gbn_theme_settings', []),
            'traits' => SystemMapper::getTraitMap(),
            'payload' => [
                'size' => strlen(json_encode($gloryGbnCfg)),
                'schemas' => ContainerRegistry::rolePayload()
            ],
            'timestamp' => current_time('mysql'),
            'version' => wp_get_theme()->get('Version')
        ]
        ```
-   [x] **0.2 Endpoint de Validación:**
    -   Crear método `validate()` en `DiagnosticsHandler` que ejecute:
        -   Comparación schemas PHP vs `gloryGbnCfg`
        -   Detección de campos huérfanos en DB
        -   Validación de tipos de datos
    -   Retornar JSON con scoring de salud del sistema.
-   [x] **0.3 Seguridad y Permisos:**
    -   Verificar capability `manage_options` antes de exponer datos.
    -   Agregar nonce validation para evitar acceso no autorizado.
    -   Opcionalmente: Agregar parámetro `?format=json` vs `?format=html` para dual-mode.
-   [ ] **0.4 Documentación de API:**
    -   Crear `Diagnostics/API.md` documentando:
        -   Endpoints disponibles (`/wp-admin/admin-ajax.php?action=gbn_diagnostics_dump`)
        -   Estructura de respuesta JSON
        -   Ejemplos de uso con `curl` para la IA

**Beneficio para IA**: La IA puede ejecutar `wp_ajax GET gbn_diagnostics_dump` y obtener un snapshot completo del sistema en JSON, facilitando análisis automatizado sin leer múltiples archivos.

---

### Fase 1: Infraestructura Segura (Backend)

**Objetivo:** Crear el contenedor vacío y asegurar el flujo de datos (UI).

-   [ ] **1.1 Controlador de Admin:**
    -   Crear `Diagnostics/ControlPanelManager.php`.
    -   Registrar página gestionada `PageManager::define('gbn-control-panel', ...)` con restricción de rol `administrator`.
-   [ ] **1.2 Recolector de Datos (The Mapper):**
    -   Crear `Diagnostics/SystemMapper.php` con método estático `dump()` que agregue:
        -   `ContainerRegistry::all()` (Todos los componentes registrados).
        -   `get_option('gbn_theme_settings')` (Configuración global del tema).
        -   **Nuevo**: Método `SystemMapper::getTraitMap()` que extraiga Traits por componente:
            ```php
            foreach (ContainerRegistry::all() as $role => $data) {
                $className = "Glory\\Gbn\\Components\\{$role}\\{$role}Component";
                $traits[$role] = class_uses($className) ?: [];
            }
            ```
-   [ ] **1.3 Vista Base:**
    -   Crear plantilla HTML vacía con un contenedor `#gbn-control-app`.
    -   Encolar scripts/estilos específicos para esta página (`control-panel.css`, `control-panel.js`).

### Fase 1.5: Integración con Herramientas Existentes

**Objetivo:** Conectar el Control Panel con las herramientas de diagnóstico ya funcionales.

-   [x] **1.5.1 Link al Debug Overlay:**
    -   Agregar botón "Inspeccionar Componente" que active `Gbn.ui.debug.overlay.setBlock(roleId)` para un rol específico.
    -   Documentar tecla de acceso rápido existente (Ctrl+Alt+D).
-   [x] **1.5.2 Integración con Remote Logger:**
    -   Mostrar estado del logger (`ENABLED: true/false`) en `assets/js/services/logger.js`.
    -   Agregar toggle para activar/desactivar Remote Logger sin editar código.
    -   Mostrar últimos 100 logs del cliente (si están disponibles en el estado actual).

---

### Fase 2: Interfaz de Usuario (UI Minimalista)

**Objetivo:** Visualizar la lista de componentes y sus propiedades básicas.

-   [x] **2.1 Estructura CSS (Grid System):**
    -   Diseñar un layout de Dashboard: Sidebar (Lista de Componentes) + Main Area (Detalles).
    -   Estilo "Dark Mode" técnico: `background: #0d1117`, `color: #58a6ff`, fuente `monospace`.
-   [x] **2.2 Renderizado de Componentes:**
    -   Listar todos los roles (`principal`, `secundario`, `texto`, etc.) desde `ContainerRegistry::all()`.
    -   Al hacer clic, mostrar **Esquema Crudo** (JSON tree colapsable) + **Traits utilizados**.
    -   **Confirmación Visual de Observabilidad:** Mostrar indicador "Ready" si el componente tiene selector válido para sincronización de estilos inline/clase.

### Fase 3: Mapeo Profundo (La "Radiografía")

**Objetivo:** Cruzar los datos del esquema con las variables CSS y valores defaults.

-   [ ] **3.1 Tabla de Variables CSS:**
    -   Para cada campo del componente, generar dinámicamente el nombre de la variable CSS esperada (ej: `padding` → `--gbn-principal-padding-top`).
    -   Mostrar columnas: **Campo**, **Variable CSS**, **Valor Esperado (Schema)**, **Valor Real (DB)**.
-   [ ] **3.2 Auditoría de Defaults:**
    -   Comparar `ContainerRegistry::rolePayload()` (Schemas PHP) vs `get_option('gbn_theme_settings')` (Valores guardados).
    -   Resaltar campos con valores **divergentes** (valor en DB ≠ default del schema).
-   [ ] **3.3 Visualizador de Herencia:**
    -   Gráfico de cascada que muestre: `Configuración Local > Theme Settings > CSS Base`.
    -   Indicador visual de qué nivel está "ganando" para cada propiedad.
-   [ ] **3.4 Estadísticas de Rendimiento:**
    -   **Número total de componentes** registrados en `ContainerRegistry`.
    -   **Tamaño del payload** `gloryGbnCfg` (en KB) para detectar payloads excesivamente grandes.
    -   **Gráfico de "Campos por Componente"** (detectar componentes sobrecargados que ralentizan el panel).
    -   **Traits más usados** (estadística de cuáles se repiten más).

### Fase 4: Validación de Integridad (Bug Detector Automático)

**Objetivo:** Detectar inconsistencias entre PHP, JS y DB sin depender del DOM.

-   [ ] **4.1 Comparador de Esquemas:**
    -   Comparar `ContainerRegistry::rolePayload()` (Backend PHP) vs `gloryGbnCfg.roleSchemas` (Frontend JS).
    -   Detectar campos que existen en el schema PHP pero no se inyectaron en JS (problema de serialización).
-   [ ] **4.2 Detector de Campos Huérfanos:**
    -   Buscar propiedades en `gbn_theme_settings` (DB) que ya no existen en ningún schema registrado.
    -   Mostrar lista de "Campos obsoletos" que pueden eliminarse de forma segura.
-   [ ] **4.3 Validador de Tipos de Datos:**
    -   Para cada campo, verificar que el tipo guardado en DB coincida con el esperado en el schema.
    -   Ejemplo: Schema espera `slider` (int), pero DB tiene `"20px"` (string con unidad).
    -   Marcar en **ROJO** si hay discrepancia crítica.
-   [ ] **4.4 Reporte de Salud del Sistema:**
    -   Dashboard con scoring: **Verde** (0 errores), **Amarillo** (warnings), **Rojo** (errores críticos).
    -   Exportar reporte en formato JSON para compartir en debugging.

---

## 4. Estructura Visual Propuesta

**Interfaz Dual: UI Web + API REST**

```text
+---------------------------------------------------------------+
|  GBN CONTROL CENTER                  [v1.0]  [API Mode: ON]  |
+-------------------+-------------------------------------------+
| COMPONENTES       | DETALLES: Principal Component             |
|                   |                                           |
| > Principal       | [ Traits: HasFlexbox, HasSpacing ]        |
|   Secundario      |                                           |
|   Texto           | TABLA DE PROPIEDADES:                     |
|   Imagen          | +-----------+-------------+---------------+
|   Boton           | | Campo     | Var CSS     | Valor Actual  |
|                   | +-----------+-------------+---------------+
|                   | | padding   | --gbn-p...  | 20px (Theme)  |
| SYSTEM            | | display   | --gbn-p...  | flex (Base)   |
|                   | | color     | --gbn-p...  | #000 (Hard)   |
| > Global Vars     | +-----------+-------------+---------------+
|   Breakpoints     |                                           |
|   API Endpoints   | [ HEALTH SCORE: 95/100 ] ✅               |
+-------------------+-------------------------------------------+
| API: /wp-admin/admin-ajax.php?action=gbn_diagnostics_dump    |
+---------------------------------------------------------------+
```

**Acceso Programático (Para IA)**:

```bash
GET /wp-admin/admin-ajax.php?action=gbn_diagnostics_dump
→ Retorna JSON con estado completo del sistema
```

## 5. Estado Actual y Próximos Pasos

### Completado:

-   [x] ✅ **Aprobar roadmap** (Aprobado: 2025-12-03)
-   [x] ✅ **Crear `plan_control.md`** (Este archivo - Creado y actualizado)
-   [x] ✅ **Verificación de arquitectura** (Investigación profunda del código completada)

### En Progreso:

-   [x] ✅ **Fase 0**: API Foundation (Endpoints REST para IA)
-   [x] ✅ **Fase 1.1**: Crear `Diagnostics/ControlPanelManager.php` (Usando `PageManager`)
-   [x] ✅ **Fase 1.2**: Implementar `SystemMapper::dump()` y `getTraitMap()`
-   [x] ✅ **Fase 1.3**: Crear vista base HTML + assets

### Pendiente (Prioridad Alta):

-   [x] ✅ **Fase 0 (API)**: Endpoints REST antes de la UI (Completado)
-   [x] ✅ **Fase 1.5**: Integración con `overlay.js` (Logger completado)
-   [x] ✅ **Fase 2**: Interfaz de usuario minimalista (Sidebar + Dashboard + Detalles implementados)
-   [x] ✅ **Fase 3**: Mapeo profundo y métricas (Tabla de variables CSS + Auditoría de Defaults)
-   [x] ✅ **Fase 4**: Validación de integridad automática (Health Check implementado)

### Futuro (Opcional):

-   [ ] 📅 **Fase 5**: Edición en vivo (Experimental)

---

## 6. Notas de Implementación

### Decisiones Arquitectónicas:

1. **Ubicación `Diagnostics/` (No `Admin/`)**: Mantiene GBN autocontenido. `Glory/src/Admin/` pertenece al framework, no al módulo.
2. **Uso de `PageManager`**: Se prefiere una página frontend gestionada (`/gbn-control-panel`) sobre una página de admin (`wp-admin`) para tener control total del lienzo y evitar conflictos de CSS con el dashboard de WP.
3. **`ContainerRegistry::all()` (No `ComponentLoader::getRegistry()`)**: El método sugerido originalmente no existe. `ContainerRegistry` es la fuente de verdad.
4. **Validación Lógica (No DOM)**: La Fase 4 evita renderizar elementos ocultos que violarían el principio de aislamiento. La validación es puramente lógica (PHP/JS/DB).

### Herramientas Existentes Reutilizables:

-   **`logger.js`**: Remote Logger funcional (actualmente desactivado con `ENABLED=false`).
-   **`overlay.js`**: Debug Overlay con tecla `Ctrl+Alt+D` ya implementada.
-   **`ContainerRegistry`**: Métodos `all()`, `rolePayload()`, `selectors()` disponibles.
-   **`class_uses()`**: Función PHP nativa para extraer Traits de componentes.

### Uso de API para la IA:

La IA podrá consultar el estado del sistema ejecutando comandos como:

```bash
# Obtener snapshot completo del sistema
curl -X POST "http://localhost/wp-admin/admin-ajax.php" \
  -d "action=gbn_diagnostics_dump" \
  -d "nonce=NONCE_VALUE" \
  --cookie "wordpress_logged_in_cookie=..."

# Ejecutar validación de integridad
curl -X POST "http://localhost/wp-admin/admin-ajax.php" \
  -d "action=gbn_diagnostics_validate" \
  -d "nonce=NONCE_VALUE"
```

**Casos de Uso para IA**:

1. **Debugging Automatizado**: Antes de sugerir cambios, la IA consulta el estado actual del sistema.
2. **Validación Post-Deploy**: Después de implementar cambios, verificar que no haya regresiones.
3. **Análisis de Rendimiento**: Detectar payloads excesivamente grandes o componentes sobrecargados.
4. **Detección de Drift**: Comparar el estado del sistema vs la documentación (`plan.md`, `reglas.md`).

**Respuesta JSON Ejemplo**:

```json
{
  "status": "success",
  "data": {
    "components": {
      "principal": { "role": "principal", "config": {...}, "schema": [...] },
      "secundario": { "role": "secundario", "config": {...}, "schema": [...] }
    },
    "traits": {
      "principal": ["HasFlexbox", "HasGrid", "HasSpacing"],
      "secundario": ["HasFlexbox", "HasSpacing"]
    },
    "health": {
      "score": 95,
      "warnings": 2,
      "errors": 0,
      "issues": [
        {"type": "orphan_field", "field": "oldLayoutType", "severity": "low"}
      ]
    },
    "metrics": {
      "totalComponents": 3,
      "payloadSize": "84KB",
      "largestComponent": "principal (42 fields)"
    }
  }
}
```

### Próximo Comando Sugerido:

```bash
# Crear estructura de directorios
mkdir Glory/src/Gbn/Diagnostics
mkdir Glory/src/Gbn/assets/control-panel
```
