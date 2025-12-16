# Arquitectura SaaS - Amazon Product Plugin

## Fecha: 2025-12-15

---

## Vision General

Crear un servicio de scraping de Amazon centralizado donde:
- **Tu** controlas el servidor, el proxy y las licencias
- **Clientes** instalan un plugin liviano que se conecta a tu API
- **Stripe** maneja los pagos automaticamente

---

## Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────────────┐
│                         CLIENTES (WordPress)                         │
├──────────────────┬──────────────────┬──────────────────┬────────────┤
│   Cliente 1 WP   │   Cliente 2 WP   │   Cliente 3 WP   │    ...     │
│  (Plugin Lite)   │  (Plugin Lite)   │  (Plugin Lite)   │            │
└────────┬─────────┴────────┬─────────┴────────┬─────────┴────────────┘
         │                  │                  │
         │    Requests API (con API Key)       │
         │                  │                  │
         ▼                  ▼                  ▼
┌────────────────────────────────────────────────────────────────────┐
│                     SERVIDOR CENTRAL (VPS)                         │
│                               Glory                                │
├────────────────────────────────────────────────────────────────────┤
│                                                                    │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │
│  │  API REST    │  │  Scraper     │  │  Dashboard   │              │
│  │  Endpoints   │  │  Engine      │  │  Admin       │              │
│  └──────┬───────┘  └──────┬───────┘  └──────────────┘              │
│         │                 │                                        │
│         │                 ▼                                        │
│         │          ┌──────────────┐                                │
│         │          │    Proxy     │──────────▶ Amazon.es          │
│         │          │ (DataImpulse)│                                │
│         │          └──────────────┘                                │
│         │                                                          │
│         ▼                                                          │
│  ┌──────────────────────────────────────────────┐                  │
│  │              Base de Datos                    │                 │
│  │  - Licencias (api_keys, status, gb_used)     │                  │
│  │  - Logs de uso                               │                  │
│  │  - Cache de productos                        │                  │
│  └──────────────────────────────────────────────┘                  │
│                                                                    │
└────────────────────────────────────────────────────────────────────┘
         │
         │ Webhooks
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│                           STRIPE                                    │
│  - Suscripciones ($20/mes)                                          │
│  - 30 dias trial                                                    │
│  - Webhooks automaticos                                             │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Dos Versiones del Plugin

### 1. Plugin "Servidor" (Tu VPS)
Corre en tu servidor central. Tiene:
- Scraper real (con proxy)
- API REST para recibir peticiones de clientes
- Dashboard admin: ver usuarios, uso, licencias
- Webhook de Stripe
- Control de GB por cliente

### 2. Plugin "Cliente" (WordPress de clientes)
Se distribuye a los clientes. Tiene:
- Se conecta a TU API (no hace scraping directo)
- Necesita API Key para funcionar
- Muestra GB usados/restantes
- Link para comprar/renovar suscripcion
- Misma UI de importacion pero via tu API

---

## Modelo de Negocio

| Plan   | Precio  | GB Incluidos | Trial          |
| ------ | ------- | ------------ | -------------- |
| Basico | $20/mes | 4 GB         | 30 dias gratis |

### Costos operativos (tu lado)
- VPS: ~$5-10/mes
- Proxy (DataImpulse): ~$3.50/mes por cada 1GB
- 4 GB de proxy por cliente = ~$14/mes

### Margen
- Cliente paga: $20/mes
- Tu costo por cliente: ~$3.50/mes (proporcional del proxy)
- Margen: ~$16.50/mes por cliente

---

## Flujo Completo

### 1. Cliente nuevo quiere usar el plugin
```
1. Ve tu landing page
2. Hace clic en "Comprar" -> Stripe Checkout
3. Se suscribe ($20/mes, 30 dias gratis)
4. Stripe envia webhook a tu servidor
5. Tu servidor genera API Key unica
6. Cliente recibe email con:
   - Link para descargar plugin
   - Su API Key
   - Instrucciones de instalacion
```

