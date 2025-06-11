<?php
namespace Glory\Manager;

use Glory\Core\GloryLogger;
use Glory\Helper\ScheduleManager;

/**
 * Gestor de Opciones Configurables del Tema/Plugin.
 *
 * OpcionManager facilita el registro, la obtención y la gestión de opciones o ajustes
 * que son configurables a través de código (valores por defecto) y potencialmente
 * a través de un panel de administración (guardados como opciones de WordPress).
 *
 * Características Principales:
 * - Registro de opciones con tipos, valores por defecto, y metadatos para paneles.
 * - Sincronización inteligente entre valores por defecto definidos en código y valores en la base de datos.
 * - Detección de cambios en los valores por defecto del código mediante hashes.
 * - Manejo de escenarios donde un valor es explícitamente guardado por un panel de administración,
 *   permitiendo que estos valores prevalezcan sobre los defaults del código a menos que el default del código cambie.
 * - Registro "al vuelo" para obtener valores sin registro explícito previo, útil para configuraciones simples.
 * - Métodos de conveniencia para obtener tipos comunes de opciones (texto, HTML enriquecido, imágenes, horarios).
 * - Capacidad de resetear todas las opciones de una sección a sus valores por defecto.
 *
 * El objetivo es proporcionar un sistema robusto donde los valores por defecto se definen en el código
 * (control de versiones, despliegue fácil) pero pueden ser sobrescritos por un usuario a través de un panel,
 * y el sistema maneja conflictos o actualizaciones de manera predecible.
 *
 * @author @wandorius
 * @tarea Jules: Corregidos errores de método indefinido (getLastMessage) y propiedad indefinida (contenidoRegistrado).
 * // @tarea Jules: Evaluar si la gestión de la UI (panel de opciones inferido) y la persistencia de datos en OpcionManager
 * // podrían separarse más claramente si se desarrolla un panel de administración complejo.
 */
class OpcionManager {

	const OPCION_PREFIJO = 'glory_opcion_'; // Prefijo para las opciones guardadas en la BD. (Actualizado)
	const META_HASH_CODIGO_SUFIJO = '_code_hash_on_save'; // Sufijo para el meta que guarda el hash del valor por defecto del código al momento de guardar desde un panel.
	const META_PANEL_GUARDADO_SUFIJO = '_is_panel_value'; // Sufijo para el meta flag que indica si el valor fue guardado desde un panel de administración.

	/** @var array Almacena la configuración de todas las opciones registradas. */
	private static array $opcionesRegistradas = []; // Renombrado de $contenidoRegistrado
	/** @var \stdClass|null Objeto centinela para comparaciones con valores de BD (indica que una opción no existe). */
	private static $centinelaBd;

	/**
	 * Inicializa el objeto centinela utilizado para comparaciones con `get_option`.
	 * Este objeto es una instancia única que `get_option` puede devolver si la opción no existe
	 * y se pasa como valor por defecto, permitiendo distinguir entre una opción no existente
	 * y una opción guardada con un valor como `false` o `null`.
	 */
	public static function initEstatico() { // Podría llamarse init() si no hay ambigüedad o si se hace private.
		if (self::$centinelaBd === null) {
			self::$centinelaBd = new \stdClass();
		}
	}

