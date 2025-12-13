# Investigacion: Problema de Modo Contenido GBN

> **Fecha:** 2025-12-13  
> **Estado:** EN INVESTIGACION - Requiere aclaracion del usuario

---

## 1. Descripcion del Problema Reportado

El usuario reporta que:
> "Las paginas de un momento a otro sin motivo alguno aparecen cargando desde la base de datos sin haber guardado la pagina en GBN"

El diagnostico JSON muestra:
```json
{
  "contentMode": "editor",
  "gbn_config": "EMPTY",
  "gbn_styles": "EMPTY",
  "postContentLength": 17675
}
```

**Sintoma visible:** La pagina aparece con "Carga desde: Base de datos" y "0 bloques guardados en DB" aunque el usuario no guardo nada en GBN.

---

## 2. Arquitectura del Sistema de Modos

### 2.1 Los Dos Modos de Contenido

| Modo     | Meta Key                         | Comportamiento                                             |
| -------- | -------------------------------- | ---------------------------------------------------------- |
| `code`   | `_glory_content_mode = 'code'`   | Renderiza ejecutando la funcion PHP (ej: `landing_hero()`) |
| `editor` | `_glory_content_mode = 'editor'` | Lee HTML desde `post_content` en la base de datos          |

### 2.2 Flujo de Renderizado (TemplateGlory.php)

```php
$modo = PageManager::getModoContenidoParaPagina($postId);

if ($modo === 'editor') {
    // LEE desde base de datos
    the_content();
} else {
    // EJECUTA funcion PHP
    call_user_func($funcionRenderizar);
}
```

### 2.3 Donde se Cambia el Modo

He identificado **5 lugares** donde el sistema cambia `_glory_content_mode`:

| Archivo                          | Linea   | Contexto                                 | Cambia a                        |
| -------------------------------- | ------- | ---------------------------------------- | ------------------------------- |
| `ConfigHandler.php`              | 77      | Al guardar desde GBN                     | `editor`                        |
| `ConfigHandler.php`              | 178     | Al restaurar pagina desde GBN            | `code`                          |
| `PageManager.php`                | 256-259 | Al crear pagina nueva                    | `code` o `editor` segun default |
| `PageManager.php`                | 324     | Migracion automatica (si default=editor) | `editor`                        |
| `SyncManager.php`                | 191     | En "Restablecer a Default"               | `editor`                        |
| `DefaultContentSynchronizer.php` | 76      | En `restablecer()` (tipos de post)       | `editor`                        |

---

## 3. Analisis de Posibles Causas

### 3.1 Hipotesis A: Migracion Automatica en PageManager

**Codigo sospechoso (`PageManager::_actualizarPaginaExistente`):**
```php
if (self::$modoPorDefecto === 'editor' && $modoActual !== 'editor') {
    // Cambia automaticamente a modo editor si hash coincide
}
```

**Hallazgo:** Tu configuracion en `App/Config/pages.php` es:
```php
PageManager::setDefaultContentMode('code');
```

Por lo tanto, esta rama **NO deberia ejecutarse** porque `$modoPorDefecto === 'code'`.

**Conclusion:** Esta NO es la causa si el modo default es `code`.

---

### 3.2 Hipotesis B: "Restablecer a Default" desde Admin Bar

**Codigo (`SyncManager::resyncAllManagedPagesHtml`):**
```php
if ($modo !== 'editor') {
    update_post_meta($postId, '_glory_content_mode', 'editor');
}
```

**Problema:** Esta funcion cambia TODAS las paginas gestionadas a modo `editor` cuando se hace clic en "Restablecer a Default".

**Pregunta:** ¿Hiciste clic en "Restablecer a Default" recientemente?

---

### 3.3 Hipotesis C: Guardado desde GBN sin Darse Cuenta

**Codigo (`ConfigHandler::saveConfig`):**
```php
if ($mode === 'code') {
    update_post_meta($pageId, '_glory_content_mode', 'editor');
}
```

**Comportamiento:** Cuando GBN guarda CUALQUIER cosa (incluso al abrir el editor y cerrar sin cambios), cambia el modo a `editor`.

**Pregunta:** ¿Pudiste haber abierto GBN en alguna pagina y presionado "Guardar" aunque sea sin editar?

