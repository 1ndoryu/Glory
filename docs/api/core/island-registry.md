# IslandRegistry

Registro tipado de componentes React. Soporta carga estatica y lazy (code splitting).

## API

### register(name, component)

Registra una isla con import estatico.

```typescript
import { islandRegistry } from '@/core';
import { MiIsla } from './islands/MiIsla';

islandRegistry.register('MiIsla', MiIsla);
```

Si la isla ya existe, se sobrescribe (warning en desarrollo).

### registerLazy(name, loader)

Registra una isla con import dinamico. Se carga solo cuando aparece en el DOM.

```typescript
islandRegistry.registerLazy('PesadaIsla', () => import('./islands/PesadaIsla'));
```

Internamente usa `React.lazy()`. La isla se envuelve automaticamente en `<Suspense>` al montarse.

### registerAll(map)

Registra multiples islas de un objeto:

```typescript
import appIslands from '@app/appIslands';

islandRegistry.registerAll(appIslands);
// Equivale a: Object.entries(map).forEach(([name, comp]) => register(name, comp))
```

### resolve(name)

Resuelve una isla por nombre. Retorna `null` si no existe.

```typescript
const resolved = islandRegistry.resolve('MiIsla');
// { component: MiIsla, isLazy: false }

const lazy = islandRegistry.resolve('PesadaIsla');
// { component: React.lazy(...), isLazy: true }
```

```typescript
interface ResolvedIsland {
    component: ComponentType<Record<string, unknown>>;
    isLazy: boolean;
}
```

### has(name)

```typescript
islandRegistry.has('MiIsla'); // true
```

### getNames()

```typescript
islandRegistry.getNames(); // ['MiIsla', 'PesadaIsla', ...]
```

### size

```typescript
islandRegistry.size; // 5
```

### clear()

Limpia todo el registry. Util en tests.

```typescript
islandRegistry.clear();
```

## Tipos

```typescript
type IslandComponent = ComponentType<Record<string, unknown>>;
type IslandLoader = () => Promise<{ default: IslandComponent }>;

interface ResolvedIsland {
    component: IslandComponent;
    isLazy: boolean;
}
```

## Singleton

`islandRegistry` es un singleton exportado. Todos los modulos comparten la misma instancia.

```typescript
import { islandRegistry } from '@/core';
```
