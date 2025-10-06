# Glory Database Exporter

## 📋 Descripción

El **Glory Database Exporter** es una herramienta poderosa para exportar la base de datos completa de WordPress con **reemplazo inteligente de URLs**. Resuelve el problema común de WordPress donde las URLs quedan fijas en la base de datos, facilitando la migración entre entornos (local → producción, desarrollo → staging, etc.).

---

## ✨ Características Principales

### 🔄 Reemplazo Inteligente de URLs

El sistema maneja automáticamente todos los casos comunes de URLs en WordPress:

- ✅ URLs con y sin protocolo (`https://`, `http://`, `//`)
- ✅ URLs con y sin trailing slash (`/`)
- ✅ **Datos serializados de PHP** (muy común en WordPress)
- ✅ **Datos en formato JSON**
- ✅ URLs en todos los campos de todas las tablas
- ✅ Preserva la integridad de los datos serializados

### 🛡️ Seguridad

- ✅ Verificación de nonces para todas las acciones
- ✅ Permisos de administrador requeridos
- ✅ Protección del directorio de exportación con `.htaccess`
- ✅ Limpieza automática de exportaciones antiguas (7 días)

### 📊 Panel de Administración Intuitivo

- ✅ Interfaz moderna y fácil de usar
- ✅ Validación en tiempo real de URLs
- ✅ Sugerencias inteligentes de URLs
- ✅ Lista de exportaciones previas con opciones de descarga/eliminación
- ✅ Estadísticas de exportación (tablas procesadas, filas modificadas, etc.)

---

## 🚀 Cómo Usar

### Paso 1: Acceder al Panel

1. Inicia sesión en tu WordPress como administrador
2. Ve al menú lateral izquierdo
3. Haz clic en **"Glory Export"** (icono de base de datos)

### Paso 2: Configurar la Exportación

**Campos principales:**

- **URL Actual**: Se muestra automáticamente (solo lectura)
- **URL de Destino**: Ingresa la URL donde se instalará la base de datos
  - Ejemplo local → producción: `https://localhost/misite` → `https://misite.com`
  - Ejemplo producción → staging: `https://misite.com` → `https://staging.misite.com`

**Opciones adicionales:**

- **Incluir DROP TABLE statements**: Marca esta opción si quieres que el archivo SQL incluya comandos para eliminar tablas existentes antes de crearlas (útil para sobrescribir completamente)

### Paso 3: Exportar

1. Revisa los datos ingresados
2. Haz clic en **"Exportar Base de Datos"**
3. Espera a que se complete el proceso (puede tomar varios minutos)
4. Una vez completado, verás un mensaje con estadísticas y un botón de descarga

### Paso 4: Descargar el Archivo

- Haz clic en el botón **"Descargar"** en el mensaje de éxito
- O ve a la sección **"Exportaciones Disponibles"** más abajo
- El archivo se descargará como `glory-export-YYYY-MM-DD-HHMMSS.sql`

---

## 📥 Cómo Importar en el Servidor de Destino

### Opción 1: phpMyAdmin

1. Accede a phpMyAdmin en tu servidor de destino
2. Crea una nueva base de datos vacía (ej: `misite_db`)
3. Selecciona la base de datos
4. Ve a la pestaña **"Importar"**
5. Selecciona el archivo `.sql` descargado
6. Haz clic en **"Continuar"**

### Opción 2: Línea de Comandos (MySQL)

```bash
# Crear base de datos
mysql -u usuario -p -e "CREATE DATABASE misite_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importar archivo SQL
mysql -u usuario -p misite_db < glory-export-2025-10-03-143022.sql
```

### Opción 3: Adminer

1. Accede a Adminer en tu servidor
2. Crea una nueva base de datos
3. Selecciónala
4. Ve a **"Import"**
5. Sube el archivo `.sql`

### Paso Final: Actualizar wp-config.php

Edita el archivo `wp-config.php` en el servidor de destino con los nuevos datos de conexión:

```php
define('DB_NAME', 'misite_db');           // Nombre de la nueva BD
define('DB_USER', 'usuario_bd');          // Usuario de la BD
define('DB_PASSWORD', 'contraseña_bd');   // Contraseña de la BD
define('DB_HOST', 'localhost');           // Host (usualmente localhost)
```

---

## 🏗️ Arquitectura Técnica

### Archivos Creados

