# new

Crea un proyecto Glory nuevo desde cero.

## Uso

```bash
npx glory new <nombre> [--flags]
```

## Que hace

1. **Verifica prerequisitos** — git, Node.js, npm, PHP, Composer
2. **Clona repositorio** — `git clone --branch glory-react`
3. **Inicializa submodulos** — `git submodule update --init --recursive`
4. **Instala dependencias PHP** — `composer install`
5. **Instala dependencias npm** — `npm run install:all`
6. **Configura feature flags** — Segun flags pasados
7. **Crea isla de ejemplo** — `InicioIsland` (excepto `--minimal`)
8. **Valida tipos** — `npm run type-check`
9. **Muestra checklist**

## Flags

| Flag | Efecto |
|------|--------|
| `--minimal` | Solo React + TS + ESLint, sin isla de ejemplo |
| `--tailwind` | Activa Tailwind CSS |
| `--shadcn` | Activa shadcn/ui + Tailwind |
| `--with-stripe` | Activa integracion Stripe |

## Ejemplo

```bash
# Proyecto basico
npx glory new mi-sitio

# Con Tailwind y shadcn
npx glory new mi-sitio --shadcn

# Minimalista
npx glory new mi-sitio --minimal
```

## Resultado

```
mi-sitio/
├── App/
│   ├── Config/
│   ├── Content/
│   └── React/
│       └── islands/
│           └── InicioIsland.tsx  ← Isla de ejemplo
├── Glory/                        ← Submodulo
├── package.json
└── ...
```

## Output

```
ℹ Creando proyecto "mi-sitio"...
✓ Node.js: v24.13.0
ℹ Clonando repositorio...
ℹ Inicializando submodulos...
ℹ Instalando dependencias PHP...
ℹ Instalando dependencias npm...
✓ Tailwind CSS activado
ℹ Creando isla de ejemplo...
✓ type-check pasado correctamente

=== Proyecto "mi-sitio" creado ===

  1. cd mi-sitio
  2. npm run dev
  3. npx glory create island MiPrimeraIsla
```

## Errores comunes

| Error | Causa | Solucion |
|-------|-------|----------|
| Directorio ya existe | El nombre ya esta en uso | Elegir otro nombre |
| git no encontrado | git no instalado | Instalar git |
| Error clonando | Sin conexion o repo privado | Verificar red / permisos |
