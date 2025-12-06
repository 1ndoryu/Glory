<?php
namespace Glory\Gbn\Schema;

class SchemaBuilder {
    protected array $options = [];

    public static function create(): self {
        return new self();
    }

    public function addOption(Option $option): self {
        $this->options[] = $option;
        return $this;
    }

    public function toArray(): array {
        return array_map(fn($opt) => $opt->toArray(), $this->options);
    }

    /**
     * Extrae los campos que disparan condiciones (campos referenciados en 'condicion')
     * 
     * Analiza todas las opciones del schema y retorna un array único con los IDs
     * de campos que son usados como triggers en condiciones de otros campos.
     * 
     * Ejemplo: Si un campo tiene condition(['layout', '==', 'flex']),
     * entonces 'layout' será incluido como trigger.
     * 
     * NOTA: Este método sigue el principio OCP - cualquier nuevo campo condicional
     * será detectado automáticamente sin modificar código JavaScript.
     * 
     * @return array<string> Array único de IDs de campos que son triggers
     */
    public function extractConditionalTriggers(): array {
        $triggers = [];
        
        foreach ($this->options as $option) {
            $data = $option->toArray();
            
            if (isset($data['condicion']) && is_array($data['condicion'])) {
                $condition = $data['condicion'];
                
                // Formato canónico: [campo, operador, valor]
                // El primer elemento es siempre el campo trigger
                if (isset($condition[0]) && is_string($condition[0])) {
                    $trigger = $condition[0];
                    
                    // Evitar duplicados
                    if (!in_array($trigger, $triggers, true)) {
                        $triggers[] = $trigger;
                    }
                }
            }
        }
        
        return $triggers;
    }
}