```
themes/Avada/Glory/
├── src/
│   ├── Admin/
│   │   └── DatabaseExportController.php    # Controlador del panel de admin
│   └── Services/
│       ├── DatabaseExporter.php            # Servicio principal de exportación
│       └── DatabaseUrlReplacer.php         # Reemplazo inteligente de URLs
├── assets/
│   ├── css/
│   │   └── glory-export.css                # Estilos del panel
│   └── js/
│       └── admin/
│           └── glory-export.js             # JavaScript del panel
└── INSTRUCCIONES-EXPORTADOR-BD.md          # Este archivo
```

### Clases Principales

#### 1. `DatabaseUrlReplacer`

Clase especializada en reemplazo de URLs que:

- Detecta y procesa datos serializados de PHP
- Detecta y procesa datos JSON
- Genera todas las variaciones posibles de URLs (con/sin protocolo, con/sin slash)
- Reemplaza recursivamente en arrays y objetos
- Preserva la integridad de los datos

**Métodos públicos:**

```php
// Constructor
public function __construct(string $oldUrl, string $newUrl)

// Reemplazar URLs en cualquier tipo de dato
public function replace($data): mixed

// Obtener variaciones de URL (debug)
public function getUrlVariations(): array
```

#### 2. `DatabaseExporter`

Servicio principal que:

- Exporta todas las tablas de la base de datos
- Procesa por lotes para no sobrecargar la memoria
- Escapa correctamente los valores SQL
- Genera archivo SQL compatible con cualquier importador
- Incluye estadísticas detalladas

**Métodos públicos:**

```php
// Exportar base de datos
public function export(string $newUrl, bool $includeDropTables = false): array

// Obtener estadísticas
public function getStats(): array

// Limpiar exportaciones antiguas (estático)
public static function cleanOldExports(): int

// Listar exportaciones disponibles (estático)
public static function listExports(): array
```

#### 3. `DatabaseExportController`

Controlador que:

- Registra el menú en WordPress admin
- Maneja formularios y acciones
- Encola assets CSS/JS
- Renderiza la interfaz del panel

**Métodos públicos:**

```php
// Registrar hooks
public function registerHooks(): void

// Agregar página al menú
public function agregarPaginaExport(): void

// Manejar exportación
public function handleExportAction(): void

// Manejar descarga
public function handleDownloadAction(): void

// Manejar eliminación
public function handleDeleteAction(): void

// Renderizar página
public function renderizarPagina(): void
```

---

## 🔧 Personalización y Desarrollo

### Activar/Desactivar la Funcionalidad

En `Config/options.php` o en cualquier lugar antes de `Setup`:

```php
use Glory\Core\GloryFeatures;

// Desactivar
GloryFeatures::disable('databaseExporter');

// Activar (está activado por defecto)
GloryFeatures::enable('databaseExporter');
```

### Usar Programáticamente

```php
use Glory\Services\DatabaseExporter;

// Crear instancia
$exporter = new DatabaseExporter();

// Exportar con nueva URL
$result = $exporter->export('https://nueva-url.com', false);

if ($result['success']) {
    echo "Exportación completada!";
    echo "Archivo: " . $result['file'];
    echo "Tablas procesadas: " . $result['stats']['tables_processed'];
    echo "Filas modificadas: " . $result['stats']['rows_modified'];
} else {
    echo "Error: " . $result['message'];
}
```

### Reemplazo de URLs Sin Exportar

Si solo necesitas reemplazar URLs en memoria:

```php
use Glory\Services\DatabaseUrlReplacer;

$replacer = new DatabaseUrlReplacer(
    'https://localhost/misite',
    'https://misite.com'
);

// Reemplazar en un string
$texto = 'Visita https://localhost/misite/pagina';
$resultado = $replacer->replace($texto);
// Resultado: 'Visita https://misite.com/pagina'

// Reemplazar en datos serializados
$serializado = 'a:2:{s:3:"url";s:30:"https://localhost/misite/home";s:5:"name";s:4:"Home";}';
$resultado = $replacer->replace($serializado);
// Las URLs dentro del serializado serán reemplazadas correctamente

// Reemplazar en arrays/objetos complejos
$datos = [
    'sitio' => 'https://localhost/misite',
    'meta' => serialize(['url' => 'https://localhost/misite/page']),
    'config' => '{"api":"https://localhost/misite/api"}'
];
$resultado = $replacer->replace($datos);
// Todos los niveles y formatos serán procesados
```

---

## 🐛 Troubleshooting

### El archivo SQL no se descarga

