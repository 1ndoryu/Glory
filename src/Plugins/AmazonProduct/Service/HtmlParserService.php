<?php

namespace Glory\Plugins\AmazonProduct\Service;

/**
 * Servicio para parsear HTML de paginas de producto de Amazon
 * y extraer datos relevantes del producto.
 * 
 * Campos extraidos:
 * - asin: Identificador unico del producto
 * - title: Nombre del producto
 * - price: Precio actual
 * - original_price: Precio original (tachado)
 * - currency: Moneda (USD por defecto)
 * - image: URL de la imagen principal
 * - rating: Calificacion en estrellas
 * - reviews: Numero de reviews
 * - prime: Si es elegible para Prime
 * - category: Categoria del producto
 * - url: URL del producto en Amazon
 */
class HtmlParserService
{
    /**
     * Parsea el HTML crudo para extraer datos del producto
     * 
     * @param string $html
     * @return array
     */
    public function parseHtml(string $html): array
    {
        $price = $this->extractPrice($html);
        $originalPrice = $this->extractOriginalPrice($html);

        $data = [
            'asin' => $this->extractAsin($html),
            'title' => $this->extractTitle($html),
            'price' => $price,
            'original_price' => $originalPrice > $price ? $originalPrice : 0,
            'currency' => 'USD',
            'image' => $this->extractImage($html),
            'rating' => $this->extractRating($html),
            'reviews' => $this->extractReviews($html),
            'prime' => $this->extractPrime($html),
            'category' => $this->extractCategory($html),
            'url' => ''
        ];

        // Construir URL si tenemos ASIN
        if (!empty($data['asin'])) {
            $data['url'] = 'https://www.amazon.com/dp/' . $data['asin'];
        }

        return $data;
    }

