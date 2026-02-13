<?php

namespace Glory\Tools;

use Glory\Core\GloryLogger;

/*
 * Responsabilidad: ejecutar comandos CLI de forma segura (proc_open).
 * Maneja diferencias entre Windows y Unix, logging,
 * y detección de comandos especiales (ej. git diff --quiet).
 * Extraído de ManejadorGit para cumplir SRP (max 300 líneas).
 */
class GitCommandRunner
{
    public function ejecutar(array $comando, ?string $directorioTrabajo = null, bool $lanzarExcepcion = true): array
    {
        $comandoOriginal = $comando;
        $opcionesProc = [];
        $esWindows = (defined('WP_SYSTEM') && WP_SYSTEM === 'windows');

        if ($esWindows && isset($comando[0]) && strtolower($comando[0]) === 'git') {
            $comando[0] = 'C:/Program Files/Git/cmd/git.exe';
        }
        $opcionesProc['bypass_shell'] = false;

        $logCmd = $esWindows
            ? implode(' ', array_map(fn($a) => (strpos((string)$a, ' ') !== false ? '"' . addcslashes((string)$a, '"\\') . '"' : (string)$a), $comando))
            : implode(' ', array_map('escapeshellarg', $comando));

        GloryLogger::info("Ejecutando: [{$logCmd}] en CWD: " . ($directorioTrabajo ?: getcwd()));

        $descriptores = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $cwd = empty($directorioTrabajo) ? null : $directorioTrabajo;

        if (empty($comando) || empty(trim($comando[0]))) {
            $msg = 'El comando ejecutable está vacío.';
            GloryLogger::error($msg);
            if ($lanzarExcepcion) {
                throw new \RuntimeException($msg, -1);
            }
            return ['exito' => false, 'salida' => '', 'error' => $msg, 'codigo' => -1];
        }

        $proceso = proc_open($comando, $descriptores, $pipes, $cwd, null, $opcionesProc);

        if (!is_resource($proceso)) {
            $msg = "Fallo al iniciar proceso '{$comando[0]}'. Verifique ruta y permisos.";
            GloryLogger::error($msg);
            if ($lanzarExcepcion) {
                throw new \RuntimeException($msg, -1);
            }
            return ['exito' => false, 'salida' => '', 'error' => $msg, 'codigo' => -1];
        }

        $salida = trim(stream_get_contents($pipes[1]));
        fclose($pipes[1]);
        $error = trim(stream_get_contents($pipes[2]));
        fclose($pipes[2]);
        $codigo = proc_close($proceso);

        /* git diff --staged --quiet retorna 1 cuando hay cambios (no es un error) */
        $esDiffConCambios = (
            count($comandoOriginal) >= 4
            && strtolower($comandoOriginal[0]) === 'git'
            && $comandoOriginal[1] === 'diff'
            && $comandoOriginal[2] === '--staged'
            && $comandoOriginal[3] === '--quiet'
            && $codigo === 1
        );

        if ($codigo !== 0 && !$esDiffConCambios) {
            GloryLogger::error("Comando [{$logCmd}] falló (código {$codigo}): {$error}");
            if ($lanzarExcepcion) {
                throw new \RuntimeException("Error: [{$logCmd}] código {$codigo}. {$error}", $codigo);
            }
            return ['exito' => false, 'salida' => $salida, 'error' => $error, 'codigo' => $codigo];
        }

        if ($error && $codigo === 0) {
            GloryLogger::info("Stderr (no crítico) [{$logCmd}]: {$error}");
        }

        GloryLogger::info("Comando [{$logCmd}] OK (código {$codigo}).");
        return [
            'exito'  => ($codigo === 0 || $esDiffConCambios),
            'salida' => $salida,
            'error'  => $error,
            'codigo' => $codigo,
        ];
    }
}
