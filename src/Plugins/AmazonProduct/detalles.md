Especificacion Tecnica: Plugin de Integracion de Productos Amazon

1. Objetivo del Sistema 
Desarrollar una extension para WordPress que automatice la obtencion, visualizacion y actualizacion de productos de Amazon mediante API, priorizando el rendimiento web (WPO) y la flexibilidad en el ordenamiento de datos.

2. Arquitectura de Datos (Backend)

Fuente de Datos: Implementacion de un cliente API capaz de conectarse a un servicio intermediario de productos (Proxy API) con arquitectura modular para permitir una migracion futura a la Amazon Product Advertising API (PA-API) oficial sin reescribir la logica visual.

Autenticacion: Gestion de claves API (Key/Secret) y parametros de afiliacion (Tracking ID) configurables desde el panel de administracion.

Recuperacion de Datos: "Fetching" masivo de productos basado en palabras clave (keywords) o ASINs, recuperando: Titulo, Imagen, Precio Actual, Precio Original (disponible solo en endpoint de Deals), Calificacion (Estrellas), Reviews y Enlace de Afiliado.

NOTA: La API actual (amazon-data.p.rapidapi.com) no expone precio original en busquedas normales ni por ASIN. El precio original solo esta disponible en el endpoint deal.php para productos en oferta.

3. Logica de Negocio y Funcionalidades

Motor de Ordenamiento: Algoritmos para ordenar los resultados obtenidos segun criterios especificos:

- Mayor porcentaje de descuento (calculado dinamicamente entre precio original vs. precio venta).
- Mejores valoraciones (rating).
- Novedades (fecha de inclusion).
- Precio (ascendente/descendente).

Paginacion: Sistema de paginacion numerica backend-side (no scroll infinito) que divide los resultados en bloques de 9 a 12 items por pagina.

Filtros Disponibles:
- Por categoria
- Por rango de precio
- Por rating minimo
- Solo Prime
- Solo Ofertas (productos con descuento)

4. Interfaz de Usuario (Frontend)

Layout Responsivo: Sistema de rejilla (Grid) CSS con auto-fill.

Rendimiento: Generacion de HTML optimizado para SEO. JavaScript sin dependencias externas (vanilla JS, sin jQuery) para mejorar Core Web Vitals (LCP/CLS).

5. Gestion de Contenido

Shortcodes disponibles:
- [amazon_products] - Muestra productos importados con filtros
- [amazon_deals] - Muestra ofertas en tiempo real desde la API

Atributos de [amazon_products]:
- limit: Numero de productos (default: 12)
- min_price / max_price: Filtro de precio
- category: Slug de categoria
- only_prime: "1" para solo productos Prime
- only_deals: "1" para solo productos con descuento
- orderby: "date", "price", "rating", "discount"
- order: "ASC" o "DESC"

Ejemplos:
- [amazon_products limit="8" orderby="rating"]
- [amazon_products orderby="discount" only_deals="1"]
- [amazon_deals limit="12"]

6. Panel de Administracion

Pestanas disponibles:
- Import Products: Buscar e importar productos por keyword
- Import Deals: Importar ofertas con precio original y descuento
- API Settings: Configuracion de API key, region, affiliate tag
- Design: Personalizacion de colores del boton y precios
- Updates: Sincronizacion manual/automatica de precios
- Usage & Help: Documentacion de shortcodes

7. Archivos del Plugin

- AmazonProductPlugin.php: Punto de entrada, registro de CPT y hooks
- Controller/AdminController.php: Panel de administracion
- Controller/DemoController.php: Generacion de datos demo
- Service/AmazonApiService.php: Cliente de la API
- Renderer/ProductRenderer.php: Shortcodes y renderizado frontend
- assets/js/amazon-product.js: Filtros AJAX (vanilla JS)
- assets/css/amazon-product.css: Estilos del plugin