# `npx glory schema:generate`

Genera constantes PHP, DTOs tipados e interfaces TypeScript a partir de los schemas declarados en `App/Config/Schema/`.

## Uso

```bash
npx glory schema:generate
```

## Qué genera

Por cada archivo `*Schema.php` encontrado:

| Archivo generado | Ubicación | Contenido |
|-----------------|-----------|-----------|
| `{Clase}Cols.php` | `App/Config/Schema/_generated/` | Constantes de columna + array `TODAS` |
| `{Clase}DTO.php` | `App/Config/Schema/_generated/` | Clase inmutable con `desdeRow()`, `aArray()`, `aArrayDB()` |

Adicionalmente genera un archivo TypeScript unificado:

| Archivo | Ubicación | Contenido |
|---------|-----------|-----------|
| `schema.ts` | `App/React/types/_generated/` | Interfaces `I{Clase}` + constantes `{Clase}Cols` |

## Ejemplo de salida

### Input: `App/Config/Schema/SamplesSchema.php`

```php
class SamplesSchema extends TableSchema
{
    public function tabla(): string { return 'samples'; }
    public function columnas(): array
    {
        return [
            'id'     => ['tipo' => 'int', 'pk' => true],
            'titulo' => ['tipo' => 'string', 'max' => 200],
            'estado' => ['tipo' => 'string', 'check' => ['activo', 'inactivo']],
        ];
    }
}
```

### Output: `_generated/SamplesCols.php`

```php
final class SamplesCols
{
    const TABLA = 'samples';
    const ID = 'id';
    const TITULO = 'titulo';
    const ESTADO = 'estado';
    const TODAS = ['id', 'titulo', 'estado'];
}
```

### Output: `_generated/SamplesDTO.php`

```php
final class SamplesDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $titulo,
        public readonly string $estado
    ) {}

    public static function desdeRow(array $row): self;
    public function aArray(): array;      // ['id' => 1, 'titulo' => '...', 'estado' => '...']
    public function aArrayDB(): array;    // ['id' => 1, 'titulo' => '...', 'estado' => '...']
}
```

### Output en `schema.ts`

```typescript
export interface ISamples {
    id: number
    titulo: string
    estado: 'activo' | 'inactivo'
}

export const SamplesCols = {
    TABLA: 'samples',
    ID: 'id',
    TITULO: 'titulo',
    ESTADO: 'estado'
} as const
```

## Mapeo de tipos

| Schema | PHP | TypeScript |
|--------|-----|-----------|
| `int` | `int` | `number` |
| `string` | `string` | `string` |
| `text` | `string` | `string` |
| `float` / `decimal` | `float` | `number` |
| `bool` | `bool` | `boolean` |
| `datetime` | `string` | `string` |
| `json` | `array` | `Record<string, unknown>` |
| `array` | `array` | `string[]` |
| `vector` | `string` | `number[]` |

Columnas con `check` generan **union literals** en TypeScript en lugar de `string`.

## Características del DTO generado

- **`desdeRow(array)`**: Construye la instancia desde un row de BD. Lanza `SchemaException` si faltan columnas requeridas (sin default). Decodifica JSON strings automáticamente.
- **`aArray()`**: Retorna array con claves camelCase (para serialización a JSON/frontend).
- **`aArrayDB()`**: Retorna array con claves snake_case (para insertar/actualizar en SQL).
- **Inmutable**: Todas las propiedades son `readonly`.

## Notas

- Los archivos en `_generated/` son sobreescritos en cada ejecución. **Nunca editarlos manualmente**.
- El generador parsea los archivos PHP con regex. Si la estructura del schema es no estándar, podría fallar.
- Se crea la carpeta `_generated/` automáticamente si no existe.
- Se requiere al menos un archivo `*Schema.php` en `App/Config/Schema/`.
