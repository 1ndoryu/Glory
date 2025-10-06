<?

namespace Glory\Services;

use Glory\Core\GloryLogger;
use Glory\Exception\ExcepcionComandoFallido;

class ManejadorGit
{
    private function ejecutarComando(array $comando, ?string $directorioTrabajo = null, bool $lanzarExcepcion = true): array
    {
        $comandoAEjecutarOriginalArray = $comando;
        $comandoParaEjecucion          = $comando;
        $opcionesProc                  = [];
        $comandoParaLogRepresentacion  = '';

        $esWindows = (defined('WP_SYSTEM') && WP_SYSTEM === 'windows');

        if ($esWindows) {
            if (isset($comandoParaEjecucion[0]) && strtolower($comandoParaEjecucion[0]) === 'git') {
                // Usar la ruta completa a git.exe como proporcionaste.
                // Asegúrate de que esta ruta sea exactamente donde está tu git.exe.
                // Usamos barras inclinadas normales, ya que PHP y Windows suelen manejarlas bien.
                // O puedes usar dobles barras invertidas: 'C:\\Program Files\\Git\\cmd\\git.exe'
                $comandoParaEjecucion[0] = 'C:/Program Files/Git/cmd/git.exe';
            }
            $opcionesProc['bypass_shell'] = false;

            $comandoParaLogRepresentacion = implode(' ', array_map(function ($arg) {
                // Para el log, intentamos simular cómo se vería, escapando si contiene espacios.
                // Esta es una simulación simple para logging.
                if (is_array($arg))
                    return '(array)';  // No debería ocurrir aquí si el comando es un array de strings
                return strpos((string) $arg, ' ') !== false || strpos((string) $arg, '"') !== false ? '"' . addcslashes((string) $arg, '"\\') . '"' : (string) $arg;
            }, $comandoParaEjecucion));
        } else {
            $opcionesProc['bypass_shell'] = false;
            $comandoParaLogRepresentacion = implode(' ', array_map('escapeshellarg', $comandoParaEjecucion));
        }

        $bypassShellStatus = isset($opcionesProc['bypass_shell']) ? ($opcionesProc['bypass_shell'] ? 'true' : 'false') : 'default (false)';
        GloryLogger::info(
            'Intentando ejecutar comando (pasado como array a proc_open): [' . $comandoParaLogRepresentacion
            . '] en CWD: ' . ($directorioTrabajo ?: getcwd())
            . ' con bypass_shell=' . $bypassShellStatus
        );

        $descriptores = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $cwd = empty($directorioTrabajo) ? null : $directorioTrabajo;

        if (empty($comandoParaEjecucion) || empty(trim($comandoParaEjecucion[0]))) {
            $errorMsg = 'El comando ejecutable (primer elemento del array) está vacío.';
            GloryLogger::error($errorMsg);
            if ($lanzarExcepcion) {
                throw new ExcepcionComandoFallido($errorMsg, -1);
            }
            return ['exito' => false, 'salida' => '', 'error' => $errorMsg, 'codigo' => -1];
        }

        $proceso = proc_open($comandoParaEjecucion, $descriptores, $pipes, $cwd, null, $opcionesProc);

        if (!is_resource($proceso)) {
            $primerComando = $comandoParaEjecucion[0] ?? '[no command]';
            $errorMsg      = "Fallo crítico al iniciar el proceso para '{$primerComando}'. ";
            if ($esWindows) {
                $errorMsg .= "Causa común en Windows: '{$primerComando}' no se encuentra en la ruta especificada, no es ejecutable, o no hay permisos. Verifique la ruta y los permisos del sistema. Ruta intentada: '{$comandoParaEjecucion[0]}'.";
            } else {
                $errorMsg .= "Causa común: '{$primerComando}' no es ejecutable o no se encuentra en el PATH del usuario del servidor web.";
            }
            GloryLogger::error($errorMsg);
            if ($lanzarExcepcion) {
                throw new ExcepcionComandoFallido($errorMsg, -1);
            }
            return ['exito' => false, 'salida' => '', 'error' => $errorMsg, 'codigo' => -1];
        }

        $salida = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $codigoSalida = proc_close($proceso);

        $salidaLimpia = trim($salida);
        $errorLimpio  = trim($error);

        // Usar $comandoParaLogRepresentacion para una descripción más fiel de lo que se intentó ejecutar
        $logCmdDesc = "'" . $comandoParaLogRepresentacion . "'";

        $esComandoGitDiffStagedQuietOriginal = (
            count($comandoAEjecutarOriginalArray) >= 4 &&
            strtolower($comandoAEjecutarOriginalArray[0]) === 'git' &&  // Comprobar el comando original 'git'
            $comandoAEjecutarOriginalArray[1] === 'diff' &&
            $comandoAEjecutarOriginalArray[2] === '--staged' &&
            $comandoAEjecutarOriginalArray[3] === '--quiet'
        );
        $esDiffStagedConCambios              = ($esComandoGitDiffStagedQuietOriginal && $codigoSalida === 1);

        if ($codigoSalida !== 0 && !$esDiffStagedConCambios) {
            GloryLogger::error("Comando {$logCmdDesc} falló con código {$codigoSalida}. Error: {$errorLimpio}");
            if ($salidaLimpia) {
                GloryLogger::info("Salida estándar (en error) para {$logCmdDesc}: {$salidaLimpia}");
            }
            if ($lanzarExcepcion) {
                throw new ExcepcionComandoFallido(
                    "Error al ejecutar {$logCmdDesc}. Código: {$codigoSalida}.", $codigoSalida, $errorLimpio
                );
            }
            return ['exito' => false, 'salida' => $salidaLimpia, 'error' => $errorLimpio, 'codigo' => $codigoSalida];
        }

        if ($errorLimpio && $codigoSalida === 0 && !$esDiffStagedConCambios) {
            GloryLogger::info("Stderr (info no crítica, código 0) para {$logCmdDesc}: {$errorLimpio}");
        }

        GloryLogger::info("Comando {$logCmdDesc} ejecutado (código {$codigoSalida}).");
        return [
            'exito'  => ($codigoSalida === 0 || $esDiffStagedConCambios),
            'salida' => $salidaLimpia,
            'error'  => $errorLimpio,
            'codigo' => $codigoSalida
        ];
    }

