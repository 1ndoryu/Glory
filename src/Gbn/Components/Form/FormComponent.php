<?php

namespace Glory\Gbn\Components\Form;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasBackground;
use Glory\Gbn\Traits\HasBorder;
use Glory\Gbn\Traits\HasLayoutOptions;
use Glory\Gbn\Icons\IconRegistry;

/**
 * FormComponent - Contenedor de Formulario para GBN
 * 
 * Componente contenedor <form> con configuración de action, method, 
 * y honeypot anti-spam básico.
 * 
 * BUG-007 FIX: Ahora incluye HasLayoutOptions para permitir layout 
 * grid con columnas configurables (ej: 1fr 1fr para formularios de 2 columnas).
 * 
 * @role form
 * @selector [gloryForm]
 */
class FormComponent extends AbstractComponent
{
    use HasSpacing;
    use HasBackground;
    use HasBorder;
    use HasLayoutOptions;

    protected string $id = 'form';
    protected string $label = 'Formulario';

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryForm',
            'dataAttribute' => 'data-gbn-form',
        ];
    }

    public function getDefaults(): array
    {
        return [
            'action' => '',
            'method' => 'POST',
            'formId' => '',
            'successMessage' => '¡Formulario enviado con éxito!',
            'errorMessage' => 'Hubo un error al enviar el formulario.',
            'honeypot' => true,
            'ajaxSubmit' => true,
            // Layout defaults (BUG-007 FIX)
            'layout' => 'flex',
            'direction' => 'column',
            'gap' => 16,
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // Tab: Configuración
        $schema->addOption(
            Option::text('formId', 'ID del Formulario')
                ->default('')
                ->description('Identificador único para AJAX (ej: contacto)')
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::text('action', 'URL de Acción')
                ->default('')
                ->description('Dejar vacío para AJAX interno')
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::iconGroup('method', 'Método HTTP')
                ->options([
                    ['valor' => 'POST', 'etiqueta' => 'POST', 'icon' => IconRegistry::get('http.post')],
                    ['valor' => 'GET', 'etiqueta' => 'GET', 'icon' => IconRegistry::get('http.get')],
                ])
                ->default('POST')
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::toggle('ajaxSubmit', 'Envío AJAX')
                ->default(true)
                ->description('Enviar sin recargar página')
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::toggle('honeypot', 'Honeypot Anti-Spam')
                ->default(true)
                ->tab('Configuración')
        );

        // Tab: Mensajes
        $schema->addOption(
            Option::text('successMessage', 'Mensaje de Éxito')
                ->default('¡Formulario enviado con éxito!')
                ->tab('Mensajes')
        );

        $schema->addOption(
            Option::text('errorMessage', 'Mensaje de Error')
                ->default('Hubo un error al enviar el formulario.')
                ->tab('Mensajes')
        );

        // Tab: Email (Fase 14.5: Configuración de notificación por correo)
        $schema->addOption(
            Option::text('emailSubject', 'Asunto del Email')
                ->default('Nuevo mensaje de formulario: {{formId}}')
                ->description('Usa {{formId}} para insertar el ID del formulario')
                ->tab('Email')
        );

        // Tab: Estilo - Layout Options (BUG-007 FIX)
        // Permite configurar el formulario como flex (columna) o grid (columnas múltiples)
        foreach ($this->getLayoutOptions('full', 'layout') as $option) {
            $option->tab('Estilo');
            $schema->addOption($option);
        }

        foreach ($this->getSpacingOptions() as $option) {
            $option->tab('Estilo');
            $schema->addOption($option);
        }

        $schema->addOption(
            Option::color('backgroundColor', 'Color de Fondo')
                ->allowTransparency()
                ->tab('Estilo')
        );

        $this->addBorderOptions($schema, 'Estilo');

        return $schema->toArray();
    }

    public function getIcon(): string
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 7h10M7 12h10M7 17h6"/></svg>';
    }

    public function getTemplate(): string
    {
        return '<form gloryForm method="post" class="gbn-form"><div gloryDiv style="display:flex;flex-direction:column;gap:16px;"><!-- Agregar campos aquí --></div></form>';
    }

    /**
     * Componentes permitidos como hijos directos del formulario.
     * Incluye todos los componentes de campos de formulario.
     * 
     * @return array<string>
     */
    public function getAllowedChildren(): array
    {
        return ['input', 'textarea', 'select', 'submit', 'secundario'];
    }
}
