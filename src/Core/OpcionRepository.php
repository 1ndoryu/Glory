<?

namespace Glory\Core;

/**
 * Gestiona el acceso a la base de datos para las opciones del tema.
 *
 * Esta clase implementa el patrón Repository para abstraer la capa de persistencia
 * (la tabla de opciones de WordPress) del resto de la aplicación. Es la única
 * clase que debe interactuar directamente con get_option, update_option y
 * delete_option para las opciones de Glory, manejando los prefijos y
 * sufijos de las claves de forma interna.
 */
class OpcionRepository
{
    private const OPCION_PREFIJO = 'glory_opcion_';
    private const META_HASH_CODIGO_SUFIJO = '_code_hash_on_save';
    private const META_PANEL_GUARDADO_SUFIJO = '_is_panel_value';

    /**
     * @var \stdClass Un objeto único para usar como valor por defecto que no
     * existe en la base de datos, permitiendo distinguir un 'no encontrado'
     * de un valor almacenado como null, false o cadena vacía.
     */
    private static ?\stdClass $centinelaBd = null;

    /**
     * Inicializa las propiedades estáticas de la clase.
     */
    private static function initEstatico(): void
    {
        if (self::$centinelaBd === null) {
            self::$centinelaBd = new \stdClass();
        }
    }

    /**
     * Obtiene el valor principal de una opción desde la base de datos.
     *
     * @param string $key La clave base de la opción.
     * @return mixed El valor de la opción, o el objeto centinela si no existe.
     */
    public static function get(string $key)
    {
        self::initEstatico();
        $nombreOpcion = self::getNombreOpcion($key);
        return get_option($nombreOpcion, self::$centinelaBd);
    }

    /**
     * Guarda el valor principal de una opción en la base de datos.
     *
     * @param string $key La clave base de la opción.
     * @param mixed $valor El valor a guardar.
     * @return bool True si el valor fue actualizado, false en caso contrario.
     */
    public static function save(string $key, $valor): bool
    {
        $nombreOpcion = self::getNombreOpcion($key);
        return update_option($nombreOpcion, $valor);
    }

    /**
     * Obtiene todos los metadatos asociados a una opción.
     *
     * @param string $key La clave base de la opción.
     * @return array Un array con 'esPanel' y 'hashPanel'.
     */
    public static function getPanelMeta(string $key): array
    {
        self::initEstatico();
        $nombreOpcion = self::getNombreOpcion($key);
        return [
            'esPanel' => get_option($nombreOpcion . self::META_PANEL_GUARDADO_SUFIJO, false),
            'hashPanel' => get_option($nombreOpcion . self::META_HASH_CODIGO_SUFIJO, self::$centinelaBd),
        ];
    }

    /**
     * Guarda los metadatos de una opción que ha sido guardada desde el panel.
     *
     * @param string $key La clave base de la opción.
     * @param string $hash El hash de la versión del código del valor por defecto en ese momento.
     */
    public static function savePanelMeta(string $key, string $hash): void
    {
        $nombreOpcion = self::getNombreOpcion($key);
        update_option($nombreOpcion . self::META_PANEL_GUARDADO_SUFIJO, true);
        update_option($nombreOpcion . self::META_HASH_CODIGO_SUFIJO, $hash);
    }

    /**
     * Elimina todos los metadatos de panel asociados a una opción.
     * Se usa al revertir una opción a su valor por defecto.
     *
     * @param string $key La clave base de la opción.
     */
    public static function deletePanelMeta(string $key): void
    {
        $nombreOpcion = self::getNombreOpcion($key);
        delete_option($nombreOpcion . self::META_PANEL_GUARDADO_SUFIJO);
        delete_option($nombreOpcion . self::META_HASH_CODIGO_SUFIJO);
    }

    /**
     * Devuelve el objeto centinela para comparaciones.
     *
     * @return \stdClass
     */
    public static function getCentinela(): \stdClass
    {
        self::initEstatico();
        return self::$centinelaBd;
    }

    /**
     * Construye el nombre completo de la opción para la base de datos.
     *
     * @param string $key La clave base.
     * @return string El nombre completo con prefijo.
     */
    private static function getNombreOpcion(string $key): string
    {
        return self::OPCION_PREFIJO . $key;
    }
}
