<?php
# /Glory/Class/ContentManager.php

namespace Glory\Class;

use Glory\Class\GloryLogger;
use Glory\Helper\ScheduleManager;

class ContentManager {
	const opcionPrefijo = 'glory_content_';
	const metaHashCodigoSufijo = '_code_hash_on_save';
	const metaPanelGuardadoSufijo = '_is_panel_value';

	private static array $contenidoRegistrado = [];
	private static $centinelaBd;

	// ANTERIOR: static_init
	public static function initEstatico() {
		if (self::$centinelaBd === null) {
			self::$centinelaBd = new \stdClass();
		}
	}

	public static function register(string $key, array $configuracion = []): void {
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
			'forzarDefaultAlRegistrar' => false,
		];
		$configParseada = wp_parse_args($configuracion, $defaults);
		$defaultCodigoParaHash = $configParseada['valorDefault'];
		$configParseada['hashVersionCodigo'] = md5(is_scalar($defaultCodigoParaHash) ? (string)$defaultCodigoParaHash : serialize($defaultCodigoParaHash));

		if (!isset(self::$contenidoRegistrado[$key])) {
			self::$contenidoRegistrado[$key] = $configParseada;
		} else {
			self::$contenidoRegistrado[$key]['hashVersionCodigo'] = $configParseada['hashVersionCodigo'];
			self::$contenidoRegistrado[$key]['valorDefault'] = $configParseada['valorDefault'];
		}
		self::sincronizarOpcionRegistrada($key);
	}

	// ANTERIOR: _synchronizeRegisteredOption
	private static function sincronizarOpcionRegistrada(string $key): void {
		$configCampo = self::$contenidoRegistrado[$key];
		$nombreOpcion = self::opcionPrefijo . $key;
		$valorDefaultCodigo = $configCampo['valorDefault'];
		$hashCodigoActual = $configCampo['hashVersionCodigo'];

		$valorBd = get_option($nombreOpcion, self::$centinelaBd);
		$esValorPanelFlag = get_option($nombreOpcion . self::metaPanelGuardadoSufijo, false);
		$hashAlGuardarPanel = get_option($nombreOpcion . self::metaHashCodigoSufijo, self::$centinelaBd);

		if ($configCampo['forzarDefaultAlRegistrar']) {
			if ($valorBd === self::$centinelaBd || $valorBd !== $valorDefaultCodigo) {
				update_option($nombreOpcion, $valorDefaultCodigo);
			}
			if ($esValorPanelFlag) delete_option($nombreOpcion . self::metaPanelGuardadoSufijo);
			if ($hashAlGuardarPanel !== self::$centinelaBd) delete_option($nombreOpcion . self::metaHashCodigoSufijo);
		} else {
			if ($esValorPanelFlag) {
				if ($hashAlGuardarPanel === self::$centinelaBd) {
					GloryLogger::error("SYNC ERROR [$key]: Panel value flag is TRUE, but NO HASH was stored from panel save. This is inconsistent. The code default for this key might have changed, or the hash was lost. To ensure data integrity, the current code default will be applied, and panel flags will be cleared. Please review the value in the panel. Current code default: " . print_r($valorDefaultCodigo, true));
					update_option($nombreOpcion, $valorDefaultCodigo);
					delete_option($nombreOpcion . self::metaPanelGuardadoSufijo);
				} elseif ($hashCodigoActual !== $hashAlGuardarPanel) {
					GloryLogger::error("SYNC MISMATCH [$key]: Panel value OVERWRITTEN. The code's default value has changed since this content was last saved in the panel. Applying new code default and clearing panel flags. Old code hash (at panel save): '{$hashAlGuardarPanel}', New code hash (current): '{$hashCodigoActual}'. New code default: " . print_r($valorDefaultCodigo, true));
					update_option($nombreOpcion, $valorDefaultCodigo);
					delete_option($nombreOpcion . self::metaPanelGuardadoSufijo);
					delete_option($nombreOpcion . self::metaHashCodigoSufijo);
				}
			} else {
				if ($valorBd === self::$centinelaBd) {
					update_option($nombreOpcion, $valorDefaultCodigo);
				} else {
					if ($valorBd !== $valorDefaultCodigo) {
						update_option($nombreOpcion, $valorDefaultCodigo);
					}
				}
				if ($esValorPanelFlag) {
					delete_option($nombreOpcion . self::metaPanelGuardadoSufijo);
				}
				if ($hashAlGuardarPanel !== self::$centinelaBd) {
					delete_option($nombreOpcion . self::metaHashCodigoSufijo);
				}
			}
		}
	}

	// ANTERIOR: getCodeDefaultHash
	public static function getHashDefaultCodigo(string $key): ?string {
		if (isset(self::$contenidoRegistrado[$key]['hashVersionCodigo'])) {
			return self::$contenidoRegistrado[$key]['hashVersionCodigo'];
		}
		if (isset(self::$contenidoRegistrado[$key]['valorDefault'])) {
			$valorDefault = self::$contenidoRegistrado[$key]['valorDefault'];
			return md5(is_scalar($valorDefault) ? (string)$valorDefault : serialize($valorDefault));
		}
		GloryLogger::error("getHashDefaultCodigo: CRITICAL - No default value found for key '{$key}' in registered content to calculate hash. This indicates an issue with registration flow.");
		return null;
	}

	// ANTERIOR: registerOnTheFly
	private static function registrarAlVuelo(string $key, $valorDefault, string $tipo, ?string $etiqueta, ?string $seccion, ?string $subSeccion, ?string $descripcion, bool $comportamientoEscape): void {
		if (!isset(self::$contenidoRegistrado[$key])) {
			self::registrar($key, [
				'valorDefault' => $valorDefault,
				'tipo' => $tipo,
				'etiqueta' => $etiqueta,
				'seccion' => $seccion,
				'subSeccion' => $subSeccion,
				'descripcion' => $descripcion,
				'comportamientoEscape' => $comportamientoEscape,
			]);
		}
	}

	// ANTERIOR: menu
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
			false,
			$tituloPanel,
			$seccionPanel,
			$subSeccionPanel,
			$descripcionPanel,
			'menu_structure'
		);
		return is_array($valor) ? $valor : $estructuraDefault;
	}

	public static function get(
		string $key,
		$defaultParam = '',
		bool $escaparSalida = true,
		?string $tituloPanel = null,
		?string $seccionPanel = null,
		?string $subSeccionPanel = null,
		?string $descripcionPanel = null,
		string $tipoContenido = 'text'
	) {
		if (self::$centinelaBd === null) {
			self::initEstatico();
		}

		self::registrarAlVuelo($key, $defaultParam, $tipoContenido, $tituloPanel, $seccionPanel, $subSeccionPanel, $descripcionPanel, $escaparSalida);
		
		$nombreOpcion = self::opcionPrefijo . $key;
		$valorFinal = get_option($nombreOpcion, self::$centinelaBd);

		if ($valorFinal === self::$centinelaBd) {
			GloryLogger::error("GET ERROR [$key]: Option '$nombreOpcion' NOT FOUND in DB even after sync logic in registrar() should have run. This is unexpected. Fallback to in-memory registered default for '$key'.");
			$valorFinal = self::$contenidoRegistrado[$key]['valorDefault'] ?? $defaultParam;
		}

		if (is_string($valorFinal) && $escaparSalida) {
			return esc_html($valorFinal);
		}
		return $valorFinal;
	}

	// ANTERIOR: resetSectionToDefaults
	public static function resetSeccionDefaults(string $seccionSlugAResetear): array {
		if (self::$centinelaBd === null) {
			self::initEstatico();
		}

		$resultadosReset = ['exito' => [], 'error' => [], 'noEncontradoOVacio' => false, 'camposProcesadosContador' => 0];
		$seccionExisteEnConfig = false;

		if (empty(self::$contenidoRegistrado)) {
			$resultadosReset['noEncontradoOVacio'] = true;
			return $resultadosReset;
		}

		foreach (self::$contenidoRegistrado as $key => $configCampo) {
			$seccionCampoRaw = $configCampo['seccion'] ?? 'general';
			$seccionCampoSlug = sanitize_title($seccionCampoRaw);

			if ($seccionCampoSlug === $seccionSlugAResetear) {
				$seccionExisteEnConfig = true;
				if (isset($configCampo['tipo']) && $configCampo['tipo'] === 'menu_structure') {
					continue;
				}

				$nombreOpcion = self::opcionPrefijo . $key;
				$valorDefaultCodigo = $configCampo['valorDefault'];

				update_option($nombreOpcion, $valorDefaultCodigo);
				delete_option($nombreOpcion . self::metaPanelGuardadoSufijo);
				delete_option($nombreOpcion . self::metaHashCodigoSufijo);

				$resultadosReset['exito'][] = $key;
				$resultadosReset['camposProcesadosContador']++;
			}
		}

		if (!$seccionExisteEnConfig) {
			$resultadosReset['noEncontradoOVacio'] = true;
		} elseif ($seccionExisteEnConfig && $resultadosReset['camposProcesadosContador'] === 0) {
			$resultadosReset['noEncontradoOVacio'] = true;
		}
		return $resultadosReset;
	}

	// ANTERIOR: text
	public static function texto(string $key, string $valorDefault = '', ?string $tituloPanel = null, ?string $seccionPanel = null, ?string $descripcionPanel = null): string {
		return (string) self::get($key, $valorDefault, true, $tituloPanel, $seccionPanel, null, $descripcionPanel, 'text');
	}

	// ANTERIOR: richText
	public static function richText(string $key, string $valorDefault = '', ?string $tituloPanel = null, ?string $seccionPanel = null, ?string $descripcionPanel = null): string {
		$valor = self::get($key, $valorDefault, false, $tituloPanel, $seccionPanel, null, $descripcionPanel, 'richText');
		return wp_kses_post((string)$valor);
	}

	// ANTERIOR: image
	public static function imagen(string $key, string $valorDefault = '', ?string $tituloPanel = null, ?string $seccionPanel = null, ?string $descripcionPanel = null): string {
		return (string) self::get($key, $valorDefault, false, $tituloPanel, $seccionPanel, null, $descripcionPanel, 'image');
	}

	// ANTERIOR: schedule
	public static function horario(string $key, array $horarioDefault = [], ?string $tituloPanel = null, ?string $seccionPanel = null, ?string $descripcionPanel = null): array {
		return ScheduleManager::getScheduleData($key, $horarioDefault, $tituloPanel, $seccionPanel, $descripcionPanel, 'schedule');
	}

	// ANTERIOR: getCurrentStatus
	public static function scheduleStatus(string $claveHorario, array $horarioDefault, string $zonaHoraria = 'Europe/Madrid'): array {
		return ScheduleManager::getCurrentScheduleStatus($claveHorario, $horarioDefault, $zonaHoraria);
	}

	// ANTERIOR: getRegisteredContentFields
	public static function getCamposContenidoRegistrados(): array {
		if (self::$centinelaBd === null) {
			self::initEstatico();
		}
		$camposConValoresActuales = [];

		foreach (self::$contenidoRegistrado as $key => $configCampo) {
			$nombreOpcion = self::opcionPrefijo . $key;
			$valorActualBd = get_option($nombreOpcion, self::$centinelaBd);

			$nuevaConfig = $configCampo;
			if ($valorActualBd !== self::$centinelaBd) {
				$nuevaConfig['valorActual'] = $valorActualBd;
			} else {
				$nuevaConfig['valorActual'] = $configCampo['valorDefault'] ?? null;
				GloryLogger::error("getCamposContenidoRegistrados ERROR [$key]: Option '$nombreOpcion' NOT FOUND in DB for panel. Using code default: " . substr(print_r($nuevaConfig['valorActual'], true), 0, 100) . "...");
			}
			$camposConValoresActuales[$key] = $nuevaConfig;
		}
		return $camposConValoresActuales;
	}
}