# Amazon Product Plugin - Guia de Uso Completa

## Indice
1. [Introduccion](#introduccion)
2. [Shortcodes Disponibles](#shortcodes-disponibles)
3. [Atributos del Shortcode](#atributos-del-shortcode)
4. [Ejemplos de Uso](#ejemplos-de-uso)
5. [Importacion de Productos](#importacion-de-productos)
6. [Sistema de Categorias](#sistema-de-categorias)
7. [Sincronizacion Automatica](#sincronizacion-automatica)
8. [Consejos y Mejores Practicas](#consejos-y-mejores-practicas)

---

## Introduccion

El **Amazon Product Plugin** permite integrar productos de Amazon en tu sitio web WordPress. Los productos se almacenan localmente como un Custom Post Type (`amazon_product`) y pueden mostrarse mediante shortcodes flexibles.

### Caracteristicas principales:
- Importacion de productos desde Amazon via API o manualmente (HTML)
- Almacenamiento local de datos del producto
- Filtros interactivos para los visitantes
- Sistema de categorias jerarquicas
- Sincronizacion automatica de precios
- Soporte para ofertas y descuentos
- Deteccion automatica de productos Prime

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

**Importante:** Este shortcode NO consume llamadas a la API. Muestra productos ya importados que tienen descuento registrado.

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

## Importacion de Productos

### Metodo 1: Importacion via API (Search)
1. Ve a **Amazon Products > Settings > Import**
2. Ingresa una palabra clave de busqueda
3. Selecciona la region de Amazon
4. Click en "Buscar"
5. Selecciona los productos a importar
6. Click en "Importar Seleccionados"

**Nota:** Cada busqueda consume 1 llamada a la API.

### Metodo 2: Importacion de Ofertas (Deals)
1. Ve a **Amazon Products > Settings > Deals**
2. Selecciona la region
3. Click en "Buscar Ofertas"
4. Los productos incluyen precio original y descuento

**Ventaja:** Las ofertas siempre incluyen precio original, ideal para mostrar descuentos.

### Metodo 3: Importacion Manual (HTML)
1. Ve a **Amazon Products > Settings > Manual Import**
2. Visita un producto en Amazon.com
3. Presiona `Ctrl+U` para ver el codigo fuente
4. Copia todo el HTML (`Ctrl+A` -> `Ctrl+C`)
5. Pegalo en el area de texto del plugin
6. Click en "Procesar HTML"
7. Verifica los datos extraidos
8. Click en "Guardar Producto"

**Importacion por lotes:**
- Guarda las paginas de productos como archivos `.html`
- Arrastra multiples archivos a la zona de importacion
- Revisa la tabla de productos detectados
- Importa todos o solo los seleccionados

**Ventajas de la importacion manual:**
- No consume llamadas a la API
- Incluye todos los datos: precio original, rating, reviews, categoria
- Permite importar cualquier producto de Amazon
- Descarga automatica de imagenes al servidor local

---

## Sistema de Categorias

Los productos se organizan en la taxonomia `amazon_category`.

### Categorias automaticas
- Las categorias se crean automaticamente desde la ruta de Amazon
- Ejemplo: "Electronics > Computers > Laptops" crea 3 categorias jerarquicas

### Categoria especial: Ofertas
- Los productos importados desde "Deals" se asignan automaticamente a la categoria "Ofertas"

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
2. Selecciona la frecuencia: Diaria, Dos veces al dia, o Desactivada
3. Guarda los cambios

### Que se sincroniza
- Precio actual
- Rating
- Numero de reviews
- Estado Prime

### Que NO se sincroniza
- Titulo (para preservar ediciones manuales)
- Imagen (para mantener la imagen local)
- Categoria

### Limite de sincronizacion
- Se actualizan los productos mas antiguos primero
- Maximo 50 productos por ejecucion
- Respeta el limite mensual de llamadas API

---

## Consejos y Mejores Practicas

### 1. Optimiza las llamadas API
- Importa productos via "Deals" para obtener precio original
- Usa importacion manual para productos individuales
- Configura sincronizacion solo si es necesario

### 2. Mejora el rendimiento
- Activa la opcion de descargar imagenes localmente
- Usa `limit` para evitar cargar demasiados productos
- Usa `hide_filters="1"` en widgets pequenos

### 3. SEO y contenido
- Edita los titulos de productos para mejorar SEO
- Agrega extractos personalizados
- Organiza productos en categorias relevantes

### 4. Ofertas y descuentos
- Usa `[amazon_deals]` para paginas de ofertas
- Los descuentos se calculan automaticamente
- Mantiene los productos actualizados con sincronizacion

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

### Los productos no aparecen
1. Verifica que existan productos importados en **Amazon Products > All Products**
2. Revisa la categoria configurada en el shortcode
3. Verifica los filtros de precio/rating

### Las imagenes no cargan
1. Activa "Descargar imagenes localmente" al importar
2. Las imagenes externas pueden estar bloqueadas por Amazon

### Los precios no se actualizan
1. Verifica la configuracion de sincronizacion
2. Revisa el limite de llamadas API
3. Ejecuta sincronizacion manual desde el panel

### El descuento no aparece
- El producto necesita tener `original_price` mayor que `price`
- Importa productos desde "Deals" para asegurar precio original

---

*Documento actualizado: Diciembre 2024*
*Version del plugin: AmazonProduct 2.0*
