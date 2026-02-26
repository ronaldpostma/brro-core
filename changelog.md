## V2.1.3 - Released 2026-...
- [ ] 2026-02-26 - Hide the custom help URL and Posts Menu Customization settings when developer mode is off, and prevent an empty help menu item from being added; updated `brro_plugin_settings_page` in `brro-core-settings.php` and `brro_add_custom_menu_items` in `brro-core-admin.php`.
- [ ] 2026-02-26 - Fix mobile range clamp calculations so they use the real values at mobile start/end breakpoints; updated `buildClampRange`/`calcForMobile` in `brro-core-css-calculator-script.js` and the double-input mobile branch in `brro-core-elementor-editor-script.js`.
- [ ] 2026-02-26 - Make desktop screenStart editable and change its default to 1024px (with tablet/mobile breakpoints derived accordingly); updated `brro_plugin_settings_page` in `brro-core-settings.php`, the CSS calc popup in `brro-core-admin.php`, and script localization in `brro-core.php`.

## V2.1.2 - Released 2026-02-23
- [x] 2026-02-23 - When comments are turned off, only comment REST endpoints are removed instead of all core REST routes; added `brro_filter_comment_rest_endpoints` in `brro-core-admin.php`. Fixes Site Health REST test and WooCommerce/core REST usage.

## V2.1.1 - Released 2026-02-18
- [x] 2026-02-18 - Added a fixed div 'DEVELOPMENT' badge on dev/stage/test subdomains and force the admin color scheme to sunrise; functions `brro_is_dev_site_subdomain`, `brro_render_dev_site_badge`, `brro_render_dev_site_badge_admin`, `brro_set_admin_color_scheme_on_dev` in `brro-core-global.php` and `brro-core-admin.php`.

## V2.1.0 - Released 2026-02-16
- [x] Removed dependencies on Brro Project and Brro Flex
- [x] Added 'Brro Calc' popup
- [x] Removed 'Elementor inspector'
- [x] Added WP Toolbar toggle frontend
- [x] Small tweaks and updates