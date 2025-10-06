<?

namespace Glory\Core;

use Glory\Core\GloryLogger;

/**
 * Registro central para las definiciones de las opciones del tema.
 *
 * Esta clase actúa como un almacén estático para la configuración de todas
 * las opciones gestionadas. Su única responsabilidad es mantener las definiciones
 * en memoria para que otros servicios puedan consultarlas. No interactúa
 * con la base de datos ni contiene lógica de negocio compleja.
 */
class OpcionRegistry
{
    /**
     * @var array Almacén estático para las definiciones de las opciones.
     */
    private static array $definiciones = [];

    /**
     * Define una nueva opción y la añade al registro.
     *
     * @param string $key La clave única de la opción.
     * @param array $configuracion La configuración de la opción (valorDefault, tipo, etc.).
     */
    public static function define(string $key, array $configuracion): void
    {
        if (isset(self::$definiciones[$key])) {
            GloryLogger::warning("OpcionRegistry: La opción '{$key}' ya ha sido definida. Se omite la redefinición.");
            return;
        }

        $configuracion['hashVersionCodigo'] = self::calcularHash($configuracion['valorDefault'] ?? '');
        self::$definiciones[$key] = $configuracion;
    }

    /**
     * Obtiene la definición completa de una opción específica.
     *
     * @param string $key La clave de la opción a obtener.
     * @return array|null La configuración de la opción o null si no se encuentra.
     */
    public static function getDefinicion(string $key): ?array
    {
        return self::$definiciones[$key] ?? null;
    }

    /**
     * Obtiene todas las definiciones de opciones registradas.
     *
     * @return array Un array con todas las definiciones.
     */
    public static function getDefiniciones(): array
    {
        return self::$definiciones;
    }

    /**
     * Calcula el hash MD5 para un valor, sea escalar o un array/objeto.
     *
     * @param mixed $valor El valor a hashear.
     * @return string El hash MD5.
     */
    private static function calcularHash($valor): string
    {
        $valorParaHash = is_scalar($valor) ? (string) $valor : serialize($valor);
        return md5($valorParaHash);
    }
}
