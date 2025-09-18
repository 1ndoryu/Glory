# Contexto: Cómo crear elementos personalizados para Avada Builder (Fusion Builder)

Este documento explica, paso a paso y con detalle, cómo crear nuevos elementos para Avada Builder dentro de Glory. Resume patrones detectados en el core de Avada y buenas prácticas para extender el constructor de forma segura.

---

## 1) Anatomía de un elemento en Avada

Un elemento consta de dos partes principales:

1. Clase PHP que extiende `Fusion_Element` (global, sin namespace) y registra un shortcode.
2. Mapeo del elemento al UI del builder con `fusion_builder_map( fusion_builder_frontend_data( ... ) )`.

Avada carga sus elementos cuando dispara el hook `fusion_builder_before_init`. Ahí es donde debemos incluir nuestra clase e invocar el mapeo.

---

## 2) Estructura en Glory

- Integración Avada: `Glory/src/Integration/Avada/AvadaIntegration.php`
  - Registra los hooks y llama a `fusion_builder_map` cuando el builder inicializa.
  - Carga la clase del elemento desde `Glory/src/Integration/Avada/Elements/`.
- Elementos: `Glory/src/Integration/Avada/Elements/*`
  - Ejemplo existente: `FusionSC_GloryContentRender.php`.

Mantén los elementos en esta carpeta y añade aquí tu documentación y ejemplos.

---

## 3) Patrón base de un elemento

1) Clase del elemento (global, sin namespace) que extiende `Fusion_Element` y registra el shortcode en `__construct()`.

```php
if ( ! class_exists( 'FusionSC_MiElemento' ) && class_exists( 'Fusion_Element' ) ) {
    class FusionSC_MiElemento extends Fusion_Element {
        private $counter = 1;

        public function __construct() {
            parent::__construct();
            add_shortcode( 'mi_elemento', [ $this, 'render' ] );
        }

        public static function get_element_defaults() {
            return [
                'titulo'   => '',
                'clase'    => '',
                'contenido'=> '',
            ];
        }

        public function render( $args, $content = '' ) {
            $this->defaults = self::get_element_defaults();
            $this->args     = FusionBuilder::set_shortcode_defaults( $this->defaults, $args, 'mi_elemento' );

            $this->set_element_id( $this->counter );

            $titulo = trim( (string) ( $this->args['titulo'] ?? '' ) );
            $clase  = trim( (string) ( $this->args['clase'] ?? '' ) );

            $html = '';
            if ( '' !== $titulo ) {
                $html .= '<h3>' . esc_html( $titulo ) . '</h3>';
            }

            $html .= '<div class="mi-elemento ' . esc_attr( $clase ) . '">';
            $html .= do_shortcode( $content );
            $html .= '</div>';

            $this->counter++;
            $this->on_render();
            return $html;
        }
    }

    new FusionSC_MiElemento();
}
```

2) Mapeo del elemento (se hace en `AvadaIntegration::registerElement()`):

```php
fusion_builder_map(
  fusion_builder_frontend_data(
    'FusionSC_MiElemento',
    [
      'name'            => esc_html__( 'Mi Elemento', 'glory' ),
      'shortcode'       => 'mi_elemento',
      'icon'            => 'fusiona-star',
      'allow_generator' => true,
      'inline_editor'   => false,
      'help_url'        => 'https://example.com/docs/mi-elemento',
      'params'          => [
        [ 'type' => 'textfield', 'heading' => esc_html__( 'Título', 'glory' ), 'param_name' => 'titulo', 'default' => '' ],
        [ 'type' => 'textfield', 'heading' => esc_html__( 'Clase CSS', 'glory' ), 'param_name' => 'clase', 'default' => '' ],
        [ 'type' => 'tinymce',   'heading' => esc_html__( 'Contenido', 'glory' ), 'param_name' => 'element_content', 'default' => '' ],
      ],
    ]
  )
);
```

---

## 4) Tipos de controles y dependencias

Parámetros comunes soportados en `params` (no exhaustivo):
- `textfield`, `textarea`, `tinymce`
- `select`, `radio_button_set`, `checkbox_button_set`
- `range`, `colorpickeralpha`
- `upload` (selector de medios), según versión

Cada control define:
- `type`: tipo de control.
- `param_name`: nombre de la variable que llega a `$args` en `render()`.
- `heading`: etiqueta en el UI.
- `default`: valor por defecto.
- `value`: para selects/conjuntos (array de opciones `key => label`).
- `description`: ayuda en el UI.
- `dependency`: dependencias condicionales. Ejemplo:

```php
'dependency' => [
  [ 'element' => 'modo', 'value' => 'avanzado', 'operator' => '==' ],
]
```

