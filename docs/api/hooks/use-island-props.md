# useIslandProps

Acceso a las props tipadas de una isla.

## Firma

```typescript
function useIslandProps<
    T extends Record<string, unknown> = Record<string, unknown>
>(rawProps: Record<string, unknown>): T
```

## Parametros

| Parametro | Tipo | Descripcion |
|-----------|------|-------------|
| `rawProps` | `Record<string, unknown>` | Props en bruto recibidos por la isla |

## Uso

```tsx
interface MiIslaProps {
    titulo: string;
    items: string[];
    mostrarFooter: boolean;
}

export function MiIsla(rawProps: Record<string, unknown>): JSX.Element {
    const props = useIslandProps<MiIslaProps>(rawProps);

    return (
        <div>
            <h1>{props.titulo}</h1>
            {props.items.map((item, i) => <p key={i}>{item}</p>)}
            {props.mostrarFooter && <footer>Pie</footer>}
        </div>
    );
}
```

## Notas

- El cast es via `useMemo` para estabilidad referencial
- No hace validacion runtime — solo cast de TypeScript
- Para validacion, combina con Zod u otra libreria de schemas
