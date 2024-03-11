<?php
// brro-webdev-admin.php
//
// Private / logged in only mode
// Disable admin bar for subscribers/viewers
// wp-login.php css
// Check jQuery
// Remove XML RPC and Comments
// WP Admin Menu jQuery collapse
// CSS for WP Admin Sidebar
//
// ******************************************************************************************************************************************************
//
// WP Login page
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
add_action('login_enqueue_scripts', 'brro_add_wplogin_css');
//
// ******************************************************************************************************************************************************
//
// Wordpress private / logged in only mode
function brro_admin_redirect() {
    $private_mode = get_option('brro_private_mode', 0);
    if ($private_mode == 1) {
        if ( !is_user_logged_in()) {
            wp_redirect( home_url('wp-login.php') );
            exit;
        }
    }
}
add_action('get_header', 'brro_admin_redirect');
//
// ******************************************************************************************************************************************************
// SEO addition to private mode
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
add_action('template_redirect', 'brro_temporary_unavailable');
//
// ******************************************************************************************************************************************************
//
// Disable admin bar for subscribers (for viewing link)
function brro_disable_admin_bar_for_subscribers() {
    $private_mode = get_option('brro_private_mode', 0);
    if ($private_mode == 1) {
        $user = wp_get_current_user();
        if (in_array('subscriber', $user->roles)) {
            show_admin_bar(false);
        }
    }
}
add_action('after_setup_theme', 'brro_disable_admin_bar_for_subscribers');
//
// ******************************************************************************************************************************************************
//
// Check jQuery loaded
function brro_check_jquery() {
    if (!wp_script_is('jquery', 'enqueued')) {
        wp_enqueue_script('jquery');
    }
}
add_action('wp_enqueue_scripts', 'brro_check_jquery');
//
// ******************************************************************************************************************************************************
//
// Remove XML RPC and Comments
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
// Hook into 'after_setup_theme'
add_action('after_setup_theme', 'brro_disable_xmlrpc_comments');
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
// Disable drag postboxes
function brro_disable_drag_metabox() {
    $user = get_current_user_id();
    $editorone = '2';
    $editortwo = '3';
    if( $user == $editorone || $user == $editortwo ) {
        wp_deregister_script('postbox');
    }
}
add_action( 'admin_init', 'brro_disable_drag_metabox' );
//
// ******************************************************************************************************************************************************************
//  
// INSTRUCTIONS BUTTON
add_action( 'admin_head', 'brro_instructions_button' );
function brro_instructions_button() {
    $helpUrl = get_option('brro_client_help_url','https://www.brro.nl/contact');
    ?>
    <script>
        jQuery(document).ready(function($){
            $('#dashboard-widgets-wrap').prepend('<a id="helpbutton" href="<?php echo esc_url($helpUrl); ?>" target="_blank">Hulp bij de website</a>');
        });
    </script>
  <?php
}
//
// ******************************************************************************************************************************************************
//
// jQuery for WP Admin Sidebar
add_action('admin_head', 'brro_wp_admin_sidebar_jquery');
function brro_wp_admin_sidebar_jquery() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('<li class="wp-not-current-submenu wp-menu-separator custom-sep pre-separator" aria-hidden="true"><div class="separator"></div></li>').insertAfter('li#menu-dashboard');
        $('<li class="wp-not-current-submenu wp-menu-separator custom-sep first-separator" aria-hidden="true"><div class="separator"></div></li>').insertBefore('li#menu-plugins');
        $('<li class="wp-not-current-submenu wp-menu-separator custom-sep last-separator" aria-hidden="true"><div class="separator"></div></li>').insertBefore('li#menu-users');
        // Add 'group' classes to all 'li' elements after 'li.xxx-separator'
        $('li.pre-separator').nextAll('li:not(.first-separator):not(.last-separator)').addClass('group one');
        $('li.first-separator').nextAll('li:not(.last-separator)').addClass('two');
        $('li.last-separator').nextAll('li').addClass('three');
        // Click collapse triggers
        $(document).on('click', '.pre-separator', function() {
            $(this).toggleClass('activesep');
            $('li.group.one:not(.two):not(.three)').toggle();
        });
        $(document).on('click', '.first-separator', function() {
            $(this).toggleClass('activesep');
            $('li.group.one.two:not(.three)').toggle();
        });
        $(document).on('click', '.last-separator', function() {
            $(this).toggleClass('activesep');
            $('li.group.one.two.three').toggle();
        });
        if ($('li.group.one:not(.two):not(.three)').hasClass('wp-has-current-submenu')) {
            $('li.group.one:not(.two):not(.three):not(.wp-has-current-submenu)').toggle();
        }
        if ($('li.group.one.two:not(.three)').hasClass('wp-has-current-submenu')) {
            $('li.group.one.two:not(.three):not(.wp-has-current-submenu)').toggle();
        }
        if ($('li.group.one.two.three').hasClass('wp-has-current-submenu')) {
            $('li.group.one.two.three:not(.wp-has-current-submenu)').toggle();
        }
        $('li.wp-has-current-submenu').each(function() {
            $(this).prevAll('li.custom-sep').first().addClass('activesep');
        });
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
// Dashboard CSS
add_action('admin_head', 'brro_dashboard_css');
function brro_dashboard_css() {
    $user = get_current_user_id();
    $admin = '1';
    $editorone = '2';
    $editortwo = '3';
    if( $user == $editorone || $user == $editortwo ) {
        ?>    
        <style> 
            /* cursor draggable */
            .postbox .hndle {
                cursor:default !important;
            } 
            /* Display:none > 
             * Hide all links in side and top menu */
            #adminmenu li.group:not(.three), #adminmenu li.wp-menu-separator,  
            #wpadminbar li, #screen-meta-links, .wp-admin #wpfooter, .handle-actions,
            /* Notices */
            .e-notice, div.notice:not(#user_switching):not(.error),
            /*attributes*/
            p.page-template-label-wrapper, #page_template {
                display:none;
            }
            /* Display:none >
             * Show admin menu items */
            ul.wp-submenu li:not(.wp-submenu-head),#adminmenu li.group.three:not(.wp-menu-separator):not(#collapse-menu),
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
            .index-php #dashboard-widgets-wrap {visibility:hidden;}
            .index-php #dashboard-widgets-wrap:before {visibility: visible;margin-bottom:64px;margin-top:68px;display:block;}
            #helpbutton {background-color:black;visibility:visible;padding:20px;color:white;font-size:22px;margin-left:calc(50% - 91px)}
            #helpbutton:hover {background-color:white;color:black;}
        </style>
        <?php 
    }
    ?>
    <style>   
        /* CSS for everybody in WP Admin */
        /* WP Sidebar */
        #adminmenu {
            opacity:0;
            transition: opacity 300ms ease-in-out;
        }
        #collapse-menu {
            display:none!important;
        }
        li.custom-sep.group,
        li.group:not(.wp-has-current-submenu){
            display:none;
        }
        /* Separators */
        li.custom-sep {
            height: 28px!important;
            background: darkslategrey;
            margin:24px 0 0 0!important;
            cursor:pointer;
        }
        li.activesep{
            background: darkolivegreen;
        }
        li.custom-sep:before {
            padding: 4px 0 0 12px;
            color:white;
            font-weight:600;
            display:block;
        }
        li.pre-separator:before {
            content: 'Settings';
        }
        li.first-separator:before {
            content: 'Plugins';
        }
        li.last-separator:before {
            content: 'Content';
        }
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