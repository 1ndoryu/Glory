<?php
declare(strict_types=1);

/**
 * Constructor de Formularios Fluente
 *
 * Proporciona una interfaz fluida para construir formularios HTML dinámicamente.
 * Permite encadenar métodos para agregar campos, contenido raw o callbacks,
 * facilitando la creación de formularios complejos de manera programática.
 *
 * @package Glory\Components
 */

namespace Glory\Components;

use Glory\Components\FormBuilder;

/**
 * Clase FormularioFluente.
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
    /** @var string[] Almacena las partes HTML del formulario. */
    private array $partes = [];

    /**
     * Agrega una pieza llamando a un método estático de `FormBuilder`.
     *
     * @param string     $fn   Nombre del método de FormBuilder a llamar.
     * @param array|null $args Argumentos para el método.
     * @return self
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
     * Agrega una cadena HTML sin procesar al formulario.
     *
     * @param string $html Contenido HTML raw.
     * @return self
     */
    public function agregarRaw(string $html): self
    {
        $this->partes[] = $html;
        return $this;
    }

    /**
     * Agrega el resultado de ejecutar una callable (closure, función, etc.).
     *
     * @param callable $callable Función que retorna un string HTML.
     * @return self
     */
    public function agregarCallable(callable $callable): self
    {
        $this->partes[] = (string) call_user_func($callable);
        return $this;
    }

    /**
     * Construye el formulario a partir de un array de configuración.
     *
     * Estructura soportada para cada item:
     * - ['fn' => 'campoTexto', 'args' => [...]]
     * - ['callable' => fn() => '...']
     * - ['raw' => '<div>...']
     * - '<div>...</div>' (string directo)
     *
     * @param array $config Configuración del formulario.
     * @return self
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

    /**
     * Devuelve el HTML ensamblado del formulario.
     *
     * @return string HTML completo.
     */
    public function renderizar(): string
    {
        return implode('', $this->partes);
    }

    /**
     * Alias para `renderizar`. Permite usar el objeto como string o callable.
     *
     * @return string HTML completo.
     */
    public function __toString(): string
    {
        return $this->renderizar();
    }
}
