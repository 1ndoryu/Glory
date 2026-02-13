# useGloryMedia

Acceso a imagenes del tema via la REST API de Glory.

## useGloryMedia

Obtiene la URL de una imagen por alias.

### Firma

```typescript
function useGloryMedia(alias: string): UseGloryMediaResult
```

### Retorno

```typescript
interface UseGloryMediaResult {
    url: string | null;
    alt: string | null;
    isLoading: boolean;
    error: string | null;
}
```

### Uso

```tsx
import { useGloryMedia } from '@/hooks';

function LogoIsland(): JSX.Element {
    const { url, alt, isLoading } = useGloryMedia('logo');

    if (isLoading || !url) return null;

    return <img src={url} alt={alt ?? 'Logo'} />;
}
```

## useGloryMediaAliases

Obtiene todos los alias de imagenes disponibles.

### Firma

```typescript
function useGloryMediaAliases(): {
    aliases: Record<string, string>;
    isLoading: boolean;
    error: string | null;
}
```

### Uso

```tsx
import { useGloryMediaAliases } from '@/hooks';

function GaleriaIsland(): JSX.Element {
    const { aliases, isLoading } = useGloryMediaAliases();

    if (isLoading) return <p>Cargando...</p>;

    return (
        <div>
            {Object.entries(aliases).map(([alias, url]) => (
                <img key={alias} src={url} alt={alias} />
            ))}
        </div>
    );
}
```

## Endpoints internos

| Hook | Endpoint |
|------|----------|
| `useGloryMedia(alias)` | `GET /glory/v1/images/url?alias={alias}` |
| `useGloryMediaAliases()` | `GET /glory/v1/images/aliases` |

Los alias se definen en PHP via `AssetsUtility`.
