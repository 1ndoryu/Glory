# Amazon Product Plugin - Guia de Uso Completa

## Indice
1. [Introduccion](#introduccion)
2. [Primeros Pasos](#primeros-pasos)
3. [Panel de Configuracion](#panel-de-configuracion)
4. [Importacion de Productos](#importacion-de-productos)
5. [Shortcodes Disponibles](#shortcodes-disponibles)
6. [Atributos del Shortcode](#atributos-del-shortcode)
7. [Ejemplos de Uso](#ejemplos-de-uso)
8. [Sistema de Categorias](#sistema-de-categorias)
9. [Sincronizacion Automatica](#sincronizacion-automatica)
10. [Consejos y Mejores Practicas](#consejos-y-mejores-practicas)
11. [Solucion de Problemas](#solucion-de-problemas)

---

## Introduccion

El **Amazon Product Plugin** es un servicio SaaS que permite importar y mostrar productos de Amazon en tu sitio web WordPress. Los productos se almacenan localmente como un Custom Post Type (`amazon_product`) y pueden mostrarse mediante shortcodes flexibles.

### Arquitectura del Servicio

El plugin funciona conectandose a un servidor central que realiza el scraping de Amazon:

```
Tu WordPress  -->  API Glory  -->  Amazon
   (cliente)       (servidor)      (scraping)
```

**Ventajas de este modelo:**
- No necesitas configurar APIs de Amazon ni proxies
- El servidor maneja todos los bloqueos y CAPTCHAs
- Solo necesitas tu API Key para empezar

### Caracteristicas principales
- Importacion de productos desde Amazon via servidor central
- Almacenamiento local de datos del producto
- Filtros interactivos para los visitantes
- Sistema de categorias jerarquicas
- Sincronizacion automatica de precios
- Soporte para ofertas y descuentos
- Deteccion automatica de productos Prime
- Descarga automatica de imagenes al servidor local

---

## Primeros Pasos

### 1. Obtener tu API Key

Para usar el plugin necesitas una suscripcion activa:

1. Ve a **Amazon Products > Settings > Licencia**
2. Si no tienes API Key, haz clic en **"Suscribirse Ahora"**
3. Completa el proceso de pago en Stripe
4. Recibiras tu API Key por email
5. Copia la API Key en el campo correspondiente
6. Haz clic en **"Guardar"**

**Planes disponibles:**

| Plan   | Precio  | Datos Incluidos | Trial          |
| ------ | ------- | --------------- | -------------- |
| Basico | $20/mes | 4 GB            | 30 dias gratis |

### 2. Configurar Region y Affiliate Tag

1. Ve a **Amazon Products > Settings > Configuracion**
2. Selecciona tu **Region de Amazon** (es, us, mx, etc.)
3. Ingresa tu **Tag de Afiliado** (ej: `mitienda-21`)
4. Guarda los cambios

**Importante:** Sin el tag de afiliado no ganaras comisiones por las ventas.

### 3. Importar tu primer producto

1. Ve a **Amazon Products > Settings > Import Products**
2. Escribe una palabra clave (ej: "auriculares bluetooth")
3. Haz clic en **"Buscar en Amazon"**
4. Selecciona los productos que quieres importar
5. Elige entre:
   - **Importar Rapido**: Usa datos de busqueda (mas rapido)
   - **Importar Detallado**: Obtiene mas datos como categoria y descripcion

---

## Panel de Configuracion

### Pestana: Licencia

Muestra el estado de tu suscripcion:
- **Estado**: Activa, Periodo de Prueba, o Expirada
- **Cuenta**: Email asociado a la suscripcion
- **Uso de Datos**: Barra de progreso de GB usados/disponibles
- **Proximo Pago**: Fecha de renovacion

Acciones disponibles:
- Ingresar o actualizar API Key
- Probar conexion con el servidor
- Contactar soporte via WhatsApp

### Pestana: Configuracion

Opciones de personalizacion:

| Opcion            | Descripcion                                        |
| ----------------- | -------------------------------------------------- |
| Region de Amazon  | Pais de Amazon donde buscar (es, us, mx, uk, etc.) |
| Tag de Afiliado   | Tu ID de Amazon Associates para comisiones         |
| Idioma del Plugin | Idioma de las etiquetas (precio, valoracion, etc.) |

### Pestana: Import Products

Interfaz para buscar e importar productos:
- Barra de busqueda por palabra clave
- Tabla de resultados con preview
- Botones de importacion rapida/detallada
- Widget de uso de datos en tiempo real

### Pestana: Manual Import

Importacion sin usar el servidor (offline):
- Copia el HTML de una pagina de producto de Amazon
- El plugin extrae automaticamente los datos
- Soporta importacion por lotes con archivos .html

### Pestana: Updates

Actualizacion masiva de productos:
- Actualizar precios de productos existentes
- Programar sincronizacion automatica
- Ver historial de actualizaciones

### Pestana: Design

Opciones de presentacion visual (en desarrollo).

### Pestana: Help

Documentacion y soporte.

---

## Importacion de Productos

### Metodo 1: Busqueda via API (Recomendado)

1. Ve a **Amazon Products > Settings > Import Products**
2. Ingresa una palabra clave de busqueda
3. Haz clic en **"Buscar en Amazon"**
4. Espera los resultados (puede tardar 10-30 segundos)
5. Para cada producto puedes:
   - **Importar Rapido**: Usa datos de la busqueda, sin peticion extra
   - **Importar Detallado**: Obtiene datos adicionales (categoria, descripcion)

**Nota:** Las busquedas consumen datos de tu cuota mensual (GB).

### Metodo 2: Importacion Manual (HTML)

Ideal para productos especificos sin gastar datos:

1. Ve a **Amazon Products > Settings > Manual Import**
2. Visita el producto en Amazon
3. Presiona `Ctrl+U` para ver el codigo fuente
4. Copia todo el HTML (`Ctrl+A` -> `Ctrl+C`)
5. Pegalo en el area de texto del plugin
6. Haz clic en **"Procesar HTML"**
7. Verifica los datos extraidos
8. Haz clic en **"Guardar Producto"**

**Importacion por lotes:**
- Guarda las paginas de productos como archivos `.html`
- Arrastra multiples archivos a la zona de importacion
- Revisa la tabla de productos detectados
- Importa todos o solo los seleccionados

**Ventajas de la importacion manual:**
- No consume datos de tu cuota
- Incluye todos los datos: precio original, rating, reviews, categoria
- Permite importar cualquier producto de Amazon
- Descarga automatica de imagenes al servidor local

### Comparativa de Metodos de Importacion

| Caracteristica      | Busqueda API | Importacion Manual |
| ------------------- | ------------ | ------------------ |
| Velocidad           | Rapido       | Mas lento          |
| Consume cuota       | Si           | No                 |
| Multiples productos | Si           | Si (por lotes)     |
| Datos completos     | Var.         | Si                 |
| Requiere HTML       | No           | Si                 |

---

## Shortcodes Disponibles

### 1. `[amazon_products]`
Muestra una cuadricula de productos con filtros interactivos.

```
[amazon_products]
```

Este shortcode incluye:
- Barra de busqueda
- Filtros de precio, rating, Prime y ofertas
- Selector de ordenamiento
- Paginacion AJAX (sin recargar pagina)
- Cuadricula responsive

### 2. `[amazon_deals]`
Muestra productos con descuento (precio original > precio actual).

```
[amazon_deals]
```

**Importante:** Este shortcode NO consume datos. Muestra productos ya importados que tienen descuento registrado.

---

## Atributos del Shortcode

### Atributos de contenido

| Atributo   | Valores       | Default | Descripcion                                          |
| ---------- | ------------- | ------- | ---------------------------------------------------- |
| `limit`    | Numero        | 12      | Cantidad maxima de productos a mostrar               |
| `ids`      | "123,456,789" | -       | Mostrar productos especificos por ID de WordPress    |
| `search`   | Texto         | -       | Filtrar por palabra clave en el titulo               |
| `category` | slug          | -       | Filtrar por categoria (usar el slug de la categoria) |

### Atributos de filtrado

| Atributo     | Valores | Default | Descripcion                                 |
| ------------ | ------- | ------- | ------------------------------------------- |
| `min_price`  | Numero  | -       | Precio minimo a mostrar                     |
| `max_price`  | Numero  | -       | Precio maximo a mostrar                     |
| `min_rating` | 1-5     | -       | Rating minimo de estrellas                  |
| `only_prime` | "1"     | "0"     | Mostrar solo productos elegibles para Prime |
| `only_deals` | "1"     | "0"     | Mostrar solo productos con descuento        |

### Atributos de ordenamiento

| Atributo  | Valores                               | Default | Descripcion                |
| --------- | ------------------------------------- | ------- | -------------------------- |
| `orderby` | date, price, rating, discount, random | date    | Criterio de ordenamiento   |
| `order`   | ASC, DESC                             | DESC    | Direccion del ordenamiento |

**Valores de orderby:**
- `date` - Por fecha de importacion
- `price` - Por precio actual
- `rating` - Por calificacion de estrellas
- `discount` - Por porcentaje de descuento
- `random` - Orden aleatorio (cambia en cada carga)

### Atributos de visualizacion

| Atributo       | Valores | Default | Descripcion                            |
| -------------- | ------- | ------- | -------------------------------------- |
| `hide_filters` | "1"     | "0"     | Ocultar el panel de filtros y buscador |
| `pagination`   | "0"     | "1"     | Desactivar paginacion                  |

---

## Ejemplos de Uso

### Ejemplo 1: Productos mejor valorados
```
[amazon_products limit="8" orderby="rating" order="DESC"]
```
Muestra los 8 productos con mayor calificacion.

### Ejemplo 2: Productos especificos
```
[amazon_products ids="123,456,789"]
```
Muestra solo los productos con IDs 123, 456 y 789 de WordPress.

### Ejemplo 3: Busqueda por palabra clave
```
[amazon_products search="auriculares" limit="6"]
```
Muestra hasta 6 productos que contengan "auriculares" en el titulo.

### Ejemplo 4: Productos aleatorios
```
[amazon_products orderby="random" limit="4"]
```
Muestra 4 productos aleatorios diferentes en cada carga de pagina.

### Ejemplo 5: Solo ofertas ordenadas por descuento
```
[amazon_products only_deals="1" orderby="discount" order="DESC"]
```
Muestra productos con descuento, ordenados del mayor al menor descuento.

### Ejemplo 6: Productos Prime de precio alto
```
[amazon_products min_price="100" only_prime="1" orderby="price" order="DESC"]
```
Productos Prime de mas de $100, ordenados por precio descendente.

### Ejemplo 7: Widget compacto (sin filtros)
```
[amazon_products hide_filters="1" pagination="0" limit="3"]
```
3 productos sin filtros ni paginacion. Ideal para sidebars o widgets.

### Ejemplo 8: Productos de una categoria
```
[amazon_products category="electronica" limit="12"]
```
12 productos de la categoria con slug "electronica".

### Ejemplo 9: Rango de precios
```
[amazon_products min_price="25" max_price="75" orderby="price" order="ASC"]
```
Productos entre $25 y $75, del mas barato al mas caro.

### Ejemplo 10: Productos con buen rating y Prime
```
[amazon_products min_rating="4" only_prime="1" limit="6"]
```
6 productos Prime con rating de 4 estrellas o mas.

---

## Sistema de Categorias

Los productos se organizan en la taxonomia `amazon_category`.

### Categorias automaticas
- Las categorias se crean automaticamente desde la ruta de Amazon
- Ejemplo: "Electronics > Computers > Laptops" crea 3 categorias jerarquicas

### Categoria especial: Ofertas
- Los productos con descuento pueden asignarse automaticamente a "Ofertas"

### Filtrar por categoria
```
[amazon_products category="electronics"]
```

Para encontrar el slug de una categoria:
1. Ve a **Amazon Products > Categorias**
2. El slug aparece en la columna correspondiente

---

## Sincronizacion Automatica

### Configurar sincronizacion
1. Ve a **Amazon Products > Settings > Updates**
2. Selecciona la frecuencia: Diaria, Semanal, o Desactivada
3. Guarda los cambios

### Que se sincroniza
- Precio actual
- Rating
- Numero de reviews
- Estado Prime
- Disponibilidad

### Que NO se sincroniza
- Titulo (para preservar ediciones manuales)
- Imagen (para mantener la imagen local)
- Categoria

### Limite de sincronizacion
- Se actualizan los productos mas antiguos primero
- La sincronizacion consume datos de tu cuota mensual

---

## Consejos y Mejores Practicas

### 1. Optimiza el uso de datos
- Usa **Importacion Manual** para productos individuales (no consume cuota)
- Usa **Importacion Rapida** cuando no necesites descripcion/categoria
- Configura sincronizacion solo si es necesario

### 2. Mejora el rendimiento
- Las imagenes se descargan localmente automaticamente
- Usa `limit` para evitar cargar demasiados productos
- Usa `hide_filters="1"` en widgets pequenos

### 3. SEO y contenido
- Edita los titulos de productos para mejorar SEO
- Agrega extractos personalizados
- Organiza productos en categorias relevantes

### 4. Ofertas y descuentos
- Usa `[amazon_deals]` para paginas de ofertas
- Los descuentos se calculan automaticamente
- El badge de descuento aparece cuando hay precio original

### 5. Widgets y sidebars
```
[amazon_products hide_filters="1" pagination="0" limit="3" orderby="random"]
```
Este shortcode es ideal para widgets: compacto, sin controles, productos variados.

### 6. Paginas de categoria
```
[amazon_products category="tu-categoria" limit="24"]
```
Crea paginas dedicadas para cada categoria de productos.

---

## Campos de Producto (Meta Fields)

Cada producto almacena los siguientes datos:

| Campo            | Descripcion                                   |
| ---------------- | --------------------------------------------- |
| `asin`           | Identificador unico de Amazon (10 caracteres) |
| `price`          | Precio actual del producto                    |
| `original_price` | Precio original (antes del descuento)         |
| `rating`         | Calificacion en estrellas (0-5)               |
| `reviews`        | Numero total de reviews                       |
| `prime`          | "1" si es elegible para Prime, "0" si no      |
| `currency`       | Codigo de moneda (USD, EUR, etc.)             |
| `image_url`      | URL de la imagen del producto                 |
| `product_url`    | URL del producto en Amazon                    |
| `last_synced`    | Timestamp de ultima sincronizacion            |

---

## Solucion de Problemas

### Error: "API Key no configurada"
1. Ve a **Amazon Products > Settings > Licencia**
2. Ingresa tu API Key
3. Haz clic en "Guardar"
4. Prueba la conexion

### Error: "Limite de GB alcanzado"
- Has agotado tu cuota mensual de datos
- Espera al proximo ciclo de facturacion
- O contacta soporte para ampliar tu plan

### Error: "API Key invalida o expirada"
- Verifica que la API Key este correcta
- Si expiro, renueva tu suscripcion

### Los productos no aparecen
1. Verifica que existan productos importados en **Amazon Products > All Products**
2. Revisa la categoria configurada en el shortcode
3. Verifica los filtros de precio/rating

### Las imagenes no cargan
1. Las imagenes se descargan automaticamente al importar
2. Verifica que tu servidor tenga permisos de escritura
3. Comprueba que la imagen existe en la biblioteca de medios

### Los precios no se actualizan
1. Verifica la configuracion de sincronizacion
2. Cada actualizacion consume datos de tu cuota
3. Ejecuta actualizacion manual desde el panel

### El descuento no aparece
- El producto necesita tener `original_price` mayor que `price`
- Usa "Importar Detallado" para obtener precio original

### La busqueda tarda mucho
- Es normal que tarde 10-30 segundos
- El servidor debe hacer scraping real de Amazon
- Si tarda mas de 2 minutos, puede haber un problema temporal

---

## Soporte

Si necesitas ayuda:
- **WhatsApp:** +58 412 082 52 34
- **Email:** Contacta via panel de licencia

---

*Documento actualizado: Diciembre 2025*
*Version: Amazon Product Plugin SaaS 3.0*
