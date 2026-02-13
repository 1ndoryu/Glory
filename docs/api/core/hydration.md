# Hydration

Motor de montaje e hidratacion de islas React.

## initializeIslands(options?)

Escanea el DOM, resuelve componentes y monta cada isla.

```typescript
function initializeIslands(options?: InitOptions): void
```

### InitOptions

```typescript
interface InitOptions {
    appProvider?: ComponentType<{ children: ReactNode }>;
    suspenseFallback?: ReactNode;
}
```

| Opcion | Descripcion |
|--------|-------------|
| `appProvider` | Componente que envuelve todas las islas (ej: Context provider del proyecto) |
| `suspenseFallback` | Fallback para islas lazy. Default: "Cargando..." |

### Uso

```tsx
// main.tsx
import { initializeIslands } from './core/hydration';
import { AppProvider } from '@app/appIslands';

function init(): void {
    initializeIslands({ appProvider: AppProvider });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
```

## Flujo interno

```
1. querySelectorAll('[data-island]')
2. Por cada contenedor:
   a. Leer data-island → nombre de la isla
   b. Leer data-props → JSON → props
   c. islandRegistry.resolve(nombre) → componente
   d. Envolver en wrappers:
      StrictMode > GloryProvider > AppProvider? > ErrorBoundary > Suspense? > DevOverlay?
   e. Montar:
      - data-hydrate="true" + contenido → hydrateRoot (SSG)
      - Caso default → createRoot (CSR)
```

## Modos de montaje

### CSR (Client-Side Rendering)

Default. El contenedor HTML esta vacio. React monta desde cero.

```html
<div data-island="MiIsla" data-props='{"titulo":"Hola"}'></div>
```

### SSG (Hidratacion)

El HTML fue pre-renderizado. React hidrata el DOM existente.

```html
<div data-island="MiIsla" data-hydrate="true">
    <h1>Hola</h1> <!-- HTML pre-renderizado -->
</div>
```

### Fallback automatico

Si `hydrateRoot` falla (mismatch), Glory intenta automaticamente con `createRoot`.

```
hydrateRoot() → falla → console.warn → createRoot() → exito
```

## Debug

En desarrollo, cada isla logea su estado al montarse:

```
[Glory] Montando 3 isla(s), registry: HeaderIsland, ContenidoIsland, FooterIsland
[Glory] Isla "HeaderIsland" montada (CSR)
[Glory] Isla "ContenidoIsland" hidratada (SSG)
[Glory] Isla "FooterIsland" montada (CSR)
```