### 2. Cliente instala plugin
```
1. Sube plugin a su WordPress
2. Va a Settings del plugin
3. Introduce su API Key
4. Plugin valida key contra tu servidor
5. Si es valida: Plugin se activa
6. Si no: Muestra error y link para comprar
```

### 3. Cliente usa el plugin
```
1. Cliente busca "auriculares" en su WP
2. Plugin envia request a tu API:
   POST https://tu-servidor.com/wp-json/glory/v1/search
   Headers: X-API-Key: abc123
   Body: { keyword: "auriculares", page: 1 }
3. Tu servidor:
   - Verifica API Key
   - Verifica GB disponibles
   - Hace scraping via proxy
   - Registra bytes usados
   - Devuelve resultados
4. Plugin muestra resultados al cliente
```

---

## Endpoints API (Tu Servidor)

### Autenticacion
Todas las requests llevan header: `X-API-Key: xxxx`

### Endpoints

| Metodo | Endpoint                           | Descripcion                 |
| ------ | ---------------------------------- | --------------------------- |
| POST   | `/wp-json/glory/v1/search`         | Buscar productos            |
| POST   | `/wp-json/glory/v1/product/{asin}` | Obtener producto por ASIN   |
| GET    | `/wp-json/glory/v1/license/status` | Ver estado de licencia y GB |
| POST   | `/wp-json/glory/v1/stripe-webhook` | Recibir eventos de Stripe   |

### Ejemplo de Request
```bash
curl -X POST https://tu-servidor.com/wp-json/glory/v1/search \
  -H "X-API-Key: abc123def456" \
  -H "Content-Type: application/json" \
  -d '{"keyword": "auriculares", "page": 1, "region": "es"}'
```

### Ejemplo de Response
```json
{
  "success": true,
  "data": {
    "products": [...],
    "usage": {
      "bytes_used": 450000,
      "gb_remaining": 3.95,
      "gb_limit": 4
    }
  }
}
```

---

## Dashboard Administrativo (Tu Servidor)

### Pestaña: Usuarios/Licencias
```
┌──────────────────────────────────────────────────────────────────┐
│  LICENCIAS ACTIVAS                                    [+ Nueva]  │
├──────────────────────────────────────────────────────────────────┤
│  Email          │ API Key    │ Estado  │ GB Usado │ Expira      │
├──────────────────────────────────────────────────────────────────┤
│  cliente@x.com  │ abc123...  │ Activo  │ 1.2/4 GB │ 2025-01-15  │
│  otro@y.com     │ def456...  │ Trial   │ 0.3/4 GB │ 2025-01-10  │
│  viejo@z.com    │ ghi789...  │ Expirado│ 4.0/4 GB │ 2024-12-01  │
└──────────────────────────────────────────────────────────────────┘
```

### Pestaña: Uso/Estadisticas
```
┌──────────────────────────────────────────────────────────────────┐
│  ESTADISTICAS                                                    │
├──────────────────────────────────────────────────────────────────┤
│  Total licencias activas:     5                                  │
│  Revenue este mes:           $100                                │
│  GB usados (todos):          12.5 GB                             │
│  Requests hoy:               234                                 │
│  Errores hoy:                 3                                  │
└──────────────────────────────────────────────────────────────────┘
```

### Pestaña: Logs
```
┌──────────────────────────────────────────────────────────────────┐
│  LOGS DE ACTIVIDAD                                               │
├──────────────────────────────────────────────────────────────────┤
│  14:30  │ cliente@x.com │ search │ "auriculares" │ 450 KB │ OK  │
│  14:28  │ otro@y.com    │ asin   │ B0DQKMHGJT    │ 320 KB │ OK  │
│  14:25  │ viejo@z.com   │ search │ "palas padel" │ -      │ ERR │
└──────────────────────────────────────────────────────────────────┘
```

---

## Estructura de Archivos