	/**
	 * Registra una nueva opción configurable o actualiza una existente.
	 *
	 * Al registrar una opción, se define su configuración (tipo, valor por defecto, etiqueta, etc.)
	 * y se calcula un hash de su valor por defecto. Este hash es crucial para la lógica de sincronización.
	 * Después del registro, se llama a `sincronizarOpcionRegistrada` para asegurar que el valor
	 * en la base de datos sea consistente con las reglas definidas.
	 *
	 * @param string $key Clave única para identificar la opción.
	 * @param array $configuracion Array asociativo con la configuración de la opción:
	 *  - `valorDefault` (mixed): El valor por defecto de la opción definido en el código.
	 *  - `tipo` (string): Tipo de opción (ej. 'text', 'richText', 'image', 'menu_structure'). Default: 'text'.
	 *  - `etiqueta` (string): Etiqueta legible para mostrar en un panel. Default: se genera desde `$key`.
	 *  - `seccion` (string): Slug de la sección a la que pertenece en un panel. Default: 'general'.
	 *  - `subSeccion` (string): Slug de la subsección. Default: 'general'.
	 *  - `etiquetaSeccion` (string): Etiqueta de la sección. Default: se genera desde `seccion`.
	 *  - `descripcion` (string): Descripción de la opción para un panel.
	 *  - `comportamientoEscape` (bool): Si el valor debe ser escapado con `esc_html` al obtenerlo con `get()`. Default: true para tipo 'text'.
	 *  - `forzarDefault` (bool): Si es true, el valor por defecto del código siempre sobrescribe el de la BD, excepto si fue guardado desde panel y el código no cambió. Default: false.
	 *  - `forzarDefaultAlRegistrar` (bool): Similar a `forzarDefault`, pero solo se aplica durante esta llamada de registro. Default: false.
	 */
	public static function register(string $key, array $configuracion = []): void { // Nombre de método público es claro.
		if (self::$centinelaBd === null) {
			self::initEstatico();
		}

		$tipoDefault = $configuracion['tipo'] ?? 'text';
		$defaults = [
			'valorDefault' => '',
			'tipo' => $tipoDefault,
			'etiqueta' => ucfirst(str_replace(['_', '-'], ' ', $key)),
			'seccion' => 'general',
			'subSeccion' => 'general',
			'etiquetaSeccion' => ucfirst(str_replace(['_', '-'], ' ', $configuracion['seccion'] ?? 'general')),
			'descripcion' => '',
			'comportamientoEscape' => ($tipoDefault === 'text'),
			'forzarDefault' => false,
			'forzarDefaultAlRegistrar' => false,
		];
		$configParseada = wp_parse_args($configuracion, $defaults);
		$defaultCodigoParaHash = $configParseada['valorDefault'];
		$configParseada['hashVersionCodigo'] = md5(is_scalar($defaultCodigoParaHash) ? (string)$defaultCodigoParaHash : serialize($defaultCodigoParaHash));

		if (!isset(self::$opcionesRegistradas[$key])) { // Actualizado a opcionesRegistradas
			self::$opcionesRegistradas[$key] = $configParseada;
		} else {
			self::$opcionesRegistradas[$key]['hashVersionCodigo'] = $configParseada['hashVersionCodigo'];
			self::$opcionesRegistradas[$key]['valorDefault'] = $configParseada['valorDefault'];
		}
		self::sincronizarOpcionRegistrada($key);
	}

