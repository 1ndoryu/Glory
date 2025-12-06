<?php
// Script de prueba simple para IconRegistry
// Ejecutar con php test_icons.php

// Autoloader simulado para la prueba
spl_autoload_register(function ($class) {
    $prefix = 'Glory\\Gbn\\';
    $base_dir = __DIR__ . '/../';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    } else {
        echo "Archivo no encontrado: $file \n";
    }
});

use Glory\Gbn\Icons\IconRegistry;

echo "Probando IconRegistry...\n";

try {
    IconRegistry::init();
    echo "[OK] Init correcto.\n";
    
    $icon = IconRegistry::get('layout.grid');
    if (strpos($icon, '<svg') !== false) {
        echo "[OK] layout.grid encontrado.\n";
    } else {
        echo "[FALLO] layout.grid no es un SVG válido.\n";
    }
    
    $iconAttr = IconRegistry::get('layout.flex', ['width' => '32', 'class' => 'my-icon']);
    if (strpos($iconAttr, 'width="32"') !== false && strpos($iconAttr, 'class="my-icon"') !== false) {
        echo "[OK] Atributos sobrescritos correctamente.\n";
    } else {
        echo "[FALLO] Sobreescritura de atributos falló: $iconAttr\n";
    }
    
    $group = IconRegistry::getGroup(['layout.block', 'layout.flex']);
    if (count($group) === 2 && $group[0]['valor'] === 'layout.block') {
        echo "[OK] getGroup funciona correctamente.\n";
    } else {
        echo "[FALLO] getGroup falló.\n";
    }

    $bgIcon = IconRegistry::get('bg.size.cover');
    if (strpos($bgIcon, '<svg') !== false) {
         echo "[OK] bg.size.cover encontrado (BackgroundIcons).\n";
    }

    $posIcon = IconRegistry::get('pos.absolute');
    if (strpos($posIcon, '<svg') !== false) {
         echo "[OK] pos.absolute encontrado (PositioningIcons).\n";
    }

} catch (Exception $e) {
    echo "[ERROR] Excepción: " . $e->getMessage() . "\n";
}
