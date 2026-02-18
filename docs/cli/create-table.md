# `npx glory create table`

Scaffolding para crear una nueva tabla con schema y migración SQL.

## Uso

```bash
npx glory create table MiTabla
```

## Qué genera

| Archivo | Ubicación | Contenido |
|---------|-----------|-----------|
| `MiTablaSchema.php` | `App/Config/Schema/` | Schema vacío con columnas template |
| `vXXX_mi_tabla.sql` | `App/Kamples/Database/migrations/` | Migración CREATE TABLE |

## Estructura del schema generado

```php
namespace App\Config\Schema;

use Glory\Contracts\TableSchema;

class MiTablaSchema extends TableSchema
{
    public function tabla(): string
    {
        return 'mi_tabla';
    }

    public function columnas(): array
    {
        return [
            'id'         => ['tipo' => 'int', 'pk' => true],
            'created_at' => ['tipo' => 'datetime', 'default' => 'NOW()'],
            'updated_at' => ['tipo' => 'datetime', 'default' => 'NOW()'],
        ];
    }
}
```

## Después de crear

1. Editar el schema con las columnas reales de la tabla.
2. Editar la migración SQL si es necesario.
3. Ejecutar `npx glory schema:generate` para generar Cols + DTO + TS.
4. Ejecutar la migración SQL en la base de datos.
