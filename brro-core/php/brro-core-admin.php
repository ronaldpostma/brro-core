<?php
if (!defined('ABSPATH')) exit;
/*
Function Index for brro-core-admin.php:
1. brro_add_wplogin_css
    - Adds custom CSS to the WordPress login page based on dynamic settings.
2. brro_admin_redirect
    - Redirects non-logged-in users to the login page if private mode is enabled.
3. brro_restrict_editor_login
    - When “Restrict editor access” is on, blocks login for users in brro_editors with a custom message.
4. brro_get_private_mode_exceptions
    - Parses and normalizes private mode exception URLs from settings.
5. brro_handle_preview_access
    - Handles preview access flow with cookie-based authentication.
6. brro_disable_admin_bar_for_subscribers
    - Disables the WordPress admin bar for users with the subscriber role when private mode is active.
7. brro_check_jquery
    - Ensures jQuery is loaded on the site, enqueuing it if necessary.
8. brro_add_custom_menu_items
    - Customizes the admin menu by removing default separators and adding custom items.
9. brro_custom_admin_menu_order
    - Reorders the admin menu items based on a specified custom order.
10. brro_allow_page_excerpt
    - Enables excerpts on the 'page' post type for SEO descriptions.
11. brro_change_posts_menu_title
    - Changes Posts menu title and icon (only when brro-project is not active).
12. brro_remove_editor_menus
    - Removes menu pages for editors and specific users based on settings.
13. brro_css_calc_popup_handler
    - Renders the chromeless CSS calculator (AJAX, admins only).
14. brro_disable_xmlrpc_comments
    - Disables XML-RPC and comments site-wide based on settings.
15. brro_remove_comments
    - Removes comment UIs and disables comment supports in admin.
*/
//
// ******************************************************************************************************************************************************
//
/* ========================================
   WP LOGIN PAGE CUSTOMIZATION
   Adds custom CSS to the WordPress login page based on dynamic settings
   ======================================== */
add_action('login_enqueue_scripts', 'brro_add_wplogin_css');
function brro_add_wplogin_css() {
    // Fetching individual settings for each condition
    $backgroundmain = get_option('brro_login_backgroundmain', 'linear-gradient(270deg, beige, blue, purple, pink)'); 
    $backgroundform = get_option('brro_login_backgroundform', 'transparent'); 
    $textlabelcolor = get_option('brro_login_textlabelcolor', '#ffffff'); 
    $sitelogo = get_option('brro_login_sitelogo', 'https://brro.nl/base/brro.svg'); 
    $logowidth = get_option('brro_login_logowidth', '140'); 
    $logoheight = get_option('brro_login_logoheight', '160');
        // Constructing the CSS string with dynamic values
    $custom_login_css = "
        :root {
        --backgroundmain: {$backgroundmain};
        --backgroundform: {$backgroundform};
        --textlabelcolor: {$textlabelcolor};
        --sitelogo: url('{$sitelogo}');
        --logowidth: {$logowidth}px;
        --logoheight: {$logoheight}px;
        }
        body.login.js.wp-core-ui{background:var(--backgroundmain)}.login form{background:var(--backgroundform)!important;font-weight:400!important;border:none!important;box-shadow:none!important}a,label,p{color:var(--textlabelcolor)!important;}#login h1 a,.login h1 a{background-image:var(--sitelogo);height:var(--logoheight);width:var(--logowidth);background-size:contain;background-repeat:no-repeat;margin-top:54px}.login h1{position:relative}.login h1:after{content:'';display:block;position:absolute;top:0;left:0;right:0;bottom:0;z-index:2}.wp-core-ui .button-primary{background:#fff!important;border-color:#000!important;color:#000!important;border-radius:0!important}.login .message,.login .notice,.login .success{text-align:center;border-left:0!important;margin-bottom:0!important;background-color:transparent!important;box-shadow:none!important;}/*#nav,#backtoblog,#loginform,#language-switcher,.privacy-policy-page-link{display:none;}div#login:not(.showlogin):after{content:'Login';display:block;text-align:center;text-decoration:underline;text-decoration-thickness:1px;text-underline-offset:2px;color:var(--textlabelcolor);font-size:20px;margin-top:48px;}*/
        ";
    // Outputting the CSS
    echo '<style>' . $custom_login_css . '</style>';
}
//
// ******************************************************************************************************************************************************
//
/* ========================================
   WORDPRESS PRIVATE MODE
   Redirects non-logged-in users to the login page if private mode is enabled
   ======================================== */
