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
        body.login.js.wp-core-ui{background:var(--backgroundmain)}.login form{background:var(--backgroundform)!important;font-weight:400!important;border:none!important;box-shadow:none!important}a,label,p{color:var(--textlabelcolor)!important;}#login h1 a,.login h1 a{background-image:var(--sitelogo);height:var(--logoheight);width:var(--logowidth);background-size:contain;background-repeat:no-repeat;margin-top:54px}.login h1{position:relative}.login h1:after{content:'';display:block;position:absolute;top:0;left:0;right:0;bottom:0;z-index:2}.wp-core-ui .button-primary{background:#fff!important;border-color:#000!important;color:#000!important;border-radius:0!important}.login .message,.login .notice,.login .success{border-left:0!important;margin-bottom:0!important;background-color:transparent!important;box-shadow:none!important;}#nav,#backtoblog,#loginform,#language-switcher{display:none;}
        ";
    // Outputting the CSS
    echo '<style>' . $custom_login_css . '</style>';
    ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        jQuery(function($) {
            console.log('jQuery loaded');
            $('h1').css('cursor','pointer').click(function() {
                console.log('Clicked H1');
                $('#nav, #backtoblog, #loginform').slideToggle('slow');
                $('.password-input').removeAttr('disabled');
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
    $private_mode = get_option('brro_private_mode', 0);
    if ($private_mode == 1) {
        if ( !is_user_logged_in()) {
            wp_redirect( home_url('wp-login.php') );
            exit;
        }
    }
}
//
// ******************************************************************************************************************************************************
// SEO addition to private mode
add_action('template_redirect', 'brro_temporary_unavailable');
function brro_temporary_unavailable() {
    $private_mode = get_option('brro_private_mode', 0);
    if ($private_mode == 1) {
        if ( !is_user_logged_in()) {
            header("HTTP/1.1 503 Service Temporarily Unavailable");
            header("Status: 503 Service Temporarily Unavailable");
            header("Retry-After: 3600"); // Tells search engines to check back in an hour
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
    $editors = array('2', '3', '4', '5');
    if (in_array($user, $editors)) {
        $helpUrl = get_option('brro_client_help_url','https://www.brro.nl/contact');
    } else {
        $helpUrl = 'https://www.brro.nl/';
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Add separator classes
        $('#toplevel_page_brro-toggle-core, #toplevel_page_brro-toggle-functionality, #toplevel_page_brro-toggle-content').addClass('brro-separator');
        $('#toplevel_page_brro-toggle-core').nextUntil('#toplevel_page_brro-toggle-functionality').addClass('brro-core');
        $('#toplevel_page_brro-toggle-functionality').nextUntil('#toplevel_page_brro-toggle-content').addClass('brro-functionality');
        $('#toplevel_page_brro-toggle-content').nextUntil('#collapse-menu').addClass('brro-content');
        // Brro help link
        $('#toplevel_page_brro-help-link a').attr('href', '<?php echo esc_url($helpUrl); ?>').attr('target', '_blank');
        
        function brroSeparatorClick(brroSeparator, brroLiClass) {
            $(document).on('click', brroSeparator, function(event) {
                event.preventDefault();
                $(this).toggleClass('wp-has-current-submenu wp-not-current-submenu');
                $(this).find('a').toggleClass('wp-has-current-submenu wp-not-current-submenu').blur();
                $('li' + brroLiClass).toggle();
            });
        }
        // Handle click events for separator
        brroSeparatorClick('#toplevel_page_brro-toggle-core', '.brro-core');
        brroSeparatorClick('#toplevel_page_brro-toggle-functionality', '.brro-functionality');
        brroSeparatorClick('#toplevel_page_brro-toggle-content', '.brro-content');
        // Check and toggle classes on page load
        function brroSeparatorOnLoad(brroSeparator, brroLiClass) {
            if ($('li' + brroLiClass).hasClass('wp-has-current-submenu')) {
                $(brroSeparator).toggleClass('wp-has-current-submenu wp-not-current-submenu');
                $(brroSeparator + ' a').toggleClass('wp-has-current-submenu wp-not-current-submenu').blur();
                $('li' + brroLiClass + ':not(.wp-has-current-submenu):not(.wp-menu-separator)').toggle();
            }
        }
        // Toggle states on page load for each type
        brroSeparatorOnLoad('#toplevel_page_brro-toggle-core', '.brro-core');
        brroSeparatorOnLoad('#toplevel_page_brro-toggle-functionality', '.brro-functionality');
        brroSeparatorOnLoad('#toplevel_page_brro-toggle-content', '.brro-content');
        // Fade in the menu to make it visible after modifications
        setTimeout(function() {
            $('#adminmenu').css('opacity', '1');
        }, 100);
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
    // Iterate over the menu items and remove separators
    foreach ($menu as $index => $item) {
        if ('wp-menu-separator' === $item[4]) {
            unset($menu[$index]);
        }
    }
    // Add custom separators
    add_menu_page('WP Core','|','read','brro-toggle-core','','dashicons-arrow-down-alt2');
    add_menu_page('Plugin Settings','|','read','brro-toggle-functionality','','dashicons-arrow-down-alt2');
    add_menu_page('Site Content','|','read','brro-toggle-content','','dashicons-arrow-down-alt2');
    // Add Brro help item
    add_menu_page('Brro, help!','Brro, help!','read','brro-help-link','','dashicons-external');
}
//
// ******************************************************************************************************************************************************************
//
// WP Admin Sidebar order
add_filter('custom_menu_order', '__return_true'); // Enable custom menu ordering.
add_filter('menu_order', 'brro_custom_admin_menu_order', 10); // Function for the custom order
function brro_custom_admin_menu_order($menu_ord) {
    if (!$menu_ord) return true;
    $custom_order = array(
        'index.php', // Dashboard
        'brro-help-link',
        'brro-toggle-core',
        'edit-comments.php', // Comments
        'themes.php', // Appearance   
        'tools.php', // Tools
        'options-general.php', // Settings
        'brro-toggle-functionality',
        'plugins.php', // Plugins
    );
    $custom_post_types = array(); // Initialize an array to hold custom post type menu items
    // Insert all menu items after "Plugins", except for custom posts
    foreach ($menu_ord as $menu_item) {
        if (!in_array($menu_item, $custom_order)) {
            // Check if the item is a custom post type menu item
            if (strpos($menu_item, 'edit.php?post_type=brro') !== false) {
                // If so, add to the custom post types array instead of the main custom order
                $custom_post_types[] = $menu_item;
            } else {
                // Otherwise, add it to the main custom order
                $custom_order[] = $menu_item;
            }
        }
    }
    // Append these specific items after plugins
    $custom_order[] = 'brro-toggle-content';
    $custom_order[] = 'upload.php'; // Media
    $custom_order[] = 'users.php'; // Users
    $custom_order[] = 'edit.php?post_type=page'; // Pages
    $custom_order[]= 'edit.php'; // Posts
    // Append brro custom post type menu items at the end
    $custom_order = array_merge($custom_order, $custom_post_types);
    return $custom_order;
}
//
// ******************************************************************************************************************************************************************
//  
// Dashboard CSS
add_action('admin_head', 'brro_dashboard_css');
function brro_dashboard_css() {
    $user = get_current_user_id();
    $admin = '1';
    $editors = array('2', '3', '4', '5');
    if (in_array($user, $editors)) {
        ?>    
        <style> 
            /* cursor draggable */
            .postbox .hndle {
                cursor:default !important;
            } 
            /* Display > none 
             * Hide all links in top menu and page attributes */ 
            #wpadminbar li, .wp-admin #wpfooter, .handle-actions,
            /* Notices */
            .e-notice, div.notice:not(#user_switching):not(.error),
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
        /* Hide items by default, except active */
        li.brro-content:not(.wp-has-current-submenu):not(#toplevel_page_brro-help-link),
        li.brro-functionality:not(.wp-has-current-submenu),
        li.brro-core:not(.wp-has-current-submenu),
        li#collapse-menu,
        li.wp-menu-separator{
            display:none;
        }
        /* Separators */
        .brro-separator .wp-menu-name {font-size:0;}
        .brro-separator .wp-menu-name:after {font-size:14px;}
        .brro-separator {mix-blend-mode: luminosity;background-color: rgba(255, 255, 255, .1);}
        .brro-separator.wp-has-current-submenu .wp-menu-image:before {transform:rotate(180deg)}
        .brro-separator:not(#toplevel_page_brro-toggle-core) a {margin-top:24px;}
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
    </style> 
<?php 
}