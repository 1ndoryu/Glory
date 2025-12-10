<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

/**
 * Help Tab - Usage documentation and shortcode reference.
 */
class HelpTab implements TabInterface
{
    public function getSlug(): string
    {
        return 'help';
    }

    public function getLabel(): string
    {
        return 'Usage & Help';
    }

    public function render(): void
    {
?>
        <h3>Como usar el plugin</h3>
        <p>Usa el shortcode <code>[amazon_products]</code> para mostrar productos en cualquier pagina.</p>

        <h4>Atributos disponibles:</h4>
        <table class="wp-list-table widefat fixed striped" style="max-width: 800px;">
            <thead>
                <tr>
                    <th style="width: 150px;">Atributo</th>
                    <th style="width: 120px;">Valor</th>
                    <th>Descripcion</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>limit</code></td>
                    <td>Numero (12)</td>
                    <td>Cantidad de productos a mostrar.</td>
                </tr>
                <tr>
                    <td><code>ids</code></td>
                    <td>"123,456,789"</td>
                    <td>Mostrar productos especificos por ID de WordPress, separados por coma.</td>
                </tr>
                <tr>
                    <td><code>search</code></td>
                    <td>"palabra"</td>
                    <td>Filtrar productos guardados que contengan la palabra en el titulo.</td>
                </tr>
                <tr>
                    <td><code>category</code></td>
                    <td>slug</td>
                    <td>Filtrar por categoria (usar el slug).</td>
                </tr>
                <tr>
                    <td><code>min_price</code></td>
                    <td>Numero</td>
                    <td>Precio minimo.</td>
                </tr>
                <tr>
                    <td><code>max_price</code></td>
                    <td>Numero</td>
                    <td>Precio maximo.</td>
                </tr>
                <tr>
                    <td><code>min_rating</code></td>
                    <td>1-5</td>
                    <td>Rating minimo de estrellas.</td>
                </tr>
                <tr>
                    <td><code>only_prime</code></td>
                    <td>"1"</td>
                    <td>Mostrar solo productos Prime.</td>
                </tr>
                <tr>
                    <td><code>only_deals</code></td>
                    <td>"1"</td>
                    <td>Mostrar solo productos con descuento.</td>
                </tr>
                <tr>
                    <td><code>orderby</code></td>
                    <td>date, price, rating, discount, random</td>
                    <td>Ordenar por fecha, precio, rating, descuento o aleatorio.</td>
                </tr>
                <tr>
                    <td><code>order</code></td>
                    <td>ASC, DESC</td>
                    <td>Orden ascendente o descendente.</td>
                </tr>
                <tr>
                    <td><code>hide_filters</code></td>
                    <td>"1"</td>
                    <td>Ocultar el panel de filtros y buscador.</td>
                </tr>
                <tr>
                    <td><code>pagination</code></td>
                    <td>"0"</td>
                    <td>Desactivar paginacion (muestra todos hasta el limit).</td>
                </tr>
            </tbody>
        </table>

        <h4 style="margin-top: 30px;">Ejemplos de uso:</h4>
        <table class="wp-list-table widefat fixed striped" style="max-width: 800px;">
            <thead>
                <tr>
                    <th>Shortcode</th>
                    <th>Descripcion</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[amazon_products limit="8" orderby="rating"]</code></td>
                    <td>8 productos mejor valorados</td>
                </tr>
                <tr>
                    <td><code>[amazon_products ids="123,456,789"]</code></td>
                    <td>Mostrar productos especificos por ID</td>
                </tr>
                <tr>
                    <td><code>[amazon_products search="auriculares" limit="6"]</code></td>
                    <td>Productos que contengan "auriculares" en el titulo</td>
                </tr>
                <tr>
                    <td><code>[amazon_products orderby="random" limit="4"]</code></td>
                    <td>4 productos aleatorios</td>
                </tr>
                <tr>
                    <td><code>[amazon_products only_deals="1" orderby="discount"]</code></td>
                    <td>Solo ofertas, ordenadas por mayor descuento</td>
                </tr>
                <tr>
                    <td><code>[amazon_products min_price="50" only_prime="1"]</code></td>
                    <td>Productos Prime de mas de 50 euros</td>
                </tr>
                <tr>
                    <td><code>[amazon_products hide_filters="1" pagination="0" limit="3"]</code></td>
                    <td>3 productos sin filtros ni paginacion (ideal para widgets)</td>
                </tr>
                <tr>
                    <td><code>[amazon_deals limit="12"]</code></td>
                    <td>12 productos con descuento (desde DB)</td>
                </tr>
            </tbody>
        </table>

        <h4 style="margin-top: 30px;">Shortcode de Ofertas:</h4>
        <p>Usa <code>[amazon_deals]</code> para mostrar productos <strong>guardados</strong> que tienen descuento (precio original mayor al precio actual).</p>
        <ul style="list-style: disc; margin-left: 20px;">
            <li><code>limit</code>: Numero de ofertas a mostrar (default: 12).</li>
            <li><code>orderby</code>: "discount" (default), "date", "price", "rating".</li>
            <li><code>order</code>: "DESC" (default) o "ASC".</li>
            <li><code>category</code>: Filtrar por categoria (slug).</li>
        </ul>
        <p><strong>Importante:</strong> Este shortcode NO consume llamadas a la API. Lee productos ya importados desde la base de datos.</p>
        <p>Para importar ofertas nuevas con precio original, usa el panel <strong>Admin > Amazon Products > Import Deals</strong>.</p>
<?php
    }
}