add_action('template_redirect', 'brro_admin_redirect');
function brro_admin_redirect() {
    // Only act when private mode is enabled
    $private_mode = get_option('brro_private_mode', 0);
    if ($private_mode != 1) { return; }

    // Logged-in users proceed
    if (is_user_logged_in()) { return; }

    // Normalize current request path
    $uri_raw = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    $current_path = trailingslashit(parse_url($uri_raw, PHP_URL_PATH) ?: '/');

    // Exceptions
    $exception_paths = brro_get_private_mode_exceptions();
    if (in_array($current_path, $exception_paths, true)) { return; }

    // Preview access flow (handles its own redirects)
    if (brro_handle_preview_access($current_path)) { return; }

    // Allow access if preview cookie is set (granted via /preview)
    if (isset($_COOKIE['preview_access']) && $_COOKIE['preview_access'] === 'true') { return; }

    // Redirect to configured URL or login
    $redirect_url_setting = trim(get_option('brro_private_mode_redirect', ''));
    $redirect_url = home_url('wp-login.php'); // Default fallback
    
    // Validate URL - check if not empty and is a valid URL or path
    if (!empty($redirect_url_setting)) {
        // If it starts with /, treat as path and convert to full URL
        if (strpos($redirect_url_setting, '/') === 0) {
            $redirect_url = home_url($redirect_url_setting);
        } else {
            // Otherwise, validate as full URL
            $validated_url = filter_var($redirect_url_setting, FILTER_VALIDATE_URL);
            if ($validated_url !== false) {
                $redirect_url = esc_url_raw($validated_url);
            }
        }
    }
    
    if (!headers_sent()) {
        wp_safe_redirect($redirect_url);
        exit;
    }
}

/* ========================================
   RESTRICT EDITOR ACCESS
   When enabled, users in brro_editors cannot log in; they see a custom message.
   ======================================== */
add_filter('wp_authenticate_user', 'brro_restrict_editor_login', 10, 2);
function brro_restrict_editor_login($user, $password) {
    if (is_wp_error($user)) {
        return $user;
    }
    $restrict = get_option('brro_restrict_editor_access', 0);
    if ((int) $restrict !== 1) {
        return $user;
    }
    $raw = get_option('brro_editors', '2,3,4,5');
    $editors = array_filter(array_map('intval', explode(',', $raw)), function($id) {
        return $id > 0;
    });
    if (empty($editors) || !in_array((int) $user->ID, $editors, true)) {
        return $user;
    }
    return new WP_Error(
        'brro_restrict_editor_access',
        __('Ronald van Brro is momenteel kritieke wijzigingen op de site aan het doorvoeren, en inloggen is tijdelijk niet mogelijk. Bij spoed, mail support@brro.nl', 'brro-core')
    );
}

/* ========================================
   PRIVATE MODE EXCEPTIONS PARSER
   Parses and normalizes private mode exception URLs from settings
   ======================================== */
function brro_get_private_mode_exceptions() {
    $private_redirect_exceptions = get_option('brro_private_redirect_exceptions', '');
    $exceptions = array_filter(array_map('trim', explode("\n", $private_redirect_exceptions)));
    return array_map(function($url){
        // If it starts with /, treat as path directly
        if (strpos($url, '/') === 0) {
            return trailingslashit($url);
        }
        // Otherwise, try to parse as URL
        $path = parse_url($url, PHP_URL_PATH);
        return trailingslashit($path ? $path : '/');
    }, $exceptions);
}

/* ========================================
   PREVIEW ACCESS HANDLER
   Handles preview access flow with cookie-based authentication
   ======================================== */
