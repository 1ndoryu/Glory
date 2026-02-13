# create component

Crea un componente reutilizable en `App/React/components/`.

## Uso

```bash
npx glory create component <Nombre>
```

## Que genera

```
App/React/components/{Nombre}.tsx
```

## Ejemplo

```bash
npx glory create component BotonPrimario
```

### BotonPrimario.tsx

```tsx
import type { ReactNode } from 'react';

interface BotonPrimarioProps {
    children?: ReactNode;
}

export function BotonPrimario({ children }: BotonPrimarioProps): JSX.Element {
    return (
        <div className="contenedorBotonPrimario">
            {children}
        </div>
    );
}
```

## Comportamiento

- Si el componente ya existe, muestra error
- El nombre se convierte a PascalCase
- Crea el directorio `components/` si no existe
