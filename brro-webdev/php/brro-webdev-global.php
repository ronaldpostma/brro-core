<?php
//
// ******************************************************************************************************************************************************************
//  
// Add body classes frontend
add_filter( 'body_class', 'brro_wp_css_body_class' );
function brro_wp_css_body_class( $classes ){
    $user = get_current_user_id();
    $admin = 1;
    $get_editors = get_option('brro_editors', '2,3,4,5');
    $editors = array_map('intval', explode(',', $get_editors)); 
    if (in_array($user, $editors)) {
        $classes[] = 'webeditor';  
    }
    // Add body class for guests
    if (!is_user_logged_in())  {
        $classes[] = 'guest';  
    }
    if ($user == $admin ){
        $classes[] = 'webadmin';  
    }
    // Check if the current page has a parent
    if ( $post = get_post() ) {
        if ( $post->post_parent ) {
            // Add 'child' class if the current page has a parent
            $classes[] = 'child';
        } else {
            // Add 'parent' class if the current page doesn't have a parent
            $classes[] = 'parent';
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
    // Check if we are on a post edit screen
    if (is_admin() && get_current_screen()->base == 'post' && get_current_screen()->id != 'edit-post') {
        // Get the current post ID
        $post_id = get_the_ID();
        $post_type = get_post_type($post_id);
        if ($post_id) {
            // Add post ID to the body class
            $classes .= ' post-id-' . $post_id;
        }
        if ($post_type) {
            $classes .= ' post-type-' . $post_type;
        }
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
        return;
    }
    // Shortcode attributes
    $attributes = shortcode_atts([
        'before' => '', // Default value if 'before' attribute is not provided
        'field' => '',  // The ACF field name
        'after' => '',  // Default value if 'after' attribute is not provided
        'id' => get_the_ID(), // // Default value if 'id' attribute is not provided is the current post
    ], $atts);

    // Retrieve the ACF field value. get_field() function checks the current post by default
    $acfValue = get_field($attributes['field'], $attributes['id']);

    // Check if the ACF field value is not empty or false
    if (!empty($acfValue)) {
        // Concatenate the before string, ACF field value, and after string
        $output = $attributes['before'] . $acfValue . $attributes['after'];
    } else {
        // If ACF field is empty or not found, return an empty string or a default message
        $output = ''; // Or use a default message like "ACF field not found."
    }

    // Return the final output
    return $output;
}
//
// ******************************************************************************************************************************************************************
//  
// Shortcode constructor for media query line breaks. Example: [break min="600" max="1200"]
add_shortcode('break', 'brro_custom_break_shortcode');
function brro_custom_break_shortcode($atts) {
    // Generate a random ID: 6 characters + 3 digits
    $randomId = 'a' . substr(md5(uniqid(mt_rand(), true)), 0, 4) . mt_rand(10, 99);
    // Extract attributes
    $attributes = shortcode_atts(array(
        'min' => null,
        'max' => null,
    ), $atts);
    $min = $attributes['min'];
    $max = $attributes['max'];
    // Start output buffering
    ob_start();
    // Construct output based on parameters given in the shortcode
    if (!is_null($min) || !is_null($max)) {
        echo '<style>';
        if (!is_null($min) && !is_null($max)) {
            // Both min and max provided
            echo "@media (min-width:{$min}px) and (max-width:{$max}px){#{$randomId} {display:block!important;}}";
        } elseif (!is_null($min)) {
            // Only min provided
            echo "@media (min-width:{$min}px){#{$randomId} {display:block!important;}}";
        } elseif (!is_null($max)) {
            // Only max provided
            echo "@media (max-width:{$max}px){#{$randomId} {display:block!important;}}";
        }
        echo '</style>';
        echo "<span style='display:none' id='{$randomId}'></span>";
    } else {
        // Neither min nor max provided, output only a line break
        echo '<br>';
    }
    // Return the buffered content
    return ob_get_clean();
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
    // Explode the 'style' string into an array based on spaces
    $style = explode(' ', $attributes['style']);
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
        #nav-icon .bar {width: 100%; height: {$style[2]}; border-radius: {$style[3]}; background-color: {$style[4]};transition:transform 300ms ease, opacity 300ms ease, background-color 300ms ease;}
        #nav-icon.open .bar,#nav-icon:hover .bar {background-color: {$style[5]};}
        #nav-icon:not(.open):hover .bar.three {transform: translateY(calc(.25 * {$style[2]}));}
        #nav-icon:not(.open):hover .bar.one {transform: translateY(calc(0px - (.25 * {$style[2]})));}
        .bar.one {align-self:flex-start;}
        .bar.three {align-self:flex-end;}
        #nav-icon.open .bar.two {transform: rotate(45deg);}
        #nav-icon.open .bar.one {transform: translateY(calc(({$style[1]} - (3 * {$style[2]})) / 2 + {$style[2]})) rotate(-45deg);}
        #nav-icon.open:not(:hover) .bar.one,#nav-icon.open:not(:hover) .bar.two {background-color: {$style[4]};}
        #nav-icon.open .bar.three {opacity:0;} 
        </style>";
    echo '<div id="nav-icon" style="width:'.$style[0].';height:'.$style[1].';position:relative;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:space-between;"><span class="bar one"></span><span class="bar two"></span><span class="bar three"></span></div>';
    echo '</div>';
    // Return the buffered content
    return ob_get_clean();
}