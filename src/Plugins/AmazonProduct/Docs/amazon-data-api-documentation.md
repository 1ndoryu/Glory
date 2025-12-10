# Documentación de Amazon Data API

Esta API permite acceder a datos de productos de Amazon, incluyendo detalles por ASIN, búsqueda por palabras clave, ofertas y conversión de UPC a ASIN.

## Autenticación

La API utiliza autenticación mediante headers HTTP. Debes incluir los siguientes headers en todas tus peticiones:

*   `x-rapidapi-host`: `amazon-data.p.rapidapi.com`
*   `x-rapidapi-key`: Tu clave de API (ej. `3e6d2a0132mshd9b3c27226e4c9ep1b7530jsn741d80b6c262`)

## Endpoints

### 1. Lookup Product by ASIN Code

Obtiene información detallada de un producto específico utilizando su código ASIN.

**Método:** `GET`
**URL:** `https://amazon-data.p.rapidapi.com/asin.php`

**Parámetros:**

| Parámetro | Tipo   | Requerido | Descripción |
| :--- | :--- | :--- | :--- |
| `asin`    | string | Sí        | El código ASIN del producto (ej. `B07FZ8S74R`). |
| `region`  | string | Sí        | La región de Amazon (ej. `us`). |

**Ejemplo de Petición (cURL):**

```bash
curl --request GET \
	--url 'https://amazon-data.p.rapidapi.com/asin.php?asin=B07FZ8S74R&region=us' \
	--header 'x-rapidapi-host: amazon-data.p.rapidapi.com' \
	--header 'x-rapidapi-key: TU_API_KEY'
```

**Ejemplo de Respuesta:**

```json
{
  "asin": "B000B5MI3Q",
  "asin_condition": "new",
  "asin_currency": "USD",
  "asin_images": [
    "https://images-na.ssl-images-amazon.com/images/I/81NnTOfWgpL.jpg",
    "https://images-na.ssl-images-amazon.com/images/I/81-UvUkjtLL.jpg"
  ],
  "asin_informations": {
    "ASIN": "B000B5MI3Q",
    "Item model number": "SKX007K",
    "Product Dimensions": "5.9 x 5.9 x 5.9 inches; 3.2 ounces",
    "Release Date": "September 30, 2014",
    "Shipping Weight": "11.2 ounces",
    "UPC": "751744007014 093179005303 722630852698"
  },
  "asin_name": "Seiko Men's Automatic Analogue Watch with Rubber Strap SKX007K",
  "asin_options": [],
  "asin_price": 374.96,
  "asin_related": [
    {
      "asin": "B08RYY1F8P",
      "asin_image": "https://images-na.ssl-images-amazon.com/images/I/510ro4z2zIL._SR200,200_.jpg",
      "asin_name": "Men's Automatic Watch...",
      "asin_price": 98.99
    }
  ],
  "brand_name": "SEIKO",
  "category_path": "Men > Wrist Watches",
  "in_stock": true,
  "is_prime": false,
  "merchant_id": "A1DGD346C4AXB3",
  "merchant_name": "CW_Watches",
  "merchant_reputation_percent": "0",
  "total_review": 1507,
  "total_start": 4.3,
  "weight": 0.84,
  "weight_unit": "pounds"
}
```

### 2. Search Products By Keywords

Busca productos en Amazon utilizando palabras clave.

**Método:** `GET`
**URL:** `https://amazon-data.p.rapidapi.com/search.php`

**Parámetros:**

| Parámetro | Tipo   | Requerido | Descripción |
| :--- | :--- | :--- | :--- |
| `keyword` | string | Sí        | Palabras clave para la búsqueda (ej. `echo dot`). |
| `region`  | string | Sí        | La región de Amazon (ej. `us`). |
| `page`    | number | No        | Número de página para los resultados (ej. `1`). |

**Ejemplo de Petición (cURL):**

```bash
curl --request GET \
	--url 'https://amazon-data.p.rapidapi.com/search.php?keyword=echo%20dot&region=us&page=1' \
	--header 'x-rapidapi-host: amazon-data.p.rapidapi.com' \
	--header 'x-rapidapi-key: TU_API_KEY'
```

**Ejemplo de Respuesta:**

```json
[
  {
    "asin": "B07FZ8S74R",
    "asin_condition": "new",
    "asin_currency": "USD",
    "asin_images": [
      "https://images-na.ssl-images-amazon.com/images/I/71OynVYSSCL.jpg"
    ],
    "asin_name": "Echo Dot (3rd Gen) - Smart speaker with Alexa - Charcoal",
    "asin_options": {
      "color": [
        {
          "option_data": {
            "asin": "B07N8RPRF7",
            "data_name": "Sandstone",
            "in_stock": true
          }
        }
      ]
    },
    "asin_price": 43.59,
    "brand_name": "Amazon",
    "category_path": "",
    "image_url": "https://images-na.ssl-images-amazon.com/images/I/71OynVYSSCL.jpg",
    "in_stock": true,
    "is_prime": true,
    "merchant_id": "ATVPDKIKX0DER",
    "merchant_name": "Amazon.com",
    "merchant_reputation_percent": "0",
    "rating": 4.7,
    "total_review": 1051193,
    "weight": 0.78,
    "weight_unit": "pounds"
  }
]
```

### 3. Get Deals and Offers

Recupera una lista de ofertas y promociones actuales de Amazon.

**Endpoint:** `GET https://amazon-data.p.rapidapi.com/deal.php`

**Parámetros:**

