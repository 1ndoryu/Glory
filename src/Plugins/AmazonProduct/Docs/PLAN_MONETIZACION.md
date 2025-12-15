# Plan de Monetizacion - Amazon Product Plugin

## Fecha: 2025-12-15

---

## Contexto

El cliente pago **$20 USD** por RapidAPI que no funciono. Como compensacion, le ofreceremos **30 dias gratis** del plugin.

El plugin se convertira en un producto de pago con el siguiente modelo:
- **Trial:** 30 dias gratis
- **Precio:** $20 USD/mes despues del trial
- **Plataforma de pagos:** Stripe

---

## Datos de Stripe

### Link de Suscripcion
```
https://buy.stripe.com/8x26oG58XchA56va31cAo0c
```
Este link ya tiene configurado:
- 30 dias gratis (trial)
- $20/mes despues

### API Keys (Test Mode)
> **IMPORTANTE**: Las API Keys NO deben guardarse en el repositorio.
> Configurar en `wp-config.php`:
> ```php
> define('GLORY_STRIPE_PUBLISHABLE_KEY', 'pk_test_XXXXXXXXX');
> define('GLORY_STRIPE_SECRET_KEY', 'sk_test_XXXXXXXXX');
> define('GLORY_STRIPE_WEBHOOK_SECRET', 'whsec_XXXXXXXXX');
> ```

### Documentacion
- API General: https://docs.stripe.com/api
- Webhooks: https://docs.stripe.com/webhooks
- Customer Portal: https://docs.stripe.com/customer-management

---

## Arquitectura de la Integracion

```
[Cliente]
    |
    v
[Stripe Checkout] --> Link de compra ($20/mes, 30 dias trial)
    |
    v
[Stripe] --> Procesa pago, crea Subscription
    |
    v
[Webhook] --> Envia evento a WordPress
    |
    v
[WordPress] --> Guarda estado de licencia en options
    |
    v
[Plugin] --> Verifica licencia antes de funcionar
```

---

## Flujo del Usuario

### 1. Primera vez (sin licencia)
1. Usuario instala plugin
2. Ve pantalla de "Activar Licencia"
3. Hace clic en link de Stripe Checkout
4. Completa suscripcion (30 dias gratis)
5. Stripe envia webhook a WordPress
6. Plugin se activa automaticamente

### 2. Usuario con licencia activa
1. Plugin funciona normalmente
2. Puede ver estado de suscripcion en Settings
3. Link para gestionar suscripcion (Stripe Customer Portal)

### 3. Licencia expirada/cancelada
1. Plugin muestra mensaje de renovar
2. Funcionalidad bloqueada hasta renovar
3. Los productos existentes siguen visibles (no se borran)

---

## Componentes a Desarrollar

### Fase 1: Estructura Base
- [ ] Crear `Service/LicenseService.php` - Gestiona estado de licencia
- [ ] Crear `Service/StripeWebhookHandler.php` - Recibe webhooks
- [ ] Crear tabla o options para guardar licencia
- [ ] Crear endpoint para webhook (`/wp-json/glory/v1/stripe-webhook`)

### Fase 2: UI de Activacion
- [ ] Crear `Admin/Tabs/LicenseTab.php` - Pantalla de licencia
- [ ] Mostrar estado actual (activa/trial/expirada)
- [ ] Boton para comprar/renovar
- [ ] Link a Customer Portal de Stripe

### Fase 3: Bloqueo de Funcionalidad
- [ ] Modificar `AdminController.php` - Verificar licencia
- [ ] Si no hay licencia, mostrar solo LicenseTab
- [ ] Bloquear import/update si no hay licencia

### Fase 4: Webhook de Stripe
- [ ] Registrar endpoint REST API
- [ ] Verificar firma del webhook (seguridad)
- [ ] Manejar eventos:
  - `customer.subscription.created` - Activar licencia
  - `customer.subscription.updated` - Actualizar estado
  - `customer.subscription.deleted` - Desactivar licencia
  - `invoice.paid` - Renovacion exitosa
  - `invoice.payment_failed` - Pago fallido

---

## Datos a Guardar en WordPress

