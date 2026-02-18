# `npx glory schema:validate`

Escanea archivos PHP del proyecto buscando accesos a columnas de BD como strings hardcodeados y los valida contra los schemas registrados.

## Uso

```bash
npx glory schema:validate
```

## Qué detecta

### Columnas desconocidas (errores)

Busca patrones como `$row['columna']`, `$usuario['columna']`, `$sample['columna']`, etc. y verifica que `columna` exista en algún schema registrado.

```
App/Kamples/Services/MiServicio.php:
    L42: columna 'usernmae' no existe en ningun schema (quizas: 'username')
    L87: columna 'completa' no existe en ningun schema (quizas: 'completada')
```

### Meta keys sin schema (warnings)

Detecta `get_post_meta()` con meta keys que no están en ningún `PostTypeSchema`.

## Variables detectadas

El validador busca accesos con estas variables comunes:

`$row`, `$resultado`, `$usuario`, `$sample`, `$pub`, `$datos`, `$registro`, `$fila`, `$r`, `$usr`, `$connectId`, `$notif`, `$stats`, `$conv`, `$col`, `$msg`, `$like`, `$follow`, `$desc`, `$rep`, `$com`, `$report`

## Exclusiones

- Archivos en `vendor/`, `node_modules/`, `_generated/`, `.git/`, `cache/`
- Líneas que ya usan constantes (`Cols::`)
- Claves genéricas no relacionadas con BD: `error`, `success`, `message`, `data`, `status`, `code`, etc.
- Comentarios (`//`, `/* */`)

## Flujo recomendado

```bash
# Después de regenerar schemas
npx glory schema:generate

# Validar que no queden strings hardcodeados
npx glory schema:validate

# Si hay problemas, reemplazar:
# $row['columna']  →  $row[TablaCols::COLUMNA]
```

## Sugerencias Levenshtein

Cuando una columna desconocida es similar a una conocida (distancia ≤ 2), el validador sugiere la corrección:

```
L42: columna 'credor_id' no existe en ningun schema (quizas: 'creador_id')
```
