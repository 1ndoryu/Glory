# Schema System — Tipado end-to-end

El Schema System de Glory resuelve un problema fundamental de PHP + SQL: **los nombres de columna son strings sin validación**. Un typo como `$row['usename']` en lugar de `$row['username']` pasa silenciosamente y solo se detecta en producción.

## Problema que resuelve

```php
/* PHP no detecta errores en claves de array hasta runtime */
$row['username']      // correcto
$row['user_name']     // typo → null silencioso
$row['usernmae']      // typo → null silencioso
get_post_meta($id, 'precio')   // meta key real: '_precio' → null silencioso
```

Cuando se renombra una columna (ej: `completa` → `completada`), hay que buscar manualmente en todo el proyecto. Sin el Schema System, es imposible saber si omitiste alguna referencia.

## Arquitectura

```
┌────────────────────────────────────────────────────────┐
│ Source of Truth: App/Config/Schema/*Schema.php          │
│ (declaraciones manuales de tablas y columnas)           │
└────────────────────┬───────────────────────────────────┘
                     │ npx glory schema:generate
                     ▼
┌────────────────────────────────────────────────────────┐
│ _generated/                                             │
│ ├── {Tabla}Cols.php    Constantes PHP                   │
│ ├── {Tabla}DTO.php     Clase tipada + desdeRow()        │
│ └── schema.ts          Interfaces + constantes TS       │
└────────────────────┬───────────────────────────────────┘
                     │ Runtime
                     ▼
┌────────────────────────────────────────────────────────┐
│ SchemaRegistry     Carga lazy, valida en WP_DEBUG       │
│ PostgresService    Valida queries contra schemas        │
│ Controladores      Usan XxxCols::COLUMNA en todo acceso │
└────────────────────────────────────────────────────────┘
```

## Componentes del sistema

### 1. Contratos (`Glory/src/Contracts/`)

#### `TableSchema` (tablas custom)

Clase base abstracta para definir schemas de tablas PostgreSQL/MySQL. Cada tabla de la aplicación **debe** tener una clase que la extienda.

```php
namespace Glory\Contracts;

abstract class TableSchema
{
    abstract public function tabla(): string;
    abstract public function columnas(): array;

    /* Opcionales */
    public function pkCompuesta(): array { return []; }
    public function uniqueCompuestos(): array { return []; }

    /* Helpers */
    public function nombreColumnas(): array;
    public function columnaExiste(string $nombre): bool;
    public function obtenerColumna(string $nombre): ?array;
    public function tipoPHP(string $nombre): ?string;
    public function tipoTS(string $nombre): ?string;
    public function validarClaves(array $datos): array;
}
```

#### `PostTypeSchema` (Custom Post Types WP)

Para post types de WordPress con meta fields tipados. Genera getters seguros en lugar de `get_post_meta()` directo.

```php
abstract class PostTypeSchema
{
    abstract public function postType(): string;
    abstract public function meta(): array;

    public function taxonomias(): array { return []; }
    public function nombreMetas(): array;
    public function metaExiste(string $key): bool;
    public function tipoPHPMeta(string $key): ?string;
    public function tipoTSMeta(string $key): ?string;
}
```

### 2. Definición de columnas

Cada columna es un array asociativo con las siguientes propiedades:

| Propiedad | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `tipo` | string | (requerido) | `int`, `string`, `text`, `float`, `decimal`, `bool`, `datetime`, `json`, `array`, `vector` |
| `pk` | bool | `false` | Primary key |
| `nullable` | bool | `false` | Permite NULL |
| `default` | mixed | — | Valor por defecto |
| `unico` | bool | `false` | Constraint UNIQUE |
| `max` | int | — | Longitud máxima (strings) |
| `ref` | string | — | Foreign key: `'tabla(columna)'` |
| `check` | array | — | Valores permitidos (CHECK constraint) |

### Mapeo de tipos

| Schema | PHP | TypeScript | PostgreSQL |
|--------|-----|-----------|------------|
| `int` | `int` | `number` | `INTEGER` |
| `float` | `float` | `number` | `REAL` |
| `decimal` | `float` | `number` | `DECIMAL` |
| `string` | `string` | `string` | `VARCHAR(max)` |
| `text` | `string` | `string` | `TEXT` |
| `bool` | `bool` | `boolean` | `BOOLEAN` |
| `datetime` | `string` | `string` | `TIMESTAMP` |
| `json` | `array` | `Record<string, unknown>` | `JSONB` |
| `array` | `array` | `string[]` | `TEXT[]` |
| `vector` | `string` | `number[]` | `VECTOR(n)` |

Cuando una columna tiene `check`, TypeScript genera un **union literal** en lugar de `string`:

```typescript
/* Sin check: */ tipo: string
/* Con check: */ tipo: 'loop' | 'oneshot' | 'fx' | 'vocal' | 'stem' | 'otro'
```

### 3. Ejemplo completo de schema

