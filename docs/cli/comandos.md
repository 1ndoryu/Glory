# Comandos CLI

Glory CLI es una herramienta de scaffolding y gestion de proyectos.

## Instalacion

Ya incluido en el proyecto. No requiere instalacion global.

```bash
# Via npx
npx glory <comando>

# Via npm script
npm run create -- <tipo> <nombre>

# Directo
node Glory/cli/glory.mjs <comando>
```

## Resumen de comandos

| Comando | Descripcion |
|---------|-------------|
| `create island <Nombre>` | Crea isla React (.tsx + .css + registro) |
| `create page <nombre>` | Crea isla + registro en pages.php |
| `create component <Nombre>` | Crea componente en App/React/components/ |
| `create hook <nombre>` | Crea hook en App/React/hooks/ |
| `setup [--flags]` | Inicializa proyecto existente |
| `new <nombre> [--flags]` | Crea proyecto nuevo desde cero |
| `--help` | Muestra ayuda |

## Flags de proyecto

| Flag | Efecto |
|------|--------|
| `--minimal` | Solo React + TS + ESLint (sin extras) |
| `--tailwind` | Activa Tailwind CSS |
| `--shadcn` | Activa shadcn/ui (implica --tailwind) |
| `--with-stripe` | Activa integracion Stripe |

## Ejemplos

```bash
# Scaffolding
npx glory create island MiSeccion
npx glory create page contacto
npx glory create component BotonPrimario
npx glory create hook useProductos

# Proyecto
npx glory new mi-proyecto --tailwind
npx glory setup --shadcn
```

## Requisitos

- Node.js 18+
- El comando debe ejecutarse desde la raiz del proyecto (donde esta `package.json`)

## Validacion de nombres

- Minimo 2 caracteres
- Debe empezar con una letra
- Solo letras, numeros, guiones y guiones bajos
