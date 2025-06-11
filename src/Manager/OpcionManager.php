<?

namespace Glory\Manager;

use Glory\Core\GloryLogger;
use Glory\Core\OpcionRegistry;
use Glory\Core\OpcionRepository;

/**
 * Gestiona el acceso en tiempo de ejecución a los valores de las opciones.
 *
 * Actúa como un Service/Facade que utiliza OpcionRegistry para las definiciones
 * y OpcionRepository para el acceso a datos. Su única responsabilidad es
 * proveer una API simple y segura para OBTENER los valores de las opciones
 * desde cualquier parte de la aplicación.
 *
 * La lógica de registro y sincronización fue movida a Glory\Core\OpcionConfigurator.
 */
class OpcionManager
{
    /**
     * Obtiene el valor de una opción. Este es el getter principal y único.
     * La opción debe estar previamente definida mediante OpcionConfigurator::register().
     *
     * @param string $key La clave única de la opción.
     * @param mixed|null $valorPorDefecto Opcional. Valor a devolver si la opción no tiene un valor guardado.
     * Si es null, se usará el 'valorDefault' de la definición.
     * @return mixed El valor de la opción.
     */
    public static function get(string $key, $valorPorDefecto = null)
    {
        $config = OpcionRegistry::getDefinicion($key);

        if (!$config) {
            GloryLogger::warning("OpcionManager: Se intentó obtener la opción no definida '{$key}'. Es necesario definirla con OpcionConfigurator::register().");
            return $valorPorDefecto;
        }
        
        $valorObtenido = OpcionRepository::get($key);

        if ($valorObtenido === OpcionRepository::getCentinela()) {
            $valorFinal = $valorPorDefecto ?? $config['valorDefault'];
        } else {
            $valorFinal = $valorObtenido;
        }

        $debeEscapar = $config['comportamientoEscape'] ?? false;

        if (is_string($valorFinal) && $debeEscapar) {
            return esc_html($valorFinal);
        }

        return $valorFinal;
    }

    public static function texto(string $key, string $default = ''): string
    {
        return (string) self::get($key, $default);
    }

    public static function richText(string $key, string $default = ''): string
    {
        $valor = self::get($key, $default);
        return wp_kses_post((string)$valor);
    }

    public static function imagen(string $key, string $default = ''): string
    {
        return (string) self::get($key, $default);
    }

    public static function menu(string $key, array $default = []): array
    {
        $valor = self::get($key, $default);
        return is_array($valor) ? $valor : $default;
    }
}