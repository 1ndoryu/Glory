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

### Fase 7: Testing y Deploy - PENDIENTE
1. [ ] Testing en local
2. [ ] Deploy servidor en VPS
3. [ ] Configurar dominio y SSL
4. [ ] Crear webhook en Stripe Dashboard
5. [ ] Prueba con cliente real

---

## Configuracion del Servidor

### Variable de Entorno (wp-config.php)
```php
// En tu servidor central
define('GLORY_AMAZON_MODE', 'server');
define('GLORY_STRIPE_SECRET_KEY', 'sk_live_xxx');
define('GLORY_STRIPE_WEBHOOK_SECRET', 'whsec_xxx');

// En los WordPress de clientes
define('GLORY_AMAZON_MODE', 'client');
define('GLORY_API_SERVER', 'https://api.tuservicio.com');
```

---

