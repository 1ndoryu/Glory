<?php 

namespace Glory\Exception;

/**
 * Excepción personalizada para errores durante la ejecución de comandos externos.
 *
 * Permite capturar no solo el mensaje de error, sino también la salida
 * específica del error del comando y su código de salida.
 *
 * @author @wandorius
 * @tarea Jules: Añadidos DocBlocks y revisión general.
 */
class ExcepcionComandoFallido extends \Exception
{
    /** @var string La salida de error literal del comando. */
    protected $salidaError;
    /** @var int El código de salida del comando. */
    protected $codigoSalida;

    /**
     * Constructor de ExcepcionComandoFallido.
     *
     * @param string $mensaje El mensaje de la excepción.
     * @param int $codigoSalida El código de salida del comando fallido.
     * @param string $salidaError La salida de error literal del comando.
     * @param \Throwable|null $previo La excepción previa, si existe.
     */
    public function __construct(string $mensaje = '', int $codigoSalida = 0, string $salidaError = '', ?\Throwable $previo = null)
    {
        parent::__construct($mensaje, $codigoSalida, $previo); // El código de la excepción se pasa como segundo argumento a \Exception
        $this->salidaError  = $salidaError;
        $this->codigoSalida = $codigoSalida; // Se guarda específicamente para este tipo de excepción
    }

    /**
     * Obtiene la salida de error literal del comando.
     *
     * @return string
     */
    public function getSalidaError(): string
    {
        return $this->salidaError;
    }

    /**
     * Obtiene el código de salida del comando.
     *
     * @return int
     */
    public function getCodigoSalida(): int
    {
        return $this->codigoSalida;
    }
}
