<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

/**
 * API Setup Wizard Tab - Guia paso a paso para configurar la API de RapidAPI.
 * 
 * Proporciona una experiencia guiada para que el usuario configure
 * su cuenta de RapidAPI y obtenga su API Key.
 */
class ApiSetupWizardTab implements TabInterface
{
    public function getSlug(): string
    {
        return 'api-setup-wizard';
    }

    public function getLabel(): string
    {
        return 'Configuracion Guiada';
    }

    public function render(): void
    {
        $this->enqueueAssets();
        $this->renderWizard();
    }

    private function enqueueAssets(): void
    {
        wp_enqueue_style(
            'amazon-api-wizard',
            get_template_directory_uri() . '/Glory/src/Plugins/AmazonProduct/assets/css/api-wizard.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'amazon-api-wizard',
            get_template_directory_uri() . '/Glory/src/Plugins/AmazonProduct/assets/js/api-wizard.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('amazon-api-wizard', 'apiWizardData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('api_wizard_nonce'),
            'currentApiKey' => get_option('amazon_api_key', ''),
        ]);
    }

    private function renderWizard(): void
    {
        $currentApiKey = get_option('amazon_api_key', '');
        $isConfigured = !empty($currentApiKey);
?>
        <div id="api-wizard-container" class="api-wizard-container">
            <!-- Header del Wizard -->
            <div class="wizard-header">
                <h2>Configuracion de API de Amazon</h2>
                <p class="wizard-subtitle">
                    Sigue estos pasos para configurar tu acceso a la API de productos de Amazon.
                    Este proceso solo tomara unos minutos.
                </p>

                <?php if ($isConfigured): ?>
                    <div class="wizard-status wizard-status-success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span>API configurada correctamente</span>
                    </div>
                <?php else: ?>
                    <div class="wizard-status wizard-status-pending">
                        <span class="dashicons dashicons-warning"></span>
                        <span>API pendiente de configurar</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Indicador de Progreso -->
            <div class="wizard-progress">
                <div class="progress-step active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label">Crear Cuenta</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label">Elegir Plan</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label">Obtener Key</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="4">
                    <div class="step-number">4</div>
                    <div class="step-label">Configurar</div>
                </div>
            </div>

            <!-- Contenedor de Pasos -->
            <div class="wizard-steps">
                <!-- Paso 1: Crear cuenta en RapidAPI -->
                <div class="wizard-step active" data-step="1">
                    <div class="step-content">
                        <div class="step-icon">
                            <span class="dashicons dashicons-admin-users"></span>
                        </div>
                        <h3>Paso 1: Crear cuenta en RapidAPI</h3>
                        <p class="step-description">
                            RapidAPI es una plataforma que nos permite acceder a la API de datos de Amazon
                            de forma segura y con precios accesibles.
                        </p>

                        <div class="step-instructions">
                            <ol>
                                <li>Haz clic en el boton de abajo para ir a RapidAPI</li>
                                <li>Crea una cuenta gratuita (puedes usar Google, GitHub o email)</li>
                                <li>Verifica tu email si es necesario</li>
                                <li>Una vez tengas tu cuenta, vuelve aqui y continua al siguiente paso</li>
                            </ol>
                        </div>

                        <div class="step-action">
                            <a href="https://rapidapi.com/auth/sign-up"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="button button-primary button-hero wizard-external-link">
                                <span class="dashicons dashicons-external"></span>
                                Crear cuenta en RapidAPI
                            </a>
                        </div>

                        <div class="step-tip">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <strong>Tip:</strong> La cuenta de RapidAPI es gratuita. Solo pagaras por el plan de API que elijas.
                        </div>
                    </div>
                </div>

                <!-- Paso 2: Elegir plan de API -->
                <div class="wizard-step" data-step="2">
                    <div class="step-content">
                        <div class="step-icon">
                            <span class="dashicons dashicons-cart"></span>
                        </div>
                        <h3>Paso 2: Suscribirse al plan de la API</h3>
                        <p class="step-description">
                            Ahora necesitas suscribirte a la API de Amazon Data.
                            Recomendamos el plan de <strong>$20/mes</strong> que incluye suficientes
                            llamadas para gestionar cientos de productos.
                        </p>

                        <div class="plan-comparison">
                            <div class="plan-card recommended">
                                <div class="plan-badge">Recomendado</div>
                                <h4>Plan Pro</h4>
                                <div class="plan-price">$20<span>/mes</span></div>
                                <ul class="plan-features">
                                    <li><span class="dashicons dashicons-yes"></span> 10,000 llamadas/mes</li>
                                    <li><span class="dashicons dashicons-yes"></span> Datos en tiempo real</li>
                                    <li><span class="dashicons dashicons-yes"></span> Soporte prioritario</li>
                                    <li><span class="dashicons dashicons-yes"></span> Ideal para tiendas medianas</li>
                                </ul>
                            </div>
                        </div>

                        <div class="step-instructions">
                            <ol>
                                <li>Haz clic en el boton para ir a la pagina de precios de la API</li>
                                <li>Selecciona el plan "Pro" de $20/mes</li>
                                <li>Completa el pago con tu tarjeta</li>
                                <li>Una vez suscrito, continua al siguiente paso</li>
                            </ol>
                        </div>

                        <div class="step-action">
                            <a href="https://rapidapi.com/magedata/api/amazon-data/pricing"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="button button-primary button-hero wizard-external-link">
                                <span class="dashicons dashicons-external"></span>
                                Ver planes y suscribirse
                            </a>
                        </div>

                        <div class="step-tip">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <strong>Tip:</strong> El plan Pro de $20/mes es suficiente para la mayoria de tiendas.
                            Revisa los planes disponibles para elegir el que mejor se adapte a tu volumen.
                        </div>
                    </div>
                </div>

                <!-- Paso 3: Obtener API Key -->
                <div class="wizard-step" data-step="3">
                    <div class="step-content">
                        <div class="step-icon">
                            <span class="dashicons dashicons-admin-network"></span>
                        </div>
                        <h3>Paso 3: Copiar tu API Key</h3>
                        <p class="step-description">
                            Ahora necesitas copiar tu clave de API (X-RapidAPI-Key) desde RapidAPI.
                        </p>

                        <div class="step-instructions">
                            <ol>
                                <li>Haz clic en el boton para ir al panel de la API</li>
                                <li>Busca la seccion <strong>"X-RapidAPI-Key"</strong> en el panel derecho</li>
                                <li>Haz clic en el icono de copiar junto a la clave</li>
                                <li>Vuelve aqui y pega la clave en el siguiente paso</li>
                            </ol>
                        </div>

                        <div class="api-key-visual">
                            <div class="visual-header">Donde encontrar tu API Key:</div>
                            <div class="visual-mockup">
                                <div class="mockup-label">X-RapidAPI-Key</div>
                                <div class="mockup-value">
                                    <span class="key-placeholder">abc123def456...</span>
                                    <span class="dashicons dashicons-clipboard"></span>
                                </div>
                            </div>
                        </div>

                        <div class="step-action">
                            <a href="https://rapidapi.com/magedata/api/amazon-data/playground"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="button button-primary button-hero wizard-external-link">
                                <span class="dashicons dashicons-external"></span>
                                Ir al panel de RapidAPI
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Paso 4: Configurar API Key -->
                <div class="wizard-step" data-step="4">
                    <div class="step-content">
                        <div class="step-icon">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                        <h3>Paso 4: Guardar tu API Key</h3>
                        <p class="step-description">
                            Pega tu clave de API aqui para completar la configuracion.
                        </p>

                        <div class="api-key-form">
                            <div class="form-group">
                                <label for="wizard-api-key">X-RapidAPI-Key</label>
                                <input type="text"
                                    id="wizard-api-key"
                                    class="wizard-api-key-input"
                                    placeholder="Pega tu API Key aqui..."
                                    value="<?php echo esc_attr($currentApiKey); ?>">
                                <p class="field-description">
                                    Esta clave se guardara de forma segura en tu sitio WordPress.
                                </p>
                            </div>

                            <div class="form-actions">
                                <button type="button"
                                    id="wizard-test-api"
                                    class="button button-secondary">
                                    <span class="dashicons dashicons-networking"></span>
                                    Probar conexion
                                </button>
                                <button type="button"
                                    id="wizard-save-api"
                                    class="button button-primary button-hero">
                                    <span class="dashicons dashicons-saved"></span>
                                    Guardar API Key
                                </button>
                            </div>

                            <div id="wizard-api-result" class="api-result hidden"></div>
                        </div>

                        <div class="step-tip success-tip hidden" id="wizard-success-message">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <strong>Felicidades!</strong> Tu API esta configurada correctamente.
                            Ya puedes empezar a importar productos de Amazon.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navegacion del Wizard -->
            <div class="wizard-navigation">
                <button type="button" id="wizard-prev" class="button button-secondary" disabled>
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    Anterior
                </button>
                <button type="button" id="wizard-next" class="button button-primary">
                    Siguiente
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </button>
            </div>

            <!-- Ayuda adicional -->
            <div class="wizard-help">
                <h4><span class="dashicons dashicons-editor-help"></span> Necesitas ayuda?</h4>
                <p>
                    Si tienes problemas durante la configuracion, puedes:
                </p>
                <ul>
                    <li>Revisar la pestaña de <strong>Ayuda</strong> en este panel</li>
                    <li>Contactar con soporte tecnico +58 412 082 5234</li>
                </ul>
            </div>

            <!-- FAQ Section -->
            <div class="wizard-faq">
                <h3><span class="dashicons dashicons-format-chat"></span> Preguntas Frecuentes</h3>

                <div class="faq-item">
                    <div class="faq-question">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                        <strong>Como recibo el dinero de las ventas?</strong>
                    </div>
                    <div class="faq-answer">
                        <p>Las comisiones se gestionan directamente a traves de tu cuenta de <strong>Amazon Afiliados</strong> (Amazon Associates).
                            El sistema funciona asi:</p>
                        <ul>
                            <li><strong>Tag de afiliado:</strong> Cada producto tiene tu tag de afiliado (configurado en "API Settings").
                                Cuando alguien compra a traves de tu enlace, Amazon registra la comision.</li>
                            <li><strong>Metodos de pago:</strong> Puedes recibir el dinero por deposito directo en cuenta bancaria (IBAN),
                                cheque regalo de Amazon, o cheque postal.</li>
                            <li><strong>Umbral minimo:</strong> El pago minimo es de 25 euros para deposito directo o cheque regalo.</li>
                            <li><strong>Plazo:</strong> Amazon paga aproximadamente 60 dias despues del cierre del mes
                                (ej: comisiones de enero se pagan a finales de marzo).</li>
                        </ul>
                        <p><em>Importante:</em> Debes configurar tu informacion fiscal en tu cuenta de Amazon Afiliados para recibir pagos.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                        <strong>Cual es la diferencia con AAWP?</strong>
                    </div>
                    <div class="faq-answer">
                        <p>El funcionamiento de las comisiones es <strong>identico</strong> a como era con AAWP:</p>
                        <ul>
                            <li>Ambos sistemas usan tu tag de Amazon Afiliados.</li>
                            <li>Las comisiones se registran igual en tu cuenta de Amazon Associates.</li>
                            <li>El metodo de pago es el mismo que tenias configurado.</li>
                        </ul>
                        <p>La unica diferencia es la fuente de datos: en lugar de usar la API oficial de Amazon (PA-API),
                            usamos RapidAPI que es mas accesible y tiene mejor relacion calidad/precio.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                        <strong>Como funciona la API de pago de RapidAPI?</strong>
                    </div>
                    <div class="faq-answer">
                        <p>RapidAPI es un intermediario que nos da acceso a los datos de productos de Amazon:</p>
                        <ul>
                            <li><strong>Plan Pro ($20/mes):</strong> 10,000 llamadas/mes - recomendado para tiendas con trafico medio.</li>
                            <li><strong>Otros planes:</strong> Hay planes de mayor capacidad si necesitas mas llamadas.</li>
                            <li><strong>Llamadas:</strong> Cada vez que buscas o importas un producto, se usa una llamada.</li>
                            <li><strong>Cache:</strong> El sistema guarda los datos localmente para minimizar llamadas innecesarias.</li>
                        </ul>
                        <p>El pago de RapidAPI es <strong>independiente</strong> de las comisiones de Amazon.
                            RapidAPI cobra por el acceso a los datos; Amazon te paga las comisiones por ventas.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                        <strong>Hay restriccion en la cantidad de productos?</strong>
                    </div>
                    <div class="faq-answer">
                        <p><strong>No hay limite</strong> en la cantidad de productos que puedes mostrar en tu web.
                            Los productos se guardan en tu base de datos de WordPress.</p>
                        <p>El unico limite es el numero de <strong>llamadas a la API</strong> segun tu plan,
                            pero esto solo afecta cuando importas o actualizas productos, no cuando los muestras.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                        <strong>Como agrego productos a mi tienda?</strong>
                    </div>
                    <div class="faq-answer">
                        <p>Hay varias formas de agregar productos, algunas <strong>requieren API</strong> y otras <strong>no</strong>:</p>

                        <p><strong>Sin API (manual):</strong></p>
                        <ul>
                            <li><strong>Como post type:</strong> Ve a "Productos Amazon" en el menu lateral de WordPress y crea un nuevo producto manualmente, igual que crearias una pagina o entrada.</li>
                            <li><strong>Importar HTML:</strong> Copia el HTML de la pagina del producto en Amazon (Ctrl+U), ve a "Manual Import" y pegalo. El sistema extraera los datos automaticamente.</li>
                        </ul>

                        <p><strong>Con API (automatico):</strong></p>
                        <ul>
                            <li><strong>Busqueda:</strong> En "Import Products", busca por palabra clave y selecciona los productos que quieres importar.</li>
                            <li><strong>Por ASIN:</strong> Si conoces el codigo ASIN del producto (lo encuentras en la URL de Amazon), importalo directamente.</li>
                            <li><strong>Ofertas:</strong> La pestaña "Import Deals" trae ofertas actuales automaticamente.</li>
                            <li><strong>Sincronizacion:</strong> Configura actualizaciones automaticas de precios en "API Settings".</li>
                        </ul>

                        <p><em>Nota:</em> Los metodos sin API son utiles si no quieres pagar por la API o para productos puntuales.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                        <strong>Donde configuro mi tag de Amazon Afiliados?</strong>
                    </div>
                    <div class="faq-answer">
                        <p>Ve a la pestaña <strong>"API Settings"</strong> en este mismo panel y busca el campo
                            <strong>"Amazon Affiliate Tag"</strong>. Introduce tu tag (ej: <code>mitag-21</code>) y guarda.
                        </p>
                        <p>Este tag se anadira automaticamente a todos los enlaces de productos.</p>
                    </div>
                </div>
            </div>
        </div>
<?php
    }
}
