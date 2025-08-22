<?php
if (!defined('ABSPATH')) exit;
/*
Function Index for brro-core-settings.php:
1. brro_plugin_add_settings_page
   - Creates the admin menu page for Brro plugin settings.
2. brro_plugin_settings_page
   - Renders the settings page content with all configuration options.
3. brro_plugin_register_settings
   - Registers all settings fields with WordPress settings API.
*/
//
// Create menu item
add_action('admin_menu', 'brro_plugin_add_settings_page');
function brro_plugin_add_settings_page() {
    add_menu_page(
        'Brro Plugin Settings', // Page title
        'Brro', // Menu title
        'manage_options', // Capability
        'brro-plugin-settings', // Menu slug
        'brro_plugin_settings_page', // Function that outputs the page content
        'dashicons-smiley', // Icon
    );
}
//
// Settings page content
function brro_plugin_settings_page() {
    $developer_mode = get_option('brro_developer_mode', 0);
    $private_mode = get_option('brro_private_mode', 0);
    $mobile_end = (int)get_option('brro_tablet_start') - 1;
    $tablet_end = (int)get_option('brro_desktop_start') - 1;
    $lock_screen = get_option('brro_lock_screen', 0);
    if ($lock_screen == 1) {
        ?>
        <script>
            jQuery(function($){
                $('.screensizes select, .screensizes input').addClass('manual-disable');
            });
        </script>
        <?php
    }
    if ($developer_mode == 0) {
        ?>
        <style>.devmode_only{display:none;}</style>
        <?php
    }
    ?>
    <script>
    jQuery(function($){
        // Show/hide posts menu options based on radio selection
        $('input[name="brro_change_posts_menu"]').on('change', function() {
            if ($(this).val() == '1') {
                $('#posts-menu-options').show();
            } else {
                $('#posts-menu-options').hide();
            }
        });
    });
    </script>
    <?php
    ?>
    <div class="wrap">
        <h2>Brro Plugin Settings</h2>
        <style>
            .td-p0 td { padding-left: 0px; }
            label + label { margin-left: 16px; }
            .manual-disable {
                pointer-events: none;
                background: rgba(255, 255, 255, .5) !important;
                border-color: rgba(220, 220, 222, .75) !important;
                box-shadow: inset 0 1px 2px rgba(0, 0, 0, .04) !important;
                color: rgba(44, 51, 56, .5) !important;
            }
        </style>
        <form method="post" action="options.php">
            <?php settings_fields('brro-plugin-settings-group'); ?>
            <?php do_settings_sections('brro-plugin-settings-group'); ?>
            
            <!-- Plugin Mode -->
            <fieldset>
                <legend><h3 style="margin: 40px 0 16px 0;">Choose Plugin Mode</h3></legend>
                <label>
                    <input type="radio" name="brro_developer_mode" value="1" <?php checked(1, $developer_mode); ?>>
                    Development
                </label>
                <label>
                    <input type="radio" name="brro_developer_mode" value="0" <?php checked(0, $developer_mode); ?>>
                    Website Live
                </label>
            </fieldset>

            <div class="devmode_only">
                <!-- Private Mode -->
                <fieldset>
                    <legend><h3 style="margin: 40px 0 16px 0;">Website Private Mode</h3></legend>
                    <label>
                        <input type="radio" name="brro_private_mode" value="1" <?php checked(1, $private_mode); ?>>
                        Private (logged in only)
                    </label>
                    <label>
                        <input type="radio" name="brro_private_mode" value="0" <?php checked(0, $private_mode); ?>>
                        Open publicly
                    </label><br><br>
                    <label for="brro_private_mode_redirect">Redirect URL:</label><br>
                    <input type="text" name="brro_private_mode_redirect" id="brro_private_mode_redirect" value="<?php echo esc_url(get_option('brro_private_mode_redirect', home_url('/wp-login.php'))); ?>"><br><br>
                    <label for="brro_private_redirect_exceptions">Enter URLs, one per line:</label><br>
                    <textarea name="brro_private_redirect_exceptions" id="brro_private_redirect_exceptions" rows="5" cols="50"><?php echo esc_textarea(get_option('brro_private_redirect_exceptions')); ?></textarea>
                </fieldset>
                
                <!-- Screen Size References -->
                <fieldset>
                    <legend><h3 style="margin: 40px 0 0 0;">Screen Size References</h3></legend>
                    <p><strong>NOTE: desktopEnd cannot be set lower than desktopRef</strong></p>
                    <table class="form-table td-p0 screensizes" style="width: auto;">
                        <tr>
                            <th>Device</th>
                            <th>screenRef - reference size from design</th>
                            <th>screenStart - to generate css clamp() </th>
                            <th>screenEnd - to generate css clamp()</th>
                        </tr>
                        <tr valign="top">
                            <td>Desktop</td>
                            <td>
                                <select name="brro_desktop_ref">
                                    <option value="1440" <?php selected('1440', get_option('brro_desktop_ref')); ?>>1440</option>
                                    <option value="1600" <?php selected('1600', get_option('brro_desktop_ref')); ?>>1600</option>
                                    <option value="1920" <?php selected('1920', get_option('brro_desktop_ref')); ?>>1920</option>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="brro_desktop_start" value="1180" />
                                <input type="number" value="1180" disabled />
                            </td>
                            <td>
                                <select name="brro_desktop_end">
                                    <option value="1440" <?php selected('1440', get_option('brro_desktop_end')); ?>>1440</option>
                                    <option value="1600" <?php selected('1600', get_option('brro_desktop_end')); ?>>1600</option>
                                    <option value="1920" <?php selected('1920', get_option('brro_desktop_end')); ?>>1920</option>
                                    <option value="0" <?php selected('0', get_option('brro_desktop_end')); ?>>0: open-ended scaling</option>
                                </select>
                            </td>
                        </tr>
                        <tr valign="top">
                            <td>Tablet</td>
                            <td>
                                <select name="brro_tablet_ref">
                                    <option value="768" <?php selected('768', get_option('brro_tablet_ref')); ?>>768</option>
                                    <option value="810" <?php selected('810', get_option('brro_tablet_ref')); ?>>810</option>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="brro_tablet_start" value="768" />
                                <input type="number" value="768" disabled />
                            </td>
                            <td>
                                <input type="number" value="<?php echo esc_attr($tablet_end); ?>" disabled />
                            </td>
                        </tr>
                        <tr valign="top">
                            <td>Mobile</td>
                            <td>
                                <select name="brro_mobile_ref">
                                    <option value="360" <?php selected('360', get_option('brro_mobile_ref')); ?>>360</option>
                                    <option value="390" <?php selected('390', get_option('brro_mobile_ref')); ?>>390</option>
                                    <option value="414" <?php selected('414', get_option('brro_mobile_ref')); ?>>414</option>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="brro_mobile_start" value="320" />
                                <input type="number" value="320" disabled />
                            </td>
                            <td>
                                <input type="number" value="<?php echo esc_attr($mobile_end); ?>" disabled />
                            </td>
                        </tr>
                    </table>
                </fieldset>
                
                <!-- Lock Screen References -->
                <fieldset>
                    <legend><h3 style="margin: 40px 0 16px 0;">Lock screen references</h3></legend>
                    <label>
                        <input type="radio" name="brro_lock_screen" value="1" <?php checked(1, get_option('brro_lock_screen')); ?>>
                        Yes
                    </label>
                    <label>
                        <input type="radio" name="brro_lock_screen" value="0" <?php checked(0, get_option('brro_lock_screen')); ?>>
                        No
                    </label>
                </fieldset>
                
                <!-- Inspector CSS Settings -->
                <fieldset style="max-width: 420px;">
                    <legend><h3 style="margin: 40px 0 16px 0;">Inspector CSS colors</h3></legend>
                    <label for="brro_blend_mode">Mix blend mode:</label>
                    <input style="float: right;" type="text" name="brro_blend_mode" value="<?php echo esc_attr(get_option('brro_blend_mode', 'screen')); ?>">
                    <br><br>
                    <label for="brro_parent_border_color">Parent container border color:</label>
                    <input style="float: right;" type="text" name="brro_parent_border_color" value="<?php echo esc_attr(get_option('brro_parent_border_color', '#FF0000')); ?>">
                    <br><br>
                    <label for="brro_child_border_color">Child container border color:</label>
                    <input style="float: right;" type="text" name="brro_child_border_color" value="<?php echo esc_attr(get_option('brro_child_border_color', '#00FF00')); ?>">
                    <br><br>
                    <label for="brro_child_child_border_color">Child > Child containers border color:</label>
                    <input style="float: right;" type="text" name="brro_child_child_border_color" value="<?php echo esc_attr(get_option('brro_child_child_border_color', '#0000FF')); ?>">
                    <br><br>
                    <label for="brro_widget_text_color">Widget element box shadow color:</label>
                    <input style="float: right;" type="text" name="brro_widget_text_color" value="<?php echo esc_attr(get_option('brro_widget_text_color', '#DDD')); ?>">
                </fieldset>
                
                <!-- WP Login Page Settings -->
                <fieldset style="max-width: 420px;">
                    <legend><h3 style="margin: 40px 0 16px 0;">wp-login.php custom CSS</h3></legend>
                    <label for="brro_login_backgroundmain">Background:</label>
                    <input style="float: right;" type="text" name="brro_login_backgroundmain" value="<?php echo esc_attr(get_option('brro_login_backgroundmain', 'linear-gradient(270deg, beige, blue, purple, pink)')); ?>">
                    <br><br>
                    <label for="brro_login_backgroundform">Background form:</label>
                    <input style="float: right;" type="text" name="brro_login_backgroundform" value="<?php echo esc_attr(get_option('brro_login_backgroundform', 'transparent')); ?>">
                    <br><br>
                    <label for="brro_login_textlabelcolor">Text color in labels:</label>
                    <input style="float: right;" type="text" name="brro_login_textlabelcolor" value="<?php echo esc_attr(get_option('brro_login_textlabelcolor', '#ffffff')); ?>">
                    <br><br>
                    <label for="brro_login_sitelogo">Logo URL:</label>
                    <input style="float: right;" type="text" name="brro_login_sitelogo" value="<?php echo esc_attr(get_option('brro_login_sitelogo', 'https://brro.nl/base/brro.svg')); ?>">
                    <br><br>
                    <label for="brro_login_logowidth">Logo width (px):</label>
                    <input style="float: right;" type="number" name="brro_login_logowidth" value="<?php echo esc_attr(get_option('brro_login_logowidth', '140')); ?>">
                    <br><br>
                    <label for="brro_login_logoheight">Logo height (px):</label>
                    <input style="float: right;" type="number" name="brro_login_logoheight" value="<?php echo esc_attr(get_option('brro_login_logoheight', '160')); ?>">
                </fieldset>
                
                <!-- Menu Items -->
                <fieldset>
                    <legend><h3 style="margin: 40px 0 16px 0;">Append menu items to 'Site Content'</h3></legend>
                    <textarea id="brro_append_menuitems" name="brro_append_menuitems" rows="10" cols="50"><?php echo esc_textarea(get_option('brro_append_menuitems', '')); ?></textarea>
                </fieldset>
                
                <!-- Site Editors -->
                <fieldset>
                    <legend><h3 style="margin: 40px 0 16px 0;">Array of site editors</h3></legend>
                    <input type="text" id="brro_editors" name="brro_editors" value="<?php echo esc_attr(get_option('brro_editors', '2,3,4,5')); ?>" />
                </fieldset>
                
                <!-- Editor Menu Pages to Remove -->
                <fieldset>
                    <legend><h3 style="margin: 40px 0 16px 0;">Menu pages to remove for editors</h3></legend>
                    <p>Enter one menu page slug per line. These pages will be hidden from editors:</p>
                    <textarea id="brro_editors_remove_menupages" name="brro_editors_remove_menupages" rows="10" cols="50"><?php echo esc_textarea(get_option('brro_editors_remove_menupages', '')); ?></textarea>
                    <br><br>
                    <small>Examples: upload.php, themes.php, tools.php, users.php, profile.php, plugins.php, brro-separator-core, edit.php?post_type=elementor_library, snippets, elementor, brro-plugin-settings, jet-dashboard, jet-smart-filters, edit.php?post_type=acf-field-group, update-core.php</small>
                </fieldset>
                
                <!-- Specific User Menu Pages to Remove -->
                <fieldset>
                    <legend><h3 style="margin: 40px 0 16px 0;">Menu pages to remove for specific users</h3></legend>
                    <p>Enter one entry per line in format: user_id,menu_page_slug. These pages will be hidden from specific users:</p>
                    <textarea id="brro_users_remove_menupages" name="brro_users_remove_menupages" rows="10" cols="50"><?php echo esc_textarea(get_option('brro_users_remove_menupages', '')); ?></textarea>
                    <br><br>
                    <small>Examples: 6,upload.php, 7,themes.php, 8,tools.php, 9,users.php, 10,profile.php, 11,plugins.php</small>
                </fieldset>
                
                <!-- XML RPC Settings -->
                <fieldset>
                    <legend><h3 style="margin: 40px 0 16px 0;">Turn off support XML RPC</h3></legend>
                    <label>
                        <input type="radio" name="brro_xmlrpc_off" value="1" <?php checked(1, get_option('brro_xmlrpc_off')); ?>>
                        Yes
                    </label>
                    <label>
                        <input type="radio" name="brro_xmlrpc_off" value="0" <?php checked(0, get_option('brro_xmlrpc_off')); ?>>
                        No
                    </label>
                </fieldset>
                
                <!-- Comments Settings -->
                <fieldset>
                    <legend><h3 style="margin: 40px 0 16px 0;">Turn off support for comments</h3></legend>
                    <label>
                        <input type="radio" name="brro_comments_off" value="1" <?php checked(1, get_option('brro_comments_off')); ?>>
                        Yes
                    </label>
                    <label>
                        <input type="radio" name="brro_comments_off" value="0" <?php checked(0, get_option('brro_comments_off')); ?>>
                        No
                    </label>
                </fieldset>
            </div>
            
            <!-- Help URL Settings -->
            <fieldset style="max-width: 420px;">
                <legend><h3 style="margin: 40px 0 16px 0;">Custom URL @ top of admin side menu</h3></legend>
                <label for="brro_client_help_url">URL:</label>
                <input style="float: right;" type="text" name="brro_client_help_url" value="<?php echo esc_attr(get_option('brro_client_help_url', 'https://www.brro.nl/contact')); ?>">
                <br><br>
                <label for="brro_client_help_menutitle">Menu title:</label>
                <input style="float: right;" type="text" name="brro_client_help_menutitle" value="<?php echo esc_attr(get_option('brro_client_help_menutitle', 'Brro, help!')); ?>">
            </fieldset>
            
            <?php if (!brro_is_project_active()): ?>
            <!-- Posts Menu Customization (only when brro-project is not active) -->
            <fieldset style="max-width: 420px;">
                <legend><h3 style="margin: 40px 0 16px 0;">Posts Menu Customization</h3></legend>
                <label>
                    <input type="radio" name="brro_change_posts_menu" value="1" <?php checked(1, get_option('brro_change_posts_menu', 0)); ?>>
                    Yes, change 'Posts' menu title
                </label>
                <label>
                    <input type="radio" name="brro_change_posts_menu" value="0" <?php checked(0, get_option('brro_change_posts_menu', 0)); ?>>
                    No, keep default
                </label>
                <br><br>
                <div id="posts-menu-options" style="<?php echo (get_option('brro_change_posts_menu', 0) == 1) ? 'display: block;' : 'display: none;'; ?>">
                    <label for="brro_posts_menu_title">Posts menu title:</label>
                    <input style="float: right;" type="text" name="brro_posts_menu_title" value="<?php echo esc_attr(get_option('brro_posts_menu_title', 'Articles')); ?>">
                    <br><br>
                    <label for="brro_posts_menu_icon">Posts Dashicons slug:</label>
                    <input style="float: right;" type="text" name="brro_posts_menu_icon" value="<?php echo esc_attr(get_option('brro_posts_menu_icon', 'dashicons-admin-post')); ?>">
                    <br><br>
                    <small>Example dashicons: dashicons-admin-post, dashicons-format-aside, dashicons-format-standard, dashicons-format-quote</small>
                </div>
            </fieldset>
            <?php endif; ?>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
//
// Register settings
add_action('admin_init', 'brro_plugin_register_settings');
function brro_plugin_register_settings() {
    // register settings devmode
    register_setting('brro-plugin-settings-group', 'brro_developer_mode');
    // register settings elementor css clamp/var
    register_setting('brro-plugin-settings-group', 'brro_desktop_end');
    register_setting('brro-plugin-settings-group', 'brro_desktop_ref');
    register_setting('brro-plugin-settings-group', 'brro_desktop_start');
    register_setting('brro-plugin-settings-group', 'brro_tablet_ref');
    register_setting('brro-plugin-settings-group', 'brro_tablet_start');
    register_setting('brro-plugin-settings-group', 'brro_mobile_ref');
    register_setting('brro-plugin-settings-group', 'brro_mobile_start');
    register_setting('brro-plugin-settings-group', 'brro_lock_screen');
    // register settings frontend inspector
    register_setting('brro-plugin-settings-group', 'brro_blend_mode');
    register_setting('brro-plugin-settings-group', 'brro_parent_border_color');
    register_setting('brro-plugin-settings-group', 'brro_child_border_color');
    register_setting('brro-plugin-settings-group', 'brro_child_child_border_color');
    register_setting('brro-plugin-settings-group', 'brro_widget_text_color');
    // register settings wp login
    register_setting('brro-plugin-settings-group', 'brro_login_backgroundmain');
    register_setting('brro-plugin-settings-group', 'brro_login_backgroundform');
    register_setting('brro-plugin-settings-group', 'brro_login_textlabelcolor');
    register_setting('brro-plugin-settings-group', 'brro_login_sitelogo');
    register_setting('brro-plugin-settings-group', 'brro_login_logowidth');
    register_setting('brro-plugin-settings-group', 'brro_login_logoheight');
    // wp private mode
    register_setting('brro-plugin-settings-group', 'brro_private_mode');
    register_setting('brro-plugin-settings-group', 'brro_private_redirect_exceptions');
    register_setting('brro-plugin-settings-group', 'brro_private_mode_redirect');
    // register comments settings
    register_setting('brro-plugin-settings-group', 'brro_xmlrpc_off');
    register_setting('brro-plugin-settings-group', 'brro_comments_off');
    // login url for brro
    register_setting('brro-plugin-settings-group', 'brro_client_help_url');
    register_setting('brro-plugin-settings-group', 'brro_client_help_menutitle');
    // append menu items in admin menu
    register_setting('brro-plugin-settings-group', 'brro_append_menuitems');
    // Alternative site editors for custom wp backend UI
    register_setting('brro-plugin-settings-group', 'brro_editors');
    // Menu pages to remove for editors
    register_setting('brro-plugin-settings-group', 'brro_editors_remove_menupages');
    // Menu pages to remove for specific users
    register_setting('brro-plugin-settings-group', 'brro_users_remove_menupages');
    // Posts menu customization (only when brro-project is not active)
    register_setting('brro-plugin-settings-group', 'brro_change_posts_menu');
    register_setting('brro-plugin-settings-group', 'brro_posts_menu_title');
    register_setting('brro-plugin-settings-group', 'brro_posts_menu_icon');
}