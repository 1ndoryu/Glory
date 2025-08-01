<?php
namespace Glory\Handler\Form;

use Glory\Handler\Form\FormHandlerInterface;
use Glory\Core\OpcionRepository;

class GuardarHomeBlurHandler implements FormHandlerInterface
{
    public function procesar(array $postDatos, array $archivos): array
    {
        // Verificar permisos.
        if (!current_user_can('edit_theme_options')) {
            throw new \Exception('No tienes permisos para realizar esta acción.');
        }

        // Mapeo de claves recibidas a las claves de opciones en BD.
        $mapeo = [
            'enabled'  => 'home_blur_effect_enabled',
            'opacity'  => 'home_blur_opacity',
            'blur'     => 'home_blur_amount',
            'c1_top'   => 'home_blur_circle1_top',
            'c1_left'  => 'home_blur_circle1_left',
            'c2_top'   => 'home_blur_circle2_top',
            'c2_left'  => 'home_blur_circle2_left',
        ];

        foreach ($mapeo as $clavePost => $claveOption) {
            if (isset($postDatos[$clavePost])) {
                $valor = sanitize_text_field($postDatos[$clavePost]);
                OpcionRepository::save($claveOption, $valor);
            }
        }

        return ['alert' => 'Ajustes del efecto blur guardados.'];
    }
} 