| Parámetro | Tipo   | Requerido | Descripción                                      |
| :-------- | :----- | :------- | :----------------------------------------------- |
| `region`  | string | Sí       | La región de donde obtener ofertas (ej. `us`).   |
| `page`    | integer| Sí       | El número de página de resultados (ej. `1`).     |

**Ejemplo de Solicitud:**

```bash
curl --request GET \
	--url 'https://amazon-data.p.rapidapi.com/deal.php?region=us&page=1' \
	--header 'x-rapidapi-host: amazon-data.p.rapidapi.com' \
	--header 'x-rapidapi-key: TU_API_KEY'
```

**Ejemplo de Respuesta:**

```json
[
  {
    "asin": "B08KYMF1JC",
    "asin_image": "https://images-na.ssl-images-amazon.com/images/I/41om2HECGwL._SR400,400_.jpg",
    "asin_rating_star": 4.6,
    "asin_total_review": 200,
    "deal_currency": "USD",
    "deal_description": "Littmann stethoscopes are on sale for limited time only...",
    "deal_id": "0a2535c3",
    "deal_max_list_price": 349,
    "deal_max_percent_off": 32,
    "deal_max_price": 311,
    "deal_min_list_price": 118,
    "deal_min_percent_off": 11,
    "deal_min_price": 73,
    "deal_ms_to_end": 2410509534,
    "deal_ms_to_start": -91489466,
    "deal_title": "Up to 20% off Littmann stethoscopes",
    "deal_type": "MULTIPLE",
    "merchant_id": "ATVPDKIKX0DER",
    "merchant_name": "Amazon.com"
  },
  {
    "asin": "B07R1WKMB3",
    "asin_image": "https://images-na.ssl-images-amazon.com/images/I/61ThS8IOTNL._SR400,400_.jpg",
    "asin_rating_star": 4.7,
    "asin_total_review": 13838,
    "deal_currency": "USD",
    "deal_description": "Gel Pens for Adult Coloring Books...",
    "deal_id": "2e095e48",
    "deal_max_list_price": 15,
    "deal_max_percent_off": 52,
    "deal_max_price": 7,
    "deal_min_list_price": 15,
    "deal_min_percent_off": 52,
    "deal_min_price": 7,
    "deal_ms_to_end": 166810534,
    "deal_ms_to_start": -437089466,
    "deal_title": "Gel Pens for Adult Coloring Books...",
    "deal_type": "SINGLE",
    "merchant_id": "A1XOGDWTW14TOQ",
    "merchant_name": "Nailuo"
  }
]
```

---

### 4. UPC To ASIN

Convierte un código UPC (Universal Product Code) a un ASIN de Amazon y recupera detalles del producto.

**Endpoint:** `GET https://amazon-data.p.rapidapi.com/upc.php`

**Parámetros:**

| Parámetro | Tipo   | Requerido | Descripción                                      |
| :-------- | :----- | :------- | :----------------------------------------------- |
| `upc`     | string | Sí       | El código UPC a convertir (ej. `841667180021`).  |

**Ejemplo de Solicitud:**

```bash
curl --request GET \
	--url 'https://amazon-data.p.rapidapi.com/upc.php?upc=841667180021' \
	--header 'x-rapidapi-host: amazon-data.p.rapidapi.com' \
	--header 'x-rapidapi-key: TU_API_KEY'
```

**Ejemplo de Respuesta:**

```json
[
  {
    "asin": "B07FZ8S74R",
    "asin_condition": "new",
    "asin_currency": "USD",
    "asin_images": [
      "https://images-na.ssl-images-amazon.com/images/I/71OynVYSSCL.jpg",
      "https://images-na.ssl-images-amazon.com/images/I/6182S7MYC2L.jpg"
    ],
    "asin_name": "Echo Dot (3rd Gen) - Smart speaker with Alexa - Charcoal",
    "asin_options": {
      "color": [
        {
          "option_data": {
            "asin": "B07N8RPRF7",
            "data_name": "Sandstone",
            "in_stock": true
          }
        }
      ],
      "configuration": [
        {
          "option_data": {
            "asin": "B07N8RPRF7",
            "data_name": "Device only",
            "in_stock": true
          }
        }
      ]
    }
  }
]
```

## Manejo de Errores

La API utiliza códigos de estado HTTP estándar para indicar el éxito o fracaso de una solicitud.

| Código de Estado | Descripción |
| :--- | :--- |
| `200` | OK - La solicitud fue exitosa. |
| `400` | Bad Request - La solicitud fue inválida o faltan parámetros. |
| `401` | Unauthorized - Clave API inválida o faltante. |
| `403` | Forbidden - No tienes permiso para acceder a este recurso. |
| `404` | Not Found - El recurso solicitado no pudo ser encontrado. |
| `429` | Too Many Requests - Límite de tasa excedido. |
| `500` | Internal Server Error - Algo salió mal en el lado del servidor. |

## Límites de Tasa

Por favor consulta tu plan de suscripción en RapidAPI para límites de tasa específicos. Típicamente, hay límites en el número de solicitudes por segundo/minuto/mes.

## Mejores Prácticas

*   **Caché:** Almacena en caché las respuestas donde sea posible para reducir el uso de la API y mejorar el rendimiento.
*   **Manejo de Errores:** Implementa un manejo de errores robusto para gestionar límites de tasa y posibles tiempos de inactividad de la API.
*   **Selección de Región:** Asegúrate de consultar la región correcta para tu audiencia objetivo para obtener precios y disponibilidad precisos.