    public function obtenerUrlRemota(string $nombreRemoto, string $rutaRepo): ?string
    {
        $resultado = $this->ejecutarComando(['git', 'remote', 'get-url', $nombreRemoto], $rutaRepo, false);

        if ($resultado['exito']) {
            GloryLogger::info("URL actual para '{$nombreRemoto}': " . $resultado['salida']);
            return $resultado['salida'];
        }

        GloryLogger::warning("No se pudo obtener URL para '{$nombreRemoto}'. Error: " . $resultado['error']);
        return null;
    }

    public function establecerUrlRemota(string $nombreRemoto, string $nuevaUrl, string $rutaRepo): bool
    {
        GloryLogger::info("Intentando establecer URL para '{$nombreRemoto}' a: {$nuevaUrl}");

        $resultadoSet = $this->ejecutarComando(['git', 'remote', 'set-url', $nombreRemoto, $nuevaUrl], $rutaRepo, false);

        if ($resultadoSet['exito']) {
            GloryLogger::info("URL para '{$nombreRemoto}' actualizada (set-url) correctamente.");
            return true;
        }

        GloryLogger::warning("Falló 'set-url' para '{$nombreRemoto}' (Error: {$resultadoSet['error']}). Intentando 'add'...");
        $resultadoAdd = $this->ejecutarComando(['git', 'remote', 'add', $nombreRemoto, $nuevaUrl], $rutaRepo, false);

        if ($resultadoAdd['exito']) {
            GloryLogger::info("Remote '{$nombreRemoto}' añadido con URL correcta.");
            return true;
        }

        GloryLogger::error("Falló también 'add' para '{$nombreRemoto}' (Error: {$resultadoAdd['error']}). No se pudo configurar la URL remota.");
        return false;
    }

