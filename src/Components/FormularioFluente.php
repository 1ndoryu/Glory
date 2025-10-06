<?php
declare(strict_types=1);

namespace Glory\Components;

use Glory\Components\FormBuilder;

/**
 * FormularioFluente
 *
 * Pequeña clase reusable para construir formularios usando una API fluida o
 * a partir de una configuración (array). Soporta:
 * - items con 'fn' + 'args' que llaman a FormBuilder::<fn>(args)
 * - items con 'callable' que ejecutan una función/cierre y usan su retorno
 * - items con 'raw' que insertan HTML tal cual
 *
 * Diseñada para ser ligera y fácilmente testeable.
 */
class FormularioFluente
{
    /** @var string[] */
    private array $partes = [];

    /**
     * Agrega una pieza llamando a FormBuilder::<fn> con argumentos opcionales.
     */
    public function agregar(string $fn, ?array $args = null): self
    {
        if ($args !== null) {
            $this->partes[] = call_user_func([FormBuilder::class, $fn], $args);
        } else {
            $this->partes[] = call_user_func([FormBuilder::class, $fn]);
        }
        return $this;
    }

    /**
     * Agrega una cadena HTML sin procesar.
     */
    public function agregarRaw(string $html): self
    {
        $this->partes[] = $html;
        return $this;
    }

    /**
     * Agrega el resultado de ejecutar una callable (closure, función, etc.).
     */
    public function agregarCallable(callable $callable): self
    {
        $this->partes[] = (string) call_user_func($callable);
        return $this;
    }

    /**
     * Construye el formulario a partir de una configuración.
     * Cada item puede ser:
     * - ['fn' => 'campoTexto', 'args' => [...]]
     * - ['callable' => fn() => '...']
     * - ['raw' => '<div>...']
     *
     * Retorna la misma instancia (fluente).
     */
    public function agregarDesdeConfig(array $config): self
    {
        foreach ($config as $item) {
            if (isset($item['callable']) && is_callable($item['callable'])) {
                $this->agregarCallable($item['callable']);
                continue;
            }

            if (isset($item['raw'])) {
                $this->agregarRaw((string) $item['raw']);
                continue;
            }

            if (isset($item['fn'])) {
                $args = $item['args'] ?? null;
                $this->agregar($item['fn'], $args);
                continue;
            }

            // Soporte para elementos que ya son strings (HTML)
            if (is_string($item)) {
                $this->agregarRaw($item);
                continue;
            }
        }

        return $this;
    }

    /** Devuelve el HTML ensamblado. */
    public function renderizar(): string
    {
        return implode('', $this->partes);
    }

    /** Alias para renderizar — útil para pasar como callable. */
    public function __toString(): string
    {
        return $this->renderizar();
    }
}


