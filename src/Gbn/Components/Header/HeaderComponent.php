<?php

namespace Glory\Gbn\Components\Header;

use Glory\Gbn\Components\AbstractComponent;

/**
 * Componente Header para GBN.
 * 
 * Contenedor principal del header del sitio.
 * Permite contener el LogoComponent y MenuComponent como hijos.
 * Replica la estructura de Glory\Components\HeaderRenderer pero de forma independiente.
 * 
 * @role header
 * @selector [gloryHeader]
 */
class HeaderComponent extends AbstractComponent
{
    protected string $id = 'header';
    protected string $label = 'Header';

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): string
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="6" rx="1"/><line x1="4" y1="6" x2="6" y2="6"/><line x1="9" y1="6" x2="15" y2="6"/><line x1="18" y1="6" x2="20" y2="6"/></svg>';
    }

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryHeader',
            'dataAttribute' => 'data-gbn-header'
        ];
    }

    public function getTemplate(): string
    {
        // Template HTML base para insertar un nuevo header
        return '<header gloryHeader class="gbn-header siteMenuW" role="banner">
    <div class="siteMenuContainer">
        <div gloryLogo class="siteMenuLogo">
            <a href="/" rel="home">Logo</a>
        </div>
        <nav gloryMenu class="siteMenuNav" role="navigation">
            <ul class="menu menu-level-1">
                <li><a href="#">Inicio</a></li>
                <li><a href="#">Servicios</a></li>
                <li><a href="#">Contacto</a></li>
            </ul>
        </nav>
        <button aria-label="Toggle menu" class="burger" type="button">
            <span></span>
            <span></span>
        </button>
    </div>
</header>';
    }

    public function getSchema(): array
    {
        return [
            // Tab: Configuración
            [
                'id' => 'isFixed',
                'type' => 'toggle',
                'label' => 'Header Fijo',
                'default' => true,
                'tab' => 'Configuración',
                'description' => 'El header se mantendrá fijo al hacer scroll'
            ],
            [
                'id' => 'showScrollEffect',
                'type' => 'toggle',
                'label' => 'Efecto al Scroll',
                'default' => true,
                'tab' => 'Configuración',
                'description' => 'Aplica efecto visual cuando el usuario hace scroll'
            ],
            [
                'id' => 'scrolledClass',
                'type' => 'text',
                'label' => 'Clase al Scroll',
                'default' => 'scrolled',
                'tab' => 'Configuración',
                'condition' => ['showScrollEffect', '===', true]
            ],
            // Tab: Estilo
            [
                'id' => 'backgroundColor',
                'type' => 'color',
                'label' => 'Color de Fondo',
                'default' => 'transparent',
                'tab' => 'Estilo'
            ],
            [
                'id' => 'backgroundColorScrolled',
                'type' => 'color',
                'label' => 'Color al Scroll',
                'default' => 'rgba(255, 255, 255, 0.9)',
                'tab' => 'Estilo',
                'description' => 'Color de fondo cuando el usuario hace scroll'
            ],
            [
                'id' => 'padding',
                'type' => 'spacing',
                'label' => 'Padding',
                'default' => [
                    'superior' => '0',
                    'derecho' => '0',
                    'inferior' => '0',
                    'izquierdo' => '0'
                ],
                'tab' => 'Estilo'
            ],
            [
                'id' => 'containerMaxWidth',
                'type' => 'text',
                'label' => 'Ancho Máximo Contenedor',
                'default' => '100%',
                'tab' => 'Estilo'
            ],
            [
                'id' => 'backdropBlur',
                'type' => 'slider',
                'label' => 'Blur de Fondo (px)',
                'default' => 0,
                'min' => 0,
                'max' => 30,
                'tab' => 'Estilo',
                'description' => 'Efecto glassmorphism'
            ],
            [
                'id' => 'zIndex',
                'type' => 'text',
                'label' => 'Z-Index',
                'default' => '1000',
                'tab' => 'Estilo'
            ],
            // Tab: Avanzado
            [
                'id' => 'customClass',
                'type' => 'text',
                'label' => 'Clases Personalizadas',
                'default' => '',
                'tab' => 'Avanzado'
            ],
            [
                'id' => 'excludeOnPages',
                'type' => 'text',
                'label' => 'Excluir en Páginas (IDs)',
                'default' => '',
                'tab' => 'Avanzado',
                'description' => 'IDs de páginas separadas por coma donde no se mostrará el header'
            ]
        ];
    }

    public function getDefaults(): array
    {
        return [
            'isFixed' => true,
            'showScrollEffect' => true,
            'scrolledClass' => 'scrolled',
            'backgroundColor' => 'transparent',
            'backgroundColorScrolled' => 'rgba(255, 255, 255, 0.9)',
            'padding' => [
                'superior' => '0',
                'derecho' => '0',
                'inferior' => '0',
                'izquierdo' => '0'
            ],
            'containerMaxWidth' => '100%',
            'backdropBlur' => 0,
            'zIndex' => '1000',
            'customClass' => '',
            'excludeOnPages' => ''
        ];
    }

    /**
     * Los hijos permitidos dentro del Header.
     * El menú y el logo pueden agregarse como componentes independientes.
     */
    public function getAllowedChildren(): array
    {
        return ['logo', 'menu', 'secundario', 'button'];
    }
}
