# React Islands

El patron React Islands permite montar multiples componentes React independientes en una pagina HTML servida por WordPress.

## Que es una isla

Una isla es un componente React que se monta en un contenedor HTML especifico. Cada isla:

- Es **independiente** — si una falla, las demas siguen
- Tiene su propio **error boundary**
- Recibe **props tipados** via `data-props`
- Se envuelve automaticamente en **GloryProvider**

## Como funciona

### 1. PHP genera el contenedor

```html
<div data-island="ContactoIsland" data-props='{"titulo":"Contacto"}'></div>
```

### 2. React lo monta

```tsx
// main.tsx registra la isla
islandRegistry.register('ContactoIsland', ContactoIsland);

// hydration.tsx busca [data-island] y monta
initializeIslands();
```

### 3. Estructura resultante

```
<div data-island="ContactoIsland">
  <StrictMode>
    <GloryProvider>
      <ErrorBoundary islandName="ContactoIsland">
        <ContactoIsland titulo="Contacto" />
      </ErrorBoundary>
    </GloryProvider>
  </StrictMode>
</div>
```

## Registro de islas

### Registro estatico

El componente se incluye en el bundle principal:

```tsx
import { ContactoIsland } from './islands/ContactoIsland';

islandRegistry.register('ContactoIsland', ContactoIsland);
```

### Registro lazy

El componente se carga bajo demanda (code splitting):

```tsx
islandRegistry.registerLazy('PesadaIsland', () => import('./islands/PesadaIsland'));
```

Las islas lazy se envuelven automaticamente en `<Suspense>` con un fallback de carga.

### Registro batch

Para registrar multiples islas desde un mapa:

```tsx
import appIslands from '@app/appIslands';

islandRegistry.registerAll(appIslands);
```

## Props

Las islas reciben props de dos formas:

### Via data-props (desde PHP)

```php
PageManager::reactPage('contacto', 'ContactoIsland', [
    'titulo' => 'Contacto',
    'email' => 'info@ejemplo.com'
]);
```

Se serializa como JSON en `data-props` y se parsea automaticamente.

### Via window globals

Contenido de WordPress disponible via `useGloryContent()`:

```tsx
const { data } = useGloryContent<WPPost>('blog');
```

## Multiples islas por pagina

Puedes tener varias islas en la misma pagina:

```html
<div data-island="HeaderIsland"></div>
<div data-island="ContenidoIsland" data-props='{"id":42}'></div>
<div data-island="FooterIsland"></div>
```

Cada una se monta y funciona de forma independiente.

## Hidratacion vs CSR

| Modo | Cuando | Como |
|------|--------|------|
| **CSR** (default) | El contenedor esta vacio | `createRoot()` |
| **SSG** | El contenedor tiene `data-hydrate="true"` + HTML pre-renderizado | `hydrateRoot()` |

Si la hidratacion falla, Glory hace fallback automatico a CSR.