### Plugin Servidor (tu VPS)
```
Glory/src/Plugins/AmazonProduct/
├── Mode/
│   └── ServerMode.php              # Detecta que es servidor
├── Api/
│   ├── SearchEndpoint.php          # POST /search
│   ├── ProductEndpoint.php         # POST /product/{asin}
│   ├── LicenseEndpoint.php         # GET /license/status
│   └── StripeWebhookEndpoint.php   # POST /stripe-webhook
├── Service/
│   ├── LicenseService.php          # CRUD de licencias
│   ├── UsageTracker.php            # Conteo de GB
│   ├── ApiKeyGenerator.php         # Genera keys unicas
│   └── WebScraperProvider.php      # El scraper (ya existe)
├── Admin/
│   └── Tabs/
│       ├── LicensesTab.php         # Lista de licencias
│       ├── StatsTab.php            # Estadisticas
│       └── LogsTab.php             # Logs de uso
└── Model/
    └── License.php                 # Modelo de licencia
```

### Plugin Cliente (distribuido)
```
Glory/src/Plugins/AmazonProduct/
├── Mode/
│   └── ClientMode.php              # Detecta que es cliente
├── Service/
│   ├── ApiClient.php               # Se conecta a tu servidor
│   ├── ProductImporter.php         # Importa desde tu API
│   └── ClientSyncService.php       # Sincronizacion automatica programada
├── Admin/
│   └── Tabs/
│       ├── ImportTab.php           # UI de importacion
│       ├── ManualImportTab.php     # Import HTML (offline)
│       ├── ClientLicenseTab.php    # Configurar API Key + Estado
│       ├── ClientSettingsTab.php   # Region, Affiliate Tag, Idioma
│       └── UpdatesTab.php          # Actualizacion automatica
└── Config/
    └── ServerConfig.php            # URL de tu servidor
```

---

## Plan de Implementacion por Pasos

### Fase 1: Modo Servidor (Base) - COMPLETADO
1. [x] Crear `Mode/PluginMode.php` - Detectar modo servidor/cliente
2. [x] Crear `Model/License.php` - Estructura de licencia
3. [x] Crear `Service/LicenseService.php` - CRUD licencias
4. [x] Crear tabla custom en DB para licencias

### Fase 2: API Endpoints - COMPLETADO
1. [x] Registrar REST API routes
2. [x] Crear `Api/ApiEndpoints.php` - Search y Product
3. [x] Crear `Api/StripeWebhookHandler.php`
4. [x] Implementar autenticacion por API Key

### Fase 3: Control de Uso - COMPLETADO
1. [x] Crear `Service/UsageController.php`
2. [x] Registrar bytes por request
3. [x] Verificar limite antes de cada request
4. [x] Rate limiting (30 req/min)
5. [x] Deteccion de anomalias

### Fase 4: Dashboard Admin - COMPLETADO
1. [x] Crear `Controller/ServerAdminController.php`
2. [x] Crear `Admin/Tabs/LicensesTab.php`
3. [x] Crear `Admin/Tabs/ServerStatsTab.php`
4. [x] Crear `Admin/Tabs/ServerLogsTab.php`
5. [x] Crear `Admin/Tabs/ServerSettingsTab.php`

### Fase 5: Stripe Integration - COMPLETADO
1. [x] Crear webhook handler con verificacion de firma
2. [x] Manejar eventos de suscripcion
3. [x] Generar API Key automaticamente
4. [x] Enviar email al cliente (basico)

### Fase 6: Plugin Cliente - COMPLETADO
1. [x] Crear `Service/ApiClient.php` - Conectarse a API externa
2. [x] Crear `Admin/Tabs/ClientLicenseTab.php` - Configurar API Key
3. [x] Adaptar ImportTab para usar API externa
4. [x] Mostrar GB usados/restantes