**Solución**: Verifica que el directorio `wp-content/uploads/glory-exports/` tenga permisos de escritura (755 o 775).

```bash
chmod 755 wp-content/uploads/glory-exports/
```

### Error al importar: "Unknown collation"

**Solución**: Edita el archivo SQL y cambia las líneas de collation:

```sql
-- Busca y reemplaza:
utf8mb4_unicode_520_ci → utf8mb4_unicode_ci
```

### La importación es muy lenta

**Solución**: Para bases de datos grandes (>100MB), usa línea de comandos en lugar de phpMyAdmin:

```bash
mysql -u usuario -p misite_db < archivo.sql
```

### Algunas URLs no se reemplazaron

**Problema**: Muy raro, pero puede ocurrir con formatos de serialización corruptos.

**Solución**: Ejecuta el script de búsqueda y reemplazo de WordPress después de importar:

```bash
wp search-replace 'https://url-vieja.com' 'https://url-nueva.com' --all-tables
```

### Error de memoria durante la exportación

**Solución**: Aumenta el límite de memoria en `wp-config.php`:

```php
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

---

## 📝 Casos de Uso Comunes

### 1. Migrar de Local a Producción

```
Local:      http://localhost/misite
Producción: https://misite.com

1. Exportar con URL de destino: https://misite.com
2. Subir archivos de WordPress a producción vía FTP/SSH
3. Crear base de datos en producción
4. Importar el SQL generado
5. Actualizar wp-config.php
6. ¡Listo!
```

### 2. Crear Sitio de Staging

```
Producción: https://misite.com
Staging:    https://staging.misite.com

1. Exportar con URL de destino: https://staging.misite.com
2. Copiar archivos a subdirectorio staging
3. Crear nueva base de datos staging
4. Importar el SQL
5. Configurar wp-config.php para staging
```

### 3. Cambiar de Dominio

```
Viejo: https://misite-viejo.com
Nuevo: https://misite-nuevo.com

1. Exportar con URL de destino: https://misite-nuevo.com
2. Mantener archivos en el mismo servidor
3. Importar SQL en la misma BD (sobrescribir)
4. Actualizar DNS
5. ¡El sitio funciona con el nuevo dominio!
```

### 4. Migrar de HTTP a HTTPS

```
Actual: http://misite.com
Nueva:  https://misite.com

1. Instalar certificado SSL
2. Exportar con URL de destino: https://misite.com
3. Importar SQL (sobrescribir)
4. Configurar redirecciones 301 en .htaccess
```

---

## 🎯 Ventajas vs Otras Soluciones

| Característica | Glory Exporter | WP Search-Replace | Duplicator | phpMyAdmin Export |
|----------------|----------------|-------------------|------------|-------------------|
| Reemplazo de URLs | ✅ Automático | ⚠️ Manual | ✅ Automático | ❌ No |
| Datos serializados | ✅ Correcto | ✅ Correcto | ✅ Correcto | ❌ Rompe datos |
| Interfaz integrada | ✅ Admin WP | ⚠️ CLI | ✅ Admin WP | ⚠️ phpMyAdmin |
| Exportar archivos | ❌ Solo BD | ❌ Solo BD | ✅ BD + archivos | ❌ Solo BD |
| Tamaño límite | ⚠️ PHP limit | ✅ Ilimitado | ⚠️ Límites hosting | ⚠️ PHP limit |
| Instalación | ✅ Incluido | ⚠️ WP-CLI | ⚠️ Plugin externo | ✅ Incluido |
| Gratis | ✅ | ✅ | ⚠️ Pro para todo | ✅ |

---

## 📞 Soporte y Contribuciones

- **Logs**: Los errores se registran automáticamente en el sistema `GloryLogger`
- **Debug**: Activa `WP_DEBUG` para ver logs detallados
- **Reportar bugs**: Contacta al equipo de desarrollo con los logs

---

## 🔄 Changelog

### v1.0.0 (2025-10-03)
- ✨ Versión inicial
- ✅ Reemplazo inteligente de URLs (serialized, JSON, etc.)
- ✅ Panel de administración completo
- ✅ Gestión de exportaciones (listar, descargar, eliminar)
- ✅ Limpieza automática de archivos antiguos
- ✅ Validación y seguridad completa
- ✅ Interfaz responsive y moderna

---

## 📄 Licencia

Este módulo es parte del sistema Glory y está sujeto a la misma licencia del tema Avada.

---

**¡Disfruta de migraciones sin dolor con Glory Database Exporter! 🚀**