	/**
	 * Sincroniza el valor de una opción registrada en la base de datos con su definición en código.
	 *
	 * Esta es la lógica central que decide si el valor por defecto del código debe sobrescribir
	 * un valor existente en la base de datos. Considera:
	 * - Si se fuerza el valor por defecto (`forzarDefaultAlRegistrar`).
	 * - Si el valor fue guardado a través de un panel de administración (`esValorPanelFlag`).
	 * - Si el valor por defecto del código ha cambiado desde que se guardó en el panel (comparando hashes).
	 *
	 * El objetivo es permitir que los valores guardados por el usuario en un panel persistan,
	 * a menos que el valor por defecto subyacente en el código cambie, lo que indica que
	 * el panel podría estar mostrando/guardando algo obsoleto.
	 *
	 * @param string $key La clave de la opción a sincronizar.
	 */
	private static function sincronizarOpcionRegistrada(string $key): void {
		$configOpcion = self::$opcionesRegistradas[$key];
		$nombreOpcion = self::OPCION_PREFIJO . $key;
		$valorDefaultCodigo = $configOpcion['valorDefault'];
		$hashCodigoActual = $configOpcion['hashVersionCodigo'];

		$valorBd = get_option($nombreOpcion, self::$centinelaBd);
		$esValorPanelFlag = get_option($nombreOpcion . self::META_PANEL_GUARDADO_SUFIJO, false);
		$hashAlGuardarPanel = get_option($nombreOpcion . self::META_HASH_CODIGO_SUFIJO, self::$centinelaBd);

		$sobrescribirConDefaultCodigo = false;
		$mensajeLog = '';

		if ($configOpcion['forzarDefaultAlRegistrar'] ?? false) {
			if ($valorBd === self::$centinelaBd || $valorBd !== $valorDefaultCodigo) {
				$sobrescribirConDefaultCodigo = true;
				$mensajeLog = "OpcionManager (sincronizar): '{$key}' se actualizará a default de código debido a 'forzarDefaultAlRegistrar'.";
			}
		} elseif ($esValorPanelFlag) {
			if ($hashAlGuardarPanel === self::$centinelaBd) {
				$sobrescribirConDefaultCodigo = true;
				$mensajeLog = "OpcionManager (sincronizar): '{$key}' (valor de panel) inconsistente (sin hash guardado). Se revierte a default de código.";
				GloryLogger::error($mensajeLog . " Default: " . print_r($valorDefaultCodigo, true));
			} elseif ($hashCodigoActual !== $hashAlGuardarPanel) {
				$sobrescribirConDefaultCodigo = true;
				$mensajeLog = "OpcionManager (sincronizar): '{$key}' (valor de panel) obsoleto (default de código cambió). Se revierte a default de código. Hash panel: {$hashAlGuardarPanel}, Hash código: {$hashCodigoActual}.";
				GloryLogger::warning($mensajeLog . " Default: " . print_r($valorDefaultCodigo, true));
			}
			// Si los hashes coinciden, $sobrescribirConDefaultCodigo permanece false, el valor de panel se mantiene.
		} else { // No es valor de panel Y no se fuerza al registrar
			if ($valorBd === self::$centinelaBd) {
				$sobrescribirConDefaultCodigo = true;
				$mensajeLog = "OpcionManager (sincronizar): '{$key}' no existe en BD. Se establece a default de código.";
			} elseif (($configOpcion['forzarDefault'] ?? false) && $valorBd !== $valorDefaultCodigo) {
				$sobrescribirConDefaultCodigo = true;
				$mensajeLog = "OpcionManager (sincronizar): '{$key}' se actualizará a default de código debido a 'forzarDefault' y diferencia con valor en BD.";
			}
			// Si no es $valorBd === self::$centinelaBd Y (NO forzarDefault O valorBd es igual al default), no se sobrescribe.
		}

		if ($sobrescribirConDefaultCodigo) {
			update_option($nombreOpcion, $valorDefaultCodigo);
			if (!empty($mensajeLog)) GloryLogger::info($mensajeLog); // Evitar logs duplicados si ya se hizo error/warning.

			// Si se sobrescribe, siempre se limpian los flags de panel, ya que el valor del código toma precedencia.
			if ($esValorPanelFlag) { delete_option($nombreOpcion . self::META_PANEL_GUARDADO_SUFIJO); }
			if ($hashAlGuardarPanel !== self::$centinelaBd) { delete_option($nombreOpcion . self::META_HASH_CODIGO_SUFIJO); }
		} elseif (!$esValorPanelFlag) {
			// Caso residual: No se sobrescribió Y no era un valor de panel originalmente.
			// Limpiar flags por si estaban sucios de alguna manera (poco probable pero seguro).
			// Esto cubre el caso donde $valorBd existe, no se fuerza default, y el valor de BD se mantiene.
			// No se necesita loguear aquí, es una limpieza silenciosa.
			if (get_option($nombreOpcion . self::META_PANEL_GUARDADO_SUFIJO, false)) {
				 delete_option($nombreOpcion . self::META_PANEL_GUARDADO_SUFIJO);
			}
			if (get_option($nombreOpcion . self::META_HASH_CODIGO_SUFIJO, self::$centinelaBd) !== self::$centinelaBd) {
				 delete_option($nombreOpcion . self::META_HASH_CODIGO_SUFIJO);
			}
		}
		// Si $esValorPanelFlag es true Y $sobrescribirConDefaultCodigo es false, significa que el valor del panel es válido y se mantiene,
		// incluyendo sus flags. No se hace nada en ese caso.
	}

	/**
	 * Obtiene el hash MD5 del valor por defecto de un campo de contenido tal como está definido en el código.
	 * Si el hash ya fue calculado y almacenado en `$contenidoRegistrado`, lo devuelve directamente.
	 * De lo contrario, lo calcula al momento.
	 *
	 * @param string $key La clave del campo de contenido.
	 * @return string|null El hash MD5 del valor por defecto, o null si no se puede calcular.
	 */
	public static function getHashDefaultCodigo(string $key): ?string {
		if (isset(self::$opcionesRegistradas[$key]['hashVersionCodigo'])) {
			return self::$opcionesRegistradas[$key]['hashVersionCodigo'];
		}
		// Fallback por si el hash no se calculó en el registro (no debería ocurrir).
		if (isset(self::$opcionesRegistradas[$key]['valorDefault'])) {
			$valorDefault = self::$opcionesRegistradas[$key]['valorDefault'];
			return md5(is_scalar($valorDefault) ? (string)$valorDefault : serialize($valorDefault));
		}
		GloryLogger::error("Obtener Hash Default Código (getHashDefaultCodigo): CRÍTICO - No se encontró valor por defecto para la clave '{$key}' en el contenido registrado para calcular el hash. Esto indica un problema con el flujo de registro.");
		return null;
	}