### Fase 7: Testing y Deploy - COMPLETADO
1. [x] Deploy servidor en VPS (api.wandori.us)
2. [x] Configurar dominio y SSL
3. [x] Configurar wp-config.php con GLORY_AMAZON_MODE=server
4. [x] Verificar API REST funciona (endpoints registrados)
5. [x] Crear licencia de prueba
6. [x] Test endpoint `/license/status` - OK
7. [x] Test endpoint `/search` - OK (devuelve productos de Amazon)
8. [x] Test desde cliente local con API Key - OK (funciona con licencia de prueba)
9. [x] Probar flujo completo de importacion - OK (buscar, importar, actualizar funciona)

### Fase 8: Stripe + Proxy - COMPLETADO (2025-12-15)
1. [x] Configurar webhook en Stripe Dashboard
2. [x] Configurar constantes en wp-config.php (GLORY_STRIPE_SECRET_KEY, GLORY_STRIPE_WEBHOOK_SECRET)
3. [x] Probar webhook con Stripe CLI (stripe trigger customer.subscription.created)
4. [x] Verificar que webhook crea licencia automaticamente - OK
5. [x] Email de bienvenida enviado automaticamente - OK
6. [x] Configurar proxy (DataImpulse) - GLORY_PROXY_HOST y GLORY_PROXY_AUTH
7. [x] Probar scraping con proxy activado - OK (devuelve 20+ productos)
8. [x] Prueba con webhook de Stripe - OK (licencia creada, email enviado)
9. [x] Configurado para PRODUCCION (claves live)

---

## Configuracion del Servidor

### Archivo wp-config.php (en VPS)

Las constantes deben ir en `wp-config.php` (antes de "That's all, stop editing!"):

```php
/* Glory SaaS Mode */
define('GLORY_AMAZON_MODE', 'server');
define('GLORY_STRIPE_SECRET_KEY', 'sk_test_xxxxxxxxxxxxx');
define('GLORY_STRIPE_WEBHOOK_SECRET', 'whsec_xxxxxxxxxxxxx');

/* Proxy DataImpulse */
define('GLORY_PROXY_HOST', 'gw.dataimpulse.com:823');
define('GLORY_PROXY_AUTH', 'usuario:contraseña');
```

### Para WordPress de clientes
No necesitan wp-config especial, solo configurar la API Key desde el admin.
La URL del servidor ya esta hardcodeada: `https://api.wandori.us`

---

## URLs de la API (Produccion)

| Endpoint                                                        | Metodo | Descripcion        |
| --------------------------------------------------------------- | ------ | ------------------ |
| `https://api.wandori.us/wp-json/glory/v1/amazon/license/status` | GET    | Estado de licencia |
| `https://api.wandori.us/wp-json/glory/v1/amazon/search`         | POST   | Buscar productos   |
| `https://api.wandori.us/wp-json/glory/v1/amazon/product/{asin}` | POST   | Obtener por ASIN   |
| `https://api.wandori.us/wp-json/glory/v1/amazon/stripe-webhook` | POST   | Webhook de Stripe  |

---

## Licencias de Prueba

### Licencia Manual (creada para testing)
- **Email:** test@example.com
- **API Key:** `0345cb1aec74ef685957b92a95dbf7ffb0a95df7686f098d00bef55dc118f0f9`
- **GB Limit:** 4
- **Expira:** 2026-01-14
- **Status:** active

### Licencia via Stripe Webhook (2025-12-15)
- **Email:** test_Tby4Jf1S@stripe-test.local (temporal, cliente CLI sin email)
- **Status:** creada automaticamente via webhook
- **Nota:** En produccion, los clientes de Stripe Checkout siempre tienen email real

---

## Comandos Utiles

### Probar webhook con Stripe CLI
```powershell
# En Windows (primera vez, actualizar PATH)
$env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

# Login (una sola vez)
stripe login

# Disparar evento de prueba
stripe trigger customer.subscription.created
```

### Ver logs en VPS
```bash
tail -50 /var/www/wandori/wp-content/themes/glory/logs/glory.log
```

