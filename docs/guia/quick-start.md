# Quick Start

Proyecto funcionando en 5 minutos.

## Requisitos

- Node.js 18+
- PHP 8.0+
- Composer
- WordPress 6.0+ instalado

## Instalacion

### Opcion A: Proyecto nuevo

```bash
npx glory new mi-proyecto
cd mi-proyecto
npm run dev
```

Con extras:

```bash
npx glory new mi-proyecto --tailwind --shadcn
```

### Opcion B: En WordPress existente

```bash
cd wp-content/themes
git clone --branch glory-react https://github.com/1ndoryu/glorytemplate.git
cd glorytemplate
node Glory/cli/glory.mjs setup
```

## Primera isla

```bash
npx glory create island HolaMundo
```

Esto genera:

```
App/React/islands/HolaMundoIsland.tsx   ← Componente
App/React/styles/holaMundo.css          ← Estilos
App/React/appIslands.tsx                ← Registro (auto-actualizado)
```

## Registrar como pagina

```bash
npx glory create page hola-mundo
```

Esto crea la isla **y** la registra en `App/Config/pages.php`, creando automaticamente la pagina en WordPress.

## Desarrollo

```bash
npm run dev
```

Abre WordPress en el navegador. Vite HMR actualizara los cambios en tiempo real.

## Build de produccion

```bash
npm run build
```

## Verificar tipos

```bash
npm run type-check
```

## Scripts disponibles

| Script | Que hace |
|--------|----------|
| `npm run dev` | Servidor de desarrollo con HMR |
| `npm run build` | Build de produccion |
| `npm run lint` | Verificar errores ESLint |
| `npm run lint:fix` | Auto-fix ESLint |
| `npm run format` | Formatear con Prettier |
| `npm run type-check` | Verificar tipos TypeScript |
| `npm run install:all` | Instalar todas las dependencias |
