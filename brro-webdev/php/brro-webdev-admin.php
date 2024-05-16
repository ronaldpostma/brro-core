<?php
// brro-webdev-admin.php
//
// Login page wp-login.php
// Private / logged in only mode
// Disable admin bar for subscribers/viewers
// Check jQuery
// Remove XML RPC and Comments
// WP Admin Menu Customization
// CSS for WP Admin 
//
// ******************************************************************************************************************************************************
//
// WP Login page
add_action('login_enqueue_scripts', 'brro_add_wplogin_css');
function brro_add_wplogin_css() {
    // Fetching individual settings for each condition
    $backgroundmain = get_option('brro_login_backgroundmain', 'linear-gradient(270deg, beige, blue, purple, pink)'); 
    $backgroundform = get_option('brro_login_backgroundform', 'transparent'); 
    $textlabelcolor = get_option('brro_login_textlabelcolor', '#ffffff'); 
    $sitelogo = get_option('brro_login_sitelogo', 'https://brro.nl/wp-content/uploads/2023/10/brro.svg'); 
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
        body.login.js.wp-core-ui{background:var(--backgroundmain)}.login form{background:var(--backgroundform)!important;font-weight:400!important;border:none!important;box-shadow:none!important}a,label,p{color:var(--textlabelcolor)!important;}#login h1 a,.login h1 a{background-image:var(--sitelogo);height:var(--logoheight);width:var(--logowidth);background-size:contain;background-repeat:no-repeat;margin-top:54px}.login h1{position:relative}.login h1:after{content:'';display:block;position:absolute;top:0;left:0;right:0;bottom:0;z-index:2}.wp-core-ui .button-primary{background:#fff!important;border-color:#000!important;color:#000!important;border-radius:0!important}.login .message,.login .notice,.login .success{text-align:center;border-left:0!important;margin-bottom:0!important;background-color:transparent!important;box-shadow:none!important;}#nav,#backtoblog,#loginform,#language-switcher,.privacy-policy-page-link{display:none;}div#login:not(.showlogin):after{content:'Login';display:block;text-align:center;text-decoration:underline;text-decoration-thickness:1px;text-underline-offset:2px;color:var(--textlabelcolor);font-size:20px;margin-top:48px;}
        ";
    // Outputting the CSS
    echo '<style>' . $custom_login_css . '</style>';
    ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        jQuery(function($) {
            $('div#login:not(.showlogin)').css('cursor','pointer');
            $(document).on('click', 'div#login:not(.showlogin)', function() {
                $('#nav, #backtoblog, #loginform').slideToggle('slow');
                $('.password-input').removeAttr('disabled');
                $('div#login').addClass('showlogin');
                $('div#login.showlogin').css('cursor','default');
            });
        }); 
    </script>    
    <?php
}
//
// ******************************************************************************************************************************************************
//
// Wordpress private / logged in only mode
add_action('get_header', 'brro_admin_redirect');
function brro_admin_redirect() {
    // Check if the private mode is enabled
    $private_mode = get_option('brro_private_mode', 0);
    if ($private_mode == 1) {
        // If user is logged in, no action is needed
        if (is_user_logged_in()) {
            return;
        }
        // Check if the preview access cookie is set and is true
        if (isset($_COOKIE['preview_access']) && $_COOKIE['preview_access'] == 'true') {
            return; // Bypass further checks if the cookie is valid
        }
        $uri = $_SERVER['REQUEST_URI'];
        $preview_regex = '/\/preview\/?$/';
        // Allow temporary login link redirect to preview site
        if (preg_match($preview_regex, $uri)) {
            // Set a cookie to indicate preview access that expires in 2 hours
            setcookie('preview_access', 'true', time() + 7200, COOKIEPATH, COOKIE_DOMAIN);
            wp_redirect(home_url());
            exit; // Bypass further checks
        } else {
            // Redirect to the login page if not logged in and not accessing a preview URL
            wp_redirect(home_url('wp-login.php'));
            exit;
        }
    }
}
//
// ******************************************************************************************************************************************************
//
// Disable admin bar for subscribers (for viewing link)
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
// Check if jQuery loaded (in WP 6.x it failed to load on some sites)
add_action('wp_enqueue_scripts', 'brro_check_jquery');
function brro_check_jquery() {
    if (!wp_script_is('jquery', 'enqueued')) {
        wp_enqueue_script('jquery');
    }
}
//
// ******************************************************************************************************************************************************
//
// Remove XML RPC and Comments
// Hook XML-RPCinto 'after_setup_theme'
add_action('after_setup_theme', 'brro_disable_xmlrpc_comments');
function brro_disable_xmlrpc_comments() {
    $xmlrpc_off = get_option('brro_xmlrpc_off', 0);
    if ($xmlrpc_off == 1) {
        add_filter('xmlrpc_enabled', '__return_false');
    }
    $comments_off = get_option('brro_comments_off', 0);
    if ($comments_off == 1) {
        add_filter('comments_open', '__return_false', 20, 2); // Close comments on the front-end
        add_filter('pings_open', '__return_false', 20, 2); // Close pings on the front-end
        add_filter('comments_array', '__return_empty_array', 10, 2); // Hide existing comments
    }
}
// Remove comments
add_action('admin_init', function () {
    $comments_off = get_option('brro_comments_off', 0);
    if ($comments_off == 1) {
        global $pagenow;
        if ($pagenow === 'edit-comments.php') {
            wp_safe_redirect(admin_url());
            exit;
        }
        // Remove comments metabox from dashboard
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
        // Disable support for comments and trackbacks in post types
        foreach (get_post_types() as $post_type) {
            if ( (class_exists( 'WooCommerce' )) && ($post_type === 'shop_order') )  {
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
});
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
//
// ******************************************************************************************************************************************************************
// 
// WP Admin UX for site owners and editors
//
// jQuery for WP Admin Sidebar
add_action('admin_head', 'brro_wp_admin_sidebar_jquery');
function brro_wp_admin_sidebar_jquery() {
    $user = get_current_user_id();
    $get_editors = get_option('brro_editors', '2,3,4,5');
    $editors = array_map('intval', explode(',', $get_editors));
    if (in_array($user, $editors)) {
        $helpUrl = get_option('brro_client_help_url','https://www.brro.nl/contact');
    } else {
        $helpUrl = 'https://www.brro.nl/';
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Add separator classes
        $('#toplevel_page_brro-separator-core, #toplevel_page_brro-separator-functionality, #toplevel_page_brro-separator-content').addClass('brro-separator');
        $('#toplevel_page_brro-separator-core').nextUntil('#toplevel_page_brro-separator-functionality').addClass('brro-core');
        $('#toplevel_page_brro-separator-functionality').nextUntil('#toplevel_page_brro-separator-content').addClass('brro-functionality');
        $('#toplevel_page_brro-separator-content').nextUntil('#collapse-menu').addClass('brro-content');
        // Brro help link
        $('#toplevel_page_brro-help-link a').attr('href', '<?php echo esc_url($helpUrl); ?>').attr('target', '_blank');
        setTimeout(function() {
            $('#adminmenu').css('opacity', '1');
        }, 100);
        $('.acf-field input, .acf-field textarea').each(function() {
            var maxLength = $(this).attr('maxlength');
            $(this).parent().attr('brro-acf-data-maxlength', maxLength);
        });
    });
    </script>
    <?php
}
//
// ******************************************************************************************************************************************************************
//  
//  Add custom separators and delete the default ons
add_action('admin_menu', 'brro_add_custom_menu_items');
function brro_add_custom_menu_items() {
    global $menu;
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
    
    
}
//
// ******************************************************************************************************************************************************************
//
// WP Admin Sidebar order
add_filter('custom_menu_order', '__return_true'); // Enable custom menu ordering.
add_filter('menu_order', 'brro_custom_admin_menu_order', 1000); // Function for the custom order
function brro_custom_admin_menu_order($menu_ord) {
    if (!$menu_ord) return true;
    $custom_order = array(
        'index.php', // Dashboard
        'brro-help-link', // Brro help outward link
        'brro-separator-core', // Brro separator
        'edit-comments.php', // Comments
        'themes.php', // Appearance   
        'tools.php', // Tools
        'brro-plugin-settings', // Brro
        'options-general.php', // Settings
        'brro-separator-functionality', // Brro separator
        'plugins.php', // Plugins
    );
    // Initialize array to hold menu items fetched from plugin settings
    $plugin_settings_menuitems = array(); // To hold menu items fetched from plugin settings
    $append_custom_menuitems = get_option('brro_append_menuitems'); // Fetch items to append from settings list
    $menuitem_strings = explode("\n", $append_custom_menuitems); // Convert to array 
    $menuitem_strings = array_map('trim', $menuitem_strings); // Trim each string to remove possible white spaces
    // Insert all menu items after "Plugins", except for exceptions from settings
    foreach ($menu_ord as $menu_item) {
        // Skip if the item is already in the custom order
        if (!in_array($menu_item, $custom_order)) {
            $matched = false; // Flag to indicate if a match was found in plugin settings
            // Check against plugin settings items
            foreach ($menuitem_strings as $item) {
                if ($menu_item === $item) {
                    $plugin_settings_menuitems[] = $menu_item;
                    $matched = true;
                    break; // Stop the loop if a match is found
                }
            }
            // For items not matching specific criteria and not in plugin settings
            if (!$matched) {
                $custom_order[] = $menu_item;
            }
        }
    }
    // Append these specific items after plugins
    $custom_order[] = 'brro-separator-content'; // Brro separator
    $custom_order[] = 'upload.php'; // Media
    $custom_order[] = 'users.php'; // Users
    $custom_order[] = 'edit.php?post_type=page'; // Pages
    $custom_order[] = 'edit.php'; // Posts
    // Final assembly of the custom order array
    $custom_order = array_merge($custom_order, $plugin_settings_menuitems);
    return $custom_order;
}
// Hook into 'admin_menu' to remove certain menu pages if not site main admin
add_action('admin_menu', 'brro_remove_nonadmin_menus');
function brro_remove_nonadmin_menus() {
    $user = get_current_user_id();
    $admin = 1;
    if ($user !== $admin) {
        // Remove specific menu pages for editors
        remove_menu_page('options-general.php'); // Site settings
        remove_menu_page('themes.php'); // Themes
        remove_menu_page('brro-plugin-settings'); // Brro settings
    }
}
//
// ******************************************************************************************************************************************************************
//  
// Dashboard CSS
add_action('admin_head', 'brro_dashboard_css');
function brro_dashboard_css() {
    $user = get_current_user_id();
    $get_editors = get_option('brro_editors', '2,3,4,5');
    $editors = array_map('intval', explode(',', $get_editors));
    if (in_array($user, $editors)) {
        ?>    
        <style> 
            /* Display > none 
             * Hide all links in top menu and page attributes */ 
            #wpadminbar li, .wp-admin #wpfooter,
            /* Notices */
            .e-notice, div.notice:not(#user_switching):not(.error), .updated.woocommerce-message,
            /*attributes*/
            p.page-template-label-wrapper, #page_template {
                display:none;
            }
            /* Display > reset initial
            /* Show Admin top menu */
            #wpadminbar li#wp-admin-bar-site-name, #wpadminbar li#wp-admin-bar-my-account, #wpadminbar li#wp-admin-bar-logout {
                display:inherit!important;
            }
            /* Customize media page */
            .upload-php .row-actions .edit,
            .upload-php .row-actions .delete,
            .upload-php .bulkactions,
            .media-new-php a.edit-attachment {display:none;}
            .upload-php .filename {font-weight:bold;font-size:14px;}
            /* Hide publishing actions in pages and posts */
            #misc-publishing-actions > div:not(.curtime):not(.misc-pub-post-status), 
            #minor-publishing-actions {
                visibility:hidden;
                height:2px;
                overflow:hidden;
                padding:0;
            }
            /* Custom dashboard */
            .index-php .wrap h1,
            .index-php #dashboard-widgets-wrap {visibility:hidden;}
            .index-php #dashboard-widgets-wrap:before {visibility: visible;margin-bottom:64px;margin-top:68px;display:block;}
        </style>
        <?php 
    }
    ?>
    <style>   
        /* CSS for everybody in WP Admin */
        /* WP Sidebar */
        #adminmenu {
            opacity:0;
            transition: opacity 150ms ease-in-out;
        }
        /* Content editor */
        @media (min-width:1700px) {
            #poststuff #post-body.columns-2 {
                max-width: 1180px;
                margin-left: calc((100% - 1500px) / 2);
            }
        }
        /* Hide items by default */
        li#collapse-menu,
        li.wp-menu-separator{
            display:none;
        }
        /* Separators */
        .brro-separator .wp-menu-name {font-size:0;}
        .brro-separator .wp-menu-name:after {font-size:14px;}
        .brro-separator {background-color: rgba(143, 4, 86, .9);pointer-events:none;}
        .brro-separator.wp-has-current-submenu .wp-menu-image:before {transform:rotate(180deg)}
        .brro-separator:not(#toplevel_page_brro-separator-core) a {margin-top:24px;}
        #toplevel_page_brro-help-link a {margin-bottom:20px;}
        /* Code Snippets */
        .cloud-connect-wrap,
        a[data-snippet-type="bundles"],
        a[data-snippet-type="cloud_search"],
        a[data-snippet-type="cloud"],
        span.cloud,
        .submit-inline button:first-of-type,
        .generate-button {
            display:none;
        }
        /* Uitleg bij featured image */
        #postimagediv h2:after {
            margin-left:6px;
        }
        #postimagediv h2 {
            justify-content:start;
        }
        /* Tekst uitleg bij 'samenvatting */
        textarea#excerpt + p:before {
            display: block;
            font-size: 13px;
            line-height: 1.5;
        }
        textarea#excerpt + p {
            font-size:0px!important;
        }
        /* link select ACF */
        body:not(.post-type-locateandfiltermap) .select2-container .select2-selection--single {width:auto!important;height:auto!important;}
        /* :before character length ACF */
        div[brro-acf-data-maxlength]:before {
            content: 'Maximaal ' attr(brro-acf-data-maxlength) ' karakters';
            font-weight:400;
            margin:4px 0;
            font-style:italic;
        }
    </style> 
<?php 
}