---


## Notas Tecnicas

### Flujo Completo de Suscripcion
```
1. Cliente ve panel "Sin licencia activa" en su WP
2. Hace clic en "Suscribirse" 
3. Redirige a Stripe Checkout
4. Introduce email y paga (o inicia trial)
5. Stripe envia webhook customer.subscription.created
6. Servidor crea licencia y genera API Key
7. Servidor envia email con API Key al cliente
8. Cliente copia API Key en su panel WP
9. Plugin se activa y puede importar productos
```

### Verificar Envio de Emails en VPS
```bash
# Ver si hay errores de mail
tail -100 /var/log/mail.log

# Probar envio manual
php -r "mail('tu@email.com', 'Test', 'Contenido');"
```

---

## Proxy DataImpulse - Configuracion Tecnica

### Tipos de Conexion

| Puerto | Tipo     | Comportamiento                                    |
| ------ | -------- | ------------------------------------------------- |
| 823    | Rotating | IP cambia automaticamente con cada nueva conexion |
| 10000+ | Sticky   | IP se mantiene por 30 mins (configurable)         |

### Parametros de Usuario

El usuario del proxy puede incluir parametros adicionales:

```
usuario__cr.XX              -> Geo-targeting (IP del pais XX)
usuario__sessid.ABC         -> Sesion especifica (misma IP mientras use ABC)
usuario__sessttl.60         -> Session TTL en minutos
usuario__cr.es;sessid.ABC   -> Combinacion de parametros
```

### Solucion para Forzar Rotacion de IP

**PROBLEMA DETECTADO (2025-12-15):**
El puerto 823 deberia rotar automaticamente, pero la configuracion del Dashboard 
de DataImpulse puede override esto con un "rotation interval" sticky.

**SOLUCION IMPLEMENTADA:**
Usar un `sessid` UNICO por cada request. DataImpulse asigna una IP diferente
a cada sessid diferente. Al generar un UUID aleatorio por request:

```php
$sessionId = bin2hex(random_bytes(8)); // Genera: a1b2c3d4e5f67890
$proxyAuth = "{$user}__cr.{$country};sessid.{$sessionId}:{$pass}";
```

Esto garantiza una IP nueva en cada request, independientemente de la 
configuracion del dashboard.

### Diagnostico Confirmado (2025-12-16)

Se creo endpoint de diagnostico para verificar IPs de salida reales:

```
GET https://api.wandori.us/wp-json/glory/v1/amazon/proxy-diagnostic?secret=glory-diag-2024
```

**Resultados:**

| Test              | IP de Salida     | Pais       | ISP                                |
| ----------------- | ---------------- | ---------- | ---------------------------------- |
| Sin proxy         | `167.86.117.147` | Francia    | Contabo GmbH (VPS)                 |
| Con proxy         | `95.56.180.69`   | Kazajistan | JSC Kazakhtelecom                  |
| Con proxy + cr.es | `176.101.23.2`   | **Espana** | Television por Cable Santa Pola SL |

**Conclusiones:**
- El proxy funciona correctamente
- El geo-targeting (cr.es) funciona
- Las IPs son residenciales REALES (no datacenter)
- La IP `67.213.121.97` que mostraba CURL era solo el gateway de DataImpulse

### Configuracion CURL Adicional

Para garantizar nuevas conexiones TCP:

```php
CURLOPT_FRESH_CONNECT => true  // Forzar nueva conexion TCP
CURLOPT_FORBID_REUSE => true   // Impedir reutilizacion
```

---

## Tareas Pendientes

### 1. Optimizacion de Peticiones - COMPLETADO (2025-12-16)
**Problema:** Cuando el usuario da clic en "actualizar" un producto, se hacia una 
nueva peticion al servidor aunque los resultados de la busqueda ya contenian 
la informacion necesaria.