	/**
	 * Registra un campo de contenido "al vuelo" si no ha sido registrado previamente.
	 *
	 * Este método es utilizado internamente por los captadores (`get`, `texto`, etc.) para asegurar
	 * que un campo esté registrado (y por lo tanto sincronizado) antes de intentar obtener su valor.
	 * Esto permite usar los captadores sin una llamada explícita a `register()` para cada campo,
	 * lo cual puede ser conveniente para configuraciones simples o para asegurar retrocompatibilidad.
	 *
	 * @param string $key Clave del campo.
	 * @param mixed $valorDefault Valor por defecto a usar si se registra.
	 * @param string $tipo Tipo de campo.
	 * @param string|null $etiqueta Etiqueta para el panel.
	 * @param string|null $seccion Sección para el panel.
	 * @param string|null $subSeccion Subsección para el panel.
	 * @param string|null $descripcion Descripción para el panel.
	 * @param bool $comportamientoEscape Comportamiento de escapado.
	 */
	private static function registrarAlVuelo(string $key, $valorDefault, string $tipo, ?string $etiqueta, ?string $seccion, ?string $subSeccion, ?string $descripcion, bool $comportamientoEscape): void {
		if (!isset(self::$opcionesRegistradas[$key])) {
			// Registra el contenido con la configuración proporcionada si aún no existe.
			// Esto permite que los métodos get (texto, imagen, etc.) funcionen sin un registro explícito previo,
			// usando los parámetros proporcionados como configuración temporal para la sincronización.
			self::register($key, [
				'valorDefault' => $valorDefault,
				'tipo' => $tipo,
				'etiqueta' => $etiqueta,
				'seccion' => $seccion,
				'subSeccion' => $subSeccion,
				'descripcion' => $descripcion,
				'comportamientoEscape' => $comportamientoEscape,
				// 'forzarDefaultAlRegistrar' podría ser true aquí si se quiere que el primer get establezca el valor.
				// Por defecto, se deja en false para que el registro al vuelo no sea destructivo si ya hay un valor en BD.
			]);
		}
	}

	/**
	 * Obtiene un campo de contenido de tipo estructura de menú.
	 * Es un captador especializado que llama a `get()` con `escaparSalida` en `false`
	 * y un tipo 'menu_structure'. Asegura que el resultado sea un array.
	 *
	 * @param string $key Clave del campo de menú.
	 * @param array $estructuraDefault Estructura de menú por defecto si no hay valor o el valor no es un array.
	 * @param string|null $tituloPanel Título para el panel.
	 * @param string|null $seccionPanel Sección para el panel.
	 * @param string|null $subSeccionPanel Subsección para el panel.
	 * @param string|null $descripcionPanel Descripción para el panel.
	 * @return array La estructura del menú.
	 */
	public static function menu(
		string $key,
		array $estructuraDefault = [],
		?string $tituloPanel = null,
		?string $seccionPanel = null,
		?string $subSeccionPanel = null,
		?string $descripcionPanel = null
	): array {
		$valor = self::get(
			$key,
			$estructuraDefault,
			false, // Los menús generalmente no se escapan como HTML simple.
			$tituloPanel,
			$seccionPanel,
			$subSeccionPanel,
			$descripcionPanel,
			'menu_structure' // Tipo específico para estructuras de menú.
		);
		// Asegura que el valor devuelto sea un array; si no, devuelve la estructura por defecto.
		return is_array($valor) ? $valor : $estructuraDefault;
	}

