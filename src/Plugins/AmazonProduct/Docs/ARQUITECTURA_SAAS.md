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
│   └── ProductImporter.php         # Importa desde tu API
├── Admin/
│   └── Tabs/
│       ├── ImportTab.php           # UI de importacion
│       ├── ManualImportTab.php     # Import HTML (offline)
│       └── LicenseTab.php          # Configurar API Key
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
8. [ ] Test desde cliente local con API Key
9. [ ] Probar flujo completo de importacion

### Fase 8: Stripe + Proxy - COMPLETADO (2025-12-15)
1. [x] Configurar webhook en Stripe Dashboard
2. [x] Configurar constantes en wp-config.php (GLORY_STRIPE_SECRET_KEY, GLORY_STRIPE_WEBHOOK_SECRET)
3. [x] Probar webhook con Stripe CLI (stripe trigger customer.subscription.created)
4. [x] Verificar que webhook crea licencia automaticamente - OK
5. [x] Email de bienvenida enviado automaticamente - OK
6. [x] Configurar proxy (DataImpulse) - GLORY_PROXY_HOST y GLORY_PROXY_AUTH
7. [x] Probar scraping con proxy activado - OK (devuelve 20+ productos)
8. [ ] Prueba con cliente real de pago (Stripe Checkout)

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

## Tareas Pendientes (Proxima Sesion)

### 1. Flujo de Pago del Cliente
- [ ] Añadir boton "Suscribirse" en el panel del cliente (ClientLicenseTab)
- [ ] El boton debe redirigir a Stripe Checkout
- [ ] El cliente introduce su email en Stripe Checkout al pagar

### 2. Identificacion del WordPress del Cliente
**Pregunta:** ¿Como identificamos el WordPress del cliente cuando paga?

**Solucion propuesta:**
- El cliente paga con su email en Stripe
- Stripe envia webhook con el email
- El servidor genera la API Key y la envia por email
- El cliente copia la API Key en su panel de WordPress
- No necesitamos identificar su WordPress directamente

### 3. Verificacion de Emails
- [ ] Configurar SMTP en el servidor VPS para enviar emails reales
- [ ] Verificar que `wp_mail()` funciona correctamente
- [ ] Probar envio de email de bienvenida
- [ ] Alternativa: usar servicio como SendGrid, Mailgun o Amazon SES

### 4. Limpieza de Codigo
- [ ] Remover logs de diagnostico de `ApiEndpoints.php`
- [ ] Remover logs de diagnostico de `StripeWebhookHandler.php`
- [ ] Los logs actuales son utiles para debugging pero no para produccion

### 5. Stripe Checkout
- [ ] Crear producto en Stripe con precio recurrente ($20/mes)
- [ ] Configurar periodo de prueba de 30 dias
- [ ] Obtener URL de checkout o crear Payment Link
- [ ] Añadir URL al boton del cliente

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

### Configuracion CURL Adicional

Para garantizar nuevas conexiones TCP:

```php
// Forzar nueva conexion TCP (evita reusar conexion existente)
CURLOPT_FRESH_CONNECT => true

// Impedir reutilizacion de la conexion despues del request
CURLOPT_FORBID_REUSE => true
```

### Verificacion de Rotacion

Para verificar que las IPs estan rotando, revisar los logs:

```bash
# En VPS
tail -100 /var/www/wandori/wp-content/themes/glory/logs/glory.log | grep "IP:"
```

Cada request deberia mostrar una IP diferente (campo `CURLINFO_PRIMARY_IP`).

### Configuracion del Dashboard (Opcional)

Si prefieres no usar sessid dinamico, puedes configurar en el Dashboard:
1. Ir a [app.dataimpulse.com](https://app.dataimpulse.com)
2. Seccion "Proxy Configuration"
3. Establecer "Rotation Interval" en **0** o "After every request"

---
