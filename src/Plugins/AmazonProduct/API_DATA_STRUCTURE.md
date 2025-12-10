# Amazon API - Documentacion de Estructura de Datos

Este documento describe la estructura de datos de cada endpoint de la API de Amazon
(amazon-data.p.rapidapi.com) y como se mapean a los meta fields de WordPress.

## Endpoints Disponibles

| Endpoint   | Descripcion                   | Uso en Plugin      |
| ---------- | ----------------------------- | ------------------ |
| search.php | Buscar productos por keyword  | ImportTab          |
| deal.php   | Obtener ofertas con descuento | DealsTab           |
| asin.php   | Obtener producto por ASIN     | ProductSyncService |

---

## 1. Endpoint: search.php

**URL:** `https://amazon-data.p.rapidapi.com/search.php`

**Parametros:**
- `keyword` (string): Palabra clave de busqueda
- `region` (string): Codigo de region (us, es, uk, de, fr, it, ca, jp, au, br, mx)
- `page` (int): Numero de pagina (default: 1)

### Campos de Respuesta (por producto)

| Campo API           | Tipo   | Descripcion                   | Meta Field WP   | Se Guarda |
| ------------------- | ------ | ----------------------------- | --------------- | --------- |
| asin                | string | Amazon Standard ID            | asin            | Si        |
| asin_name           | string | Nombre del producto           | post_title      | Si        |
| asin_price          | float  | Precio actual                 | price           | Si        |
| asin_original_price | float  | Precio original (raro)        | original_price  | Si        |
| asin_list_price     | float  | Precio de lista (alternativo) | original_price  | Si        |
| asin_images         | array  | URLs de imagenes              | image_url       | Si [0]    |
| total_start         | float  | Calificacion (estrellas)      | rating          | Si        |
| total_review        | int    | Numero de reviews             | reviews         | Si        |
| is_prime            | bool   | Elegible para Prime           | prime           | Si        |
| category_path       | string | Ruta de categoria             | amazon_category | Si        |
| product_url         | string | URL del producto              | product_url     | Si        |
| asin_badge          | string | Badge (Best Seller, etc.)     | -               | No        |
| sponsored           | bool   | Es anuncio patrocinado        | -               | No        |
| variant             | array  | Variantes disponibles         | -               | No        |

### Notas importantes:
- `asin_original_price` raramente esta disponible en busquedas normales
- `asin_list_price` es una alternativa pero tambien es raro
- Para obtener precio original real, usar endpoint `deal.php`

---

## 2. Endpoint: deal.php

**URL:** `https://amazon-data.p.rapidapi.com/deal.php`

**Parametros:**
- `region` (string): Codigo de region
- `page` (int): Numero de pagina

### Campos de Respuesta (por oferta)

| Campo API            | Tipo   | Descripcion                 | Meta Field WP  | Se Guarda |
| -------------------- | ------ | --------------------------- | -------------- | --------- |
| asin                 | string | Amazon Standard ID          | asin           | Si        |
| deal_title           | string | Titulo de la oferta         | post_title     | Si        |
| deal_description     | string | Descripcion                 | post_content   | Si        |
| deal_min_price       | float  | Precio minimo actual        | price          | Si        |
| deal_min_list_price  | float  | Precio original             | original_price | Si        |
| deal_min_percent_off | int    | Porcentaje de descuento     | -              | No*       |
| deal_currency        | string | Codigo de moneda (USD, EUR) | -              | No        |
| asin_image           | string | URL de imagen               | image_url      | Si        |
| asin_rating_star     | float  | Calificacion (estrellas)    | rating         | Si        |
| asin_total_review    | int    | Numero de reviews           | reviews        | Si        |
| deal_state           | string | Estado (AVAILABLE, etc.)    | -              | No        |
| deal_type            | string | Tipo de oferta              | -              | No        |
| deal_ends_at         | string | Fecha fin de oferta         | -              | No**      |

### Notas importantes:
- *`deal_min_percent_off`: No se guarda porque se calcula dinamicamente con DiscountCalculator
- **`deal_ends_at`: Podria ser util guardar para mostrar countdown
- Este endpoint siempre incluye precio original (a diferencia de search.php)
- Productos importados desde deals se asignan automaticamente a categoria "Ofertas"

---

## 3. Endpoint: asin.php

**URL:** `https://amazon-data.p.rapidapi.com/asin.php`

**Parametros:**
- `asin` (string): ASIN del producto
- `region` (string): Codigo de region

### Campos de Respuesta

