<?php
/**
 * Plugin Name: Brro Core
 * Plugin URI: https://github.com/ronaldpostma/brro-core
 * Description: Global core functions and development tools for sites developed by Brro.
 * Version: 2.1.0
 * Author: Ronald Postma @ Brro.nl
 * Author URI: https://brro.nl/
 * License: GPLv2 or later
 * Text Domain: brro-core
 * 
 */
if (!defined('ABSPATH')) exit;
//
// Include php function files
// 
require_once plugin_dir_path(__FILE__) . '/php/brro-core-settings.php';
// 
require_once plugin_dir_path(__FILE__) . '/php/brro-core-admin.php';
// 
require_once plugin_dir_path(__FILE__) . '/php/brro-core-global.php';
//
// Detect if Elementor is active (late, after plugins load)
add_action('plugins_loaded', 'brro_set_elementor_flag', 20);
function brro_set_elementor_flag() {
    $active = did_action('elementor/loaded') || class_exists('\\Elementor\\Plugin') || defined('ELEMENTOR_VERSION');
    if (!defined('BRRO_ELEMENTOR_ACTIVE')) {
        define('BRRO_ELEMENTOR_ACTIVE', $active ? true : false);
    }
}
function brro_is_elementor_active() {
    return defined('BRRO_ELEMENTOR_ACTIVE') && BRRO_ELEMENTOR_ACTIVE;
}
//
// Load script for Elementor Editor Panel
add_action('plugins_loaded', function() {
    if ( brro_is_elementor_active() ) {
        add_action( 'elementor/editor/after_enqueue_scripts', 'brro_enqueue_script_elementor_editor' );
    }
}, 30);
function brro_enqueue_script_elementor_editor() {
    $developer_mode = get_option('brro_developer_mode', 0);
    if ( brro_is_elementor_active() && $developer_mode == 1 && is_user_logged_in() ) {
        wp_enqueue_script( 'brro-core-elementor-editor-script', plugins_url( '/js/brro-core-elementor-editor-script.js', __FILE__ ), [ 'jquery' ], '1.0.0', true );
        // Localize script with data from your settings
        $script_data = array(
            'desktopEnd' => get_option('brro_desktop_end'),
            'desktopRef' => get_option('brro_desktop_ref'),
            'desktopStart' => get_option('brro_desktop_start'),
            'tabletRef'  => get_option('brro_tablet_ref'),
            'tabletStart'  => get_option('brro_tablet_start'),
            'mobileRef'  => get_option('brro_mobile_ref'),
            'mobileStart'  => get_option('brro_mobile_start'),
            'developerMode'  => get_option('brro_developer_mode'),
        );
        wp_localize_script('brro-core-elementor-editor-script', 'pluginSettings', $script_data);
    }
}
//
// Load script and popout window for responsive CSS calculator
add_action( 'admin_enqueue_scripts', 'brro_enqueue_script_css_calculator' );
function brro_enqueue_script_css_calculator() {
    $developer_mode = get_option('brro_developer_mode', 0);
    if ($developer_mode == 1 && is_user_logged_in() ) {
        wp_enqueue_script( 'brro-core-css-calculator-script', plugins_url( '/js/brro-core-css-calculator-script.js', __FILE__ ), [ 'jquery' ], '1.0.0', true );
        // Localize script with data from your settings
        $script_data = array(
            'desktopEnd' => get_option('brro_desktop_end'),
            'desktopRef' => get_option('brro_desktop_ref'),
            'desktopStart' => get_option('brro_desktop_start'),
            'tabletRef'  => get_option('brro_tablet_ref'),
            'tabletStart'  => get_option('brro_tablet_start'),
            'mobileRef'  => get_option('brro_mobile_ref'),
            'mobileStart'  => get_option('brro_mobile_start'),
            'developerMode'  => get_option('brro_developer_mode'),
        );
        wp_localize_script('brro-core-css-calculator-script', 'pluginSettings', $script_data);
    }
}
//
// Load assets for wp admin area
add_action( 'admin_enqueue_scripts', 'brro_webdev_enqueue_admin_assets');
function brro_webdev_enqueue_admin_assets() {
    // Load admin css for all backend users
    $load_admin_css_all = (int) get_option('brro_admin_css_all', 1);
    if ($load_admin_css_all === 1) {
        wp_enqueue_style( 'brro-core-wp-admin-style', plugins_url( '/css/brro-core-wp-admin-style.css', __FILE__ ), [], '1.0.0', 'all' );
    }
    wp_enqueue_script( 'brro-core-wp-admin-script', plugins_url( '/js/brro-core-wp-admin-script.js', __FILE__ ), ['jquery'], '1.0.0', true );
    // Localize script with data from your settings
    $script_data = array(
        'helpUrl' => get_option('brro_client_help_url'),
    );
    wp_localize_script('brro-core-wp-admin-script', 'pluginSettings', $script_data);
    // 
    // Load backend css for Brro editors
    $load_admin_css_editors = (int) get_option('brro_admin_css_editors', 1);
    if ($load_admin_css_editors === 1) {
        $user = get_current_user_id();
        $get_editors = get_option('brro_editors', '2,3,4,5');
        $editors = array_filter(array_map('intval', explode(',', $get_editors)), function($id) {
            return $id > 0;
        }); 
        // Client users / editors
        if (in_array($user, $editors)) {
            wp_enqueue_style( 'brro-core-wp-admin-editors-style', plugins_url( '/css/brro-core-wp-admin-editors-style.css', __FILE__ ), [], '1.0.0', 'all' );
        }
    }
}
/**
 * ===============================================
 * UPDATE MECHANISM
 * ===============================================
 */