function brro_handle_preview_access($current_path) {
    // Check if the current path is a preview path
    $is_preview = (bool) preg_match('/\/preview\/?$/', $current_path);
    if (!$is_preview) { return false; }

    // Check if the user has preview access via a cookie
    $has_preview_access = isset($_COOKIE['preview_access']) && $_COOKIE['preview_access'] === 'true';
    if ($has_preview_access) {
        // Redirect to home if headers are not already sent
        if (!headers_sent()) {
            wp_safe_redirect(home_url());
            exit;
        }
        return true;
    }

    // Set a cookie for preview access and redirect to home if headers are not already sent
    if (!headers_sent()) {
        setcookie('preview_access', 'true', array(
            'expires' => time() + 7200, // Cookie expires in 2 hours
            'path' => COOKIEPATH,
            'domain' => COOKIE_DOMAIN,
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ));
        wp_safe_redirect(home_url());
        exit;
    }
    return true;
}
//
// ******************************************************************************************************************************************************
//
/* ========================================
   ADMIN BAR CONTROL FOR SUBSCRIBERS
   Disables the WordPress admin bar for users with the subscriber role when private mode is active
   ======================================== */
add_action('after_setup_theme', 'brro_disable_admin_bar_for_subscribers');
function brro_disable_admin_bar_for_subscribers() {
    $private_mode = get_option('brro_private_mode', 0);
    if ($private_mode == 1) {
        $user = wp_get_current_user();
        if (in_array('subscriber', $user->roles)) {
            show_admin_bar(false);
        }
    }
}
//
// ******************************************************************************************************************************************************
//
/* ========================================
   JQUERY LOADING CHECK
   Ensures jQuery is loaded on the site, enqueuing it if necessary
   ======================================== */
add_action('wp_enqueue_scripts', 'brro_check_jquery');
function brro_check_jquery() {
    if (!wp_script_is('jquery', 'enqueued')) {
        wp_enqueue_script('jquery');
    }
}
//
// ******************************************************************************************************************************************************************
//  
/* ========================================
   CUSTOM ADMIN MENU ITEMS
   Customizes the admin menu by removing default separators and adding custom items
   ======================================== */
add_action('admin_menu', 'brro_add_custom_menu_items');
function brro_add_custom_menu_items() {
    global $menu;
    if (!is_array($menu)) {
        error_log('Global menu is not an array in brro_add_custom_menu_items');
        return;
    }
    $brrohelp = get_option('brro_client_help_menutitle', 'Brro, help!');
    // Iterate over the menu items and remove separators
    foreach ($menu as $index => $item) {
        if ('wp-menu-separator' === $item[4]) {
            unset($menu[$index]);
        }
    }
    // Add custom separators
    add_menu_page('WP Core','|','read','brro-separator-core','','dashicons-arrow-down-alt2');
    add_menu_page('Plugin Settings','|','read','brro-separator-functionality','','dashicons-arrow-down-alt2');
    add_menu_page('Site Content','|','read','brro-separator-content','','dashicons-arrow-down-alt2');
    // Add Brro help item
    add_menu_page($brrohelp,$brrohelp,'read','brro-help-link','','dashicons-external');   
    // Add Chromeless Popup mock link (placeholder) for site administrator role
    add_menu_page('CSS calc','CSS calc','manage_options','brro-calc-popup','','dashicons-calculator');
}
//
// ******************************************************************************************************************************************************************
//
/* ========================================
   ADMIN MENU REORDERING
   Reorders the admin menu items based on a specified custom order
   ======================================== */