| Campo API         | Tipo   | Descripcion                 | Meta Field WP | Se Guarda |
| ----------------- | ------ | --------------------------- | ------------- | --------- |
| asin              | string | Amazon Standard ID          | asin          | Si        |
| asin_name         | string | Nombre del producto         | -             | No*       |
| price             | float  | Precio actual               | price         | Si (sync) |
| asin_price        | float  | Precio actual (alternativo) | price         | Si (sync) |
| total_start       | float  | Calificacion                | rating        | Si (sync) |
| asin_rating_star  | float  | Calificacion (alternativo)  | rating        | Si (sync) |
| total_review      | int    | Numero de reviews           | reviews       | Si (sync) |
| asin_total_review | int    | Reviews (alternativo)       | reviews       | Si (sync) |
| is_prime          | bool   | Elegible para Prime         | prime         | Si (sync) |
| asin_images       | array  | URLs de imagenes            | -             | No*       |
| description       | string | Descripcion completa        | -             | No        |
| features          | array  | Lista de caracteristicas    | -             | No        |
| specifications    | object | Especificaciones tecnicas   | -             | No        |

### Notas importantes:
- *Durante sincronizacion (ProductSyncService) solo se actualizan: price, rating, reviews, prime
- El nombre e imagen no se actualizan para preservar ediciones manuales
- Este endpoint se usa principalmente para actualizar productos existentes

---

## Meta Fields de WordPress

### Campos guardados en post_meta

| Meta Key                | Tipo   | Descripcion                           | Origen API              |
| ----------------------- | ------ | ------------------------------------- | ----------------------- |
| asin                    | string | Amazon Standard Identification Number | Todos                   |
| price                   | float  | Precio actual del producto            | Todos                   |
| original_price          | float  | Precio original (antes de descuento)  | deal.php principalmente |
| rating                  | float  | Calificacion en estrellas (0-5)       | Todos                   |
| reviews                 | int    | Numero total de reviews               | Todos                   |
| prime                   | string | "1" si es Prime, "0" si no            | Todos                   |
| image_url               | string | URL de la imagen principal            | Todos                   |
| product_url             | string | URL del producto en Amazon            | Generado                |
| _thumbnail_url_external | string | URL para thumbnail externo            | Todos                   |
| last_synced             | int    | Timestamp de ultima sincronizacion    | ProductSyncService      |

---

## Taxonomia: amazon_category

- **Slug:** amazon_category
- **Jerarquica:** Si
- **Formato:** Se crea desde `category_path` (ej: "Electronics > Computers > Laptops")
- **Categoria especial:** "Ofertas" - asignada automaticamente a productos de deal.php

---

## Campos que NO se guardan (pero podrian ser utiles)

| Campo API            | Endpoint   | Utilidad potencial                     |
| -------------------- | ---------- | -------------------------------------- |
| deal_ends_at         | deal.php   | Countdown de oferta                    |
| deal_min_percent_off | deal.php   | Mostrar badge de % descuento           |
| deal_currency        | deal.php   | Formato de moneda correcto             |
| asin_badge           | search.php | Mostrar "Best Seller", "Amazon Choice" |
| features             | asin.php   | Lista de caracteristicas del producto  |
| specifications       | asin.php   | Tabla de especificaciones              |
| variant              | search.php | Selector de variantes (color, tamanho) |

---

## Mapeo Rapido: API -> ProductImporter

### importProduct() (desde search.php)
```
asin            -> meta: asin
asin_name       -> post_title, post_content
asin_price      -> meta: price
asin_original_price / asin_list_price -> meta: original_price
total_start     -> meta: rating
total_review    -> meta: reviews
is_prime        -> meta: prime
asin_images[0]  -> meta: image_url, _thumbnail_url_external
category_path   -> taxonomy: amazon_category
(generado)      -> meta: product_url
```

### importDeal() (desde deal.php)
```
asin                -> meta: asin
deal_title          -> post_title
deal_description    -> post_content
deal_min_price      -> meta: price
deal_min_list_price -> meta: original_price
asin_rating_star    -> meta: rating
asin_total_review   -> meta: reviews
(hardcoded "1")     -> meta: prime
asin_image          -> meta: image_url
(generado)          -> meta: product_url
(auto "Ofertas")    -> taxonomy: amazon_category
```

---

## Regiones Soportadas

| Codigo | Dominio       | Pais           |
| ------ | ------------- | -------------- |
| us     | amazon.com    | Estados Unidos |
| es     | amazon.es     | Espana         |
| uk     | amazon.co.uk  | Reino Unido    |
| de     | amazon.de     | Alemania       |
| fr     | amazon.fr     | Francia        |
| it     | amazon.it     | Italia         |
| ca     | amazon.ca     | Canada         |
| jp     | amazon.co.jp  | Japon          |
| au     | amazon.com.au | Australia      |
| br     | amazon.com.br | Brasil         |
| mx     | amazon.com.mx | Mexico         |

---

*Documento generado: 10/12/2024*
*Version del plugin: AmazonProduct 2.0*
