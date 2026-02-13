<?php

namespace Glory\Tools;

use Glory\Core\GloryLogger;

/*
 * Operaciones Git de alto nivel: clonar, commit, push, branch management.
 * Delega la ejecucion de comandos a GitCommandRunner.
 */
class ManejadorGit
{
    private GitCommandRunner $runner;

    public function __construct()
    {
        $this->runner = new GitCommandRunner();
    }

    private function cmd(array $comando, ?string $cwd = null, bool $throw = true): array
    {
        return $this->runner->ejecutar($comando, $cwd, $throw);
    }

    public function obtenerUrlRemota(string $nombreRemoto, string $rutaRepo): ?string
    {
        $resultado = $this->cmd(['git', 'remote', 'get-url', $nombreRemoto], $rutaRepo, false);
        if ($resultado['exito']) {
            GloryLogger::info("URL actual para '{$nombreRemoto}': " . $resultado['salida']);
            return $resultado['salida'];
        }
        GloryLogger::warning("No se pudo obtener URL para '{$nombreRemoto}'. Error: " . $resultado['error']);
        return null;
    }

    public function establecerUrlRemota(string $nombreRemoto, string $nuevaUrl, string $rutaRepo): bool
    {
        GloryLogger::info("Estableciendo URL para '{$nombreRemoto}' a: {$nuevaUrl}");
        $resultadoSet = $this->cmd(['git', 'remote', 'set-url', $nombreRemoto, $nuevaUrl], $rutaRepo, false);
        if ($resultadoSet['exito']) {
            GloryLogger::info("URL para '{$nombreRemoto}' actualizada correctamente.");
            return true;
        }

        GloryLogger::warning("Fallo set-url para '{$nombreRemoto}'. Intentando add...");
        $resultadoAdd = $this->cmd(['git', 'remote', 'add', $nombreRemoto, $nuevaUrl], $rutaRepo, false);
        if ($resultadoAdd['exito']) {
            GloryLogger::info("Remote '{$nombreRemoto}' agregado con URL correcta.");
            return true;
        }

        GloryLogger::error("Fallo add para '{$nombreRemoto}'. No se pudo configurar la URL remota.");
        return false;
    }

    public function clonarOActualizarRepo(string $repoUrl, string $rutaLocal, string $ramaTrabajo): bool
    {
        GloryLogger::info("Gestionando repo {$repoUrl} en {$rutaLocal} (rama: {$ramaTrabajo})");
        $gitDir = $rutaLocal . DIRECTORY_SEPARATOR . '.git';
        $repoExiste = is_dir($gitDir);

        if (!$repoExiste) {
            GloryLogger::info("Repositorio no encontrado. Clonando desde {$repoUrl}...");
            if (file_exists($rutaLocal)) {
                GloryLogger::warning("Ruta {$rutaLocal} existe pero no es repo Git.");
            }
            $directorioPadre = dirname($rutaLocal);
            if (!is_dir($directorioPadre)) {
                mkdir($directorioPadre, 0775, true);
            }
            $resultadoClone = $this->cmd(['git', 'clone', $repoUrl, $rutaLocal], null, false);
            if (!$resultadoClone['exito']) {
                GloryLogger::error('Fallo la clonacion: ' . $resultadoClone['error']);
                return false;
            }
            GloryLogger::info('Repositorio clonado.');
        }

        GloryLogger::info("Verificando URL del remote 'origin'...");
        $urlActual = $this->obtenerUrlRemota('origin', $rutaLocal);
        if (!$urlActual || $urlActual !== $repoUrl) {
            $logMsg = $urlActual ? "URL de 'origin' difiere ({$urlActual}). Corrigiendo..." : "Remote 'origin' no encontrado. Estableciendo...";
            GloryLogger::warning($logMsg);
            if (!$this->establecerUrlRemota('origin', $repoUrl, $rutaLocal)) {
                return false;
            }
        }

        GloryLogger::info('Limpiando estado local (fetch, checkout, reset, clean)...');
        $fetchRes = $this->cmd(['git', 'fetch', 'origin'], $rutaLocal, false);
        if (!$fetchRes['exito']) {
            GloryLogger::warning("Primer git fetch fallo. Intentando con prune...");
            $this->cmd(['git', 'remote', 'prune', 'origin'], $rutaLocal, false);
            $fetchRes = $this->cmd(['git', 'fetch', 'origin'], $rutaLocal, false);
            if (!$fetchRes['exito']) {
                GloryLogger::error("git fetch fallo tras prune. Error: {$fetchRes['error']}. Abortando.");
                return false;
            }
        }

        $ramaPrincipal = $this->determinarRamaPrincipalRemota($rutaLocal) ?: 'main';
        if (!$this->cmd(['git', 'checkout', $ramaPrincipal], $rutaLocal, false)['exito']) {
            GloryLogger::warning("Checkout a '{$ramaPrincipal}' fallo. Intentando 'master'...");
            if (!$this->cmd(['git', 'checkout', 'master'], $rutaLocal, false)['exito']) {
                GloryLogger::error("Fallo checkout a '{$ramaPrincipal}' y 'master'. Abortando.");
                return false;
            }
            $ramaPrincipal = 'master';
        }

        if (!$this->cmd(['git', 'reset', '--hard', "origin/{$ramaPrincipal}"], $rutaLocal, false)['exito']) {
            GloryLogger::error("Fallo git reset --hard origin/{$ramaPrincipal}.");
            return false;
        }

        $this->cmd(['git', 'clean', '-fdx'], $rutaLocal, false);
        GloryLogger::info("Asegurando rama de trabajo '{$ramaTrabajo}'...");
        return $this->asegurarRamaDeTrabajo($rutaLocal, $ramaTrabajo, $ramaPrincipal);
    }

