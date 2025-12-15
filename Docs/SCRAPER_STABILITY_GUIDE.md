# Guía de Estabilidad del Scraper de Emergencia (100% Uptime Guide)

Esta guía explica cómo mantener el plugin funcionando de forma estable incluso si Amazon intenta bloquear las conexiones, asegurando el funcionamiento "100% del tiempo".

## 1. Entendiendo el Problema

Cuando usas el **Web Scraper**, tu servidor se conecta directamente a Amazon simulando ser un navegador (Chrome). Amazon tiene sistemas de seguridad para detectar estos bots.

Si haces demasiadas peticiones en poco tiempo desde la misma IP:
1.  Amazon puede mostrar un CAPTCHA (el scraper no verá productos).
2.  Amazon puede bloquear tu IP temporalmente (Error 503).

## 2. Solución Profesional: Proxies (Recomendado)

La forma de asegurar el funcionamiento continuo es **cambiar tu IP automáticamente** usando un PROXY.

### ¿Qué es un Proxy?
Es un intermediario. En lugar de:
`Tu Servidor -> Amazon` (Amazon ve tu IP y la bloquea)
Haces:
`Tu Servidor -> Proxy (IP Rotativa) -> Amazon` (Amazon ve IPs diferentes cada vez)

### Cómo Configurar un Proxy
El plugin ahora soporta proxies HTTP y SOCKS5.

1.  **Contrata un servicio de Proxies**:
    *   **Opción Premium (Mejor)**: BrightData, Oxylabs, Smartproxy. Busca "Residential Proxies".
    *   **Opción Económica**: Webshare.io, IPRoyal.
2.  **Obtén los datos**: Te darán algo como:
    *   Host: `pr.oxylabs.io`
    *   Port: `7777`
    *   User: `user123`
    *   Pass: `pass123`
3.  **Configura en el Plugin**:
    *   Ve a **Amazon Product -> Settings**.
    *   En **Scraper Proxy Settings**:
        *   **Proxy Host:Port**: `pr.oxylabs.io:7777`
        *   **Proxy Auth**: `user123:pass123`
    *   Guarda los cambios.

El scraper ahora usará el proxy. Si una IP falla, el servicio de proxy te dará otra automáticamente.

## 3. Solución Definitiva: Amazon PA-API

El scraper es una solución de "guerrilla". La solución oficial y 100% estable permitida por Amazon es la **PA-API (Product Advertising API)**.

**Requisitos:**
*   Tener una cuenta de Amazon Afiliados activa.
*   **Haber conseguido 3 ventas cualificadas** en los últimos 180 días.

**Pasos:**
1.  Consigue las 3 ventas usando los enlaces que genera este plugin (el scraper funciona perfecto para empezar).
2.  Una vez aprobada la API en tu panel de Afiliados, genera las `Access Key` y `Secret Key`.
3.  Ve a la configuración del plugin y cambia el **API Provider** a **Amazon PA-API 5.0**.
4.  Introduce tus claves.

Esta es la meta a largo plazo.

## 4. Tips para evitar bloqueos (Sin Proxy)

Si no quieres pagar un proxy todavía:
1.  **No importes masivamente**: Importa productos de 5 en 5.
2.  **Sincronización**: Configura la frecuencia de actualización (Sync Frequency) a "Weekly" o "Off" en lugar de "Daily".
3.  **Manual Import**: Si el buscador falla, usa siempre la pestaña **Manual Import** arrastrando el HTML. Esto NUNCA falla porque tú haces la navegación en tu PC.

## 5. Solución de Problemas Comunes

| Síntoma                        | Causa Probable                                    | Solución                                                                                    |
| ------------------------------ | ------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| Búsqueda devuelve 0 resultados | Bloqueo temporal de IP o cambio en HTML de Amazon | 1. Prueba en 1 hora.<br>2. Configura un Proxy.<br>3. Usa Importación Manual.                |
| Error 503/429                  | Demasiadas peticiones                             | Espera y baja la velocidad.                                                                 |
| Precios a 0                    | Amazon cambió el diseño de su web                 | Contacta al desarrollador para actualizar los selectores XPath en `WebScraperProvider.php`. |

---

**Resumen:** Para máxima estabilidad hoy mismo, usa un **Proxy Residencial**. Para estabilidad eterna gratis, consigue las 3 ventas y cámbiate a la **PA-API Oficial**.