**Solucion implementada:**
- [x] Evaluado: los datos de busqueda SON suficientes para importacion basica
- [x] Implementados DOS botones de importacion:
  - **Rapida**: Usa datos de busqueda directamente (sin peticion extra)
  - **Detallada**: Hace peticion extra para obtener categoria, descripcion, etc.
- [x] Archivos modificados: `ImportTab.php` (nuevo metodo `ajaxQuickImport`)

**Comparativa de datos:**

| Campo               | Busqueda (Rapida) | Pagina Producto (Detallada) |
| ------------------- | ----------------- | --------------------------- |
| asin                | ✅                 | ✅                           |
| asin_name (titulo)  | ✅                 | ✅                           |
| asin_price          | ✅                 | ✅                           |
| asin_currency       | ✅                 | ✅                           |
| image_url           | ✅ (1 imagen)      | ✅ (multiples)               |
| rating              | ✅                 | ✅                           |
| total_review        | ✅                 | ✅                           |
| asin_original_price | ❌                 | ✅                           |
| discount_percent    | ❌                 | ✅                           |
| category_path       | ❌                 | ✅ (VERIFICAR)               |
| is_prime            | ❌                 | ✅                           |
| asin_informations   | ❌                 | ✅                           |

---

## Lista de Verificacion - Importacion Detallada

**ACTUALIZADO 2025-12-16:** Verificado y funcionando correctamente.

| Dato            | ¿Se extrae? | Selector/Metodo                  | Notas                          |
| --------------- | ----------- | -------------------------------- | ------------------------------ |
| Titulo          | ✅           | `#productTitle`                  | OK                             |
| Precio actual   | ✅           | `extractPriceFromHtml()`         | OK                             |
| Precio original | ✅           | `extractOriginalPriceFromHtml()` | OK                             |
| Categoria       | ✅           | Multiples intentos (4 metodos)   | **SOLUCIONADO** - Ver detalles |
| Descripcion     | ✅           | `#feature-bullets`               | OK                             |
| Rating          | ✅           | Regex rating                     | OK                             |
| Reviews count   | ✅           | Regex reviews                    | OK                             |
| Prime           | ✅           | `.a-icon-prime`                  | OK                             |
| Imagenes        | ✅           | `#landingImage`                  | OK + descarga local con proxy  |

---

## Mejoras Implementadas (2025-12-16)

### 1. Descarga de Imagenes con Proxy
**Problema:** Las imagenes de Amazon se usaban via hotlinking (URL directa), lo que:
- Puede ser bloqueado por Amazon
- Depende de servidores externos
- No permite editar la imagen

**Solucion:**
- `ImageDownloaderService.php` ahora descarga imagenes usando el proxy configurado
- Las imagenes se guardan en la biblioteca de medios de WordPress
- Se asignan automaticamente como imagen destacada (thumbnail)
- Fallback a URL externa si la descarga falla

**Archivos modificados:**
- `Service/ImageDownloaderService.php` (nuevo metodo `downloadWithProxy()`)
- `Service/ProductImporter.php` (usa ImageDownloaderService)
- `Renderer/CardRenderer.php` (prioriza thumbnail local)

### 2. Extraccion de Categorias Mejorada
**Problema:** Las categorias no se extraian correctamente del HTML de Amazon.

**Solucion:** Implementados 4 metodos de extraccion en cascada:
1. `wayfinding-breadcrumbs_feature_div` (layout clasico)
2. `nav-subnav` con atributo `data-category`
3. Links con href `/b/` (categoria base)
4. JSON schema en el HTML

**Archivo:** `Service/WebScraperProvider.php` metodo `extractCategoryFromHtml()`

### 3. Botones de Reimportacion Mejorados
**Problema:** Despues de importar, desaparecian los botones de accion.

**Solucion:**
- Se muestran 3 botones despues de importar:
  - **Ver Producto**: Abre el editor de WordPress
  - **Reimp. Rapida**: Actualiza con datos de busqueda
  - **Reimp. Detallada**: Actualiza obteniendo datos completos
