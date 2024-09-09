<?php
// brro-webdev-settings.php
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
    <div class="wrap">
        <h2>Brro Plugin Settings</h2>
        <style>
            .td-p0 td {padding-left:0px;}
            label + label {margin-left:16px;}
            .manual-disable {
                pointer-events:none;
                background: rgba(255,255,255,.5)!important;
                border-color: rgba(220,220,222,.75)!important;
                box-shadow: inset 0 1px 2px rgba(0,0,0,.04)!important;
                color: rgba(44,51,56,.5)!important;
            }
        </style>
        <form method="post" action="options.php">
            <?php settings_fields('brro-plugin-settings-group'); ?>
            <?php do_settings_sections('brro-plugin-settings-group'); ?>
        <!-- Radio button for Developer Mode and Website Live -->
            <fieldset>
                <h3 style="margin:40px 0 16px 0;">Choose Plugin Mode</h3>
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
        <!-- Radio button for Private Mode -->
            <fieldset>
                <h3 style="margin:40px 0 16px 0;">Website Private Mode</h3>
                <label>
                    <input type="radio" name="brro_private_mode" value="1" <?php checked(1, $private_mode); ?>>
                    Private (logged in only)
                </label>
                <label>
                    <input type="radio" name="brro_private_mode" value="0" <?php checked(0, $private_mode); ?>>
                    Open publicly
                </label>
            </fieldset>
        <!-- Form with Screensize references -->
            <h3 style="margin:40px 0 0 0;">Screen Size References</h3>
            <br>
            <b> NOTE: desktopEnd can not set be lower than desktopRef </b>
                <table class="form-table td-p0 screensizes" style="width:auto;">
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
        <!-- Lock settings for the screen references" -->
            <fieldset>
                <h3 style="margin:40px 0 16px 0;">Lock screen references</h3>
                <label>
                    <input type="radio" name="brro_lock_screen" value="1" <?php checked(1, get_option('brro_lock_screen')); ?>>
                    Yes
                </label>
                
                <label>
                    <input type="radio" name="brro_lock_screen" value="0" <?php checked(0, get_option('brro_lock_screen')); ?>>
                    No
                </label>
            </fieldset>
        <!-- Custom CSS Settings for Inspector -->
            <fieldset style="max-width:420px;">
                <h3 style="margin:40px 0 16px 0;">Inspector CSS colors</h3>
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
        <!-- Custom CSS Settings for WP Login Page -->
            <fieldset style="max-width:420px;">
                <h3 style="margin:40px 0 16px 0;">wp-login.php custom CSS</h3>
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
        <!-- Append menu items to 'Site content' -->
            <fieldset>
                <h3 style="margin:40px 0 16px 0;">Append menu items to 'Site Content'</h3>
                    <textarea id="brro_append_menuitems" name="brro_append_menuitems" rows="10" cols="50"><?php echo esc_textarea(get_option('brro_append_menuitems', '')); ?></textarea>
            </fieldset>
        <!-- Array of site editors -->
            <fieldset>
                <h3 style="margin:40px 0 16px 0;">Array of site editors</h3>
                    <input type="text" id="brro_editors" name="brro_editors" value="<?php echo esc_attr(get_option('brro_editors', '2,3,4,5')); ?>" />
            </fieldset>
        <!-- Checkbox for "Turn off support XML RPC" -->
            <fieldset>
                <h3 style="margin:40px 0 16px 0;">Turn off support XML RPC</h3>
                <label>
                    <input type="radio" name="brro_xmlrpc_off" value="1" <?php checked(1, get_option('brro_xmlrpc_off')); ?>>
                    Yes
                </label>
                
                <label>
                    <input type="radio" name="brro_xmlrpc_off" value="0" <?php checked(0, get_option('brro_xmlrpc_off')); ?>>
                    No
                </label>
            </fieldset>
        <!-- Checkbox for "Turn off support Comments" -->
            <fieldset>
                <h3 style="margin:40px 0 16px 0;">Turn off support for comments</h3>
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
        <!-- Website help dashboard button url -->
            <fieldset style="max-width:420px;">
                <h3 style="margin:40px 0 16px 0;">Brro.nl HELP login URL</h3>
                <label for="brro_client_help_url">URL:</label>
                <input style="float: right;" type="text" name="brro_client_help_url" value="<?php echo esc_attr(get_option('brro_client_help_url', 'https://www.brro.nl/contact')); ?>">
                <br><br>      
                <label for="brro_client_help_menutitle">Menu title:</label>
                <input style="float: right;" type="text" name="brro_client_help_menutitle" value="<?php echo esc_attr(get_option('brro_client_help_menutitle', 'Brro, help!')); ?>">    
            </fieldset>    
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
    // register comments settings
    register_setting('brro-plugin-settings-group', 'brro_xmlrpc_off');
    register_setting('brro-plugin-settings-group', 'brro_comments_off');
    // login url for brro
    register_setting('brro-plugin-settings-group', 'brro_client_help_url');
    register_setting('brro-plugin-settings-group', 'brro_client_help_menutitle');
    // append menu items in admin menu
    register_setting('brro-plugin-settings-group', 'brro_append_menuitems');
    // Alternative site editors for older sites
    register_setting('brro-plugin-settings-group', 'brro_editors');
}