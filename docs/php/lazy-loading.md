# Lazy Loading

Las islas lazy se cargan bajo demanda — solo cuando aparecen en el DOM.

## Registrar isla lazy

```tsx
import { islandRegistry } from '@/core';

islandRegistry.registerLazy('PesadaIsla', () => import('./islands/PesadaIsla'));
```

Internamente usa `React.lazy()` para code splitting via Vite.

## Que pasa al montar

1. `hydration.tsx` detecta que la isla es lazy (`isLazy: true`)
2. Envuelve en `<Suspense>` con fallback
3. Vite carga el chunk bajo demanda
4. El componente se renderiza cuando esta listo

## Suspense fallback

Default:

```tsx
<div style={{ padding: '12px', textAlign: 'center', color: '#9ca3af' }}>
    Cargando...
</div>
```

Personalizable:

```tsx
initializeIslands({
    suspenseFallback: <MiSpinner />,
});
```

## Cuando usar lazy

| Escenario | Recomendacion |
|-----------|---------------|
| Isla chica, siempre visible | Registro estatico |
| Isla grande, below-the-fold | **Lazy** |
| Isla con deps pesadas (editor, charts) | **Lazy** |
| Isla condicional (solo admin) | **Lazy** |

## Bundle impact

```
// Estatico: se incluye en el bundle principal
islandRegistry.register('Chica', ChicaIsla);

// Lazy: genera un chunk separado
islandRegistry.registerLazy('Grande', () => import('./islands/GrandeIsla'));
// → dist/GrandeIsla-[hash].js (cargado on-demand)
```
