# GBN Control Center - Roadmap

> [!NOTE]
> Herramienta de observabilidad interna para mapear, visualizar y auditar el estado del sistema GBN en tiempo real.

---

## 1. Visión

**Objetivo:** Dashboard dentro del admin de WordPress que funciona como radiografía completa del sistema GBN.

### Principios de Diseño

1. **Aislamiento Total**: Assets propios, cero riesgo de regresiones
2. **Solo Lectura**: Visualizar y auditar, no modificar
3. **Minimalismo Funcional**: Diseño limpio, monoespaciado, alto contraste
4. **Verdad Computada**: Muestra lo que el sistema cree que son los defaults y variables

---

## 2. Arquitectura Técnica

### Ubicación y Acceso

| Componente | Ubicación                                           | Función                             |
| :--------- | :-------------------------------------------------- | :---------------------------------- |
| Backend    | `Glory/src/Gbn/Diagnostics/ControlPanelManager.php` | Registra página `gbn-control-panel` |
| Frontend   | `Glory/src/Gbn/assets/control-panel/`               | `app.js` y `style.css` propios      |
| API        | `Ajax/Handlers/DiagnosticsHandler.php`              | Endpoints REST para IA              |

### Fuente de Datos

El objeto `gloryGbnSystemMap` inyectado por PHP contiene:

1. **Registry Dump**: Todos los componentes y schemas
2. **Theme Settings**: Configuración global (`wp_options`)
3. **CSS Vars Map**: Variables CSS esperadas por el sistema

---

## 3. Estado de Implementación

### ✅ Fase 0: API Foundation (COMPLETADO)

**Endpoints REST funcionales:**

```bash
# Snapshot completo del sistema
GET /wp-admin/admin-ajax.php?action=gbn_diagnostics_dump

# Validación de integridad
GET /wp-admin/admin-ajax.php?action=gbn_diagnostics_validate
```

**Estructura de respuesta JSON:**

```json
{
  "status": "success",
  "data": {
    "components": {
      "principal": { "role": "principal", "config": {...}, "schema": [...] }
    },
    "traits": {
      "principal": ["HasFlexbox", "HasGrid", "HasSpacing"]
    },
    "health": {
      "score": 95,
      "warnings": 2,
      "errors": 0,
      "issues": [...]
    },
    "metrics": {
      "totalComponents": 8,
      "payloadSize": "84KB",
      "largestComponent": "principal (42 fields)"
    }
  }
}
```

**Seguridad implementada:**
- ✅ Capability `manage_options` requerido
- ✅ Nonce validation
- ✅ JSON/HTML dual-mode

### ✅ Fase 1: Infraestructura Backend (COMPLETADO)

| Tarea                | Estado | Archivo                                 |
| :------------------- | :----- | :-------------------------------------- |
| Controlador de Admin | ✅      | `Diagnostics/ControlPanelManager.php`   |
| Recolector de Datos  | ✅      | `Diagnostics/SystemMapper.php`          |
| Vista Base HTML      | ✅      | Template con `#gbn-control-app`         |
| Assets Propios       | ✅      | `control-panel.css`, `control-panel.js` |

### ✅ Fase 1.5: Integración con Herramientas (COMPLETADO)

- ✅ Link a Debug Overlay (`Ctrl+Alt+D`)
- ✅ Estado Remote Logger (ENABLED: true/false)
- ✅ Toggle para activar/desactivar logger
- ✅ Últimos 100 logs del cliente

### ✅ Fase 2: UI Minimalista (COMPLETADO)

**Layout implementado:**
- ✅ Grid System (Sidebar + Main Area)
- ✅ Dark Mode técnico (`#0d1117`, `monospace`)
- ✅ Lista de componentes con roles
- ✅ Esquema JSON colapsable
- ✅ Traits utilizados por componente
- ✅ Indicador "Ready" para observabilidad

### ✅ Fase 3: Mapeo Profundo (COMPLETADO)