/**
 * Check for plugin updates from custom endpoint
 * 
 * Hooks into WordPress update system to check for new versions
 * by fetching update info from a JSON endpoint.
 * 
 * @param object $checked_data WordPress update data
 * @return object Modified update data with plugin update info if available
 */
function brro_check_for_plugin_update($checked_data) {
    if (empty($checked_data->checked)) {
        return $checked_data;
    }
    
    // Define the plugin slug
    $plugin_slug = 'brro-core';
    $plugin_path = plugin_basename(__FILE__);
    
    // Fetch the latest plugin info from custom URI
    $response = brro_get_plugin_update_info();
    
    // Ensure the plugin_path key is set and valid before comparing versions
    if ($response && isset($checked_data->checked[$plugin_path]) && version_compare($checked_data->checked[$plugin_path], $response->new_version, '<')) {
        $checked_data->response[$plugin_path] = (object) [
            'url' => $response->url,
            'slug' => $plugin_slug,
            'package' => $response->package,
            'new_version' => $response->new_version,
            'tested' => $response->tested,
        ];
    }
    
    return $checked_data;
}
add_filter('pre_set_site_transient_update_plugins', 'brro_check_for_plugin_update');

/**
 * Fetch plugin update information from JSON endpoint
 * 
 * Retrieves update information from the custom JSON endpoint hosted
 * at brro.nl. The JSON should contain version, package URL, and other
 * update metadata.
 * 
 * @return object|false Plugin update data object or false on failure
 */
function brro_get_plugin_update_info() {
    $update_info_url = 'https://www.brro.nl/git-webhook/brro-plugin-info.json';
    $response = wp_remote_get($update_info_url);
    
    if (is_wp_error($response)) {
        return false; // Bail early on request error
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);
    
    if (!is_null($data)) {
        return $data;
    }
    
    return false;
}

/**
 * Add "View changes" link to plugin row meta
 * 
 * Adds a link to the GitHub releases page in the plugin list table,
 * allowing users to view changelog and release notes.
 * 
 * @param array $links Existing plugin row meta links
 * @param string $file Plugin file path
 * @return array Modified links array
 */
add_filter('plugin_row_meta', 'brro_plugin_row_meta', 10, 2);
function brro_plugin_row_meta($links, $file) {
    if ($file == plugin_basename(__FILE__)) {
        $new_links = array(
            '<a href="https://github.com/ronaldpostma/brro-core/releases" target="_blank">' . __('View changes', 'brro-core') . '</a>',
        );
        $links = array_merge($links, $new_links);
    }
    return $links;
}
