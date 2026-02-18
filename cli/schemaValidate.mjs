/**
 * Glory CLI — Schema Validator
 *
 * Escanea archivos PHP del proyecto buscando accesos a columnas como strings
 * hardcodeados ($row['xxx'], $resultado['xxx'], etc.) y los cruza contra
 * los schemas registrados para detectar columnas inexistentes.
 *
 * Uso: npx glory schema:validate
 */

import { readFileSync, readdirSync, statSync, existsSync } from 'node:fs';
import { resolve, relative, extname } from 'node:path';
import { getProjectRoot, log } from './utils.mjs';

/* Parsear schemas igual que el generador para obtener columnas válidas */
function cargarSchemas(schemaDir) {
    const archivos = readdirSync(schemaDir).filter(f => f.endsWith('Schema.php'));
    const schemas = new Map();

    for (const archivo of archivos) {
        const contenido = readFileSync(resolve(schemaDir, archivo), 'utf-8');

        const tablaMatch = contenido.match(/return\s+['"]([a-z_]+)['"]/);
        if (!tablaMatch) continue;

        const tabla = tablaMatch[1];
        const columnasMatch = contenido.match(/public\s+function\s+columnas\s*\(\s*\)\s*:\s*array\s*\{[\s\S]*?return\s*\[([\s\S]*?)\];\s*\}/);
        if (!columnasMatch) continue;

        const columnas = new Set();
        const regex = /'([a-z_]+)'\s*=>/g;
        let match;
        while ((match = regex.exec(columnasMatch[1])) !== null) {
            columnas.add(match[1]);
        }

        schemas.set(tabla, { columnas, archivo });
    }

    return schemas;
}

/* Obtener todas las columnas conocidas (union de todas las tablas) */
function todasColumnasConocidas(schemas) {
    const todas = new Set();
    for (const { columnas } of schemas.values()) {
        for (const col of columnas) {
            todas.add(col);
        }
    }
    return todas;
}

/* Listar archivos PHP recursivamente, excluyendo vendor, _generated, node_modules */
function listarPHP(dir, archivos = []) {
    const excluir = ['vendor', 'node_modules', '_generated', '.git', 'cache'];

    const entries = readdirSync(dir);
    for (const entry of entries) {
        if (excluir.includes(entry)) continue;

        const ruta = resolve(dir, entry);
        const stat = statSync(ruta);

        if (stat.isDirectory()) {
            listarPHP(ruta, archivos);
        } else if (extname(entry) === '.php') {
            archivos.push(ruta);
        }
    }

    return archivos;
}

/* Claves genéricas que no son columnas de DB (constante fuera del loop) */
const GENERICAS = new Set(['error', 'success', 'message', 'data', 'status', 'code', 'type', 'result', 'count', 'total', 'items', 'page', 'limit', 'offset', 'key', 'value', 'name', 'label', 'action', 'method', 'url', 'path', 'host', 'port', 'body', 'headers', 'params', 'query']);

/* Escanear un archivo PHP buscando accesos a arrays con string keys */
function escanearArchivo(contenido, ruta, columnasConocidas) {
    const problemas = [];
    const lineas = contenido.split('\n');

    /*
     * Patrones a detectar:
     * $row['columna'], $resultado['columna'], $usuario['columna'],
     * $sample['columna'], $pub['columna'], $datos['columna'],
     * $registro['columna'], $fila['columna'], $r['columna']
     */
    const patronAcceso = /\$(?:row|resultado|usuario|sample|pub|datos|registro|fila|r|usr|connectId|notif|stats|conv|col|msg|like|follow|desc|rep|com|report)\['([a-z_]+)'\]/g;

    /*
     * Patron para get_post_meta
     */
    const patronMeta = /get_post_meta\s*\(\s*\$\w+\s*,\s*'([a-z_]+)'\s*(?:,\s*true\s*)?\)/g;

    for (let i = 0; i < lineas.length; i++) {
        const linea = lineas[i];

        /* Ignorar comentarios */
        if (linea.trim().startsWith('//') || linea.trim().startsWith('*') || linea.trim().startsWith('/*')) {
            continue;
        }

        /* Ignorar lineas que ya usan constantes (Cols::) */
        if (linea.includes('Cols::')) {
            continue;
        }

        /* Buscar accesos a arrays */
        let match;
        patronAcceso.lastIndex = 0;
        while ((match = patronAcceso.exec(linea)) !== null) {
            const columna = match[1];

            /* Ignorar claves genéricas que no son columnas */
            if (GENERICAS.has(columna)) {
                continue;
            }

            if (!columnasConocidas.has(columna)) {
                /* Buscar sugerencia */
                let sugerencia = '';
                for (const conocida of columnasConocidas) {
                    if (levenshtein(columna, conocida) <= 2) {
                        sugerencia = ` (quizas: '${conocida}')`;
                        break;
                    }
                }

                problemas.push({
                    archivo: ruta,
                    linea: i + 1,
                    columna: columna,
                    tipo: 'columna_desconocida',
                    sugerencia,
                    contexto: linea.trim()
                });
            }
        }

        /* Buscar get_post_meta */
        patronMeta.lastIndex = 0;
        while ((match = patronMeta.exec(linea)) !== null) {
            const metaKey = match[1];
            /* Los post_meta los reportamos como info, no error, ya que no tenemos PostTypeSchemas definidos aun */
            problemas.push({
                archivo: ruta,
                linea: i + 1,
                columna: metaKey,
                tipo: 'meta_sin_schema',
                sugerencia: '',
                contexto: linea.trim()
            });
        }
    }

    return problemas;
}

/* Implementación simple de distancia Levenshtein */
function levenshtein(a, b) {
    const m = a.length;
    const n = b.length;
    const dp = Array.from({ length: m + 1 }, () => Array(n + 1).fill(0));

    for (let i = 0; i <= m; i++) dp[i][0] = i;
    for (let j = 0; j <= n; j++) dp[0][j] = j;

    for (let i = 1; i <= m; i++) {
        for (let j = 1; j <= n; j++) {
            dp[i][j] = Math.min(
                dp[i - 1][j] + 1,
                dp[i][j - 1] + 1,
                dp[i - 1][j - 1] + (a[i - 1] !== b[j - 1] ? 1 : 0)
            );
        }
    }

    return dp[m][n];
}

/* Punto de entrada */
export function schemaValidate() {
    const root = getProjectRoot();
    const schemaDir = resolve(root, 'App/Config/Schema');

    if (!existsSync(schemaDir)) {
        log('No existe App/Config/Schema/. Ejecuta "npx glory schema:generate" primero.', 'error');
        return false;
    }

    log('Cargando schemas...', 'info');
    const schemas = cargarSchemas(schemaDir);

    if (schemas.size === 0) {
        log('No se encontraron schemas.', 'error');
        return false;
    }

    log(`${schemas.size} tablas cargadas: ${[...schemas.keys()].join(', ')}`, 'info');

    const columnasConocidas = todasColumnasConocidas(schemas);
    log(`${columnasConocidas.size} columnas conocidas en total`, 'info');

    /* Escanear archivos PHP */
    log('\nEscaneando archivos PHP...', 'info');
    const archivosPHP = listarPHP(root);
    log(`${archivosPHP.length} archivos PHP encontrados`, 'info');

    let totalProblemas = 0;
    let totalWarnings = 0;

    for (const archivo of archivosPHP) {
        const contenido = readFileSync(archivo, 'utf-8');
        const problemas = escanearArchivo(contenido, archivo, columnasConocidas);

        if (problemas.length > 0) {
            const rutaRelativa = relative(root, archivo);
            const errores = problemas.filter(p => p.tipo === 'columna_desconocida');
            const warnings = problemas.filter(p => p.tipo === 'meta_sin_schema');

            if (errores.length > 0) {
                console.log(`\n  ${rutaRelativa}:`);
                for (const p of errores) {
                    console.log(`    L${p.linea}: columna '${p.columna}' no existe en ningun schema${p.sugerencia}`);
                    totalProblemas++;
                }
            }

            if (warnings.length > 0) {
                for (const p of warnings) {
                    totalWarnings++;
                }
            }
        }
    }

    console.log('');
    if (totalProblemas > 0) {
        log(`${totalProblemas} columnas desconocidas encontradas.`, 'error');
        log(`Corrige usando constantes: $row[TablaCols::COLUMNA] en lugar de $row['columna']`, 'info');
    } else {
        log('Sin columnas desconocidas. Todo limpio.', 'success');
    }

    if (totalWarnings > 0) {
        log(`${totalWarnings} get_post_meta() sin PostTypeSchema (info, no error).`, 'warn');
    }

    return totalProblemas === 0;
}
