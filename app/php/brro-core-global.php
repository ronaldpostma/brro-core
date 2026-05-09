<?php
if (!defined('ABSPATH')) exit;
/*
Function Index for brro-core-global.php:
1. brro_wp_css_body_class
   - Adds custom classes to the body tag based on user roles and page properties.
2. brro_add_post_id_admin_body_class
   - Adds post ID and post type as classes in the admin body for styling purposes.
3. brro_acf_content_shortcode
   - Creates a shortcode to display ACF field data with optional before and after HTML.
4. brro_get_cached_acf_field
   - Retrieves an ACF field value with caching to improve performance.
5. brro_clear_acf_field_cache
   - Clears the cached ACF field value when the ACF field is updated.
6. brro_handle_updated_post_meta
   - Hooks into post meta updates to clear cached ACF field values.
7. brro_navburger_shortcode
   - Generates a customizable navigation burger icon via a shortcode.
8. brro_toolbar_toggle_handler
   - Handles toolbar toggle for admins via URL param
9. brro_toolbar_toggle_button
   - Displays fixed Toolbar toggle button for admins on frontend
10. brro_is_dev_site_subdomain
   - Detects if site host uses a dev/stage/staging/test subdomain.
11. brro_render_dev_site_badge
   - Renders a fixed "Dev site" badge on the frontend.
*/
//
// ******************************************************************************************************************************************************************
//  
/* ========================================
   FRONTEND BODY CLASSES
   Adds custom classes to the body tag based on user roles and page properties
   ======================================== */
add_filter( 'body_class', 'brro_wp_css_body_class' );
function brro_wp_css_body_class( $classes ){
    $user = get_current_user_id();
    $get_editors = get_option('brro_editors', '2,3,4,5');
    $editors = array_map('intval', explode(',', $get_editors)); 
    if (in_array($user, $editors)) {
        $classes[] = 'webeditor';  
    }
    // Add body class for guests
    if (!is_user_logged_in())  {
        $classes[] = 'guest';  
    }
    if (current_user_can('administrator')){
        $classes[] = 'webadmin';  
    }
    // Check if the current page is hierarchical and determine if it's a child or parent
    if ( $post = get_post() ) {
        if ( is_post_type_hierarchical( $post->post_type ) ) {
            if ( $post->post_parent ) {
                // Add 'child' class if the current hierarchical post has a parent
                $classes[] = 'child';
            } else {
                // Add 'parent' class if the current hierarchical post doesn't have a parent
                $classes[] = 'parent';
            }
        } else {
            $classes[] = 'not-hierarchical';
        }
    }
    // Check if the current post has a featured image
    if ( is_single() && has_post_thumbnail() ) {
        $classes[] = 'featuredimg-set';
    }
    return $classes;
}
/* ========================================
   ADMIN BODY CLASSES
   Adds post ID and post type as classes in the admin body for styling purposes
   ======================================== */
add_filter('admin_body_class', 'brro_add_post_id_admin_body_class');
function brro_add_post_id_admin_body_class($classes) {
    // Check if we are on a post edit screen robustly
    if (!is_admin() || !function_exists('get_current_screen')) {
        return $classes;
    }
    $screen = get_current_screen();
    if (!is_object($screen) || $screen->base !== 'post' || $screen->id === 'edit-post') {
        return $classes;
    }
    // Get the current post ID (fallback to request if needed)
    $post_id = get_the_ID();
    if (empty($post_id) && isset($_GET['post'])) {
        $post_id = (int) $_GET['post'];
    }
    $post_type = $post_id ? get_post_type($post_id) : '';
    if ($post_id) {
        $classes .= ' post-id-' . $post_id;
    }
    if (!empty($post_type)) {
        $classes .= ' post-type-' . $post_type;
    }
    // Add role-based classes for admin screens
    if (function_exists('wp_get_current_user')) {
        $user = wp_get_current_user();
        if ($user && !empty($user->roles) && is_array($user->roles)) {
            if (in_array('administrator', $user->roles, true)) {
                $classes .= ' webadmin';
            }
            if (in_array('editor', $user->roles, true)) {
                $classes .= ' webeditor';
            }
            if (in_array('subscriber', $user->roles, true)) {
                $classes .= ' websubscriber';
            }
        }
    }
    return $classes;
}
//
// ******************************************************************************************************************************************************************
//  
/* ========================================
   ACF CONTENT SHORTCODE
   Creates a shortcode to display ACF field data with optional before and after HTML
   Example: [acfcontent before="<span>" field="custom_title" after="</span>"]
   ======================================== */
