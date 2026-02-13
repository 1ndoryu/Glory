# Assets

El sistema de assets gestiona la carga de scripts y estilos de Vite en WordPress.

## AssetManager

```php
use Glory\Manager\AssetManager;

// Establecer version del tema (cache busting)
AssetManager::setThemeVersion('0.1.1');
```

## Como funciona

1. Vite genera un manifest en build (`manifest.json`)
2. `AssetManager` lee el manifest y encola los assets correctos
3. En desarrollo, carga directamente del dev server de Vite (HMR)

## Configuracion

```php
// App/Config/config.php
AssetManager::setThemeVersion('0.1.1');
```

## Desarrollo vs Produccion

| Modo | Que hace |
|------|----------|
| **Desarrollo** | Inyecta el cliente HMR de Vite, carga modulos ES directamente |
| **Produccion** | Lee `manifest.json`, encola bundles optimizados con hash |

## Clases internas

| Clase | Responsabilidad | Lineas |
|-------|----------------|--------|
| `AssetManager` | Enqueue de scripts/estilos, manifest Vite | ~279 |
| `FolderScanner` | Escanea carpetas de assets | ~63 |
| `AssetsUtility` | Utilidades de paths y URLs | ~85 |
| `AssetResolver` | Resolucion de rutas de assets | ~169 |
| `AssetImporter` | Importacion masiva de assets | ~289 |
| `AssetLister` | Listado de assets disponibles | ~205 |

## Scripts npm

```bash
npm run dev     # Vite dev server (HMR habilitado)
npm run build   # Build de produccion (genera manifest.json)
```
