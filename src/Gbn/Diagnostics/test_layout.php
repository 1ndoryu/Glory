<?php
// Script de prueba para HasLayoutOptions y compatibilidad
// Ejecutar con php test_layout.php

// Autoloader simulado
spl_autoload_register(function ($class) {
    // Definir mapeo de namespaces a directorios
    $map = [
        'Glory\\Gbn\\Traits\\' => __DIR__ . '/../Traits/',
        'Glory\\Gbn\\Icons\\' => __DIR__ . '/../Icons/',
        'Glory\\Gbn\\Schema\\' => __DIR__ . '/../Schema/',
    ];
    
    foreach ($map as $prefix => $base_dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});

if (!class_exists('Glory\\Gbn\\Schema\\Option')) {
    // Definimos una clase simulada en el namespace global para la prueba
    // y hacemos un alias si es posible, o simplemente usamos el autoloader real que ya definimos arriba.
    // Dado que el autoloader está configurado, debería encontrar la clase real.
    // Pero si falla, aquí hay un fallback simple sin namespace.
}

// Intentar usar la clase real primero
if (file_exists(__DIR__ . '/../Schema/Option.php')) {
    // El autoloader debería encargarse
}

use Glory\Gbn\Traits\HasLayoutOptions;
use Glory\Gbn\Icons\IconRegistry;
use Glory\Gbn\Schema\Option; // This will use the real one via autoloader

class TestLayoutComponent {
    use HasLayoutOptions;
    
    public function getOpts($level = 'full') {
        return $this->getLayoutOptions($level);
    }
}

echo "Probando HasLayoutOptions...\n";

try {
    $component = new TestLayoutComponent();
    $options = $component->getOpts('full');
    
    echo "Total opciones (full): " . count($options) . "\n";
    
    // Verificar iconGroup layout
    $layoutOpt = $options[0];
    $data = $layoutOpt->toArray();
    
    if ($data['id'] === 'layout' && $data['tipo'] === 'icon_group') {
        echo "[OK] Opción 'layout' encontrada.\n";
        if (isset($data['opciones'][0]['icon']) && strpos($data['opciones'][0]['icon'], '<svg') !== false) {
             echo "[OK] Iconos cargados correctamente en layout.\n";
        } else {
             echo "[FALLO] Iconos faltantes en layout.\n";
        }
    } else {
        echo "[FALLO] Opción 'layout' no es la primera o tiene tipo incorrecto. ID: " . ($data['id'] ?? 'N/A') . "\n";
    }
    
    // Verificar flex options (solo si están)
    $hasFlexDir = false;
    foreach ($options as $opt) {
        $d = $opt->toArray();
        if (($d['id'] ?? '') === 'flexDirection') $hasFlexDir = true;
    }
    if ($hasFlexDir) {
        echo "[OK] Opciones Flex incluidas (flexDirection).\n";
    }
    
    echo "Prueba completada.\n";
    
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}