add_shortcode('acfcontent', 'brro_acf_content_shortcode');
function brro_acf_content_shortcode($atts) {
    if (!function_exists('get_field')) {
        return '';
    }
    // Shortcode attributes
    $attributes = shortcode_atts([
        'before' => '',  // Default value if 'before' attribute is not provided
        'field' => '',   // The ACF field name
        'after' => '',   // Default value if 'after' attribute is not provided
        'id' => get_the_ID(),  // Default value if 'id' attribute is not provided is the current post
        'cache' => 'on',  // Default to using cache
        'autop' => 'off'  // Default to not using wpautop
    ], $atts);
    // Determine whether to use cached version or not
    $use_cache = $attributes['cache'] !== 'off';
    // Determine whether to use wpautop or not
    $use_autop = $attributes['autop'] === 'on';
    // Retrieve the ACF field value using the appropriate method
    if ($use_cache) {
        $acfValue = brro_get_cached_acf_field($attributes['field'], $attributes['id']);
    } else {
        $acfValue = get_field($attributes['field'], $attributes['id']);
    }
    // Apply wpautop if required
    if ($use_autop && is_string($acfValue)) {
        $acfValue = wpautop($acfValue);
    }
    // Check if the ACF field value is complex (array or object)
    if (is_array($acfValue) || is_object($acfValue)) {
        // Return empty if the ACF field value is not a simple string or HTML value
        return '';
    }
    // Check if the ACF field value is not empty or false
    if (!empty($acfValue)) {
        // Concatenate the before string, ACF field value, and after string safely
        $before = is_string($attributes['before']) ? wp_kses_post($attributes['before']) : '';
        $after = is_string($attributes['after']) ? wp_kses_post($attributes['after']) : '';
        $output = $before . $acfValue . $after;
    } else {
        // If ACF field is empty or not found, return an empty string or a default message
        $output = '';  // Or use a default message like "ACF field not found."
    }
    // Return the final output
    return $output;
}
/* ========================================
   ACF FIELD CACHING
   Retrieves an ACF field value with caching to improve performance
   ======================================== */
function brro_get_cached_acf_field($field_name, $post_id) {
    $transient_key = 'acf_field_' . $post_id . '_' . $field_name;
    $cached_value = get_transient($transient_key);
    if ($cached_value !== false) {
        return $cached_value;
    }
    $acf_value = get_field($field_name, $post_id);
    $success = set_transient($transient_key, $acf_value, 12 * HOUR_IN_SECONDS);
    return $acf_value;
}
/* ========================================
   ACF CACHE CLEARING
   Clears the cached ACF field value when the ACF field is updated
   ======================================== */
function brro_clear_acf_field_cache($post_id, $meta_key) {
    $transient_key = 'acf_field_' . $post_id . '_' . $meta_key;
    $success = delete_transient($transient_key);
}
/* ========================================
   POST META UPDATE HANDLER
   Hooks into post meta updates to clear cached ACF field values
   ======================================== */
add_action('updated_post_meta', 'brro_handle_updated_post_meta', 10, 4);
add_action('added_post_meta', 'brro_handle_updated_post_meta', 10, 4);
add_action('deleted_post_meta', 'brro_handle_updated_post_meta', 10, 4);

function brro_handle_updated_post_meta($meta_id, $post_id, $meta_key, $_meta_value) {
    brro_clear_acf_field_cache($post_id, $meta_key);
}
//
// ******************************************************************************************************************************************************************
//  
/* ========================================
   NAVIGATION BURGER SHORTCODE
   Generates a customizable navigation burger icon via a shortcode
   Example usage: [navburger style="60px 40px 8px 3px red green"]
   ======================================== */
