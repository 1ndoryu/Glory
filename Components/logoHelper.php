<?php
# App/Glory/Components/LogoHelper.php  
# En construcción

namespace Glory\Components;

class LogoHelper
{
    /**
     * Fetches logos defined via the WordPress admin.
     * Placeholder for future implementation.
     *
     * @return array An array of logo data, e.g., [['url' => '...', 'alt' => '...'], ...]
     * @TODO Implement logic to retrieve logos from WP options, Customizer, CPTs, ACF Repeater, etc.
     */
    private static function fetchAdminLogos(): array
    {
        // Example: Fetch from an option that stores an array of logos
        // $logos = get_option('glory_client_logos', []);
        // return is_array($logos) ? $logos : [];

        // For now, return empty as it's not implemented
        return [];
    }

    /**
     * Fetches default logos (SVGs) from a specified theme folder.
     *
     * @param string $folderRelPath Relative path from the theme root (e.g., 'assets/svg/client-logos').
     * @return array An array of logo data [['url' => '...', 'alt' => '...'], ...].
     */
    private static function fetchDefaultLogos(string $folderRelPath): array
    {
        $defaultLogos = [];
        $themeDir = get_template_directory();
        $fullFolderPath = $themeDir . '/' . trim($folderRelPath, '/');
        $folderUrl = get_template_directory_uri() . '/' . trim($folderRelPath, '/');

        if (!is_dir($fullFolderPath)) {
            return [];
        }

        $svgFiles = glob($fullFolderPath . '/*.svg');

        if ($svgFiles === false) {
            return [];
        }

        foreach ($svgFiles as $svgFilePath) {
            $fileName = basename($svgFilePath);
            $logoUrl  = $folderUrl . '/' . $fileName;
            $altText  = 'Logo: ' . ucwords(str_replace(['-', '_'], ' ', pathinfo($fileName, PATHINFO_FILENAME)));

            $defaultLogos[] = [
                'url' => $logoUrl,
                'alt' => $altText,
                'type' => 'default-logo'
            ];
        }

        return $defaultLogos;
    }


    /**
     * Generates the HTML markup for the client logos scroller.
     * Includes the necessary outer wrapper for CSS animation.
     *
     * @param string $defaultLogoFolderRelPath Relative path to the default logos folder.
     * @param bool $includeAdminLogos Whether to include logos defined in WP Admin.
     * @return string HTML output for client logos scroller, or empty string if none found.
     */

    private static function getHtml(string $defaultLogoFolderRelPath = 'assets/svg/client-logos', bool $includeAdminLogos = true): string
    {
        $adminLogos = [];
        if ($includeAdminLogos) {
            $adminLogos = self::fetchAdminLogos();
            foreach ($adminLogos as &$logo) {
                $logo['type'] = 'admin-logo';
            }
            unset($logo);
        }

        $defaultLogos = self::fetchDefaultLogos($defaultLogoFolderRelPath);
        $allLogos = array_merge($adminLogos, $defaultLogos);

        if (empty($allLogos)) {
            return ''; # No logos to display
        }

        ob_start(); ?>
        
        <div class="logo-scroller-outer">
            <div class="client-logos-wrap">
                <?php foreach ($allLogos as $logo) : ?>
                    <?php if (isset($logo['url']) && isset($logo['alt'])) : ?>
                        <div class="client-logo <?= esc_attr($logo['type']) ?>">
                            <img src="<?= esc_url($logo['url']) ?>" alt="<?= esc_attr($logo['alt']) ?>">
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php # JS will duplicate logos here 
                ?>
            </div>
        </div>

<?php
        return ob_get_clean();
    }


    /**
     * Prints the client logos HTML structure directly.
     * Use this method in your theme template files.
     *
     * @param string $defaultLogoFolderRelPath Relative path within the theme to the folder containing default SVG logos. Defaults to 'assets/svg/client-logos'.
     * @param bool $includeAdminLogos Set to true to attempt loading logos from WP Admin (when implemented), false to only show defaults. Defaults to true.
     */
    public static function render(string $defaultLogoFolderRelPath = 'assets/svg/client-logos', bool $includeAdminLogos = true): void
    {
        echo self::getHtml($defaultLogoFolderRelPath, $includeAdminLogos);
    }
}