    public function clonarOActualizarRepo(string $repoUrl, string $rutaLocal, string $ramaTrabajo): bool
    {
        GloryLogger::info("Gestionando repo {$repoUrl} en {$rutaLocal} (rama: {$ramaTrabajo})");

        $gitDir     = $rutaLocal . DIRECTORY_SEPARATOR . '.git';
        $repoExiste = is_dir($gitDir);

        if (!$repoExiste) {
            GloryLogger::info("Repositorio no encontrado. Clonando desde {$repoUrl}...");
            if (file_exists($rutaLocal)) {
                GloryLogger::warning("Ruta {$rutaLocal} existe pero no es repo Git. Se intentará eliminar.");
                // TODO: Implementar una función de borrado recursivo segura en PHP si es necesario,
                // o asegurarse de que los permisos permitan al usuario del servidor web hacerlo.
            }

            $directorioPadre = dirname($rutaLocal);
            if (!is_dir($directorioPadre)) {
                mkdir($directorioPadre, 0775, true);
            }

            $resultadoClone = $this->ejecutarComando(['git', 'clone', $repoUrl, $rutaLocal], null, false);
            if (!$resultadoClone['exito']) {
                GloryLogger::error('Falló la clonación: ' . $resultadoClone['error']);
                return false;
            }
            GloryLogger::info('Repositorio clonado.');
        }

        GloryLogger::info("Verificando URL del remote 'origin'...");
        $urlActual = $this->obtenerUrlRemota('origin', $rutaLocal);
        if (!$urlActual || $urlActual !== $repoUrl) {
            $logMsg = $urlActual ? "URL de 'origin' difiere ({$urlActual}). Corrigiendo..." : "Remote 'origin' no encontrado o sin URL. Estableciendo...";
            GloryLogger::warning($logMsg);
            if (!$this->establecerUrlRemota('origin', $repoUrl, $rutaLocal))
                return false;
        }

        GloryLogger::info('Limpiando estado local (fetch, checkout, reset, clean)...');

        $fetchRes = $this->ejecutarComando(['git', 'fetch', 'origin'], $rutaLocal, false);
        if (!$fetchRes['exito']) {
            GloryLogger::warning("Primer 'git fetch' falló: {$fetchRes['error']}. Intentando con 'prune'...");
            $this->ejecutarComando(['git', 'remote', 'prune', 'origin'], $rutaLocal, false);
            $fetchRes = $this->ejecutarComando(['git', 'fetch', 'origin'], $rutaLocal, false);
            if (!$fetchRes['exito']) {
                GloryLogger::error("'git fetch' falló incluso después de 'prune'. Error: {$fetchRes['error']}. Abortando.");
                return false;
            }
        }
        GloryLogger::info("'git fetch origin' exitoso.");

        $ramaPrincipal = $this->determinarRamaPrincipalRemota($rutaLocal) ?: 'main';

        if (!$this->ejecutarComando(['git', 'checkout', $ramaPrincipal], $rutaLocal, false)['exito']) {
            GloryLogger::warning("Checkout a '{$ramaPrincipal}' falló. Intentando 'master' como fallback...");
            if (!$this->ejecutarComando(['git', 'checkout', 'master'], $rutaLocal, false)['exito']) {
                GloryLogger::error("Falló checkout a '{$ramaPrincipal}' y a 'master'. Abortando.");
                return false;
            }
            $ramaPrincipal = 'master';
        }

        if (!$this->ejecutarComando(['git', 'reset', '--hard', "origin/{$ramaPrincipal}"], $rutaLocal, false)['exito']) {
            GloryLogger::error("Falló 'git reset --hard origin/{$ramaPrincipal}'. Repo podría estar inconsistente.");
            return false;
        }

        $this->ejecutarComando(['git', 'clean', '-fdx'], $rutaLocal, false);

        GloryLogger::info("Asegurando que estemos en la rama de trabajo '{$ramaTrabajo}'...");
        return $this->asegurarRamaDeTrabajo($rutaLocal, $ramaTrabajo, $ramaPrincipal);
    }