    public function hacerCommit(string $rutaRepo, string $mensaje): bool
    {
        GloryLogger::info("Intentando commit en {$rutaRepo}");
        if (!$this->cmd(['git', 'add', '-A'], $rutaRepo, false)['exito']) {
            GloryLogger::error("Fallo git add -A.");
            return false;
        }

        $statusRes = $this->cmd(['git', 'status', '--porcelain'], $rutaRepo, false);
        if (!$statusRes['exito'] || empty($statusRes['salida'])) {
            GloryLogger::warning('No hay cambios detectados para commitear.');
            return false;
        }

        GloryLogger::info("Realizando commit: '{$mensaje}'");
        $commitRes = $this->cmd(['git', 'commit', '-m', $mensaje], $rutaRepo, false);
        if (!$commitRes['exito']) {
            GloryLogger::error("git commit fallo: " . $commitRes['error']);
            return false;
        }

        if ($this->commitTuvoCambiosReales($rutaRepo)) {
            GloryLogger::info('Commit realizado con cambios reales.');
            return true;
        }

        GloryLogger::warning("git commit ejecutado pero sin cambios efectivos (commit vacio).");
        return false;
    }

    public function hacerPush(string $rutaRepo, string $rama, bool $establecerUpstream = false): bool
    {
        GloryLogger::info("Intentando push de rama '{$rama}' a origin...");
        $comando = ['git', 'push'];
        if ($establecerUpstream) {
            array_push($comando, '--set-upstream', 'origin', $rama);
        } else {
            array_push($comando, 'origin', $rama);
        }

        $resultado = $this->cmd($comando, $rutaRepo, false);
        if (!$resultado['exito']) {
            GloryLogger::error("Fallo git push para rama '{$rama}': " . $resultado['error']);
            return false;
        }
        GloryLogger::info("Push de '{$rama}' a origin exitoso.");
        return true;
    }

    public function descartarCambiosLocales(string $rutaRepo): bool
    {
        GloryLogger::warning("Descartando todos los cambios locales en {$rutaRepo}...");
        $resetOk = $this->cmd(['git', 'reset', '--hard', 'HEAD'], $rutaRepo, false)['exito'];
        $cleanOk = $this->cmd(['git', 'clean', '-fdx'], $rutaRepo, false)['exito'];

        if ($resetOk && $cleanOk) {
            GloryLogger::info('Cambios locales descartados.');
            return true;
        }
        GloryLogger::error('Fallo al descartar cambios. Reset: ' . ($resetOk ? 'OK' : 'FALLO') . ', Clean: ' . ($cleanOk ? 'OK' : 'FALLO'));
        return false;
    }

