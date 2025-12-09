<?php

/**
 * PostRenderStyles - Generador de estilos CSS para PostRender
 * 
 * Maneja la generación de CSS scoped, estilos inline del contenedor,
 * y atributos data-* para el componente PostRender.
 * 
 * Parte del REFACTOR-003: División de PostRenderProcessor.php
 * 
 * @package Glory\Gbn\Components\PostRender
 */

namespace Glory\Gbn\Components\PostRender;

class PostRenderStyles
{
    /**
     * Configuración del PostRender.
     */
    private array $config;

    /**
     * Clase CSS única de la instancia.
     */
    private string $instanceClass;

    /**
     * Constructor.
     * 
     * @param array $config Configuración del PostRender
     * @param string $instanceClass Clase CSS única de la instancia
     */
    public function __construct(array $config, string $instanceClass)
    {
        $this->config = $config;
        $this->instanceClass = $instanceClass;
    }

    /**
     * Genera estilos CSS scoped para esta instancia.
     * 
     * @return string CSS generado
     */
    public function generateScopedCss(): string
    {
        $css = [];
        $class = '.' . $this->instanceClass;

        // Layout del contenedor mejorado con constantes
        $layout = $this->config['layout'] ?? ($this->config['displayMode'] ?? 'grid');
        
        if ($layout === 'grid') {
            $css[] = $this->generateGridCss($class);
        } elseif ($layout === 'flex') {
            $css[] = $this->generateFlexCss($class);
        }

        // Estilos de item (PostItem) - usar minúsculas porque DOMDocument convierte atributos
        $css[] = "{$class} [glorypostitem] { transition: box-shadow 0.3s ease, transform 0.3s ease; }";
        
        // Efecto hover del item
        $hoverCss = $this->generateHoverCss($class);
        if ($hoverCss) {
            $css[] = $hoverCss;
        }

        return implode("\n", $css);
    }

    /**
     * Genera CSS para layout grid.
     * 
     * @param string $class Selector CSS
     * @return string CSS de grid
     */
    private function generateGridCss(string $class): string
    {
        $columns = (int) ($this->config['gridColumns'] ?? 3);
        $gap = $this->config['gap'] ?? ($this->config['gridGap'] ?? '20px');
        
        $css = "{$class} { display: grid; grid-template-columns: repeat({$columns}, 1fr); gap: {$gap}; }\n";
        
        // Responsive: 2 columnas en tablet, 1 en móvil
        $css .= "@media (max-width: 768px) { {$class} { grid-template-columns: repeat(2, 1fr); } }\n";
        $css .= "@media (max-width: 480px) { {$class} { grid-template-columns: 1fr; } }";
        
        return $css;
    }

    /**
     * Genera CSS para layout flex.
     * 
     * @param string $class Selector CSS
     * @return string CSS de flex
     */
    private function generateFlexCss(string $class): string
    {
        $direction = $this->config['flexDirection'] ?? 'row';
        $wrap = $this->config['flexWrap'] ?? 'wrap';
        $align = $this->config['alignItems'] ?? ($this->config['flexAlign'] ?? 'stretch');
        $justify = $this->config['justifyContent'] ?? ($this->config['flexJustify'] ?? 'flex-start');
        $gap = $this->config['gap'] ?? '20px';
        
        return "{$class} { display: flex; flex-direction: {$direction}; flex-wrap: {$wrap}; align-items: {$align}; justify-content: {$justify}; gap: {$gap}; }";
    }

    /**
     * Genera CSS para efectos hover.
     * 
     * @param string $class Selector CSS
     * @return string|null CSS de hover o null si no aplica
     */
    private function generateHoverCss(string $class): ?string
    {
        $hoverEffect = $this->config['hoverEffect'] ?? 'none';
        
        switch ($hoverEffect) {
            case 'lift':
                return "{$class} [glorypostitem]:hover { box-shadow: 0 8px 16px rgba(0,0,0,0.1); transform: translateY(-4px); }";
            case 'scale':
                return "{$class} [glorypostitem]:hover { transform: scale(1.02); }";
            case 'glow':
                return "{$class} [glorypostitem]:hover { box-shadow: 0 0 20px rgba(59, 130, 246, 0.3); }";
            default:
                return null;
        }
    }

    /**
     * Genera estilos inline para el contenedor.
     * 
     * @return string Estilos inline
     */
    public function getContainerStyles(): string
    {
        $styles = [];

        // Padding/Margin del contenedor
        if (!empty($this->config['padding'])) {
            $styles[] = 'padding: ' . $this->config['padding'];
        }
        if (!empty($this->config['margin'])) {
            $styles[] = 'margin: ' . $this->config['margin'];
        }

        // Border
        if (!empty($this->config['hasBorder']) && $this->config['hasBorder']) {
            if (!empty($this->config['borderWidth'])) {
                $styles[] = 'border-width: ' . $this->config['borderWidth'];
            }
            if (!empty($this->config['borderStyle'])) {
                $styles[] = 'border-style: ' . $this->config['borderStyle'];
            }
            if (!empty($this->config['borderColor'])) {
                $styles[] = 'border-color: ' . $this->config['borderColor'];
            }
        }
        if (!empty($this->config['borderRadius'])) {
            $styles[] = 'border-radius: ' . $this->config['borderRadius'];
        }

        return implode('; ', $styles);
    }

    /**
     * Genera atributos data-* para el contenedor.
     * 
     * @return string Atributos HTML
     */
    public function getContainerAttributes(): string
    {
        $attrs = [
            'data-post-type="' . esc_attr($this->config['postType'] ?? 'post') . '"',
            'data-posts-per-page="' . esc_attr($this->config['postsPerPage'] ?? 6) . '"',
        ];

        // Agregar layout pattern si existe
        $layoutPattern = $this->config['layoutPattern'] ?? 'none';
        if ($layoutPattern !== 'none') {
            $attrs[] = 'data-pattern="' . esc_attr($layoutPattern) . '"';
        }

        // Agregar hover effect si existe
        $hoverEffect = $this->config['hoverEffect'] ?? 'none';
        if ($hoverEffect !== 'none') {
            $attrs[] = 'data-hover-effect="' . esc_attr($hoverEffect) . '"';
        }

        return implode(' ', $attrs);
    }
}
