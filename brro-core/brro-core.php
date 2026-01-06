<?php
/**
 * Plugin Name: Brro Core
 * Plugin URI: https://github.com/ronaldpostma/brro-core
 * Description: Global core functions and development tools for sites developed by Brro.
 * Version: 2.0.1
 * Author: Ronald Postma @ Brro.nl
 * Author URI: https://brro.nl/
 * License: GPLv2 or later
 * Text Domain: brro-core
 * 
 */
if (!defined('ABSPATH')) exit;
//
/* ========================================
   BRRO-PROJECT PLUGIN DETECTION
   Checks if brro-project plugin is active and available
   ======================================== */
   function brro_is_project_active() {
    // Check if brro-project plugin is active using WordPress plugin API
    if (function_exists('is_plugin_active')) {
        return is_plugin_active('brro-project/brro-project.php');
    }
    // Fallback: check if plugin file exists and is loaded
    return class_exists('Brro_Project') || 
           defined('BRRO_PROJECT_VERSION') || 
           file_exists(WP_PLUGIN_DIR . '/brro-project/brro-project.php');
    // Example usage:
    // if (!brro_is_project_active()) {
    //     do something if brro-project plugin is not active
    // }
}
/* ========================================
   BRRO FLEX THEME DETECTION
   Checks if brro-flex-theme is active and available
   ======================================== */
   function brro_is_flex_theme_active() {
    // Check if brro-flex-theme is the active theme
    $current_theme = wp_get_theme();
    if ($current_theme->get('Name') === 'Brro Flex Theme' || $current_theme->get('TextDomain') === 'brro-flex-theme' || $current_theme->get_stylesheet() === 'brro-flex-theme') {
        return true;
    }
    // Fallback: check if theme class or constant exists, or theme directory exists
    return class_exists('Brro_Flex_Theme') || 
           defined('BRRO_FLEX_THEME_VERSION') || 
           file_exists(get_theme_root() . '/brro-flex-theme/style.css');
    // Example usage:
    // if (!brro_is_flex_theme_active()) {
    //     do something if brro-flex-theme is not active
    // }
}
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
    // For all users
    if (brro_is_project_active() || !brro_is_flex_theme_active()) {
        wp_enqueue_style( 'brro-core-wp-admin-style', plugins_url( '/css/brro-core-wp-admin-style.css', __FILE__ ), [], '1.0.0', 'all' );
    }
    wp_enqueue_script( 'brro-core-wp-admin-script', plugins_url( '/js/brro-core-wp-admin-script.js', __FILE__ ), ['jquery'], '1.0.0', true );
    // Localize script with data from your settings
    $script_data = array(
        'helpUrl' => get_option('brro_client_help_url'),
    );
    wp_localize_script('brro-core-wp-admin-script', 'pluginSettings', $script_data);
    // 
    // For specific users
    if (brro_is_project_active() || !brro_is_flex_theme_active()) {
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
/*
*
* Update mechanism
*
*/
function brro_check_for_plugin_update($checked_data) {
    if (empty($checked_data->checked)) return $checked_data;
    // Define the plugin slug
    $plugin_slug = 'brro-core';
    $plugin_path = plugin_basename(__FILE__);
    // Fetch the latest plugin info from your custom URI
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
// Add link to Github to see changes
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
