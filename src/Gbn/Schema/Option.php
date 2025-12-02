<?php
namespace Glory\Gbn\Schema;

class Option {
    protected array $data = [];

    public function __construct(string $type, string $id, string $label) {
        $this->data['tipo'] = $type;
        $this->data['id'] = $id;
        $this->data['etiqueta'] = $label;
    }

    public static function text(string $id, string $label): self {
        return new self('text', $id, $label);
    }

    public static function select(string $id, string $label): self {
        return new self('select', $id, $label);
    }
    
    public static function color(string $id, string $label): self {
        return new self('color', $id, $label);
    }

    public static function spacing(string $id, string $label): self {
        return new self('spacing', $id, $label);
    }

    public static function slider(string $id, string $label): self {
        return new self('slider', $id, $label);
    }
    
    public static function iconGroup(string $id, string $label): self {
        return new self('icon_group', $id, $label);
    }

    public static function fraction(string $id, string $label): self {
        return new self('fraction', $id, $label);
    }

    public static function typography(string $id, string $label): self {
        return new self('typography', $id, $label);
    }

    public static function richText(string $id, string $label): self {
        return new self('rich_text', $id, $label);
    }

    public function default($value): self {
        $this->data['defecto'] = $value;
        return $this;
    }

    public function options(array $options): self {
        // Normalize options if simple array or preserve structure if already correct
        $normalized = [];
        foreach ($options as $key => $val) {
            if (is_array($val) && isset($val['valor'])) {
                $normalized[] = $val;
            } else {
                // Support simple ['value' => 'Label'] format
                $normalized[] = ['valor' => $key, 'etiqueta' => $val];
            }
        }
        $this->data['opciones'] = $normalized;
        return $this;
    }

    public function min(int $min): self {
        $this->data['min'] = $min;
        return $this;
    }

    public function max(int $max): self {
        $this->data['max'] = $max;
        return $this;
    }

    public function step(int $step): self {
        $this->data['paso'] = $step;
        return $this;
    }

    public function unit(string $unit): self {
        $this->data['unidad'] = $unit;
        return $this;
    }

    public function units(array $units): self {
        $this->data['unidades'] = $units;
        return $this;
    }

    public function fields(array $fields): self {
        $this->data['campos'] = $fields;
        return $this;
    }

    public function allowTransparency(bool $allow = true): self {
        $this->data['permiteTransparencia'] = $allow;
        return $this;
    }

    public function condition(string $field, string $value): self {
        $this->data['condicion'] = [$field, $value];
        return $this;
    }

    public function toArray(): array {
        return $this->data;
    }
}