add_filter('custom_menu_order', '__return_true'); // Enable custom menu ordering.
add_filter('menu_order', 'brro_custom_admin_menu_order', 1000); // Function for the custom order
function brro_custom_admin_menu_order($menu_ord) {
    // Validate input array
    if (!is_array($menu_ord)) {
        error_log('Menu order is not an array in brro_custom_admin_menu_order');
        return true;
    }
    //
    // Define the core custom order for admin menu items
    $custom_order = array(
        'index.php', // Dashboard
        'brro-help-link', // Brro help outward link
        'brro-calc-popup', // CSS calc
        'brro-separator-core', // Brro separator
        'edit-comments.php', // Comments
        'themes.php', // Appearance   
        'tools.php', // Tools
        'brro-plugin-settings', // Brro
        'options-general.php', // Settings
        'brro-separator-functionality', // Brro separator
        'plugins.php', // Plugins
    );
    //
    // Get plugin settings menu items from database
    $plugin_settings_menuitems = array();
    $append_custom_menuitems = get_option('brro_append_menuitems');
    // Parse plugin settings menu items if they exist
    $menuitem_strings = array();
    if (!empty($append_custom_menuitems)) {
        $menuitem_strings = array_map('trim', explode("\n", $append_custom_menuitems));
    }
    //
    // Process each menu item from WordPress
    foreach ($menu_ord as $menu_item) {
        // Skip items already in our custom order
        if (!in_array($menu_item, $custom_order)) {
            $matched = false;
            //
            // Check if this item matches any plugin settings items
            if (!empty($menuitem_strings)) {
                foreach ($menuitem_strings as $item) {
                    if ($menu_item === $item) {
                        $plugin_settings_menuitems[] = $menu_item;
                        $matched = true;
                        break; // Found match, stop inner loop
                    }
                }
            }
            //
            // Add to custom order if not matched in plugin settings
            if (!$matched) {
                $custom_order[] = $menu_item;
            }
        }
    }
    //
    // Add content section items after plugins
    $custom_order[] = 'brro-separator-content'; // Brro separator
    $custom_order[] = 'upload.php'; // Media
    $custom_order[] = 'users.php'; // Users
    $custom_order[] = 'edit.php?post_type=page'; // Pages
    $custom_order[] = 'edit.php'; // Posts
    //
    // Combine custom order with plugin settings items
    return array_merge($custom_order, $plugin_settings_menuitems);
}
//
// ******************************************************************************************************************************************************************
//  
/* ========================================
   PAGE EXCERPTS ENABLEMENT
   Enables excerpts on the 'page' post type for SEO descriptions
   ======================================== */
add_action('init', 'brro_allow_page_excerpt_for_seo');
function brro_allow_page_excerpt_for_seo() {
    add_post_type_support('page', 'excerpt');
}
//
// ******************************************************************************************************************************************************************
//  
/* ========================================
   POSTS MENU CUSTOMIZATION
   Changes Posts menu title and icon (only when brro-project is not active)
   ======================================== */
add_action( 'admin_menu', 'brro_change_posts_menu_title' );
function brro_change_posts_menu_title() {
    // Only proceed if brro-project is not active and the setting is enabled
    if (!brro_is_project_active() && get_option('brro_change_posts_menu', 0) == 1) {
        global $menu;
        $custom_title = get_option('brro_posts_menu_title', 'Articles');
        $custom_icon = get_option('brro_posts_menu_icon', 'dashicons-admin-post');
        
        // Loop through the menu to find the Posts menu item
        foreach ( $menu as $key => $item ) {
            if ( $item[2] === 'edit.php' ) {
                $menu[$key][0] = $custom_title;
                $menu[$key][6] = $custom_icon;
                break; // Exit the loop after updating the Posts menu item
            }
        }
    }
}

/* ========================================
   EDITOR MENU PAGE REMOVAL
   Removes menu pages for editors and specific users based on settings
   ======================================== */
add_action('admin_init', 'brro_remove_wp_admin_menu_items', 9999);
function brro_remove_wp_admin_menu_items() {
    // Only proceed if brro-project is not active
    if (!brro_is_project_active()) {
        $user = get_current_user_id();
        // Client editors
        $get_editors = get_option('brro_editors', '2,3,4,5');
        $editors = array_filter(array_map('intval', explode(',', $get_editors)), function($id) {
            return $id > 0;
        }); 
        if (in_array($user, $editors)) {
            $get_editors_remove_menupages = get_option('brro_editors_remove_menupages', '');
            if (!empty($get_editors_remove_menupages)) {
                $editors_remove_menupages = array_filter(array_map('trim', explode("\n", $get_editors_remove_menupages)));
                foreach ($editors_remove_menupages as $menu_page) {
                    if (!empty($menu_page)) {
                        remove_menu_page($menu_page);
                    }
                }
            }
        }
        
        // Remove menu pages for specific users
        $get_users_remove_menupages = get_option('brro_users_remove_menupages', '');
        if (!empty($get_users_remove_menupages)) {
            $users_remove_menupages = array_filter(array_map('trim', explode("\n", $get_users_remove_menupages)));
            foreach ($users_remove_menupages as $entry) {
                if (!empty($entry)) {
                    $parts = array_map('trim', explode(',', $entry));
                    if (count($parts) === 2) {
                        $target_user_id = (int) $parts[0];
                        $menu_page = $parts[1];
                        if ($target_user_id === $user && !empty($menu_page)) {
                            remove_menu_page($menu_page);
                        }
                    }
                }
            }
        }
    }
}