| Funcionalidad                             | Estado |
| :---------------------------------------- | :----- |
| Tabla de Variables CSS                    | ✅      |
| Auditoría de Defaults                     | ✅      |
| Visualizador de Herencia                  | ✅      |
| Estadísticas de Rendimiento               | ✅      |
| Métricas (Payload Size, Top Traits, etc.) | ✅      |

**Nota:** Se implementó dashboard de métricas completo. Se solucionó bug de parsing SVG en consola escapando JSON.

### ✅ Fase 4: Validación de Integridad (COMPLETADO)

| Validador                          | Estado |
| :--------------------------------- | :----- |
| Comparador de Esquemas (PHP vs JS) | ✅      |
| Detector de Campos Huérfanos       | ✅      |
| Validador de Tipos de Datos        | ✅      |
| Reporte de Salud del Sistema       | ✅      |

**Health Check:**
- **Verde** (0 errores)
- **Amarillo** (warnings)
- **Rojo** (errores críticos)
- Exportable en JSON

---

## 4. Interfaz Actual

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
|                   | +-----------+-------------+---------------+
| > Global Vars     |                                           |
|   Breakpoints     | [ HEALTH SCORE: 95/100 ] ✅               |
|   API Endpoints   |                                           |
+-------------------+-------------------------------------------+
| API: /wp-admin/admin-ajax.php?action=gbn_diagnostics_dump    |
+---------------------------------------------------------------+
```

---

## 5. Uso de API para IA

### Endpoint de Diagnóstico

```bash
# Snapshot completo
curl -X POST "http://localhost/wp-admin/admin-ajax.php" \
  -d "action=gbn_diagnostics_dump" \
  -d "nonce=NONCE_VALUE" \
  --cookie "wordpress_logged_in_cookie=..."

# Validación de integridad
curl -X POST "http://localhost/wp-admin/admin-ajax.php" \
  -d "action=gbn_diagnostics_validate" \
  -d "nonce=NONCE_VALUE"
```

### Casos de Uso

1. **Debugging Automatizado**: Consultar estado antes de sugerir cambios
2. **Validación Post-Deploy**: Verificar ausencia de regresiones
3. **Análisis de Rendimiento**: Detectar payloads grandes o componentes sobrecargados
4. **Detección de Drift**: Comparar sistema vs documentación

---

## 6. Trabajo Futuro (Opcional)

### Fase 5: Edición en Vivo (Experimental)

- [ ] Permitir editar Theme Settings desde el Control Panel
- [ ] Botón "Limpiar campos huérfanos" con confirmación
- [ ] Reset de componente a defaults
- [ ] Exportar/Importar configuración completa

**Estado:** Pendiente - Baja prioridad  
**Rationale:** El Control Panel cumple su función primaria (observabilidad). Edición en vivo requiere validación extensiva para evitar corrupción de datos.

---

## 7. Notas de Implementación

### Decisiones Arquitectónicas

1. **`Diagnostics/` (No `Admin/`)**: Mantiene GBN autocontenido
2. **Uso de `PageManager`**: Página frontend gestionada en lugar de admin WP
3. **`ContainerRegistry::all()`**: Fuente de verdad para componentes
4. **Validación Lógica**: No renderiza DOM oculto, evita violación de aislamiento

### Herramientas Reutilizables

- ✅ `logger.js`: Remote Logger (desactivado por defecto)
- ✅ `overlay.js`: Debug Overlay (`Ctrl+Alt+D`)
- ✅ `ContainerRegistry`: Métodos `all()`, `rolePayload()`, `selectors()`
- ✅ `class_uses()`: PHP nativo para extraer Traits

---

## 8. Comandos Útiles

```bash
# Acceder al Control Center
# URL: /wp-admin/admin.php?page=gbn-control-panel

# API desde línea de comandos
wp eval 'echo json_encode(Glory\Gbn\Diagnostics\SystemMapper::dump());'

# Validar integridad
wp eval 'Glory\Gbn\Ajax\Handlers\DiagnosticsHandler::validate();'
```

---

**Versión:** 2.0 (Optimizada)  
**Estado:** Fases 0-4 completadas. Fase 5 pendiente (baja prioridad)  
**Relacionado:** `plan.md`, `reglas.md`, `documentación-gbn.md`