	/**
	 * Obtiene el valor de un campo de contenido registrado.
	 *
	 * Este es el método principal para recuperar valores. Primero asegura que el campo
	 * esté registrado (usando `registrarAlVuelo`). Luego, obtiene la opción de WordPress
	 * correspondiente. Si la opción no se encuentra en la base de datos (incluso después de la
	 * sincronización), se emite un error y se devuelve el valor por defecto del código.
	 * Finalmente, aplica escapado HTML si el valor es una cadena y la configuración lo requiere.
	 *
	 * @param string $key Clave del campo.
	 * @param mixed $defaultParam Valor por defecto a usar si el campo no está registrado o no tiene valor.
	 *                            También se usa para el registro al vuelo.
	 * @param bool $escaparSalida Define si se debe aplicar `esc_html` al resultado si es una cadena.
	 *                            Este parámetro puede ser sobrescrito por la configuración 'comportamientoEscape' del campo.
	 * @param string|null $tituloPanel Título para el panel (para registro al vuelo).
	 * @param string|null $seccionPanel Sección para el panel (para registro al vuelo).
	 * @param string|null $subSeccionPanel Subsección para el panel (para registro al vuelo).
	 * @param string|null $descripcionPanel Descripción para el panel (para registro al vuelo).
	 * @param string $tipoContenido Tipo de contenido (para registro al vuelo).
	 * @return mixed El valor del campo de contenido.
	 */
	public static function get(
		string $key,
		$defaultParam = '', // Valor por defecto si no se encuentra nada, usado también para el registro al vuelo.
		bool $escaparSalida = true, // Si se debe escapar la salida HTML (predeterminado a true).
		?string $tituloPanel = null, // Título para mostrar en un panel de opciones (si aplica).
		?string $seccionPanel = null, // Sección en un panel de opciones.
		?string $subSeccionPanel = null, // Subsección en un panel de opciones.
		?string $descripcionPanel = null, // Descripción para un panel de opciones.
		string $tipoContenido = 'text' // Tipo de contenido, usado para el registro al vuelo.
	) {
		if (self::$centinelaBd === null) {
			self::initEstatico();
		}

		// Asegura que el contenido esté registrado, incluso si se llama a get() directamente.
		// Esto es crucial para que la lógica de sincronización se aplique.
		self::registrarAlVuelo($key, $defaultParam, $tipoContenido, $tituloPanel, $seccionPanel, $subSeccionPanel, $descripcionPanel, $escaparSalida);

		$nombreOpcion = self::OPCION_PREFIJO . $key;
		$valorFinal = get_option($nombreOpcion, self::$centinelaBd);

		// Si después de la lógica de registro/sincronización, la opción aún no existe en la BD,
		// es un estado inesperado. Se recurre al valor por defecto en memoria.
		if ($valorFinal === self::$centinelaBd) {
			GloryLogger::error("Error GET para '{$key}': La opción '{$nombreOpcion}' NO SE ENCONTRÓ en la BD incluso después de que la lógica de sincronización en registrar() debería haberse ejecutado. Esto es inesperado. Se recurrirá al valor por defecto registrado en memoria para '{$key}'.");
			$valorFinal = self::$opcionesRegistradas[$key]['valorDefault'] ?? $defaultParam;
		}

		// Aplica el escapado HTML si es una cadena y se ha solicitado.
		// El comportamiento de escapado definido en el registro del campo tiene prioridad.
		$debeEscapar = self::$opcionesRegistradas[$key]['comportamientoEscape'] ?? $escaparSalida;
		if (is_string($valorFinal) && $debeEscapar) {
			return esc_html($valorFinal);
		}
		return $valorFinal;
	}