---

### 3.4 Hipotesis D: Sincronizacion en Modo Desarrollo

**Codigo (`SyncManager::performAutomaticSyncIfDevMode`):**
```php
if ($devMode) {
    if (is_admin()) {
        $this->runFullSync();
    } else {
        PageManager::procesarPaginasDefinidas();
    }
}
```

**Pregunta:** ¿Tienes `GLORY_DEV_MODE` activo? Esto ejecuta sincronizacion en cada carga de admin.

---

## 4. Preguntas para Aclarar

Necesito que me confirmes los siguientes puntos:

### 4.1 Sobre el Contexto del Problema

1. **¿Cuando ocurre?**
   - [ ] Al cargar el frontend de una pagina especifica
   - [ ] Al abrir GBN en una pagina
   - [ ] Despues de ciertas acciones en el admin de WordPress
   - [ ] Aleatoriamente sin patron aparente

2. **¿En que paginas ocurre?**
   - [ ] Siempre en la misma pagina (¿cual?)
   - [ ] En varias paginas diferentes
   - [ ] En TODAS las paginas gestionadas

3. **¿Que acciones realizaste antes de notar el problema?**
   - [ ] Hice clic en "Restablecer a Default" desde la barra de admin
   - [ ] Guarde algo desde GBN (aunque sea sin cambios)
   - [ ] Edite codigo PHP de alguna pagina
   - [ ] Ninguna accion especifica, aparecio solo

### 4.2 Sobre la Configuracion

4. **¿Cual es tu configuracion de modo por defecto?**
   Verificar en `App/Config/pages.php`:
   ```php
   PageManager::setDefaultContentMode('???');
   ```

5. **¿Esta activo el modo desarrollo?**
   - [ ] `GLORY_DEV_MODE = true` en `.env`
   - [ ] `WP_DEBUG = true` en `wp-config.php`

### 4.3 Sobre el Comportamiento Esperado

6. **¿Cual es tu intencion para estas paginas?**
   - [ ] Siempre renderizar desde codigo PHP (modo `code`)
   - [ ] Poder editar visualmente en GBN (modo `editor`)
   - [ ] Depende de la pagina

7. **¿Cuando esperas que una pagina cambie a modo `editor`?**
   - [ ] Solo cuando yo explicitamente guarde algo en GBN
   - [ ] Automaticamente si el default es `editor`
   - [ ] Nunca, siempre quiero modo `code`

---

## 5. Correcciones Ya Aplicadas

### 5.1 Problema 1: Imagen Destacada (CORREGIDO)

Archivo: `Glory/src/Services/Sync/PostSyncHandler.php`

**Causa:** Comparacion de assets en formatos diferentes (ruta expandida vs alias::nombre).

**Solucion:** Agregue metodo `expandAssetReference()` para normalizar ambos formatos antes de comparar.

### 5.2 Problema 2: Proteccion GBN (PARCIALMENTE CORREGIDO)

Archivos modificados:
- `Glory/src/Manager/PageManager.php`

**Cambios:**
- `_sincronizarEditorSiNoEditado()`: Ahora verifica si hay datos de GBN (`gbn_config`, `gbn_styles`) antes de sobrescribir
- `_actualizarPaginaExistente()`: Ahora protege paginas con datos GBN de migracion automatica

**Limitacion:** Esto solo protege paginas que YA tienen datos de GBN guardados. Si `gbn_config` esta vacio, el sistema sigue sincronizando.

---

## 6. Siguiente Paso Sugerido

Una vez que me aclares las preguntas de la seccion 4, puedo:

1. **Si el problema es "Restablecer a Default":**
   - Modificar `SyncManager::resyncAllManagedPagesHtml()` para respetar el modo default del sistema

2. **Si el problema es guardado accidental de GBN:**
   - Agregar confirmacion antes de cambiar modo en `ConfigHandler`
   - O: No cambiar modo a menos que haya cambios reales

3. **Si el problema es sincronizacion en dev mode:**
   - Revisar la logica de `performAutomaticSyncIfDevMode()`
   - Agregar flag para deshabilitar migracion automatica

---

**Esperando tu respuesta para continuar con la solucion correcta.**