//
// ******************************************************************************************************************************************************
//
/* ========================================
   CSS CALCULATOR POPUP HANDLER
   Renders the chromeless CSS calculator (AJAX, admins only)
   ======================================== */
add_action('wp_ajax_brro_css_calc_popup', 'brro_css_calc_popup_handler');
function brro_css_calc_popup_handler() {
    if ( ! is_user_logged_in() || ! current_user_can('manage_options') ) {
        status_header(403);
        echo 'Forbidden';
        wp_die();
    }
    // Settings for calculations
    $desktop_end   = (int) get_option('brro_desktop_end');
    $desktop_ref   = (int) get_option('brro_desktop_ref');
    $desktop_start = (int) get_option('brro_desktop_start');
    $tablet_ref    = (int) get_option('brro_tablet_ref');
    $tablet_start  = (int) get_option('brro_tablet_start');
    $mobile_ref    = (int) get_option('brro_mobile_ref');
    $mobile_start  = (int) get_option('brro_mobile_start');
    $admin_title   = 'Brro CSS calc';
    $jquery_src    = includes_url('js/jquery/jquery.min.js');
    $calc_js_src   = plugins_url('../js/brro-core-css-calculator-script.js', __FILE__);
    header('Content-Type: text/html; charset=' . get_bloginfo('charset'));
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="<?php echo esc_attr(get_bloginfo('charset')); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo esc_html($admin_title); ?></title>
    <style>
        html,body {margin:0;padding:0;background:#111;color:#eee;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;}
        .wrap {padding:16px 20px;max-width:720px;margin:0 auto;}
        h1 {font-size:16px;margin:0 0 12px 0;font-weight:600;color:#fff;}
        form {display:flex;gap:8px;align-items:center;margin-bottom:10px;}
        input[type=text] {background:#1b1b1b;border:1px solid #333;color:#eee;border-radius:4px;padding:8px 10px;min-width:260px;outline:none}
        input[type=text]:focus {border-color:#555}
        button {background:#5a2a82;color:#fff;border:1px solid #7a44ab;border-radius:4px;padding:8px 14px;cursor:pointer}
        button:hover {background:#6a3596}
        .error {color:#ff8585;font-size:12px;min-height:16px;margin:4px 0 10px}
        .outputs {display:block;margin-top:8px}
        .outputs .output {margin-top:8px}
        .output {background:#161616;border:1px solid #2a2a2a;border-radius:6px;padding:10px;display:flex;justify-content:space-between;align-items:center}
        .label {font-size:12px;color:#bbb;margin-right:8px}
        code {display:block;white-space:pre-wrap;word-break:break-all;color:#e8e8e8}
        .copy {margin-left:12px;font-size:12px;color:#aaa;cursor:pointer;user-select:none}
        .copy:hover {color:#fff}
        .copied {color:#6cff9b}
    </style>
</head>
<body>
    <div class="wrap">
        <h1><?php echo esc_html($admin_title); ?></h1>
        <form id="brro-calc-form" autocomplete="off">
            <input id="brro-calc-input" type="text" inputmode="text" spellcheck="false" aria-label="Enter number or range" />
            <button id="brro-calc-submit" type="submit">Calc</button>
        </form>
        <div id="brro-calc-error" class="error" role="alert" aria-live="polite"></div>
        <div class="outputs">
            <div class="output" data-device="desktop"><span class="label">Desktop</span><code id="brro-out-desktop"></code><span class="copy" data-copy="#brro-out-desktop">Copy</span></div>
            <div class="output" data-device="tablet"><span class="label">Tablet</span><code id="brro-out-tablet"></code><span class="copy" data-copy="#brro-out-tablet">Copy</span></div>
            <div class="output" data-device="mobile"><span class="label">Mobile</span><code id="brro-out-mobile"></code><span class="copy" data-copy="#brro-out-mobile">Copy</span></div>
        </div>
    </div>
    <script>
        window.brroSettings = {
            desktopEnd: <?php echo (int) $desktop_end; ?>,
            desktopRef: <?php echo (int) $desktop_ref; ?>,
            desktopStart: <?php echo (int) $desktop_start; ?>,
            tabletRef: <?php echo (int) $tablet_ref; ?>,
            tabletStart: <?php echo (int) $tablet_start; ?>,
            mobileRef: <?php echo (int) $mobile_ref; ?>,
            mobileStart: <?php echo (int) $mobile_start; ?>
        };
    </script>
    <script src="<?php echo esc_url( $jquery_src ); ?>"></script>
    <script src="<?php echo esc_url( $calc_js_src ); ?>"></script>
</body>
</html>
    <?php
    wp_die();
}
//
// ******************************************************************************************************************************************************
//
/* ========================================
   XML-RPC AND COMMENTS DISABLEMENT
   Disables XML-RPC and comments site-wide based on settings
   ======================================== */
add_action('after_setup_theme', 'brro_disable_xmlrpc_comments');
function brro_disable_xmlrpc_comments() {
    $xmlrpc_off = get_option('brro_xmlrpc_off', 0);
    if ($xmlrpc_off == 1) {
        add_filter('xmlrpc_enabled', '__return_false');
        // Remove X-Pingback header
        add_filter('wp_headers', function($headers) {
            unset($headers['X-Pingback']);
            return $headers;
        });
        // Block XML-RPC endpoints completely
        add_action('init', function() {
            if (strpos($_SERVER['REQUEST_URI'], 'xmlrpc.php') !== false) {
                wp_die('XML-RPC is disabled', 'XML-RPC Disabled', ['response' => 403]);
            }
        });
    }
    $comments_off = get_option('brro_comments_off', 0);
    if ($comments_off == 1) {
        add_filter('comments_open', '__return_false', 20, 2); // Close comments on the front-end
        add_filter('pings_open', '__return_false', 20, 2); // Close pings on the front-end
        add_filter('comments_array', '__return_empty_array', 10, 2); // Hide existing comments
        // Remove comment feeds
        add_action('wp_loaded', function() {
            remove_action('wp_head', 'feed_links_extra', 3);
            remove_action('wp_head', 'feed_links', 2);
        });
        // Remove comment-related REST API endpoints
        add_action('rest_api_init', function() {
            remove_action('rest_api_init', 'create_initial_rest_routes', 99);
        }, 1);
    }
}
/* ========================================
   COMMENTS UI REMOVAL
   Removes comment UIs and disables comment supports in admin
   ======================================== */
add_action('admin_init', 'brro_remove_comments');
function brro_remove_comments() {
    $comments_off = get_option('brro_comments_off', 0);
    if ($comments_off == 1) {
        global $pagenow;
        if ($pagenow === 'edit-comments.php') {
            wp_safe_redirect(admin_url(), 301);
            exit;
        }
        // Remove comments metabox from dashboard
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
        // Disable support for comments and trackbacks in post types
        $post_types = get_post_types();
        $woocommerce_active = class_exists('WooCommerce');
        if (is_array($post_types)) {
            foreach ($post_types as $post_type) {
                if ($woocommerce_active && $post_type === 'shop_order') {
                    if (post_type_supports($post_type, 'comments')) {
                        remove_post_type_support($post_type, 'trackbacks');
                    }
                    continue;
                }
                if (post_type_supports($post_type, 'comments')) {
                    remove_post_type_support($post_type, 'comments');
                    remove_post_type_support($post_type, 'trackbacks');
                }
            }
        }
    }
}
add_action('admin_menu', function () {
    $comments_off = get_option('brro_comments_off', 0);
    if ($comments_off == 1) {
        remove_menu_page('edit-comments.php'); // Remove comments page in menu
    }
});
add_action('admin_bar_menu', function ($wp_admin_bar) {
    $comments_off = get_option('brro_comments_off', 0);
    if ($comments_off == 1) {
        $wp_admin_bar->remove_node('comments'); // Remove comments links from admin bar
    }
}, 999);
