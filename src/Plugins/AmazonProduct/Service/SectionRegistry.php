<?php

namespace Glory\Plugins\AmazonProduct\Service;

/**
 * Registro automatico de secciones desde shortcodes.
 * 
 * Responsabilidades:
 * - Detectar shortcodes con atributo section="xxx"
 * - Registrar nuevas secciones automaticamente
 * - Actualizar defaults cuando el shortcode cambia
 * - Rastrear origen (archivo, linea) del shortcode
 */
class SectionRegistry
{
    private SectionManager $manager;

    /* 
     * Secciones procesadas en esta request (evita duplicados) 
     */
    private static array $processedInRequest = [];

    public function __construct(?SectionManager $manager = null)
    {
        $this->manager = $manager ?? new SectionManager();
    }

    /**
     * Procesa los atributos del shortcode y registra/actualiza la seccion.
     * 
     * Llamado desde ProductRenderer::renderShortcode() cuando existe section="xxx".
     * 
     * @param array $atts Atributos del shortcode
     * @return array Atributos modificados (con excluded_ids aplicados)
     */
    public function processShortcode(array $atts): array
    {
        $sectionSlug = $atts['section'] ?? '';

        if (empty($sectionSlug)) {
            return $atts;
        }

        $sectionSlug = sanitize_key($sectionSlug);

        if (isset(self::$processedInRequest[$sectionSlug])) {
            return $this->getEffectiveAtts($sectionSlug, $atts);
        }

        $defaults = $this->extractDefaults($atts);
        $sourceInfo = $this->getSourceInfo();

        $this->manager->register(
            $sectionSlug,
            $defaults,
            $sourceInfo['file'],
            $sourceInfo['line']
        );

        self::$processedInRequest[$sectionSlug] = true;

        return $this->getEffectiveAtts($sectionSlug, $atts);
    }

    /**
     * Obtiene los atributos efectivos para una seccion.
     */
    public function getEffectiveAtts(string $sectionSlug, array $originalAtts): array
    {
        $result = $this->manager->getEffectiveConfig($sectionSlug, $originalAtts);

        $effectiveAtts = $result['config'];
        $effectiveAtts['_excluded_ids'] = $result['excluded_ids'];
        $effectiveAtts['_section_slug'] = $sectionSlug;

        return $effectiveAtts;
    }

    /**
     * Extrae los atributos que seran los defaults de la seccion.
     * Excluye el atributo 'section' en si mismo.
     */
    private function extractDefaults(array $atts): array
    {
        $defaults = [];
        $supportedAttrs = [
            'search',
            'exclude',
            'orderby',
            'order',
            'limit',
            'only_deals',
            'only_prime',
            'min_price',
            'max_price',
            'min_rating',
            'category',
            'hide_filters',
            'pagination',
        ];

        foreach ($supportedAttrs as $attr) {
            if (isset($atts[$attr]) && $atts[$attr] !== '') {
                $defaults[$attr] = $atts[$attr];
            }
        }

        return $defaults;
    }

    /**
     * Intenta obtener informacion del archivo y linea donde esta el shortcode.
     * 
     * Usa el backtrace para encontrar el template que contiene el shortcode.
     */
    private function getSourceInfo(): array
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        $wpContentDir = str_replace('\\', '/', WP_CONTENT_DIR);

        foreach ($backtrace as $frame) {
            if (!isset($frame['file'])) {
                continue;
            }

            $file = str_replace('\\', '/', $frame['file']);

            if (
                strpos($file, '/themes/') !== false ||
                strpos($file, '/plugins/') !== false
            ) {
                if (strpos($file, '/Glory/src/') === false) {
                    $relativePath = str_replace($wpContentDir, '', $file);
                    return [
                        'file' => ltrim($relativePath, '/'),
                        'line' => $frame['line'] ?? 0,
                    ];
                }
            }
        }

        global $post;
        if ($post) {
            return [
                'file' => 'post:' . $post->ID,
                'line' => 0,
            ];
        }

        return [
            'file' => 'unknown',
            'line' => 0,
        ];
    }

    /**
     * Limpia el registro de secciones procesadas (para testing).
     */
    public function clearProcessed(): void
    {
        self::$processedInRequest = [];
    }
}
