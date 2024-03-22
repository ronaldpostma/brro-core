<?php
// Trigger add unique var CSS to brro-vars-css on page load
add_action('wp_head', 'brro_generate_inline_css');
function brro_generate_inline_css() {
    $developer_mode = get_option('brro_developer_mode', 0);
    if ($developer_mode == 0) {
        return;
    }
    error_log('Page load: Generating CSS.');
    // Append the CSS
    $content = brro_elementor_devtools_read_and_generate_css('brro_vars_elementor_extract_all');
    error_log('CSS regeneration brro_elementor_devtools_read_and_generate_css() triggered');
    if (!empty($content)) {
        echo '<style id="brro_vars_css">' . $content . '</style>';
        error_log('CSS added inline.');
    } else {
        // Optionally, log a message or handle the case where no content is generated
        error_log('No CSS content generated.');
    }
}
//
// get all single-input and range-input variables found in the brro css folder
function brro_vars_brro_extract_all() {
    $brro_css_dir = WP_PLUGIN_DIR . '/brro-production/css/';
    return brro_extract_css_variables($brro_css_dir);
}
//
// get all single-input and range-input variables found in the brro css folder
function brro_vars_elementor_extract_all() {
    $elementor_css_dir = WP_CONTENT_DIR . '/uploads/elementor/css/';
    return brro_extract_css_variables($elementor_css_dir);
}
//
// Extract all single-input and range-input variables from css files
function brro_extract_css_variables($css_dir) {
    $css_files = glob($css_dir . '*.css'); // Get all .css files in the directory
    $extracted_vars = [
        'range_input' => []
    ];
    foreach ($css_files as $file) {
        $css_content = file_get_contents($file);
        $filename = basename($file);

        // Extract range input variables
        preg_match_all('/var\(--range--(\d+)px--(\d+)px--(mobile-ref--desktop-ref|desktop-start--desktop-ref)\)/', $css_content, $range_matches);
        if (!empty($range_matches[1])) {
            $extracted_vars['range_input'][$filename] = $range_matches;
        }
    }
    return $extracted_vars;
}
// Generate the CSS based on the given single-input and range-input variables
function brro_elementor_devtools_read_and_generate_css($extraction_function) {
    // Get source CSS variables and setup empty CSS
    $extracted_vars = call_user_func($extraction_function);
    // Check if $extracted_vars contains any data
    if (empty($extracted_vars['range_input'])) {
        error_log('No CSS variables found to process.');
        return; // Exit the function if there's nothing to process
    }
    $custom_range_input_css = ''; // Initialize empty variables to accumulate CSS content
    $custom_range_input_mediaquery_css = ''; 
    // Options
    $minimizeCss = get_option('brro_minimize_css', 0); // Check if CSS should be minimized
    // Predefined screen variables 
    $desktop_end = (int)get_option('brro_desktop_end', 0);
    $desktop_ref = (int)get_option('brro_desktop_ref', 0);
    $desktop_start = (int)get_option('brro_desktop_start', 0);
    $mobile_ref = (int)get_option('brro_mobile_ref', 0);
    $mobile_start = (int)get_option('brro_mobile_start', 0);
    //
    //******************************************************************** Process RANGE input variables
    foreach ($extracted_vars['range_input'] as $filename => $matches) {
        foreach ($matches[1] as $index => $inputMin) {
            $inputMax = $matches[2][$index];
            $screenVar = $matches[3][$index];
            // Calculate and generate CSS based on the screen variable
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
                $outputMax = $baseValue + (($desktop_ref/100) * $vwTarget);
            } else if ($screenVar == 'desktop-start--desktop-ref'){
                $growthRate = ($inputMax - $inputMin) / ($desktop_ref - $desktop_start);
                $vwTarget = $growthRate * 100;
                $baseValue = $inputMin - ($growthRate * $desktop_start);
                $outputMin = $baseValue + (($desktop_start/100) * $vwTarget);
                $outputMax = $baseValue + (($desktop_ref/100) * $vwTarget);
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
        }
    }
    //
    // Comments with found filenames
    // Range input comments
    $range_input_files_comment = "/* Range input var CSS Files:\n" . brro_css_file_comments(array_keys($extracted_vars['range_input'])) . "*/\n";
    //
    // Combine comments and CSS rules within a :root block
    $custom_range_input_css = $range_input_files_comment . ":root {\n" . $custom_range_input_css . "}";

    if ($desktop_end > $desktop_ref) { 
        $custom_range_input_mediaquery_css = "\n@media (min-width:" . ($desktop_ref + 1) . "px) {\n:root {\n" . $custom_range_input_mediaquery_css . "}\n}";
    }
    // Compile and remove duplicates for 'brro-vars.css'
    $writeto_file_brro_vars_css = $custom_range_input_css . $custom_range_input_mediaquery_css . "\n/*End*/";
    $writeto_file_brro_vars_css = brro_remove_duplicate_css($writeto_file_brro_vars_css);
    //
    // Minify the output, optionally
    if ($minimizeCss == 1) {
        $writeto_file_brro_vars_css = brro_minimize_css($writeto_file_brro_vars_css);
    }
    // output of final CSS
    return $writeto_file_brro_vars_css;
}
//
// Helper functions to create the CSS files
//
// Create the comments
function brro_css_file_comments($filesUsed) {
    $commentBlock = '';
    foreach ($filesUsed as $file) {
        preg_match('/post-(\d+)/', $file, $matches);
        $postId = isset($matches[1]) ? $matches[1] : '';
        $postSlug = $postId ? get_post_field('post_name', $postId) : 'n/a';
        $commentBlock .= "{$file} {$postSlug}\n";
    }
    return $commentBlock;
}
//
// Remove duplicate lines
function brro_remove_duplicate_css($cssContent) {
    $cssLines = explode("\n", $cssContent);
    $filteredCssLines = [];
    $inputLinesSeen = [];
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
    return implode("\n", $filteredCssLines);
}
//
// Minimize the output if set to
function brro_minimize_css($css) {
    $css = preg_replace('/\s+/', ' ', $css); // Replace whitespace (spaces, tabs, line breaks) with a single space
    $css = preg_replace('/\s*:\s*/', ':', $css); // Remove spaces around colons in CSS declarations
    $css = preg_replace('/\s*{\s*/', '{', $css); // Remove spaces around opening curly braces in CSS rules
    $css = preg_replace('/\s*}\s*/', '}', $css); // Remove spaces around closing curly braces in CSS rules
    $css = preg_replace('/\s*,\s*/', ',', $css); // Remove spaces around commas in CSS selectors
    $css = preg_replace('/\s*;\s*/', ';', $css); // Remove spaces around semicolons in CSS declarations
    $css = preg_replace('/\/\*.*?\*\//s', '', $css); // Remove CSS comments (including multiline comments)
    return $css;
}
//
// Overwrite to the file in the production plugin
function brro_write_css_to_files($filePath, $content) {
    // Check if content is not empty
    if (!empty($content)) {   
        // Attempt to open the file for writing
        if ($file = fopen($filePath, 'w')) {
            fwrite($file, $content); 
            fclose($file);
            update_option('brro_frontend_var_css_version', time());
        } else {
            // Log error if the file cannot be opened or written to
            error_log("Error: Unable to open or write to {$filePath}.");
        }
    }
}
//
// Append to the file in the production plugin
function brro_add_css_to_files($filePath, $content) {
    // Check if content is not empty
    if (!empty($content)) {   
        // Open the file for appending. If the file doesn't exist, attempt to create it.
        if ($file = fopen($filePath, 'a')) {
            // Optionally, add a newline before appending the new content
            fwrite($file, PHP_EOL . $content); 
            fclose($file);
            update_option('brro_frontend_var_css_version', time());
        } else {
            // Log error if the file cannot be opened or written to
            error_log("Error: Unable to open or write to {$filePath}.");
        }
    }
}