# Brro Development Plugin for Wordpress
## /brro-webdev
 
# Index:
1. File content explanation
	* brro-webdev.php
 	* /php/brro-webdev-admin.php
 	* /php/brro-webdev-generate-css.php
 	* /php/brro-webdev-global.php
 	* /php/brro-webdev-settings.php
 	* /js/brro-backend-elementor-script.js
 	* /js/brro-frontend-inspector-script.js
 	* /css/brro-frontend-var-style.css
 	* /css/brro-inspector-style.css
2. Scope of usability
3. Developing with Code Snippets
4. Going live with brro-production
5. License

## To do: remove functions index from brro-webdev.php and place them here in the readme

# File content explanation
## brro-webdev.php
Explanation

## /php/brro-webdev-admin.php
// brro_add_wplogin_css()                          | Add WP Login CSS
// brro_admin_redirect()                           | WP Private Mode
// brro_temporary_unavailable()                    | SEO check for private mode
// brro_disable_admin_bar_for_subscribers()        | Hide admin bar in private mode
// brro_check_jquery()                             | Load jQuery if not loaded
// brro_disable_xmlrpc_comments                    | Remove comment support
// brro_disable_drag_metabox()
// brro_instructions_button()
// brro_wp_admin_sidebar_jquery()                  | Restyle the WP admin sidebar
// brro_dashboard_css()                            | Style dashboard for users

## /php/brro-webdev-generate-css.php
// brro_handle_generate_css()                      | Trigger regen CSS from frontend
// brro_elementor_devtools_read_and_generate_css() | Conditionally calculate output for css var() and write to frontend css file

## /php/brro-webdev-global.php
// brro_wp_css_body_class()

## /php/brro-webdev-settings.php
// brro_plugin_add_settings_page()
// brro_plugin_settings_page()
// brro_plugin_register_settings()                 | all plugin settings: make page, individual settings, save settings

## /js/brro-backend-elementor-script.js
Explanation

## /js/brro-frontend-inspector-script.js
Explanation

## /css/brro-frontend-var-style.css
Explanation

## /css/brro-inspector-style.css
Explanation


# Scope of usability
Explanation


# Developing Code Snippets
Explanation


# Going live with brro-production
Explanation


# License
This project is licensed under the MIT License - see the LICENSE file for details.