    public function hacerCommit(string $rutaRepo, string $mensaje): bool
    {
        GloryLogger::info("Intentando commit en {$rutaRepo}");

        if (!$this->ejecutarComando(['git', 'add', '-A'], $rutaRepo, false)['exito']) {
            GloryLogger::error("Falló 'git add -A'.");
            return false;
        }

        $statusRes = $this->ejecutarComando(['git', 'status', '--porcelain'], $rutaRepo, false);
        if (!$statusRes['exito'] || empty($statusRes['salida'])) {
            GloryLogger::warning('No hay cambios detectados para commitear.');
            return false;
        }

        GloryLogger::info("Realizando commit con mensaje: '{$mensaje}'");
        $commitRes = $this->ejecutarComando(['git', 'commit', '-m', $mensaje], $rutaRepo, false);

        if (!$commitRes['exito']) {
            GloryLogger::error("El comando 'git commit' falló. Error: " . $commitRes['error']);
            return false;
        }

        if ($this->commitTuvoCambiosReales($rutaRepo)) {
            GloryLogger::info('Commit realizado con éxito y tuvo cambios reales.');
            return true;
        }

        GloryLogger::warning("'git commit' se ejecutó, pero no resultó en cambios efectivos (commit vacío).");
        // TODO: Considerar revertir el commit vacío si es el comportamiento deseado.
        // $this->revertirCommitVacio($rutaRepo);
        return false;
    }

    public function hacerPush(string $rutaRepo, string $rama, bool $establecerUpstream = false): bool
    {
        GloryLogger::info("Intentando push de rama '{$rama}' a 'origin'...");

        $comando = ['git', 'push'];
        if ($establecerUpstream) {
            array_push($comando, '--set-upstream', 'origin', $rama);
        } else {
            array_push($comando, 'origin', $rama);
        }

        $resultado = $this->ejecutarComando($comando, $rutaRepo, false);
        if (!$resultado['exito']) {
            GloryLogger::error("Falló 'git push' para rama '{$rama}'. Error: " . $resultado['error']);
            return false;
        }

        GloryLogger::info("Push de rama '{$rama}' a origin realizado con éxito.");
        return true;
    }

    public function descartarCambiosLocales(string $rutaRepo): bool
    {
        GloryLogger::warning("¡ATENCIÓN! Descartando todos los cambios locales en {$rutaRepo}...");

        $resetOk = $this->ejecutarComando(['git', 'reset', '--hard', 'HEAD'], $rutaRepo, false)['exito'];
        $cleanOk = $this->ejecutarComando(['git', 'clean', '-fdx'], $rutaRepo, false)['exito'];

        if ($resetOk && $cleanOk) {
            GloryLogger::info('Cambios locales descartados con éxito.');
            return true;
        }

        GloryLogger::error('Falló al descartar cambios. Reset: ' . ($resetOk ? 'OK' : 'FALLO') . ', Clean: ' . ($cleanOk ? 'OK' : 'FALLO'));
        return false;
    }

