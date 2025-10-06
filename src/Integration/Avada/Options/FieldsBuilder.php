<?php

namespace Glory\Integration\Avada\Options;

use Glory\Core\OpcionRepository;

final class FieldsBuilder
{
    public static function buildFields(): array
    {
        $dynamic = Discovery::gatherOptionsFromOpcionManager();

        if (!empty($dynamic)) {
            Logger::log('buildFields:dynamic_found', ['count' => count($dynamic)]);
            $grouped = [];
            foreach ($dynamic as $opt) {
                if (self::shouldExcludeOption($opt)) {
                    Logger::log('buildFields:excluded', ['id' => $opt['id'] ?? '']);
                    continue;
                }
                $sectionSlug  = isset($opt['seccion']) && $opt['seccion'] ? (string) $opt['seccion'] : 'general';
                // Prefijar con 'glory_' para evitar colisiones con secciones nativas y asegurar id string
                $sectionId    = 'glory_' . sanitize_key($sectionSlug);
                $sectionLabel = isset($opt['etiquetaSeccion']) && $opt['etiquetaSeccion'] ? (string) $opt['etiquetaSeccion'] : esc_html__('Glory Options', 'glory-ab');
                if (!isset($grouped[$sectionId])) {
                    $grouped[$sectionId] = [
                        'label'  => $sectionLabel,
                        'id'     => $sectionId,
                        'type'   => 'sub-section',
                        'icon'   => true,
                        // Importante: mantener 'fields' como array indexado (lista) como espera Avada
                        'fields' => [],
                    ];
                }
                $field = self::mapGloryOptionToAvadaField($opt);
                if ($field && !empty($field['id'])) {
                    // Mantener lista indexada numéricamente como espera Avada/FusionRedux.
                    $grouped[$sectionId]['fields'][] = $field;
                } else {
                    Logger::log('buildFields:map_skipped', ['id' => $opt['id'] ?? '']);
                }
            }
            // NO convertir a array indexado de forma que se pierdan las claves; Avada usa la clave como subSectionId
            $result = $grouped;
            Logger::log('buildFields:dynamic_result', ['subsections' => count($result)]);
            return $result;
        }

        // Fallback con clave string estable
        $fields = [];
        $fields['glory_integrations'] = [
            'label' => esc_html__('Integrations & Tracking', 'glory-ab'),
            'id'    => 'glory_integrations',
            'type'  => 'sub-section',
            'icon'  => true,
            // Lista de campos indexada numéricamente.
            'fields' => [
                [
                    'label'       => esc_html__('Google Search Console Verification Code', 'glory-ab'),
                    'description' => esc_html__('Paste the content of the GSC verification meta tag.', 'glory-ab'),
                    'id'          => 'glory_gsc_verification_code',
                    'type'        => 'text',
                    'default'     => '',
                ],
                [
                    'label'       => esc_html__('Google Analytics 4 Measurement ID', 'glory-ab'),
                    'description' => esc_html__('Enter your GA4 Measurement ID (e.g., G-XXXXXXXXXX).', 'glory-ab'),
                    'id'          => 'glory_ga4_measurement_id',
                    'type'        => 'text',
                    'default'     => '',
                ],
                [
                    'label'       => esc_html__('Custom Header Scripts', 'glory-ab'),
                    'description' => esc_html__('Scripts or meta tags to include in <head>.', 'glory-ab'),
                    'id'          => 'glory_custom_header_scripts',
                    'type'        => 'code',
                    'choices'     => [
                        'language' => 'html',
                        'height'   => 180,
                        'theme'    => 'chrome',
                        'minLines' => 5,
                        'maxLines' => 16,
                    ],
                    'default'     => '',
                ],
            ],
        ];

        // Mantener claves string
        $result = $fields;
        Logger::log('buildFields:fallback_result', ['subsections' => count($result)]);
        return $result;
    }

    public static function shouldExcludeOption(array $opt): bool
    {
        $id = isset($opt['id']) ? (string) $opt['id'] : '';
        if (in_array($id, ['glory_logo_mode','glory_logo_text','glory_logo_image'], true)) {
            return true;
        }
        return false;
    }

    public static function mapGloryOptionToAvadaField(array $opt): ?array
    {
        if (empty($opt['id']) || empty($opt['tipo'])) {
            return null;
        }
        $typeMap = [
            'text'      => 'text',
            'textarea'  => 'textarea',
            'checkbox'  => 'switch',
            'toggle'    => 'switch',
            'radio'     => 'radio-buttonset',
            'select'    => 'select',
            'color'     => 'color-alpha',
            'numero'    => 'slider',
            'imagen'    => 'upload',
            'richText'  => 'code',
        ];
        $type = $typeMap[$opt['tipo']] ?? 'text';
        $field = [
            'label'       => isset($opt['etiqueta']) ? (string) $opt['etiqueta'] : (string) $opt['id'],
            'description' => isset($opt['descripcion']) ? (string) $opt['descripcion'] : '',
            'id'          => (string) $opt['id'],
            'type'        => $type,
            'default'     => $opt['valorDefault'] ?? '',
        ];
        if ('switch' === $type) {
            $def = $opt['valorDefault'] ?? false;
            if (is_string($def)) {
                $v = strtolower(trim($def));
                $def = in_array($v, ['1','true','on','yes'], true);
            }
            $field['default'] = $def ? '1' : '0';
            try {
                $repoVal = OpcionRepository::get($field['id']);
                if ($repoVal !== OpcionRepository::getCentinela()) {
                    $normRepo = Normalizer::normalizeOptionValueForWrite($field['id'], $repoVal);
                    $field['default'] = (string) ((int) $normRepo);
                }
            } catch (\Throwable $t) {
            }
        }
        if ('select' === $type || 'radio-buttonset' === $type) {
            if (!empty($opt['opciones']) && is_array($opt['opciones'])) {
                $choices = [];
                foreach ($opt['opciones'] as $k => $v) {
                    $choices[(string) $k] = (string) $v;
                }
                $field['choices'] = $choices;
            }
        }
        if ('code' === $type) {
            $field['choices'] = [
                'language' => 'html',
                'height'   => 180,
                'theme'    => 'chrome',
                'minLines' => 5,
                'maxLines' => 16,
            ];
        }
        if ('slider' === $type) {
            if (isset($opt['min']) || isset($opt['max']) || isset($opt['step'])) {
                $field['choices'] = [
                    'min'  => isset($opt['min']) ? (int) $opt['min'] : 0,
                    'max'  => isset($opt['max']) ? (int) $opt['max'] : 100,
                    'step' => isset($opt['step']) ? (int) $opt['step'] : 1,
                ];
            }
        }
        return $field;
    }
}


