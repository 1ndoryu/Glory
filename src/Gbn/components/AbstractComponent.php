<?php

namespace Glory\Gbn\Components;

/**
 * Clase base abstracta para componentes de GBN.
 */
abstract class AbstractComponent implements ComponentInterface
{

    /**
     * @var string ID del componente
     */
    protected string $id;

    /**
     * @var string Etiqueta del componente
     */
    protected string $label;

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSelector(): array
    {
        return [];
    }

    public function getSchema(): array
    {
        return [];
    }

    public function getDefaults(): array
    {
        return [];
    }

    public function getAssets(): array
    {
        return [
            'scripts' => [],
            'styles' => []
        ];
    }

    public function getIcon(): string
    {
        return '';
    }

    public function getTemplate(): string
    {
        return '';
    }
}