- Los productos ya importados en busqueda inicial tambien muestran estos botones
- Layout vertical con gap de 10px para mejor visibilidad

**Archivos modificados:**
- `Admin/Views/import-results-table.php`
- `assets/js/import-tab.js`

### 4. Grid de Productos con Imagen Local
**Problema:** El grid siempre mostraba la URL de Amazon.

**Solucion:** `CardRenderer::getProductMeta()` ahora:
1. Primero busca thumbnail local (`has_post_thumbnail()`)
2. Si no existe, usa `image_url` del meta
3. Ultimo fallback: `_thumbnail_url_external`

---

### 2. Flujo de Pago del Cliente - COMPLETADO (2025-12-16)
- [x] Añadir boton "Suscribirse" en el panel del cliente (ClientLicenseTab)
- [x] El boton redirige a Stripe Checkout: `https://buy.stripe.com/8x26oG58XchA56va31cAo0c`
- [x] Diseño mejorado con gradiente y hover effects
- [x] Enlace de renovacion para licencias expiradas actualizado

### 3. Verificacion de Emails - COMPLETADO (2025-12-16)
- [x] Configurar SMTP via Brevo (ex-Sendinblue)
- [x] Constantes en wp-config.php: GLORY_SMTP_HOST, GLORY_SMTP_USER, GLORY_SMTP_PASS
- [x] Servicio SmtpConfig.php creado para configurar PHPMailer
- [x] Endpoint de diagnostico: `/email-test?secret=xxx&to=email`
- [x] Probado y funcionando - emails llegan correctamente

### 4. Stripe Checkout - COMPLETADO (2025-12-16)
- [x] Crear producto en Stripe con precio recurrente ($20/mes)
- [x] Configurar periodo de prueba de 30 dias
- [x] Obtener URL de checkout: `https://buy.stripe.com/8x26oG58XchA56va31cAo0c`

### 5. Mejoras de UI Cliente - COMPLETADO (2025-12-16)
- [x] Mostrar email del cliente en panel de licencia
- [x] Arreglar bug fecha 01/01/1970 (usar expires_at_formatted)
- [x] Seccion condicional: si suscrito, mostrar gestion; si no, mostrar CTA
- [x] Boton "Contactar Soporte" con WhatsApp: +58 412 082 52 34
- [x] Label "Proximo Pago" en lugar de "Expira"

### 6. Historial de Transacciones - COMPLETADO (2025-12-16)
- [x] PostType `glory_transaction` para registrar historial
- [x] Visible en admin bajo "Productos Amazon > Transacciones"
- [x] Logs super detallados en cada evento de Stripe
- [x] Registra: nuevas suscripciones, renovaciones, cancelaciones, pagos fallidos

---

## Manejo de Renovaciones y Cancelaciones

El sistema maneja automaticamente los siguientes escenarios:

| Evento Stripe          | Accion del Sistema                               |
| ---------------------- | ------------------------------------------------ |
| `subscription.created` | Crear licencia + 4GB + enviar email              |
| `invoice.paid`         | Renovar: resetear GB a 4, extender 30 dias       |
| `subscription.deleted` | Expirar licencia (no puede usar API)             |
| `payment_failed`       | Log de alerta (Stripe reintenta automaticamente) |

**Flujo de renovacion:**
```
1. Cliente paga mes siguiente (automatico por Stripe)
2. Stripe envia webhook invoice.paid
3. Servidor resetea GB usados a 0
4. Servidor extiende expiracion 30 dias
5. Cliente sigue usando el servicio
```

**Flujo de cancelacion:**
```
1. Cliente cancela desde portal de Stripe (o pago falla definitivamente)
2. Stripe envia webhook subscription.deleted
3. Servidor marca licencia como expirada
4. Cliente ya no puede hacer requests a la API
5. Si quiere reactivar, debe suscribirse de nuevo
```

---

