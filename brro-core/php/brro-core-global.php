<?php
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
8. brro_allow_page_excerpt
   - Enables excerpts on the 'page' post type for SEO descriptions.
*/
//
// ******************************************************************************************************************************************************************
//  
// Add body classes frontend
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
// Add body class backend
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
    return $classes;
}
//
// ******************************************************************************************************************************************************************
//  
// Shortcode constructor from ACF field data. Example: [acfcontent before="<span>" field="custom_title" after="</span>"]
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
// Cache ACF get_field
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
// Hook to clear cache when ACF field is updated
function brro_clear_acf_field_cache($post_id, $meta_key) {
    $transient_key = 'acf_field_' . $post_id . '_' . $meta_key;
    $success = delete_transient($transient_key);
}
// Hook into post metadata update actions to clear transient cache for relevant ACF fields
add_action('updated_post_meta', 'brro_handle_updated_post_meta', 10, 4);
add_action('added_post_meta', 'brro_handle_updated_post_meta', 10, 4);
add_action('deleted_post_meta', 'brro_handle_updated_post_meta', 10, 4);

function brro_handle_updated_post_meta($meta_id, $post_id, $meta_key, $_meta_value) {
    brro_clear_acf_field_cache($post_id, $meta_key);
}
//
// ******************************************************************************************************************************************************************
//  
// Shortcode constructor for navburger. Example usage: [navburger style="60px 40px 8px 3px red green"]
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
// Add excerpts to pages for SEO page description
add_action('init', 'brro_allow_page_excerpt');
function brro_allow_page_excerpt() {
    add_post_type_support('page', 'excerpt');
}