---

## 5) Buenas prácticas en `render()`

- Usar `FusionBuilder::set_shortcode_defaults( $defaults, $args, $shortcode_slug )` para mezclar defaults con args.
- Sanitizar siempre: `esc_html`, `esc_attr`, `sanitize_key`, etc.
- Si ejecutas `do_shortcode`, envuelve el contenido con `ob_start()/ob_get_clean()` si necesitas capturar salida.
- Llamar a `$this->on_render()` antes de retornar para activar comportamientos de editor en vivo.
- Evitar `echo` directo salvo en bloques controlados; preferir construir `$html` y `return`.

---

## 6) CSS/JS dinámico y assets

`Fusion_Element` provee métodos para manejar estilos y scripts:

- `add_css_files()`: registra CSS base o por media queries.
- `add_scripts()`: punto para incluir scripts globales.
- `on_first_render()`: útil en editor en vivo; encolar scripts específicos de este elemento solo cuando se renderiza por primera vez. Ejemplo:

```php
public function on_first_render() {
    Fusion_Dynamic_JS::enqueue_script( 'fusion-mi-elemento' );
}
```

- `add_styling()` + `add_css_property()` + `parse_css()`: generar CSS dinámico en función de `args`. Ejemplo:

```php
protected function add_styling() {
    $css = [];
    $selector = '.mi-elemento-' . $this->get_element_id();
    if ( ! $this->is_default( 'color_texto' ) ) {
        $this->add_css_property( $selector, 'color', $this->args['color_texto'] );
    }
    return $css;
}
```

---

## 7) Inline editor y previews

- `inline_editor` en `fusion_builder_map`: si `true`, Avada habilita edición inline (útil para textos simples).
- `preview` y `preview_id`: permiten usar una plantilla de vista previa específica (archivo PHP de preview) que Avada renderiza en el panel.
- `subparam_map`: mapeo de sub-parámetros (por ejemplo tipografías agrupadas) a `param_name`.

Consulta ejemplos en core: `fusion-title.php`, `fusion-text.php`.

---

## 8) Convenciones de nombres

- Clase: `FusionSC_MiElemento` (prefijo `FusionSC_`, sufijo descriptivo). Sin namespace.
- Shortcode: `mi_elemento` (prefijo único del proyecto: `glory_...` recomendado para evitar colisiones).
- `param_name`: snake_case, legible y consistente con el render.

---

## 9) Dónde y cuándo cargar

- Hook principal: `fusion_builder_before_init`.
  - Ahí: `require_once` de la clase del elemento y `fusion_builder_map(...)`.
- Evitar cargar elementos si Avada no está presente: comprueba `function_exists('fusion_builder_map')` y `class_exists('Fusion_Element')` antes de proceder.

---

## 10) Plantillas (TemplateRegistry)

Glory implementa `Glory\Utility\TemplateRegistry` para registrar callbacks de plantillas reutilizables por `ContentRender` u otros elementos. Patrón:

```php
TemplateRegistry::register(
  'mi_template_id',
  function( \WP_Post $post, array $config = [] ) {
    // Render del item
  },
  [ 'post', 'page', 'avada_portfolio' ],
  'Mi etiqueta legible'
);
```

Usa `TemplateRegistry::options()` para llenar selects de plantillas y `appliesTo($id)` para validar compatibilidad con un `post_type` dado.

---

## 11) Checklist rápido para crear un nuevo elemento

1. Crear `Glory/src/Integration/Avada/Elements/FusionSC_MiElemento.php` con la clase, defaults y `render()`.
2. En `AvadaIntegration::registerElement()`, agregar el mapeo con `fusion_builder_map` y definir `params`.
3. (Opcional) Añadir CSS/JS en `add_css_files()`, `on_first_render()`.
4. Probar en Avada Builder: buscar tu elemento por el nombre definido en `name`.
5. Validar que el shortcode funciona fuera del builder (Gutenberg/clásico) si aplica.

---

## 12) Depuración

- Si el elemento no aparece: verifica que `fusion_builder_before_init` se ejecute y que no haya `fatal errors` (revisa `wp-content/debug.log`).
- Comprueba que no haya conflicto de clases (dos definiciones de `FusionSC_*`).
- Asegúrate de que los `param_name` usados en `params` coincidan con los leídos en `render()`.

---

## 13) Recursos del core (para referencia)

- Base: `plugins/fusion-builder/inc/class-fusion-element.php`
- Ejemplos: `plugins/fusion-builder/shortcodes/fusion-title.php`, `fusion-text.php`
- Hook de mapeo: `fusion_builder_before_init`

Mantén este archivo actualizado a medida que agreguemos elementos y patrones reutilizables.
