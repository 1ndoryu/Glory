<?php
// In your theme/plugin setup file (e.g., functions.php)

use App\Glory\ScriptManager; // Assuming ScriptManager is available

// Define the new script and localize necessary data
// Adjust the path 'App/Glory/Assets/js/GloryEmailSignup.js' as needed!
ScriptManager::define(
    'glory-email-signup', // Handle
    'App/Glory/Assets/js/GloryEmailSignup.js', // Path relative to theme root
    [], // Dependencies (e.g., ['jquery'] if you were using jQuery)
    null, // Version (auto-handled by ScriptManager based on dev mode)
    true, // In footer
    [ // Localization data
        'object_name' => 'gloryGlobalData', // JS object name
        'data' => [
            'ajax_url' => admin_url('admin-ajax.php'),
            // 'global_nonce' => wp_create_nonce('your_global_ajax_nonce') // Example if needed globally
            // Nonce is now handled per-form via hidden input for better context
        ]
    ]
);