```php
/* App/Config/Schema/SamplesSchema.php */
namespace App\Config\Schema;

use Glory\Contracts\TableSchema;

class SamplesSchema extends TableSchema
{
    public function tabla(): string
    {
        return 'samples';
    }

    public function columnas(): array
    {
        return [
            'id'          => ['tipo' => 'int', 'pk' => true],
            'creador_id'  => ['tipo' => 'int', 'ref' => 'usuarios_ext(id)'],
            'titulo'      => ['tipo' => 'string', 'max' => 200],
            'slug'        => ['tipo' => 'string', 'max' => 250, 'unico' => true],
            'bpm'         => ['tipo' => 'int', 'nullable' => true],
            'estado'      => ['tipo' => 'string', 'max' => 20, 'default' => 'activo',
                              'check' => ['procesando', 'activo', 'inactivo']],
            'tags'        => ['tipo' => 'array', 'default' => '{}'],
            'metadata'    => ['tipo' => 'json', 'default' => '{}'],
            'created_at'  => ['tipo' => 'datetime', 'default' => 'NOW()'],
        ];
    }
}
```

### 4. Archivos generados

Al ejecutar `npx glory schema:generate`, se producen 3 tipos de archivo:

#### `SamplesCols.php` — Constantes de columna

```php
final class SamplesCols
{
    const TABLA = 'samples';
    const ID = 'id';
    const CREADOR_ID = 'creador_id';
    const TITULO = 'titulo';
    /* ... */
    const TODAS = ['id', 'creador_id', 'titulo', ...];
}
```

**Uso:**

```php
use App\Config\Schema\_generated\SamplesCols;

/* Autocompletado en IDE, error si se renombra, refactor-safe */
$titulo = $row[SamplesCols::TITULO];

/* Validar que un array solo contenga columnas válidas */
$clavesExtraidas = array_keys($datos);
$invalidas = array_diff($clavesExtraidas, SamplesCols::TODAS);
```

#### `SamplesDTO.php` — Data Transfer Object

```php
final class SamplesDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $creadorId,
        public readonly string $titulo,
        /* ... */
    ) {}

    public static function desdeRow(array $row): self;
    public function aArray(): array;     // camelCase (JSON)
    public function aArrayDB(): array;   // snake_case (SQL)
}
```

`desdeRow()` valida columnas requeridas y lanza `SchemaException` si faltan.

#### `schema.ts` — Interfaces TypeScript

```typescript
export interface ISamples {
    id: number
    creadorId: number
    titulo: string
    estado: 'procesando' | 'activo' | 'inactivo'  /* Union literal desde check */
    bpm: number | null
    /* ... */
}

export const SamplesCols = {
    TABLA: 'samples',
    ID: 'id',
    CREADOR_ID: 'creador_id',
    /* ... */
} as const
```

### 5. SchemaRegistry (validación runtime)

```php
use Glory\Core\SchemaRegistry;

/* Obtener schema de una tabla */
$schema = SchemaRegistry::tabla('samples');

/* Verificar existencia de columna */
SchemaRegistry::columnaExiste('samples', 'titulo'); // true

/* Exigir en modo estricto (lanza SchemaException si WP_DEBUG) */
SchemaRegistry::exigirTabla('samples');
SchemaRegistry::exigirColumna('samples', 'titulo');
```

**Lazy-init:** SchemaRegistry carga los schemas la primera vez que se necesitan (no en cada request). Los schemas se autocargados desde `App/Config/Schema/`.

**Modo estricto:** Activo cuando `WP_DEBUG = true`. Los métodos `exigir*()` lanzan `SchemaException` en vez de solo loguear un warning.

### 6. Integración con PostgresService

`PostgresService` valida automáticamente las queries contra el SchemaRegistry en modo estricto. Extrae nombres de tabla de cláusulas `FROM`, `JOIN`, `INTO`, `UPDATE` y valida que tengan schema registrado.

```php
/* Si la tabla no tiene schema → SchemaException en WP_DEBUG */
PostgresService::consultar("SELECT * FROM tabla_sin_schema WHERE id = :id", ['id' => 1]);
```

### 7. SchemaException

```php
use Glory\Exception\SchemaException;

try {
    SchemaRegistry::exigirColumna('samples', 'columna_inexistente');
} catch (SchemaException $e) {
    $e->getTabla();    // 'samples'
    $e->getColumna();  // 'columna_inexistente'
    $e->getMessage();  // Mensaje descriptivo con sugerencias
}
```

## Flujo de trabajo

### Crear una tabla nueva

```bash
npx glory create table MiTabla
# → App/Config/Schema/MiTablaSchema.php (editar columnas)
# → App/Kamples/Database/migrations/vXXX_mi_tabla.sql
```

### Regenerar archivos después de cambios

```bash
npx glory schema:generate
# → _generated/MiTablaCols.php
# → _generated/MiTablaDTO.php
# → App/React/types/_generated/schema.ts (actualizado)
```

### Detectar strings hardcodeados

```bash
npx glory schema:validate
# Escanea PHP buscando $row['xxx'] sin Cols:: y reporta hallazgos
```

## Reglas del sistema

1. **Los archivos `_generated/` nunca se editan manualmente.** El generador los sobreescribe.
2. **Toda tabla custom debe tener un Schema.** Sin schema, el modo estricto rechaza queries.
3. **Usar `TablaCols::COLUMNA` en lugar de `'columna'`.** El IDE autocompleta y refactoriza.
4. **Los DTOs son immutables** (`readonly`). Usar `desdeRow()` para construirlos.
5. **Regenerar después de cambiar schemas.** `npx glory schema:generate` debe ejecutarse después de cualquier modificación a un archivo `*Schema.php`.
