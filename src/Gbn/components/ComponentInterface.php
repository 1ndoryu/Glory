<?php
namespace Glory\Gbn\Components;

/**
 * Interfaz que deben implementar todos los componentes de GBN.
 */
interface ComponentInterface {
    /**
     * Obtiene el ID único del componente (ej. 'principal', 'secundario').
     * @return string
     */
    public function getId(): string;

    /**
     * Obtiene la etiqueta legible del componente para la UI.
     * @return string
     */
    public function getLabel(): string;

    /**
     * Define los selectores CSS/Atributos para identificar este componente en el DOM.
     * @return array ['attribute' => 'gloryDiv', 'value' => 'optional']
     */
    public function getSelector(): array;

    /**
     * Retorna el esquema de configuración (campos del panel).
     * @return array
     */
    public function getSchema(): array;

    /**
     * Retorna los valores por defecto para la configuración.
     * @return array
     */
    public function getDefaults(): array;

    /**
     * Retorna la lista de assets (JS/CSS) necesarios para este componente.
     * @return array ['scripts' => [], 'styles' => []]
     */
    public function getAssets(): array;
}
