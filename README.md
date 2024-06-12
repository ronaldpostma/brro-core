# Brro Core Functions & Devtools
## /brro-core
 

# 1. File index
1. `brro-core.php`
   - Main plugin file that includes other PHP files, enqueues scripts and styles, and handles plugin updates.
   - Contains functions for loading scripts in Elementor editor, frontend inspector, and admin area.
   - Adds custom CSS for inspector mode and handles plugin update checks.
2. `php/brro-core-settings.php`
	- Handles the settings page for the plugin.
	- Registers settings for various plugin functionalities like developer mode, Elementor breakpoints, and frontend inspector settings.
3. `php/brro-core-admin.php`
	- Manages admin-specific functionalities.
	- Includes functions for customizing the admin menu order, disabling XML-RPC and comments, and adding custom CSS to the WordPress login page.
4. `php/brro-core-global.php`
	- Contains global functions used across the site.
	- Functions include adding custom classes to the body tag, handling ACF field data, and creating shortcodes like `acfcontent` and `navburger`.

5. `js/brro-core-elementor-editor.js`
	- JavaScript file for functionalities within the Elementor editor.
6. `js/brro-core-inspector-script.js`
	- JavaScript for the frontend inspector tool.
7. `js/brro-core-wp-admin-script.js`
	- Admin area specific scripts.

8. `css/brro-core-inspector-style.css`
	- Styles for the frontend inspector tool.
9. `css/brro-core-wp-admin-style.css`
	- General styles for the WordPress admin area.
10. `css/brro-core-wp-admin-editors-style.css`
	- Additional admin styles specific to certain user roles.
11. `css/brro-core-wp-admin-admin-style.css`
	- Styles specifically for the WordPress administrator role.

# 2. Scope of usability
GLobal core functions for all sites developed by Brro and development tools used within Elementor and the frontend.

# 3. Custom functions with brro-production
Project specific functions are be placed in [brro-production](https://github.com/ronaldpostma/brro-production).

# 4. License
This project is licensed under the MIT License - see the LICENSE file for details.