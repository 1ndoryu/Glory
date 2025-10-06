<?php

namespace Glory\Components;

class Button
{
    /**
     * Render a button HTML string.
     * $options: ['texto'=>string, 'class'=>string, 'attrs'=>array]
     */
    public static function render(array $options = []): string
    {
        $texto = $options['texto'] ?? 'Botón';
        $class = $options['class'] ?? '';
        $attrs = '';
        if (!empty($options['attrs']) && is_array($options['attrs'])) {
            foreach ($options['attrs'] as $k => $v) {
                $attrs .= sprintf(' %s="%s"', htmlspecialchars($k, ENT_QUOTES), htmlspecialchars($v, ENT_QUOTES));
            }
        }

        return sprintf('<button class="%s" type="button"%s>%s</button>', htmlspecialchars($class, ENT_QUOTES), $attrs, htmlspecialchars($texto, ENT_QUOTES));
    }
}


