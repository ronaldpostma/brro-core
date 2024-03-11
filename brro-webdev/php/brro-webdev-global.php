<?php
//
// ******************************************************************************************************************************************************************
//  
// Add body classes
add_filter( 'body_class', 'brro_wp_css_body_class' );
function brro_wp_css_body_class( $classes ){
    $user = get_current_user_id();
    $admin = '1';
    $editorone = '2';
    $editortwo = '3';
    if( $user == $editorone || $user == $editortwo ) { 
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