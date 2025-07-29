<?php
/**
 * Plugin Name: Client Dashboard
 * Description: Custom Plugin. 
 * Version: 1.0.1
 * Author: S M Nibir
 */

if (!defined('ABSPATH')) exit;

// Load all modules
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/acf-space-field.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'includes/client-dashboard-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/enqueue-admin-scripts.php';
require_once plugin_dir_path(__FILE__) . 'includes/parsedown.php';
require_once plugin_dir_path(__FILE__) . 'includes/billing-ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'includes/api-endpoints.php';


wp_localize_script(
    'clickup-admin-js',
    'clickup_ajax',
    ['ajax_url' => admin_url('admin-ajax.php')]
);
// add_action('wp_enqueue_scripts', function() {
//     if (is_page('client-portal')) { // Adjust to match your portal page
//         wp_enqueue_script('wc-add-payment-method');
//     }
// });
add_action('wp_enqueue_scripts', function () {
    if (is_page('client-portal')) { // Adjust to match your page slug or ID
        wp_enqueue_script('wc-add-payment-method');

        // Enqueue your plugin stylesheet
        wp_enqueue_style(
            'client-dashboard-style',
            plugin_dir_url(__FILE__) . 'assets/style.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . 'assets/style.css')
        );
    }
});
