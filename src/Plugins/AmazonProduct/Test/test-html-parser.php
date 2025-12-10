<?php

/**
 * Script de prueba para HtmlParserService
 * 
 * Para ejecutar:
 * 1. Accede via navegador: http://tu-sitio.local/wp-content/themes/glory/Glory/src/Plugins/AmazonProduct/test-html-parser.php
 * 2. O ejecuta via CLI: php test-html-parser.php
 * 
 * Puedes cambiar el archivo HTML de prueba modificando $testFile
 */

// Cargar el servicio
require_once __DIR__ . '/Service/HtmlParserService.php';

use Glory\Plugins\AmazonProduct\Service\HtmlParserService;

// Archivo HTML de prueba (cambia esto por tu archivo)
$testFile = __DIR__ . '/ejemplo2.html';

// Estilos para output en navegador
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Test HTML Parser</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
            .card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .success { color: #22863a; background: #dcffe4; padding: 4px 8px; border-radius: 4px; }
            .error { color: #cb2431; background: #ffeef0; padding: 4px 8px; border-radius: 4px; }
            .warning { color: #735c0f; background: #fffbdd; padding: 4px 8px; border-radius: 4px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
            th { width: 150px; color: #666; }
            td { font-family: monospace; word-break: break-all; }
            img { max-width: 200px; border: 1px solid #ddd; border-radius: 4px; }
            h1 { color: #333; }
            h2 { color: #666; margin-top: 0; }
            .file-info { color: #666; font-size: 14px; margin-bottom: 20px; }
        </style>
    </head>
    <body>';
}

function output($text, $isCli)
{
    if ($isCli) {
        echo strip_tags($text) . "\n";
    } else {
        echo $text;
    }
}

function getStatus($value, $isCli)
{
    if (empty($value) || $value === 0 || $value === 0.0) {
        return $isCli ? '[FALLO]' : '<span class="error">NO DETECTADO</span>';
    }
    return $isCli ? '[OK]' : '<span class="success">OK</span>';
}

// Verificar archivo
if (!file_exists($testFile)) {
    output("<div class='card'><h2 class='error'>Error: Archivo no encontrado</h2><p>$testFile</p></div>", $isCli);
    exit;
}

$fileSize = filesize($testFile);
$fileSizeKb = round($fileSize / 1024, 2);

output("<h1>Test de HtmlParserService</h1>", $isCli);
output("<div class='file-info'>Archivo: <strong>$testFile</strong> ({$fileSizeKb} KB)</div>", $isCli);

// Cargar HTML
$html = file_get_contents($testFile);
$htmlLength = strlen($html);

output("<div class='card'>", $isCli);
output("<h2>Informacion del HTML</h2>", $isCli);
output("<table>", $isCli);
output("<tr><th>Tamano</th><td>{$htmlLength} caracteres</td></tr>", $isCli);
output("<tr><th>Lineas</th><td>" . substr_count($html, "\n") . "</td></tr>", $isCli);
output("</table>", $isCli);
output("</div>", $isCli);

// Ejecutar parser
$parser = new HtmlParserService();
$startTime = microtime(true);
$result = $parser->parseHtml($html);
$endTime = microtime(true);
$parseTime = round(($endTime - $startTime) * 1000, 2);

output("<div class='card'>", $isCli);
output("<h2>Resultados del Parser</h2>", $isCli);
output("<p style='color:#666;'>Tiempo de ejecucion: {$parseTime}ms</p>", $isCli);
output("<table>", $isCli);

// Mostrar cada campo
$fields = [
    'asin' => 'ASIN',
    'title' => 'Titulo',
    'price' => 'Precio',
    'original_price' => 'Precio Original',
    'currency' => 'Moneda',
    'rating' => 'Rating',
    'reviews' => 'Reviews',
    'prime' => 'Prime',
    'category' => 'Categoria',
    'image' => 'Imagen URL',
    'url' => 'Product URL'
];

$successCount = 0;
$totalFields = count($fields);

foreach ($fields as $key => $label) {
    $value = $result[$key] ?? '';
    $status = getStatus($value, $isCli);

    if (!empty($value) && $value !== 0 && $value !== 0.0) {
        $successCount++;
    }

    // Truncar valores muy largos para display
    $displayValue = $value;
    if (is_string($value) && strlen($value) > 100) {
        $displayValue = substr($value, 0, 100) . '...';
    }

    if ($isCli) {
        output("$status $label: $displayValue", $isCli);
    } else {
        output("<tr><th>$label</th><td>$status $displayValue</td></tr>", $isCli);
    }
}

output("</table>", $isCli);
output("</div>", $isCli);

// Resumen
$percentage = round(($successCount / $totalFields) * 100);
$summaryClass = $percentage >= 80 ? 'success' : ($percentage >= 50 ? 'warning' : 'error');

output("<div class='card'>", $isCli);
output("<h2>Resumen</h2>", $isCli);
output("<p><span class='$summaryClass'>$successCount de $totalFields campos detectados ($percentage%)</span></p>", $isCli);

if ($percentage < 100) {
    output("<p style='color:#666;'>Campos faltantes pueden requerir ajustes en los patrones regex del parser.</p>", $isCli);
}
output("</div>", $isCli);

// Vista previa de imagen si existe
if (!empty($result['image']) && !$isCli) {
    output("<div class='card'>", $isCli);
    output("<h2>Vista Previa de Imagen</h2>", $isCli);
    output("<img src='" . htmlspecialchars($result['image']) . "' alt='Product Image'>", $isCli);
    output("</div>", $isCli);
}

// Datos crudos para debug
output("<div class='card'>", $isCli);
output("<h2>Datos Crudos (JSON)</h2>", $isCli);
output("<pre style='background:#f8f8f8;padding:15px;overflow-x:auto;border-radius:4px;'>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</pre>", $isCli);
output("</div>", $isCli);

if (!$isCli) {
    echo '</body></html>';
}
