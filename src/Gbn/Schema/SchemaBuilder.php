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
}
