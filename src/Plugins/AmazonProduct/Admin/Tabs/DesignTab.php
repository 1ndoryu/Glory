<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

/**
 * Design Tab - Visual customization settings.
 */
class DesignTab implements TabInterface
{
    public function getSlug(): string
    {
        return 'design';
    }

    public function getLabel(): string
    {
        return 'Design';
    }

    public function render(): void
    {
        if (isset($_POST['save_design']) && check_admin_referer('amazon_design_action', 'amazon_design_nonce')) {
            $this->saveSettings();
            echo '<div class="notice notice-success inline"><p>Design settings saved.</p></div>';
        }

        $this->renderForm();
    }

    private function saveSettings(): void
    {
        update_option('amazon_btn_text', sanitize_text_field($_POST['amazon_btn_text']));
        update_option('amazon_btn_bg', sanitize_hex_color($_POST['amazon_btn_bg']));
        update_option('amazon_btn_color', sanitize_hex_color($_POST['amazon_btn_color']));
        update_option('amazon_price_color', sanitize_hex_color($_POST['amazon_price_color']));
    }

    private function renderForm(): void
    {
        $btnText = get_option('amazon_btn_text', 'View on Amazon');
        $btnBg = get_option('amazon_btn_bg', '#FFD814');
        $btnColor = get_option('amazon_btn_color', '#111111');
        $priceColor = get_option('amazon_price_color', '#B12704');
?>
        <h3>Design Customization</h3>
        <form method="post">
            <?php wp_nonce_field('amazon_design_action', 'amazon_design_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="amazon_btn_text">Button Text</label></th>
                    <td>
                        <input type="text" name="amazon_btn_text" id="amazon_btn_text" value="<?php echo esc_attr($btnText); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="amazon_btn_bg">Button Background</label></th>
                    <td>
                        <input type="color" name="amazon_btn_bg" id="amazon_btn_bg" value="<?php echo esc_attr($btnBg); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="amazon_btn_color">Button Text Color</label></th>
                    <td>
                        <input type="color" name="amazon_btn_color" id="amazon_btn_color" value="<?php echo esc_attr($btnColor); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="amazon_price_color">Price Color</label></th>
                    <td>
                        <input type="color" name="amazon_price_color" id="amazon_price_color" value="<?php echo esc_attr($priceColor); ?>">
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Design', 'primary', 'save_design'); ?>
        </form>
<?php
    }
}
