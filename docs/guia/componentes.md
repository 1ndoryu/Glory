# Componentes

Los componentes son piezas reutilizables de UI que se usan dentro de las islas.

## Con CLI

```bash
npx glory create component BotonPrimario
```

Genera `App/React/components/BotonPrimario.tsx`:

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

## Uso en una isla

```tsx
import { BotonPrimario } from '../components/BotonPrimario';

export function ContactoIsland(): JSX.Element {
    return (
        <div id="seccionContacto">
            <h1>Contacto</h1>
            <BotonPrimario>Enviar mensaje</BotonPrimario>
        </div>
    );
}
```

## Convenciones

- **Nombre:** `PascalCase` (`BotonPrimario`, `TarjetaProducto`)
- **Ubicacion:** `App/React/components/`
- **Clases CSS:** Español, `camelCase` (`contenedorBoton`, `textoDestacado`)
- **Props:** Minimas y especificas (ISP)
- **Responsabilidad:** Un componente = una cosa (SRP)

## Organizacion

Para proyectos grandes, organiza por dominio:

```
App/React/components/
├── ui/                    # Componentes base
│   ├── Boton.tsx
│   ├── Input.tsx
│   └── Tarjeta.tsx
├── layout/                # Estructura
│   ├── Header.tsx
│   └── Footer.tsx
└── blog/                  # Dominio especifico
    ├── PostCard.tsx
    └── PostList.tsx
```

::: warning Limite de lineas
Cada componente debe tener maximo **300 lineas**. Si crece mas, divide en subcomponentes.
:::
