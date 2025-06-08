<?php 

namespace Glory\Exception;

class ExcepcionComandoFallido extends \Exception
{
    protected $salidaError;
    protected $codigoSalida;

    public function __construct(string $mensaje = '', int $codigoSalida = 0, string $salidaError = '', \Throwable $previo = null)
    {
        parent::__construct($mensaje, $codigoSalida, $previo);
        $this->salidaError  = $salidaError;
        $this->codigoSalida = $codigoSalida;
    }

    public function getSalidaError(): string
    {
        return $this->salidaError;
    }

    public function getCodigoSalida(): int
    {
        return $this->codigoSalida;
    }
}
