<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

use Glory\Plugins\AmazonProduct\Service\LicenseService;
use Glory\Plugins\AmazonProduct\Model\License;

/**
 * Tab para administrar licencias de clientes.
 * Solo visible en modo SERVIDOR.
 */
class LicensesTab implements TabInterface
{
    public function getSlug(): string
    {
        return 'licenses';
    }

    public function getLabel(): string
    {
        return 'Licencias';
    }

    public function render(): void
    {
        $this->handleActions();

        $counts = LicenseService::countByStatus();
        $licenses = LicenseService::getAll(['limit' => 100]);
?>
        <div id="licencias-tab">
            <h3>Gestion de Licencias</h3>

            <!-- Resumen -->
            <div class="stats-cards" style="display: flex; gap: 15px; margin-bottom: 20px;">
                <div style="background: #e8f5e9; padding: 15px; border-radius: 5px; min-width: 120px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #2e7d32;"><?php echo $counts['active']; ?></div>
                    <div style="color: #666;">Activas</div>
                </div>
                <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; min-width: 120px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #1565c0;"><?php echo $counts['trial']; ?></div>
                    <div style="color: #666;">Trial</div>
                </div>
                <div style="background: #fff3e0; padding: 15px; border-radius: 5px; min-width: 120px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #ef6c00;"><?php echo $counts['expired']; ?></div>
                    <div style="color: #666;">Expiradas</div>
                </div>
                <div style="background: #fce4ec; padding: 15px; border-radius: 5px; min-width: 120px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #c62828;"><?php echo $counts['suspended']; ?></div>
                    <div style="color: #666;">Suspendidas</div>
                </div>
            </div>

            <!-- Boton crear licencia manual -->
            <div style="margin-bottom: 20px;">
                <button type="button" class="button button-primary" onclick="document.getElementById('crear-licencia-form').style.display='block'">
                    + Crear Licencia Manual
                </button>
            </div>

            <!-- Formulario crear licencia -->
            <div id="crear-licencia-form" style="display: none; background: #f5f5f5; padding: 20px; margin-bottom: 20px; border-radius: 5px;">
                <h4>Crear Nueva Licencia</h4>
                <form method="post">
                    <?php wp_nonce_field('crear_licencia', 'license_nonce'); ?>
                    <input type="hidden" name="action" value="crear_licencia">
                    <table class="form-table">
                        <tr>
                            <th><label for="email">Email del cliente</label></th>
                            <td><input type="email" name="email" id="email" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="gb_limit">Limite GB</label></th>
                            <td><input type="number" name="gb_limit" id="gb_limit" value="4" step="0.5" min="0.5" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><label for="days_valid">Dias de validez</label></th>
                            <td><input type="number" name="days_valid" id="days_valid" value="30" min="1" class="small-text"></td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" class="button button-primary">Crear Licencia</button>
                        <button type="button" class="button" onclick="document.getElementById('crear-licencia-form').style.display='none'">Cancelar</button>
                    </p>
                </form>
            </div>

            <!-- Tabla de licencias -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 200px;">Email</th>
                        <th style="width: 150px;">API Key</th>
                        <th style="width: 80px;">Estado</th>
                        <th style="width: 100px;">GB Usado</th>
                        <th style="width: 100px;">Expira</th>
                        <th style="width: 150px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($licenses)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                No hay licencias registradas aun.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($licenses as $license): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($license->getEmail()); ?></strong>
                                </td>
                                <td>
                                    <code style="font-size: 11px;" title="<?php echo esc_attr($license->getApiKey()); ?>">
                                        <?php echo esc_html(substr($license->getApiKey(), 0, 16)); ?>...
                                    </code>
                                    <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_js($license->getApiKey()); ?>'); alert('Copiado!');">
                                        Copiar
                                    </button>
                                </td>
                                <td>
                                    <?php echo $this->renderStatusBadge($license->getStatus()); ?>
                                </td>
                                <td>
                                    <div style="margin-bottom: 5px;">
                                        <?php echo number_format($license->getGbUsed(), 2); ?> / <?php echo $license->getGbLimit(); ?> GB
                                    </div>
                                    <div style="background: #e0e0e0; border-radius: 3px; height: 8px; width: 100%;">
                                        <div style="background: <?php echo $license->isNearLimit() ? '#f44336' : '#4caf50'; ?>; height: 100%; border-radius: 3px; width: <?php echo min(100, $license->getUsagePercentage()); ?>%;"></div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    if ($license->getExpiresAt() > 0) {
                                        echo date('d/m/Y', $license->getExpiresAt());
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('license_action', 'license_nonce'); ?>
                                        <input type="hidden" name="license_id" value="<?php echo $license->getId(); ?>">

                                        <?php if ($license->getStatus() !== License::STATUS_ACTIVE): ?>
                                            <button type="submit" name="action" value="activar" class="button button-small">Activar</button>
                                        <?php endif; ?>

                                        <?php if ($license->getStatus() !== License::STATUS_SUSPENDED): ?>
                                            <button type="submit" name="action" value="suspender" class="button button-small" onclick="return confirm('Suspender esta licencia?');">Suspender</button>
                                        <?php endif; ?>

                                        <button type="submit" name="action" value="reset_gb" class="button button-small" title="Reiniciar GB usados">Reset GB</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
<?php
    }

    /**
     * Procesa acciones del formulario.
     */
    private function handleActions(): void
    {
        if (!isset($_POST['license_nonce']) || !wp_verify_nonce($_POST['license_nonce'], 'license_action') && !wp_verify_nonce($_POST['license_nonce'], 'crear_licencia')) {
            return;
        }

        $action = sanitize_text_field($_POST['action'] ?? '');

        switch ($action) {
            case 'crear_licencia':
                $email = sanitize_email($_POST['email'] ?? '');
                $gbLimit = (float) ($_POST['gb_limit'] ?? 4);
                $daysValid = (int) ($_POST['days_valid'] ?? 30);

                if (!empty($email)) {
                    $license = LicenseService::create($email);
                    if ($license) {
                        $license->setGbLimit($gbLimit);
                        $license->setExpiresAt(strtotime("+{$daysValid} days"));
                        $license->setStatus(License::STATUS_ACTIVE);
                        LicenseService::update($license);

                        echo '<div class="notice notice-success"><p>Licencia creada. API Key: <code>' . esc_html($license->getApiKey()) . '</code></p></div>';
                    }
                }
                break;

            case 'activar':
                $licenseId = (int) ($_POST['license_id'] ?? 0);
                $licenses = LicenseService::getAll();
                foreach ($licenses as $lic) {
                    if ($lic->getId() === $licenseId) {
                        LicenseService::activate($lic, 30);
                        echo '<div class="notice notice-success"><p>Licencia activada.</p></div>';
                        break;
                    }
                }
                break;

            case 'suspender':
                $licenseId = (int) ($_POST['license_id'] ?? 0);
                $licenses = LicenseService::getAll();
                foreach ($licenses as $lic) {
                    if ($lic->getId() === $licenseId) {
                        LicenseService::suspend($lic);
                        echo '<div class="notice notice-warning"><p>Licencia suspendida.</p></div>';
                        break;
                    }
                }
                break;

            case 'reset_gb':
                $licenseId = (int) ($_POST['license_id'] ?? 0);
                $licenses = LicenseService::getAll();
                foreach ($licenses as $lic) {
                    if ($lic->getId() === $licenseId) {
                        LicenseService::resetUsage($lic);
                        echo '<div class="notice notice-success"><p>GB reiniciados.</p></div>';
                        break;
                    }
                }
                break;
        }
    }

    /**
     * Renderiza badge de estado.
     */
    private function renderStatusBadge(string $status): string
    {
        $colors = [
            'active' => '#4caf50',
            'trial' => '#2196f3',
            'expired' => '#ff9800',
            'suspended' => '#f44336',
        ];

        $labels = [
            'active' => 'Activa',
            'trial' => 'Trial',
            'expired' => 'Expirada',
            'suspended' => 'Suspendida',
        ];

        $color = $colors[$status] ?? '#9e9e9e';
        $label = $labels[$status] ?? $status;

        return sprintf(
            '<span style="background: %s; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">%s</span>',
            $color,
            esc_html($label)
        );
    }
}
