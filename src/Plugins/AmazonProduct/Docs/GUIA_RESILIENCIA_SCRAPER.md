# Guia de Resiliencia del Web Scraper de Amazon

## Fecha: 2025-12-15

---

## Que es un Web Scraper y Por Que Puede Fallar

Un **Web Scraper** es un programa que visita paginas web y extrae informacion del HTML, simulando ser un navegador real. Es como si un robot visitara Amazon y copiara los precios que ve.

### Por que Amazon podria bloquear el scraper

1. **Demasiadas solicitudes** - Si hacemos muchas peticiones en poco tiempo, Amazon detecta comportamiento no humano
2. **Misma IP siempre** - Amazon ve que todas las peticiones vienen del mismo lugar
3. **User-Agent sospechoso** - Si el "navegador simulado" parece falso
4. **Patrones de acceso** - Acceso a muchas paginas en orden predecible

---

## Que es un Proxy y Para Que Sirve

### Explicacion Simple
Un **proxy** es como un intermediario. En lugar de que tu servidor hable directamente con Amazon, le dice al proxy "oye, visita esta pagina por mi y dame lo que ves".

```
SIN PROXY:
Tu Servidor --> Amazon
(Amazon ve TU IP)

CON PROXY:
Tu Servidor --> Proxy --> Amazon
(Amazon ve la IP del PROXY, no la tuya)
```

### Beneficios de usar Proxy

1. **Evita bloqueos de IP** - Si Amazon bloquea una IP, solo bloquea al proxy, no a ti
2. **Rotacion de IPs** - Los proxies residenciales rotan entre miles de IPs
3. **Geo-localizacion** - Puedes simular que estas en Espana para ver precios en euros

### Tipos de Proxies

| Tipo                        | Precio       | Confiabilidad                     | Recomendado  |
| --------------------------- | ------------ | --------------------------------- | ------------ |
| **Proxies Gratuitos**       | $0           | MUY BAJA (se caen constantemente) | NO           |
| **Datacenter**              | ~$5-20/mes   | Media                             | Para pruebas |
| **Residenciales**           | ~$10-50/mes  | ALTA                              | **SI**       |
| **Residenciales Rotativos** | ~$20-100/mes | MUY ALTA                          | **IDEAL**    |

### Proveedores Recomendados de Proxy

1. **Bright Data** (antes Luminati) - El mas profesional, desde $10/GB
2. **Smartproxy** - Buena relacion calidad/precio, desde $7/GB
3. **Oxylabs** - Muy confiable, desde $8/GB
4. **Webshare** - Economico, desde $5/mes plan basico

**Nota:** Para una tienda de afiliados con actualizaciones moderadas (50-200 productos), un plan basico de $10-20/mes deberia ser suficiente.

---

## Estrategias Implementadas en el Scraper Actual

### 1. Rotacion de User-Agents
El scraper ya tiene multiples User-Agents que simula diferentes navegadores:
- Chrome en Windows
- Chrome en Mac
- Firefox en Windows

### 2. Headers Realistas
Las peticiones incluyen headers que imitan un navegador real:
- Accept-Language
- Referer (simula venir de Google)
- Cache-Control

### 3. Cache de Resultados
Los resultados se guardan 1 hora para no repetir peticiones innecesarias.

### 4. Soporte para Proxy (YA IMPLEMENTADO)
En la configuracion ya existe opcion para configurar proxy.

---

## Mejoras Adicionales Recomendadas

### A Implementar Ahora

1. **Delays aleatorios entre peticiones** - No hacer requests muy rapido
2. **Retry con backoff exponencial** - Si falla, esperar mas antes de reintentar
3. **Multiples estrategias de parsing** - Si un selector falla, probar otro
4. **Logging detallado** - Para diagnosticar problemas

### A Considerar en el Futuro

1. **Pool de proxies** - Rotar entre varios proxies
2. **Deteccion de CAPTCHA** - Alertar cuando Amazon pida verificacion
3. **Scraper alternativo con Puppeteer** - Para casos dificiles (requiere mas recursos)
4. **API alternativa como fallback** - Si encontramos una que funcione

---

## Limites Recomendados de Uso

### Para Evitar Bloqueos

| Accion                      | Limite Recomendado      |
| --------------------------- | ----------------------- |
| Busquedas por hora          | Maximo 30               |
| Importar productos por hora | Maximo 50               |
| Actualizacion masiva        | 10 productos por minuto |
| Delay entre requests        | 2-5 segundos            |

### Configuracion de Sincronizacion Automatica

Para la actualizacion automatica de precios, se recomienda:
- **Frecuencia:** Diaria (no mas frecuente)
- **Horario:** Madrugada (menos trafico en Amazon)
- **Batch size:** 10 productos por lote
- **Delay entre lotes:** 30 segundos

---

## Que Hacer Si Amazon Bloquea el Scraper

### Sintomas de Bloqueo
1. Respuestas vacias o con error
2. Redireccion a pagina de CAPTCHA
3. Codigo HTTP 503 o 429

### Soluciones Inmediatas
1. **Esperar 24-48 horas** - Los bloqueos temporales se levantan solos
2. **Configurar un proxy** - Cambiar de IP
3. **Reducir frecuencia** - Hacer menos peticiones

### Solucion a Largo Plazo
1. Contratar un servicio de proxy residencial rotativo
2. Considerar API alternativa si aparece una funcional

---

## Como Configurar un Proxy en el Sistema

### Paso 1: Contratar Servicio de Proxy
Recomiendo Smartproxy o Webshare para empezar.

### Paso 2: Obtener Credenciales
El proveedor te dara:
- **Host:** ej. `gate.smartproxy.com`
- **Puerto:** ej. `7000`
- **Usuario:** ej. `sp12345`
- **Contrasena:** ej. `abc123xyz`

### Paso 3: Configurar en WordPress
1. Ir a **Productos Amazon** -> **API Settings**
2. En "Proxy Host:Port" poner: `gate.smartproxy.com:7000`
3. En "Proxy Auth" poner: `sp12345:abc123xyz`
4. Guardar

---

## Estado Actual del Scraper

### Caracteristicas Implementadas
- [x] Extraccion de titulo
- [x] Extraccion de precio actual (mejorado para ofertas)
- [x] Extraccion de precio original (para descuentos)
- [x] Calculo de porcentaje de descuento
- [x] Extraccion de imagen
- [x] Extraccion de rating
- [x] Extraccion de numero de reviews
- [x] Deteccion de Prime
- [x] Extraccion de categoria
- [x] Cache de resultados
- [x] Soporte para proxy
- [x] User-Agents rotativos

### Por Implementar
- [ ] Delays aleatorios entre requests
- [ ] Retry con backoff exponencial
- [ ] Mejor manejo de errores
- [ ] Deteccion de bloqueo/CAPTCHA

---

## Resumen Ejecutivo para el Cliente

**Situacion:** El scraper funciona sin costo adicional pero tiene limitaciones.

**Que puede pasar:**
- Amazon podria bloquear temporalmente (24-48h) si se hacen muchas peticiones

**Como prevenirlo:**
1. No hacer mas de 30-50 importaciones por hora
2. Usar sincronizacion diaria, no cada hora
3. Si hay problemas, considerar contratar proxy (~$10-20/mes)

**Beneficios vs RapidAPI:**
- No hay costo mensual obligatorio
- No dependemos de terceros que pueden fallar
- Control total sobre el sistema

**Riesgos:**
- Puede requerir mantenimiento si Amazon cambia su HTML
- Posibles bloqueos temporales con uso intensivo