    public function obtenerArchivosModificados(string $rutaRepo): ?array
    {
        $resultado = $this->ejecutarComando(['git', 'status', '--porcelain'], $rutaRepo, false);

        if (!$resultado['exito']) {
            GloryLogger::error("Falló 'git status --porcelain': " . $resultado['error']);
            return null;
        }

        if (empty($resultado['salida'])) {
            GloryLogger::info('No hay cambios detectados.');
            return [];
        }

        $archivos = [];
        $lineas   = explode("\n", $resultado['salida']);

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea))
                continue;

            $ruta = substr($linea, 3);

            if (strpos($linea, '->') !== false) {
                list(, $ruta) = explode(' -> ', $ruta);
            }

            if (substr($ruta, 0, 1) == '"') {
                $ruta = substr($ruta, 1, -1);
            }

            $archivos[] = $ruta;
        }

        return array_unique($archivos);
    }

    private function obtenerRamaActual(string $rutaRepo): ?string
    {
        $res = $this->ejecutarComando(['git', 'branch', '--show-current'], $rutaRepo, false);
        if ($res['exito'] && !empty($res['salida'])) {
            return $res['salida'];
        }

        $resFallback = $this->ejecutarComando(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $rutaRepo, false);
        if ($resFallback['exito'] && $resFallback['salida'] !== 'HEAD') {
            return $resFallback['salida'];
        }

        GloryLogger::warning("Repositorio en estado 'detached HEAD' o no se pudo determinar la rama.");
        return null;
    }

    private function commitTuvoCambiosReales(string $rutaRepo): bool
    {
        $resPadre = $this->ejecutarComando(['git', 'rev-parse', 'HEAD~1'], $rutaRepo, false);
        if (!$resPadre['exito']) {
            GloryLogger::info('No se encontró HEAD~1 (probablemente primer commit). Asumiendo cambios reales.');
            return true;
        }

        $resDiff = $this->ejecutarComando(['git', 'diff', 'HEAD~1', 'HEAD', '--quiet'], $rutaRepo, false);

        return $resDiff['codigo'] === 1;
    }

    private function determinarRamaPrincipalRemota(string $rutaRepo): ?string
    {
        $resShow = $this->ejecutarComando(['git', 'remote', 'show', 'origin'], $rutaRepo, false);
        if ($resShow['exito']) {
            preg_match('/HEAD branch:\s*(\S+)/', $resShow['salida'], $matches);
            if (isset($matches[1]) && !in_array($matches[1], ['(unknown)', '(ninguna)'])) {
                GloryLogger::info("Rama principal detectada (vía remote show): '{$matches[1]}'");
                return $matches[1];
            }
        }

        $resRef = $this->ejecutarComando(['git', 'symbolic-ref', 'refs/remotes/origin/HEAD'], $rutaRepo, false);
        if ($resRef['exito'] && !empty($resRef['salida'])) {
            $partes        = explode('/', $resRef['salida']);
            $ramaDetectada = end($partes);
            GloryLogger::info("Rama principal detectada (vía symbolic-ref): '{$ramaDetectada}'");
            return $ramaDetectada;
        }

        GloryLogger::warning("No se pudo determinar la rama principal remota. Se usará 'main' por defecto.");
        return null;
    }

    private function asegurarRamaDeTrabajo(string $rutaRepo, string $ramaTrabajo, string $ramaBase): bool
    {
        $resLocal    = $this->ejecutarComando(['git', 'branch', '--list', $ramaTrabajo], $rutaRepo, false);
        $existeLocal = $resLocal['exito'] && !empty($resLocal['salida']);

        $resRemota    = $this->ejecutarComando(['git', 'ls-remote', '--exit-code', '--heads', 'origin', $ramaTrabajo], $rutaRepo, false);
        $existeRemota = $resRemota['exito'];

        if ($existeLocal) {
            GloryLogger::info("Cambiando a rama local existente '{$ramaTrabajo}'.");
            $this->ejecutarComando(['git', 'checkout', $ramaTrabajo], $rutaRepo, false);
            if ($existeRemota) {
                GloryLogger::info("Actualizando '{$ramaTrabajo}' desde 'origin/{$ramaTrabajo}'.");
                $this->ejecutarComando(['git', 'reset', '--hard', "origin/{$ramaTrabajo}"], $rutaRepo, false);
            }
            return true;
        }

        if ($existeRemota) {
            GloryLogger::info("Creando rama local '{$ramaTrabajo}' desde 'origin/{$ramaTrabajo}'.");
            $resCheckout = $this->ejecutarComando(['git', 'checkout', '-b', $ramaTrabajo, "origin/{$ramaTrabajo}"], $rutaRepo, false);
            return $resCheckout['exito'];
        }

        GloryLogger::info("Creando nueva rama '{$ramaTrabajo}' desde '{$ramaBase}'.");
        $resCrear = $this->ejecutarComando(['git', 'checkout', '-b', $ramaTrabajo, $ramaBase], $rutaRepo, false);
        if (!$resCrear['exito']) {
            GloryLogger::error("No se pudo crear la rama '{$ramaTrabajo}' desde '{$ramaBase}'.");
            return false;
        }

        return true;
    }
}