    private function extractAsin(string $html): ?string
    {
        // Intento 1: Input hidden ASIN
        if (preg_match('/<input type="hidden" id="ASIN" value="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }
        // Intento 2: Canonical URL o similar
        if (preg_match('/\/dp\/([A-Z0-9]{10})/i', $html, $matches)) {
            return strtoupper($matches[1]);
        }
        // Intento 3: data-asin attribute
        if (preg_match('/data-asin="([A-Z0-9]{10})"/i', $html, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    private function extractTitle(string $html): string
    {
        // Intento 1: span productTitle
        if (preg_match('/<span id="productTitle"[^>]*>\s*(.*?)\s*<\/span>/s', $html, $matches)) {
            return trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        }
        // Intento 2: meta og:title
        if (preg_match('/<meta property="og:title" content="([^"]+)"/', $html, $matches)) {
            return trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        }
        // Intento 3: title tag (limpiar sufijos de Amazon)
        if (preg_match('/<title>([^<]+)<\/title>/', $html, $matches)) {
            $title = $matches[1];
            $title = preg_replace('/\s*[-:|]\s*Amazon\..*$/i', '', $title);
            return trim(html_entity_decode($title, ENT_QUOTES, 'UTF-8'));
        }

        return 'Producto sin titulo detectado';
    }

    private function extractPrice(string $html): float
    {
        // Intento 1: Price whole + fraction (estructura moderna)
        if (preg_match('/<span class="a-price-whole">([0-9,]+)/', $html, $whole)) {
            $priceWhole = str_replace(',', '', $whole[1]);
            $priceFraction = '00';
            if (preg_match('/<span class="a-price-fraction">([0-9]+)/', $html, $fraction)) {
                $priceFraction = $fraction[1];
            }
            return floatval($priceWhole . '.' . $priceFraction);
        }

        // Intento 2: Busqueda general de precio con simbolo US$99.95 o $99.95
        if (preg_match('/(?:US)?\$\s*([0-9,]+(?:\.[0-9]{2})?)/', $html, $matches)) {
            return floatval(str_replace(',', '', $matches[1]));
        }

        return 0.00;
    }

    private function extractOriginalPrice(string $html): float
    {
        // Intento 1: Precio tachado con clase a-text-strike
        if (preg_match('/<span[^>]*class="[^"]*a-text-strike[^"]*"[^>]*>\s*\$?\s*([0-9,]+(?:\.[0-9]{2})?)/s', $html, $matches)) {
            return floatval(str_replace(',', '', $matches[1]));
        }

        // Intento 2: Precio en basisPrice (precio base/lista)
        if (preg_match('/basisPrice[^>]*>\s*[^<]*<span[^>]*>\s*\$?\s*([0-9,]+(?:\.[0-9]{2})?)/s', $html, $matches)) {
            return floatval(str_replace(',', '', $matches[1]));
        }

        // Intento 3: Precio "Was:" o "Precio anterior:"
        if (preg_match('/(?:Was|Precio anterior|List Price)[:\s]*\$?\s*([0-9,]+(?:\.[0-9]{2})?)/i', $html, $matches)) {
            return floatval(str_replace(',', '', $matches[1]));
        }

        // Intento 4: Segundo precio encontrado (suele ser el original)
        if (preg_match_all('/\$\s*([0-9,]+\.[0-9]{2})/', $html, $matches)) {
            $prices = array_map(function ($p) {
                return floatval(str_replace(',', '', $p));
            }, $matches[1]);
            $prices = array_unique($prices);
            sort($prices);
            // Si hay mas de un precio, el mayor suele ser el original
            if (count($prices) > 1) {
                return end($prices);
            }
        }

        return 0.00;
    }

    private function extractImage(string $html): string
    {
        // Intento 1: data-a-dynamic-image (imagenes de alta calidad)
        if (preg_match('/data-a-dynamic-image="([^"]+)"/', $html, $matches)) {
            $json = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
            $images = json_decode($json, true);
            if ($images && is_array($images)) {
                $keys = array_keys($images);
                return $keys[0] ?? '';
            }
        }

        // Intento 2: img tag con id landingImage
        if (preg_match('/<img[^>]*id="landingImage"[^>]*src="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        // Intento 3: meta og:image
        if (preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        // Intento 4: Cualquier imagen grande de media-amazon
        if (preg_match('/https:\/\/m\.media-amazon\.com\/images\/I\/[a-zA-Z0-9+%-]+\._[^"\']+\.jpg/', $html, $matches)) {
            return $matches[0];
        }

        return '';
    }

    private function extractRating(string $html): float
    {
        // Buscar patron "4.7 de 5 estrellas" (espanol)
        if (preg_match('/([0-9]+[.,][0-9]+)\s*de\s*5\s*estrellas/i', $html, $matches)) {
            return floatval(str_replace(',', '.', $matches[1]));
        }
        // Buscar patron ingles "4.7 out of 5 stars"
        if (preg_match('/([0-9]+[.,][0-9]+)\s*out of\s*5\s*stars/i', $html, $matches)) {
            return floatval(str_replace(',', '.', $matches[1]));
        }
        // Buscar en clases css a-star-4-5 (4.5)
        if (preg_match('/class="[^"]*a-star-([0-9]+-[0-9]+)[^"]*"/', $html, $matches)) {
            $ratingString = str_replace('-', '.', $matches[1]);
            return floatval($ratingString);
        }
        // Buscar a-icon-alt con rating
        if (preg_match('/<span class="a-icon-alt">([0-9]+[.,][0-9]+)/', $html, $matches)) {
            return floatval(str_replace(',', '.', $matches[1]));
        }

        return 0.0;
    }

    private function extractReviews(string $html): int
    {
        // Buscar numero seguido de "calificaciones", "ratings", etc.
        if (preg_match('/([0-9,.]+)\s*(?:calificaciones|ratings|valoraciones|reviews|customer reviews)/i', $html, $matches)) {
            return intval(str_replace([',', '.', ' '], '', $matches[1]));
        }
        // Buscar en acrCustomerReviewText
        if (preg_match('/id="acrCustomerReviewText"[^>]*>([0-9,.]+)/', $html, $matches)) {
            return intval(str_replace([',', '.'], '', $matches[1]));
        }

        return 0;
    }

    private function extractPrime(string $html): bool
    {
        // Buscar icono o texto de Prime
        if (preg_match('/i-prime|a-icon-prime|prime-icon|FREE.*delivery|Envio GRATIS/i', $html)) {
            return true;
        }
        // Buscar data attribute de prime
        if (preg_match('/data-[^=]*prime[^=]*="true"/i', $html)) {
            return true;
        }
        // Buscar en alt text
        if (preg_match('/alt="[^"]*Prime[^"]*"/', $html)) {
            return true;
        }

        return false;
    }

    private function extractCategory(string $html): string
    {
        // Intento 1: Breadcrumb navigation
        if (preg_match_all('/<a[^>]*class="[^"]*a-link-normal[^"]*s-navigation-item[^"]*"[^>]*>([^<]+)<\/a>/i', $html, $matches)) {
            $categories = array_map('trim', $matches[1]);
            $categories = array_filter($categories, function ($cat) {
                return !empty($cat) && $cat !== 'Amazon' && strlen($cat) > 1;
            });
            if (!empty($categories)) {
                return implode(' > ', array_slice($categories, 0, 3));
            }
        }

        // Intento 2: wayfinding-breadcrumbs
        if (preg_match('/<div[^>]*id="wayfinding-breadcrumbs[^"]*"[^>]*>(.*?)<\/div>/s', $html, $container)) {
            if (preg_match_all('/<a[^>]*>([^<]+)<\/a>/', $container[1], $links)) {
                $categories = array_map('trim', $links[1]);
                $categories = array_filter($categories, function ($cat) {
                    return !empty($cat) && strlen($cat) > 1;
                });
                if (!empty($categories)) {
                    return implode(' > ', $categories);
                }
            }
        }

        // Intento 3: nav-subnav category
        if (preg_match('/data-category="([^"]+)"/', $html, $matches)) {
            return ucfirst(str_replace('-', ' ', $matches[1]));
        }

        return '';
    }
}