### Options (wp_options)
```php
// Estado de licencia
'glory_license_status' => 'active' | 'trial' | 'expired' | 'none'

// ID de suscripcion de Stripe
'glory_stripe_subscription_id' => 'sub_xxxxx'

// ID de cliente de Stripe
'glory_stripe_customer_id' => 'cus_xxxxx'

// Email del cliente
'glory_license_email' => 'cliente@email.com'

// Fecha de expiracion
'glory_license_expires' => '2025-01-15' // timestamp

// Fecha de inicio del trial
'glory_license_trial_start' => '2024-12-15'
```

---

## Configuracion de Webhook en Stripe

### URL del Webhook
```
https://TU-SITIO.com/wp-json/glory/v1/stripe-webhook
```

### Eventos a Escuchar
1. `customer.subscription.created`
2. `customer.subscription.updated`
3. `customer.subscription.deleted`
4. `invoice.paid`
5. `invoice.payment_failed`

### Webhook Secret
Stripe generara un `whsec_xxxxx` que usaremos para verificar firmas.

---

## Estructura de Archivos

```
Glory/src/Plugins/AmazonProduct/
├── Service/
│   ├── LicenseService.php          # Logica de licencia
│   └── StripeWebhookHandler.php    # Procesa webhooks
├── Admin/
│   └── Tabs/
│       └── LicenseTab.php          # UI de licencia
├── Controller/
│   └── StripeWebhookController.php # Endpoint REST
└── Config/
    └── StripeConfig.php            # Keys y configuracion
```

---

## Seguridad

### Verificacion de Webhook
```php
// Stripe envia header: Stripe-Signature
// Debemos verificar con webhook secret
$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
```

### Proteccion de API Keys
- Nunca exponer Secret Key en frontend
- Guardar keys en wp_options con encryption
- Usar solo Publishable Key en JavaScript

---

## Plan de Implementacion por Pasos

### Paso 1: Crear LicenseService
- Funciones: `isActive()`, `getStatus()`, `activate()`, `deactivate()`
- Guardar/leer de wp_options

### Paso 2: Crear LicenseTab
- Mostrar estado actual
- Boton de compra (link a Stripe)
- Info de suscripcion si existe

### Paso 3: Integrar verificacion en AdminController
- Si no hay licencia activa, solo mostrar LicenseTab
- Mensaje claro de que hacer

### Paso 4: Crear Webhook Endpoint
- Registrar ruta REST API
- Recibir y procesar eventos
- Actualizar estado de licencia

### Paso 5: Configurar Webhook en Stripe Dashboard
- Crear webhook apuntando a tu URL
- Seleccionar eventos necesarios
- Obtener webhook secret

### Paso 6: Testing
- Probar con tarjetas de prueba de Stripe
- Verificar activacion/desactivacion
- Probar renovacion y cancelacion

---

## Tarjetas de Prueba de Stripe

| Numero              | Resultado          |
| ------------------- | ------------------ |
| 4242 4242 4242 4242 | Pago exitoso       |
| 4000 0000 0000 0002 | Tarjeta rechazada  |
| 4000 0000 0000 3220 | Requiere 3D Secure |

---

## Estado del Proyecto

| Fase | Tarea                                    | Estado    |
| ---- | ---------------------------------------- | --------- |
| 1    | Crear LicenseService.php                 | PENDIENTE |
| 1    | Crear estructura de options              | PENDIENTE |
| 2    | Crear LicenseTab.php                     | PENDIENTE |
| 3    | Integrar verificacion en AdminController | PENDIENTE |
| 4    | Crear StripeWebhookController.php        | PENDIENTE |
| 4    | Crear StripeWebhookHandler.php           | PENDIENTE |
| 5    | Configurar webhook en Stripe             | PENDIENTE |
| 6    | Testing completo                         | PENDIENTE |

---

## Notas Importantes

### Para el cliente actual
- Ya pago $20 por RapidAPI que no funciono
- Le daremos el link de suscripcion con 30 dias gratis
- Efectivamente tiene 1 mes gratis como compensacion

### Modelo de negocio
- $20/mes es competitivo (AAWP cuesta mas)
- Incluye: scraper, proxy integrado, actualizaciones
- El proxy ($3.50/mes) lo pagas tu como costo operativo

### Escalabilidad
- Cada cliente tiene su propia suscripcion de Stripe
- Puedes tener multiples clientes
- Sistema automatico, no requiere intervencion manual

