# setup

Inicializa un proyecto Glory ya clonado.

## Uso

```bash
npx glory setup [--flags]
```

## Que hace

1. **Verifica prerequisitos** — Node.js, npm, PHP, Composer
2. **Instala dependencias PHP** — `composer install`
3. **Instala dependencias npm** — `npm run install:all`
4. **Configura feature flags** — Modifica `App/Config/control.php`
5. **Valida tipos** — `npm run type-check`
6. **Muestra checklist** — Siguientes pasos

## Flags

| Flag | Efecto |
|------|--------|
| `--tailwind` | Activa Tailwind CSS en control.php |
| `--shadcn` | Activa shadcn/ui + Tailwind en control.php |

## Ejemplo

```bash
# Setup basico
npx glory setup

# Con Tailwind
npx glory setup --tailwind

# Con shadcn/ui (incluye Tailwind)
npx glory setup --shadcn
```

## Prerequisitos verificados

| Herramienta | Que verifica |
|-------------|-------------|
| Node.js | `node --version` |
| npm | `npm --version` |
| PHP | `php --version` |
| Composer | `composer --version` |

Si falta alguno, el setup se detiene con un mensaje de error.

## Output

```
✓ Node.js: v24.13.0
✓ npm: 11.6.2
✓ PHP: 8.2.27
✓ Composer: 2.9.5
ℹ Instalando dependencias PHP...
ℹ Instalando dependencias npm...
✓ Tailwind CSS activado
✓ type-check pasado correctamente

=== Proyecto listo ===

  Siguientes pasos:
  1. npm run dev
  2. npx glory create island MiPrimeraIsla
  3. Abrir WordPress admin
```