	/**
	 * Restablece todos los campos de contenido de una sección específica a sus valores por defecto definidos en código.
	 *
	 * Itera sobre todos los campos registrados. Si un campo pertenece a la sección especificada
	 * (comparando slugs), su valor en la base de datos se actualiza con el `valorDefault` del código
	 * y se eliminan los metadatos de "guardado desde panel".
	 * Los campos de tipo 'menu_structure' se omiten de este reseteo automático.
	 *
	 * @param string $seccionSlugAResetear El slug de la sección a resetear.
	 * @return array Un array con los resultados del reseteo:
	 *  - `exito` (array): Lista de claves de campos reseteados con éxito.
	 *  - `error` (array): Lista de claves de campos que fallaron (actualmente no implementado).
	 *  - `noEncontradoOVacio` (bool): True si la sección no existe o no contenía campos reseteables.
	 *  - `camposProcesadosContador` (int): Número de campos reseteados.
	 */
	public static function resetSeccionDefaults(string $seccionSlugAResetear): array {
		if (self::$centinelaBd === null) {
			self::initEstatico();
		}

		$resultadosReset = ['exito' => [], 'error' => [], 'noEncontradoOVacio' => false, 'camposProcesadosContador' => 0];
		$seccionExisteEnConfig = false;

		if (empty(self::$opcionesRegistradas)) {
			$resultadosReset['noEncontradoOVacio'] = true; // No hay campos registrados, nada que resetear.
			return $resultadosReset;
		}

		foreach (self::$opcionesRegistradas as $key => $configCampo) {
			$seccionCampoRaw = $configCampo['seccion'] ?? 'general';
			$seccionCampoSlug = sanitize_title($seccionCampoRaw); // Normaliza el nombre de la sección a un slug.

			if ($seccionCampoSlug === $seccionSlugAResetear) {
				$seccionExisteEnConfig = true;
				// No resetea estructuras de menú automáticamente, ya que suelen ser más complejas.
				if (isset($configCampo['tipo']) && $configCampo['tipo'] === 'menu_structure') {
					continue;
				}

				$nombreOpcion = self::OPCION_PREFIJO . $key;
				$valorDefaultCodigo = $configCampo['valorDefault'];

				// Restablece la opción al valor por defecto del código.
				update_option($nombreOpcion, $valorDefaultCodigo);
				// Limpia los metadatos asociados al guardado desde panel.
				delete_option($nombreOpcion . self::META_PANEL_GUARDADO_SUFIJO);
				delete_option($nombreOpcion . self::META_HASH_CODIGO_SUFIJO);

				$resultadosReset['exito'][] = $key; // Registra la clave del campo reseteado con éxito.
				$resultadosReset['camposProcesadosContador']++;
			}
		}

		// Determina si la sección no se encontró o no contenía campos reseteables.
		if (!$seccionExisteEnConfig) {
			$resultadosReset['noEncontradoOVacio'] = true;
		} elseif ($seccionExisteEnConfig && $resultadosReset['camposProcesadosContador'] === 0) {
			// La sección existe pero no se procesó ningún campo (ej. solo contenía menús).
			$resultadosReset['noEncontradoOVacio'] = true;
		}
		return $resultadosReset;
	}

	/**
	 * Captador de conveniencia para obtener un campo de texto simple.
	 * Llama a `get()` con `escaparSalida` en `true` y tipo 'text'.
	 *
	 * @param string $key Clave del campo.
	 * @param string $valorDefault Valor por defecto.
	 * @param string|null $tituloPanel Título para el panel.
	 * @param string|null $seccionPanel Sección para el panel.
	 * @param string|null $descripcionPanel Descripción para el panel.
	 * @return string El valor del campo de texto, escapado.
	 */
	public static function texto(string $key, string $valorDefault = '', ?string $tituloPanel = null, ?string $seccionPanel = null, ?string $descripcionPanel = null): string {
		return (string) self::get($key, $valorDefault, true, $tituloPanel, $seccionPanel, null, $descripcionPanel, 'text');
	}

	/**
	 * Captador de conveniencia para obtener un campo de texto enriquecido (HTML).
	 * Llama a `get()` con `escaparSalida` en `false` (ya que se espera HTML) y tipo 'richText'.
	 * El resultado se pasa por `wp_kses_post` para asegurar que el HTML es seguro.
	 *
	 * @param string $key Clave del campo.
	 * @param string $valorDefault Valor por defecto.
	 * @param string|null $tituloPanel Título para el panel.
	 * @param string|null $seccionPanel Sección para el panel.
	 * @param string|null $descripcionPanel Descripción para el panel.
	 * @return string El valor del campo de texto enriquecido, sanitizado.
	 */
	public static function richText(string $key, string $valorDefault = '', ?string $tituloPanel = null, ?string $seccionPanel = null, ?string $descripcionPanel = null): string {
		$valor = self::get($key, $valorDefault, false, $tituloPanel, $seccionPanel, null, $descripcionPanel, 'richText');
		// Aplica kses_post para asegurar que el HTML enriquecido sea seguro.
		return wp_kses_post((string)$valor);
	}

	/**
	 * Captador de conveniencia para obtener una URL de imagen.
	 * Llama a `get()` con `escaparSalida` en `false` y tipo 'image'.
	 *
	 * @param string $key Clave del campo.
	 * @param string $valorDefault URL de imagen por defecto.
	 * @param string|null $tituloPanel Título para el panel.
	 * @param string|null $seccionPanel Sección para el panel.
	 * @param string|null $descripcionPanel Descripción para el panel.
	 * @return string La URL de la imagen.
	 */
	public static function imagen(string $key, string $valorDefault = '', ?string $tituloPanel = null, ?string $seccionPanel = null, ?string $descripcionPanel = null): string {
		// Las URLs de imágenes generalmente no necesitan escapado HTML aquí, ya que se usan en atributos src.
		return (string) self::get($key, $valorDefault, false, $tituloPanel, $seccionPanel, null, $descripcionPanel, 'image');
	}