add_shortcode('navburger', 'brro_navburger_shortcode');
function brro_navburger_shortcode($atts) {
    // Shortcode attributes, expecting one 'style' attribute
    $attributes = shortcode_atts(array(
        'style' => '',
    ), $atts);
    // Explode the 'style' string into an array and sanitize parts
    $style = array_map('trim', explode(' ', $attributes['style']));
    $w = esc_attr($style[0]);
    $h = esc_attr($style[1]);
    $bar_h = esc_attr($style[2]);
    $bar_r = esc_attr($style[3]);
    $color = esc_attr($style[4]);
    $color_hover = esc_attr($style[5]);
    // Check if all necessary parameters are provided
    if (count($style) < 6) {
        return 'Wrong parameters for shortcode [navburger]. Example usage: [navburger style="60px 40px 8px 3px red green"]';
    }
    // Start output buffering
    ob_start();
    // Construct output based on parameters given in the shortcode
    echo '<div style="display:inline-block;width:auto;">';
    echo "<script>
        document.addEventListener('DOMContentLoaded',function(){
            document.addEventListener('click',function(e){
                if(e.target.id==='nav-icon'||e.target.closest('#nav-icon')){
                    document.getElementById('nav-icon').classList.toggle('open');
                }
            });
        });
        </script>";
    echo "<style>
        #nav-icon .bar {width: 100%; height: {$bar_h}; border-radius: {$bar_r}; background-color: {$color};transition:transform 300ms ease, opacity 300ms ease, background-color 300ms ease;}
        #nav-icon.open .bar,#nav-icon:hover .bar {background-color: {$color_hover};}
        #nav-icon:not(.open):hover .bar.three {transform: translateY(calc(.25 * {$bar_h}));}
        #nav-icon:not(.open):hover .bar.one {transform: translateY(calc(0px - (.25 * {$bar_h})));}
        .bar.one {align-self:flex-start;}
        .bar.three {align-self:flex-end;}
        #nav-icon.open .bar.two {transform: rotate(45deg);}
        #nav-icon.open .bar.one {transform: translateY(calc(({$h} - (3 * {$bar_h})) / 2 + {$bar_h})) rotate(-45deg);}
        #nav-icon.open:not(:hover) .bar.one,#nav-icon.open:not(:hover) .bar.two {background-color: {$color};}
        #nav-icon.open .bar.three {opacity:0;} 
        </style>";
    echo '<div id="nav-icon" style="width:'.$w.';height:'.$h.';position:relative;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:space-between;"><span class="bar one"></span><span class="bar two"></span><span class="bar three"></span></div>';
    echo '</div>';
    // Return the buffered content
    return ob_get_clean();
}
//
// ******************************************************************************************************************************************************************
//
/* ========================================
   TOOLBAR TOGGLE FOR ADMINISTRATORS
   Fixed button to toggle show_admin_bar_front user meta on frontend
   ======================================== */
add_action( 'init', 'brro_toolbar_toggle_handler', 5 );
function brro_toolbar_toggle_handler() {
    if ( is_admin() || ! is_user_logged_in() ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( empty( $_GET['brro_toggle_toolbar'] ) || $_GET['brro_toggle_toolbar'] !== '1' ) {
        return;
    }
    if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'brro_toggle_toolbar' ) ) {
        return;
    }
    $user_id = get_current_user_id();
    if ( $user_id <= 0 ) {
        return;
    }
    $current  = get_user_meta( $user_id, 'show_admin_bar_front', true );
    $show_now = ( $current !== 'false' );
    $new_val  = $show_now ? 'false' : 'true';
    update_user_meta( $user_id, 'show_admin_bar_front', $new_val );
    $redirect = remove_query_arg( array( 'brro_toggle_toolbar', '_wpnonce' ) );
    wp_safe_redirect( $redirect );
    exit;
}

add_action( 'wp_footer', 'brro_toolbar_toggle_button' );
function brro_toolbar_toggle_button() {
    if ( is_admin() || ! is_user_logged_in() ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $url = add_query_arg(
        array(
            'brro_toggle_toolbar' => '1',
            '_wpnonce'            => wp_create_nonce( 'brro_toggle_toolbar' ),
        )
    );
    ?>
    <a href="<?php echo esc_url( $url ); ?>" class="brro-toolbar-toggle-btn" style="position:fixed;bottom:10px;left:10px;background:#23282d;color:#fff;padding:8px 14px;border-radius:5px;text-decoration:none;font-size:13px;z-index:999999;box-shadow:0 2px 5px rgba(0,0,0,0.2);">
        Toolbar
    </a>
    <?php
}

/* ========================================
   DEV SITE BADGE (FRONTEND)
   Shows a fixed badge when on dev/stage/staging/test subdomains
   ======================================== */
add_action('wp_footer', 'brro_render_dev_site_badge');
function brro_is_dev_site_subdomain() {
    $host = parse_url(home_url(), PHP_URL_HOST);
    if (!$host || !is_string($host)) {
        return false;
    }
    $host = strtolower($host);
    $pattern = '/^(dev([\-0-9a-z]+)?|stage([\-0-9a-z]+)?|staging([\-0-9a-z]+)?|test([\-0-9a-z]+)?)\./';
    return (bool) preg_match($pattern, $host);
}
function brro_render_dev_site_badge() {
    if (!brro_is_dev_site_subdomain()) {
        return;
    }
    echo '<div style="position:fixed;right:12px;bottom:12px;z-index:999999;background:#c40000;color:#fff;padding:16px 24px;border-radius:8px;font-size:20px;line-height:1;font-family:Arial,sans-serif;box-shadow:0 2px 6px rgba(0,0,0,0.25);">DEVELOPMENT</div>';
}