    public function obtenerArchivosModificados(string $rutaRepo): ?array
    {
        $resultado = $this->cmd(['git', 'status', '--porcelain'], $rutaRepo, false);
        if (!$resultado['exito']) {
            GloryLogger::error("Fallo git status --porcelain: " . $resultado['error']);
            return null;
        }
        if (empty($resultado['salida'])) {
            return [];
        }

        $archivos = [];
        foreach (explode("\n", $resultado['salida']) as $linea) {
            $linea = trim($linea);
            if (empty($linea)) {
                continue;
            }
            $ruta = substr($linea, 3);
            if (strpos($linea, '->') !== false) {
                [, $ruta] = explode(' -> ', $ruta);
            }
            if (str_starts_with($ruta, '"')) {
                $ruta = substr($ruta, 1, -1);
            }
            $archivos[] = $ruta;
        }
        return array_unique($archivos);
    }

    private function obtenerRamaActual(string $rutaRepo): ?string
    {
        $res = $this->cmd(['git', 'branch', '--show-current'], $rutaRepo, false);
        if ($res['exito'] && !empty($res['salida'])) {
            return $res['salida'];
        }
        $resFallback = $this->cmd(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $rutaRepo, false);
        if ($resFallback['exito'] && $resFallback['salida'] !== 'HEAD') {
            return $resFallback['salida'];
        }
        GloryLogger::warning("Repositorio en estado detached HEAD.");
        return null;
    }

    private function commitTuvoCambiosReales(string $rutaRepo): bool
    {
        $resPadre = $this->cmd(['git', 'rev-parse', 'HEAD~1'], $rutaRepo, false);
        if (!$resPadre['exito']) {
            return true;
        }
        $resDiff = $this->cmd(['git', 'diff', 'HEAD~1', 'HEAD', '--quiet'], $rutaRepo, false);
        return $resDiff['codigo'] === 1;
    }

    private function determinarRamaPrincipalRemota(string $rutaRepo): ?string
    {
        $resShow = $this->cmd(['git', 'remote', 'show', 'origin'], $rutaRepo, false);
        if ($resShow['exito']) {
            preg_match('/HEAD branch:\s*(\S+)/', $resShow['salida'], $matches);
            if (isset($matches[1]) && !in_array($matches[1], ['(unknown)', '(ninguna)'])) {
                return $matches[1];
            }
        }
        $resRef = $this->cmd(['git', 'symbolic-ref', 'refs/remotes/origin/HEAD'], $rutaRepo, false);
        if ($resRef['exito'] && !empty($resRef['salida'])) {
            $partes = explode('/', $resRef['salida']);
            return end($partes);
        }
        GloryLogger::warning("No se pudo determinar rama principal remota. Usando 'main' por defecto.");
        return null;
    }

    private function asegurarRamaDeTrabajo(string $rutaRepo, string $ramaTrabajo, string $ramaBase): bool
    {
        $resLocal = $this->cmd(['git', 'branch', '--list', $ramaTrabajo], $rutaRepo, false);
        $existeLocal = $resLocal['exito'] && !empty($resLocal['salida']);
        $resRemota = $this->cmd(['git', 'ls-remote', '--exit-code', '--heads', 'origin', $ramaTrabajo], $rutaRepo, false);
        $existeRemota = $resRemota['exito'];

        if ($existeLocal) {
            $this->cmd(['git', 'checkout', $ramaTrabajo], $rutaRepo, false);
            if ($existeRemota) {
                $this->cmd(['git', 'reset', '--hard', "origin/{$ramaTrabajo}"], $rutaRepo, false);
            }
            return true;
        }
        if ($existeRemota) {
            $resCheckout = $this->cmd(['git', 'checkout', '-b', $ramaTrabajo, "origin/{$ramaTrabajo}"], $rutaRepo, false);
            return $resCheckout['exito'];
        }

        GloryLogger::info("Creando nueva rama '{$ramaTrabajo}' desde '{$ramaBase}'.");
        $resCrear = $this->cmd(['git', 'checkout', '-b', $ramaTrabajo, $ramaBase], $rutaRepo, false);
        if (!$resCrear['exito']) {
            GloryLogger::error("No se pudo crear la rama '{$ramaTrabajo}' desde '{$ramaBase}'.");
            return false;
        }
        return true;
    }
}