	/**
	 * Captador de conveniencia para obtener datos de horario.
	 * Delega la lógica a `ScheduleManager::getScheduleData`.
	 * ContentManager puede actuar como un proxy para el registro inicial si `registrarAlVuelo`
	 * se invoca a través de `get` (aunque `ScheduleManager` parece tener su propia lógica de obtención).
	 *
	 * @param string $key Clave del campo de horario.
	 * @param array $horarioDefault Horario por defecto.
	 * @param string|null $tituloPanel Título para el panel.
	 * @param string|null $seccionPanel Sección para el panel.
	 * @param string|null $descripcionPanel Descripción para el panel.
	 * @return array Los datos del horario.
	 */
	public static function horario(string $key, array $horarioDefault = [], ?string $tituloPanel = null, ?string $seccionPanel = null, ?string $descripcionPanel = null): array {
		// Delega la obtención y gestión de horarios al ScheduleManager.
		// ContentManager actúa como un proxy para registrar y obtener la configuración inicial.
		// Se registra al vuelo por si este campo necesita aparecer en un panel gestionado por ContentManager.
        self::registrarAlVuelo($key, $horarioDefault, 'schedule', $tituloPanel, $seccionPanel, null, $descripcionPanel, false);
		return ScheduleManager::getScheduleData($key, $horarioDefault, $tituloPanel, $seccionPanel, $descripcionPanel, 'schedule');
	}

	/**
	 * Captador de conveniencia para obtener el estado actual de un horario.
	 * Delega la lógica a `ScheduleManager::getCurrentScheduleStatus`.
	 *
	 * @param string $claveHorario Clave del horario (debe coincidir con la usada en `horario()`).
	 * @param array $horarioDefault Horario por defecto si no se encuentra.
	 * @param string $zonaHoraria Zona horaria para calcular el estado.
	 * @return array El estado del horario.
	 */
	public static function scheduleStatus(string $claveHorario, array $horarioDefault, string $zonaHoraria = 'Europe/Madrid'): array {
		// Delega la consulta del estado del horario al ScheduleManager.
		return ScheduleManager::getCurrentScheduleStatus($claveHorario, $horarioDefault, $zonaHoraria);
	}

	/**
	 * Obtiene todos los campos de contenido registrados junto con sus valores actuales de la BD.
	 *
	 * Este método es útil para construir paneles de administración, ya que proporciona
	 * toda la configuración registrada (etiquetas, descripciones, secciones, etc.) y
	 * el valor actual que tiene cada campo en la base de datos.
	 * Si un campo no tiene valor en la BD, se utiliza su `valorDefault` del código.
	 *
	 * @return array Un array asociativo donde cada clave es la `$key` del campo y el valor
	 *               es un array con la configuración del campo más una entrada `valorActual`.
	 */
	public static function getCamposContenidoRegistrados(): array {
		if (self::$centinelaBd === null) {
			self::initEstatico();
		}
		$camposConValoresActuales = [];

		foreach (self::$opcionesRegistradas as $key => $configCampo) {
			$nombreOpcion = self::OPCION_PREFIJO . $key;
			$valorActualBd = get_option($nombreOpcion, self::$centinelaBd);

			$nuevaConfig = $configCampo; // Copia la configuración base.
			// Si se encuentra un valor en la BD, se añade a la configuración para mostrarlo (ej. en un panel).
			if ($valorActualBd !== self::$centinelaBd) {
				$nuevaConfig['valorActual'] = $valorActualBd;
			} else {
				// Si no hay valor en BD, se usa el default del código. Esto puede ocurrir si la opción nunca se guardó.
				$nuevaConfig['valorActual'] = $configCampo['valorDefault'] ?? null;
				GloryLogger::error("Error al obtener campos registrados para '{$key}': La opción '{$nombreOpcion}' NO SE ENCONTRÓ en la BD para el panel. Usando el valor por defecto del código: " . substr(print_r($nuevaConfig['valorActual'], true), 0, 100) . "...");
			}
			$camposConValoresActuales[$key] = $nuevaConfig;
		}
		return $camposConValoresActuales;
	}
}