<?php
//
// ******************************************************************************************************************************************************************
//  
// Add body classes
add_filter( 'body_class', 'brro_wp_css_body_class' );
function brro_wp_css_body_class( $classes ){
    $user = get_current_user_id();
    $admin = '1';
    $editors = array('2', '3', '4', '5'); 
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
//
// ******************************************************************************************************************************************************************
//  
// Shortcode constructor from ACF field data
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
    ], $atts);

    // Retrieve the ACF field value. get_field() function checks the current post by default
    $acfValue = get_field($attributes['field']);

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
    // Example: [acfcontent before="<span>" field="custom_title" after="</span>"]
}
//
// ******************************************************************************************************************************************************************
//  
// Shortcode constructor for media query line breaks
add_shortcode('break', 'brro_custom_break_shortcode');
function brro_custom_break_shortcode($atts) {
    // Generate a random ID: 6 characters + 3 digits
    $randomId = wp_generate_password(6, false) . wp_generate_password(3, false, 'digits');
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
    // Example: [break min="600" max="1200"]
}
//
// ******************************************************************************************************************************************************************
//  
// Prevent image scaling
add_filter('big_image_size_threshold', function($threshold, $imagesize, $file, $attachment_id) {
    // Check if the width of the image is 1920px or less
    if ($imagesize[0] <= 1920) {
        // Return false to prevent scaling down
        return false;
    }
    // Otherwise, use the default threshold
    return $threshold;
}, 10, 4);
//
// ******************************************************************************************************************************************************************
//  
// Restrict upload size images
function brro_restrict_upload_size( $size ) {
    // Get the current user's data
    $current_user = wp_get_current_user();
    // Check if the current user is an editor
    if ( in_array( 'editor', (array) $current_user->roles ) ) {
        // Access the global $_FILE array
        global $_FILES;
        // Check if a file is being uploaded
        if (!empty($_FILES)) {
            // Loop through each uploaded file
            foreach ($_FILES as $file) {
                // Check if file type is jpg, jpeg, png, or gif
                if ( preg_match( '/\.(jpg|jpeg|png|gif)$/i', $file['name'] ) ) {
                    // Set the maximum upload size to 1MB for these file types
                    $size = 1024 * 1000; // 1MB in bytes
                    break;
                }
            }
        }
    }

    return $size;
}
add_filter( 'upload_size_limit', 'brro_restrict_upload_size', 20 );
//
// ******************************************************************************************************************************************************************
//  
// Custom error message for images larger than 1MB
function brro_custom_upload_size_error( $file ) {
    // Maximum file size in bytes (1MB)
    $max_file_size = 1024 * 1000;
    // Allowed file types
    $allowed_file_types = array('jpg', 'jpeg', 'png', 'gif');
    // Get the current user's data
    $current_user = wp_get_current_user();
    // Check if the current user is an editor
    if ( in_array( 'editor', (array) $current_user->roles ) ) {
        // Check file type and size
        if ( (in_array( strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), $allowed_file_types )) && $file['size'] > $max_file_size ) {
            // Set custom error
            $file['error'] = 'Afbeelding mag maximaal 1MB groot zijn. Verklein de afbeelding tot maximaal 1600px breed via www.imageresizer.com, en/of comprimeer de afbeelding via www.tinyjpg.com';
        }
    }
    return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'brro_custom_upload_size_error' );