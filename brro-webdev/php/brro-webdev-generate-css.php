<?php
// brro-webdev-generate-css.php
//
// Trigger regen CSS from frontend
add_action('wp_ajax_generate_css', 'brro_handle_generate_css');
function brro_handle_generate_css() {
    // Call your function here
    brro_elementor_devtools_read_and_generate_css();
    // Always die in functions echoing data for AJAX requests
    wp_die();
}
//
// Update CSS File
function brro_elementor_devtools_read_and_generate_css() {
    error_log('CSS regeneration brro_elementor_devtools_read_and_generate_css() triggered');
    // Get source CSS variables and setup empty CSS
    $elementor_css_dir = WP_CONTENT_DIR . '/uploads/elementor/css/'; // Load the folder with Element CSS files    
    $css_files = glob($elementor_css_dir . '*.css'); // Use glob to get a list of all .css files in the directory
    $single_input_files_comment = "/* Single input var CSS Files:\n";
    $range_input_files_comment = "/* Range input var CSS Files:\n";
    $single_input_files_used = [];
    $range_input_files_used = [];
    //$custom_css = ''; // Initialize an empty variable to accumulate CSS content
    $custom_single_input_css = ''; // Initialize an empty variable to accumulate CSS content
    $custom_range_input_css = ''; 
    $custom_frontend_css = '';
    $custom_range_input_mediaquery_css = ''; // Initialize an empty variable to accumulate CSS content
    $minimizeCss = get_option('brro_minimize_css', 0);
    // Set all variables for calculations, empty where needed (fetch from jQuery file)
    $desktop_end = get_option('brro_desktop_end',0); $desktop_end = (int)$desktop_end;
    $desktop_ref = get_option('brro_desktop_ref',0); $desktop_ref = (int)$desktop_ref;
    $desktop_start = get_option('brro_desktop_start',0); $desktop_start = (int)$desktop_start;
    $tablet_end = $desktop_start - 1; $tablet_end = (int)$tablet_end;
    $tablet_ref = get_option('brro_tablet_ref',0); $tablet_ref = (int)$tablet_ref;
    $tablet_start = get_option('brro_tablet_start',0); $tablet_start = (int)$tablet_start;
    $mobile_end = $tablet_start - 1; $mobile_end = (int)$mobile_end;
    $mobile_ref = get_option('brro_mobile_ref',0); $mobile_ref = (int)$mobile_ref;
    $mobile_start = get_option('brro_mobile_start',0); $mobile_start = (int)$mobile_start;
    // 
    // foreach loop through each CSS file and process it
    foreach ($css_files as $file_to_read) {
        if (file_exists($file_to_read)) {
            $css_content = file_get_contents($file_to_read);
            $filename = basename($file_to_read);
            //
            // For Single input: var(--single--[inputSingle]px--[screenVar])
            preg_match_all('/var\(--single--(\d+)px--(desktop|tablet|mobile)--ref\)/', $css_content, $single_input_var_matches);
            if (!empty($single_input_var_matches[1]) && !in_array($filename, $single_input_files_used)) {
                $single_input_files_used[] = $filename; // Track file if matches found
            }
            if (!empty($single_input_var_matches[1])) {
                // Process found data and generate custom CSS for each file
                foreach ($single_input_var_matches[1] as $index => $inputSingle) {
                    $screenVar = $single_input_var_matches[2][$index];
                    //
                    // Set up calculations for CSS output
                    if ($screenVar == 'desktop'){
                        $screenStart = $desktop_start;
                        $screenRef = $desktop_ref;
                        $screenEnd = $desktop_end;
                        $vwTarget = ($inputSingle / $screenRef) * 100;
                        $outputMin = ($screenStart / 100) * $vwTarget;
                        $outputMax = ($screenEnd / 100) * $vwTarget;
                    } else if ($screenVar == 'tablet') {
                        $screenStart = $tablet_start;
                        $screenRef = $tablet_ref;
                        $screenEnd = $tablet_end;
                        $vwTarget = ($inputSingle / $screenRef) * 100;
                        $outputMin = ($screenStart / 100) * $vwTarget;
                        $outputMax = ($screenEnd / 100) * $vwTarget;
                    } else if ($screenVar == 'mobile') {
                        $screenStart = $mobile_start;
                        $screenRef = $mobile_ref;
                        $screenEnd = $mobile_end;
                        $vwTarget = ($inputSingle / $screenRef) * 100;
                        $outputMin = ($screenStart / 100) * $vwTarget;
                        $outputMax = ($screenEnd / 100) * $vwTarget;
                    } else {error_log('Error: cant read screenVar to make vwTarget.');}
                    $vwTarget = number_format($vwTarget, 2, '.', '');
                    $outputMin = number_format($outputMin, 0, '.', '');
                    $outputMax = number_format($outputMax, 0, '.', '');
                    //    
                    // Output of the CSS
                    $custom_single_input_css .= "--single--{$inputSingle}px--{$screenVar}--ref: clamp({$outputMin}px, {$vwTarget}vw, {$outputMax}px); /* screenref={$screenRef}px, scale range={$screenStart}px:{$screenEnd}px */\n";
                }
            }
            //
            // For Range(Min>Max) input: var(--range--mobileref-[inputMin]px--desktopref-[inputMax]px)
            preg_match_all('/var\(--range--(\d+)px--(\d+)px--(mobile-ref--desktop-ref|desktop-start--desktop-ref|tablet--start--end|mobile--ref--end)\)/', $css_content, $range_input_var_matches);
            if (!empty($range_input_var_matches[1]) && !in_array($filename, $range_input_files_used)) {
                $range_input_files_used[] = $filename; // Track file if matches found
            }
            if (!empty($range_input_var_matches[1])) {
                // Process found data and generate custom CSS for each file
                foreach ($range_input_var_matches[1] as $index => $inputMin) {
                    $inputMax = $range_input_var_matches[2][$index];
                    $screenVar = $range_input_var_matches[3][$index];
                    //
                    // Additional for if @media query is used, when $desktop_ref < or > than $desktop_end
                    $vwTargetQuery = ($inputMax / $desktop_ref) * 100;
                    $outputMinQuery = ($desktop_ref / 100) * $vwTargetQuery;
                    $outputMaxQuery = ($desktop_end / 100) * $vwTargetQuery;   
                    //
                    // Set up calculations for CSS output
                    if ($screenVar == 'mobile-ref--desktop-ref'){
                        $growthRate = ($inputMax - $inputMin) / ($desktop_ref - $mobile_ref);
                        $vwTarget = $growthRate * 100;
                        $baseValue = $inputMin - ($growthRate * $mobile_ref);
                        $outputMin = $baseValue + (($mobile_start/100) * $vwTarget);
                        if ( $desktop_end > $desktop_ref ) {
                            $outputMax = $baseValue + (($desktop_ref/100) * $vwTarget);
                        } else {
                            $outputMax = $baseValue + (($desktop_end/100) * $vwTarget);
                        }
                    } else if ($screenVar == 'desktop-start--desktop-ref'){
                        $growthRate = ($inputMax - $inputMin) / ($desktop_ref - $desktop_start);
                        $vwTarget = $growthRate * 100;
                        $baseValue = $inputMin - ($growthRate * $desktop_start);
                        $outputMin = $baseValue + (($desktop_start/100) * $vwTarget);
                        if ( $desktop_end > $desktop_ref ) {
                            $outputMax = $baseValue + (($desktop_ref/100) * $vwTarget);
                        } else {
                            $outputMax = $baseValue + (($desktop_end/100) * $vwTarget);
                        }
                    } else if ($screenVar == 'tablet--start--end') {
                        $growthRate = ($inputMax - $inputMin) / ($tablet_end - $tablet_start);
                        $vwTarget = $growthRate * 100;
                        $baseValue = $inputMin - ($growthRate * $tablet_start);
                        $outputMin = $baseValue + (($tablet_start/100) * $vwTarget);
                        $outputMax = $baseValue + (($tablet_end/100) * $vwTarget);
                    } else if ($screenVar == 'mobile--ref--end') {
                        $growthRate = ($inputMax - $inputMin) / ($mobile_end - $mobile_ref);
                        $vwTarget = $growthRate * 100;
                        $baseValue = $inputMin - ($growthRate * $mobile_ref);
                        $outputMin = $baseValue + (($mobile_start/100) * $vwTarget);
                        $outputMax = $baseValue + (($mobile_end/100) * $vwTarget);
                    } else {error_log('Error: cant read screenVar to make vwTarget.');}
                    //
                    //
                    $growthRate = number_format($growthRate, 2, '.', '');
                    $vwTarget = number_format($vwTarget, 2, '.', '');
                    $baseValue = number_format($baseValue, 2, '.', '');
                    $outputMin = number_format($outputMin, 0, '.', '');
                    $outputMax = number_format($outputMax, 0, '.', '');
                    $vwTargetQuery = number_format($vwTargetQuery, 2, '.', '');
                    $outputMinQuery = number_format($outputMinQuery, 0, '.', '');
                    $outputMaxQuery = number_format($outputMaxQuery, 0, '.', '');
                    //
                    //
                    //
                    //    
                    // Output of the CSS
                    if ($baseValue < 0) {
                        $baseValue = abs($baseValue);
                        $custom_range_input_css .= "--range--{$inputMin}px--{$inputMax}px--{$screenVar}: clamp({$outputMin}px, calc({$vwTarget}vw - {$baseValue}px), {$outputMax}px);\n";
                    } else {
                        $custom_range_input_css .= "--range--{$inputMin}px--{$inputMax}px--{$screenVar}: clamp({$outputMin}px, calc({$vwTarget}vw + {$baseValue}px), {$outputMax}px);\n";
                    }
                    // Additional CSS for media query
                    if ( ($desktop_end > $desktop_ref) && ($screenVar == 'mobile-ref--desktop-ref') || ($screenVar == 'desktop-start--desktop-ref') ) {
                        $custom_range_input_mediaquery_css .= "--range--{$inputMin}px--{$inputMax}px--{$screenVar}: min({$vwTargetQuery}vw, {$outputMaxQuery}px);\n";
                    } 
                    if ( ($desktop_end < $desktop_ref) && ($screenVar == 'mobile-ref--desktop-ref') ) {
                        error_log('Notice: No CSS written for $custom_range_input_css: $desktop_end < $desktop_ref not set up yet.');
                    }
                }
            }
        }
    }
    //
    // Comments with found filenames
    // Single input comments
    foreach ($single_input_files_used as $file) {
        // Extract the post ID from the file name
        preg_match('/post-(\d+)/', $file, $matches);
        $postId = isset($matches[1]) ? $matches[1] : '';
        // Retrieve the post slug using the post ID
        $postSlug = $postId ? get_post_field('post_name', $postId) : 'n/a';
        // Append filename and post slug to comments
        $single_input_files_comment .= $file . " " . $postSlug . "\n";
    }
    $single_input_files_comment .= "*/\n";
    // range input comments
    foreach ($range_input_files_used as $file) {
        // Extract the post ID from the file name
        preg_match('/post-(\d+)/', $file, $matches);
        $postId = isset($matches[1]) ? $matches[1] : '';
        // Retrieve the post slug using the post ID
        $postSlug = $postId ? get_post_field('post_name', $postId) : 'n/a';
        // Append filename and post slug to comments
        $range_input_files_comment .= $file . " " . $postSlug . "\n";
    }
    $range_input_files_comment .= "*/\n";
    //
    // Combine comments and CSS rules within a :root block
    // Single input
    $custom_single_input_css = ":root {\n" . $custom_single_input_css . "}\n";
    $custom_single_input_css = $single_input_files_comment . $custom_single_input_css;
    // range input
    $custom_range_input_css = ":root {\n" . $custom_range_input_css . "}\n";
    if ($desktop_end > $desktop_ref) { 
        $custom_range_input_mediaquery_css = "\n@media (min-width:" . $desktop_ref . "px) {\n:root {\n" . $custom_range_input_mediaquery_css . "}\n}\n";
    }
    $custom_range_input_css = $range_input_files_comment . $custom_range_input_css . $custom_range_input_mediaquery_css;
    //Combine both into total CSS
    $custom_frontend_css = $custom_single_input_css . "\n" . $custom_range_input_css;
    // Remove duplicate entries
    $cssLines = explode("\n", $custom_frontend_css);
    $filteredCssLines = []; // Array to hold the final lines, ensuring uniqueness for specific lines
    $inputLinesSeen = []; // Array to track seen "--input-" lines
    foreach ($cssLines as $line) {
        if (strpos($line, "px--") !== false) {
        // If string has "px--", Check for duplicates as set marker as 'Seen'
            if (!in_array($line, $inputLinesSeen)) {
                $inputLinesSeen[] = $line; // Mark this line as seen
                $filteredCssLines[] = $line; // Add the unique line to the final array
            }
        } else {
            $filteredCssLines[] = $line; // For lines not containing "px--", add them directly to the final array
        }
    }
    // Reassemble the CSS from the filtered lines
    $custom_frontend_css = implode("\n", $filteredCssLines);
    //
    // Minify the output, remove:
    if ($minimizeCss == 1 ) {
        $custom_frontend_css = preg_replace('/\s+/', ' ', $custom_frontend_css); // whitespace (such as spaces, tabs, and line breaks) and replace with a single space.
        $custom_frontend_css = preg_replace('/\s*:\s*/', ':', $custom_frontend_css); // spaces around colons : in CSS declarations.
        $custom_frontend_css = preg_replace('/\s*{\s*/', '{', $custom_frontend_css); // spaces before and after opening curly braces { in CSS rules.
        $custom_frontend_css = preg_replace('/\s*}\s*/', '}', $custom_frontend_css); // spaces before and after closing curly braces } in CSS rules.
        $custom_frontend_css = preg_replace('/\s*,\s*/', ',', $custom_frontend_css); // spaces around commas , in CSS selectors.
        $custom_frontend_css = preg_replace('/\s*;\s*/', ';', $custom_frontend_css); // spaces around semicolons ; in CSS declarations.
        $custom_frontend_css = preg_replace('/\/\*.*?\*\//s', '', $custom_frontend_css); // CSS comments /* text */ (including multiline comments).
    }
    //
    // Define the path for the custom CSS files within your plugin directory
    $css_frontend_file_path = plugin_dir_path(__FILE__) . '../css/brro-frontend-var-style.css';
    //
    // Empty the existing CSS file
    file_put_contents($css_frontend_file_path, ''); // This line clears the file
    //
    // Write to "brro-edt-frontend-elements.css"
    if (!empty($custom_frontend_css)) {   
        // Attempt to open the file for writing
        if ($file = fopen($css_frontend_file_path, 'w')) {
            fwrite($file, $custom_frontend_css); 
            fclose($file);
        } else {
            error_log('Error: Unable to open or write to "brro-edt-frontend-style.css".');
        }
    }
    // After regenerating the CSS, update the version stored in the database
    update_option('brro_frontend_var_css_version', time());
}