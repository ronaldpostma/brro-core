jQuery(function($) {
    //
    // Basic setup 
    // Access settings from PHP
    var desktopEnd = Number(pluginSettings.desktopEnd);
    var desktopRef = Number(pluginSettings.desktopRef);
    var desktopStart = Number(pluginSettings.desktopStart);
    var tabletEnd = desktopStart - 1;
    var tabletRef = Number(pluginSettings.tabletRef);
    var tabletStart = Number(pluginSettings.tabletStart);
    var mobileEnd = tabletStart - 1;
    var mobileRef = Number(pluginSettings.mobileRef);
    var mobileStart = Number(pluginSettings.mobileStart); 
    var developerMode = pluginSettings.developerMode; 
    console.log('developerMode: ' + developerMode + ' ( 1 = on) ( 0 = off )');
    console.log('desktopEnd: ' + desktopEnd + 'px');
    console.log('desktopRef: ' + desktopRef + 'px');
    console.log('desktopStart: ' + desktopStart + 'px'); 
    console.log('tabletEnd: ' + tabletEnd + 'px'); 
    console.log('tabletRef: ' + tabletRef + 'px'); 
    console.log('tabletStart: ' + tabletStart + 'px');
    console.log('mobileEnd: ' + mobileEnd + 'px'); 
    console.log('mobileRef: ' + mobileRef + 'px');
    console.log('mobileStart :' + mobileStart + 'px');
    //
    // CSS for button, tooltip and input repeater
    $("<style>")
    .prop("type", "text/css")
    .html(`
        .convert-button {border-bottom:1px solid;border-color:transparent;}
        .convert-button:hover {border-color:inherit!important;}
        #input-repeater {padding: 12px 40px 12px 12px;}
        #input-repeater .convert-button.calcref {position:absolute;top:18px;right:8px;}
        @media (min-width:1921px) {
            body.e-is-device-mode.elementor-device-tablet #elementor-preview-responsive-wrapper {
                min-width: ${tabletStart}px!important;
                max-width: calc(${tabletEnd}px - .34px)!important;
            }
            body.e-is-device-mode.elementor-device-mobile #elementor-preview-responsive-wrapper {
                min-width: ${mobileStart}px!important;
                max-width: calc(${mobileEnd}px - .34px)!important;
            }
        }
        .elementor-device-desktop #elementor-preview-responsive-wrapper {min-width: ${desktopStart}px!important;margin:auto;width:var(--brro-desktop--preview--width)!important;}
    `).appendTo("head");
    //
    // Append a style tag to hold temporary calculations in the head
    setTimeout(function() {
        // Style inside the preview builder
        $('#elementor-preview-iframe').contents().find('head').append('<style id="brro-variables-css-preview"></style><style id="brro-desktop-preview-width">root:{--brro-desktop--preview--width:100%;}</style>');
        // Style for the editor
        $('head').append('<style type="text/css" id="brro-desktop-preview-width">.elementor-device-desktop #elementor-preview-responsive-wrapper {--brro-desktop--preview--width:100%;}</style>');
    }, 1000);
    //
    // Add input repeater with convert button
    var waitforHeader = setInterval(function() {
        if ($('#elementor-panel-header-wrapper').length) {
            if ($('#input-repeater').length === 0) {
                $('#elementor-panel-header-wrapper').prepend('<div id="input-repeater"><input id="calcref" type="text"><div class="convert-button calcref" style="cursor: pointer;" data-input-id="calcref">Conv</div></div>');
            }
        clearInterval(waitforHeader);
        }
    }, 100);
    //
    // Prepend input for preview width Desktop
    $(document).on('click', '.MuiAppBar-root button[aria-label="Desktop"], #elementor-panel .elementor-responsive-switcher-desktop', function() {
        $('#brro-desktop-preview').remove(); // Remove existing input if any
        $('.MuiAppBar-root button[aria-label="Desktop"]').after(`<input type="number" min="${desktopStart}" id="brro-desktop-preview" />`); // Prepend new input
    });
    //
    // Removing input when switching away from "Desktop" mode
    $(document).on('click', '.MuiAppBar-root div[aria-label="Switch Device"] button:not([aria-label="Desktop"]), #elementor-panel .elementor-responsive-switcher:not(.elementor-responsive-switcher-desktop)', function() {
        $('#brro-desktop-preview').remove(); // Remove the input
    });
    //
    // Change preview width for Desktop editor
    $('.MuiAppBar-root').on('change', 'input#brro-desktop-preview', function() {
        var newValue = $(this).val(); // Get the latest input value
        if ( newValue >= desktopStart) {
            $('#brro-desktop-preview-width').empty().text(`.elementor-device-desktop #elementor-preview-responsive-wrapper {--brro-desktop--preview--width:${newValue}px;}`);
        } else {
            $('#brro-desktop-preview-width').empty().text('.elementor-device-desktop #elementor-preview-responsive-wrapper {--brro-desktop--preview--width:100%;}');
        }
    });
    //
    // Print to input repeater when value has changed:
    //
    $('#elementor-panel').on('change', 'input[type="text"]', function() {
        var newValue = $(this).val(); // Get the latest input value
        $('#input-repeater input').val(newValue);
    });
    //
    // Print to input repeater when value is double clicked
    var clickCount = 0; // Initialize click count
    var clickTimer = null; // Initialize timer
    $('#elementor-panel').on('click', 'input[type="text"]', function() {
        clickCount++; // Increment click count
        if (clickCount === 1) {
            // Start timer on first click
            clickTimer = setTimeout(function() {
                clickCount = 0; // Reset count after delay
            }, 800); // Delay in milliseconds, adjust as needed
        } else if (clickCount === 4) {
            clearTimeout(clickTimer); // Cancel timer
            clickCount = 0;
            var repeatValue = $(this).val(); // Get the current input value
            $('#input-repeater input').val(repeatValue);
        }
    });
    //
    // Create a 'Convert input' button on click in the input
    //
    $('#elementor-panel').on('click', 'input[type="text"]', function() {
        $('.convert-button:not(.calcref)').remove();
        // Add "Convert input" button
        $(this).closest('.elementor-control-content').find('.e-units-wrapper').first().before('<div class="convert-button" style="cursor: pointer;" data-input-id="' + $(this).attr('id') + '">Convert input</div>');
    });
    //
    // Remove the button when clicking outside the input field
    //
    $('#elementor-panel').on('click', function(event) {
        if (!$(event.target).closest('input[type="text"]').length) {
            $('.convert-button:not(.calcref)').remove();
        }
    });
    //
    // Calculation of new values after 'Convert' button click
    //
    $('#elementor-panel').on('click', '.convert-button', function() {
        //
        // 3.1 Get input value from manual input, to detect single or range input
        var inputId = $(this).data('input-id');
        var $input = $('#' + inputId);
        var inputValue = $input.val();
        var outputMin;
        var outputMax;
        var inputSingle;
        var mdTrue;
        // convert-button: Check if inputValue is a continuous string (no spaces, no commas, etc.)
        if ($.isNumeric(inputValue) && /^\d+$/.test(inputValue)) { //.................................IF SINGLE INPUT
            var inputSingle = inputValue;
        // convert-button: Check if inputValue is a two-part string, separated by a comma
        } else if (/^\d+,\d+$/.test(inputValue.trim())) { //..........................................IF RANGE INPUT
            var parts = inputValue.trim().split(',').map(Number);
            var inputMin = Math.min.apply(null, parts);
            var inputMax = Math.max.apply(null, parts);
        // convert-button: Check if inputValue is a three-part string, separated by a comma
        } else if (/^md,\d+,\d+$/.test(inputValue.trim())) { //.......................................IF RANGE INPUT md,##,## mobile to desktop
            var parts = inputValue.trim().split(',').slice(1).map(Number); // ........Skip the first element ('md')
            var inputMin = Math.min.apply(null, parts);
            var inputMax = Math.max.apply(null, parts);
            mdTrue = 'yes'; 
        } else {
            console.log('Error, wrong input. Script terminates.');
            return;
        }
        //
        // Set correct reference values based on device mode
        //
        if ( $('body').hasClass('elementor-device-desktop') ) {
            var screenEnd = desktopEnd;
            var screenRef = desktopRef;
            var screenStart = desktopStart;
        } else if ( $('body').hasClass('elementor-device-tablet') ) {
            var screenEnd = tabletEnd;
            var screenRef = tabletRef;
            var screenStart = tabletStart;
        } else if ( $('body').hasClass('elementor-device-mobile') ) {
            var screenEnd = mobileEnd;
            var screenRef = mobileRef;
            var screenStart = mobileStart;
        } else {
            console.log('Error: No screensize reference values. Script terminates.');
            return;
        }
        //
        // Trigger a focus on the element
        //
        $input.trigger('focus');
        // 
        // SINGLE INPUT: Check if the input is a singular numeric string, and not empty
        if ( inputSingle !== undefined && inputSingle !== '' ) {
            // 
            // Calculate the scaling target in vw
            var vwTarget = (inputSingle / screenRef) * 100;
            // Calculate the minimum value based on vw and minimum screen size for this device
            var outputMin = (screenStart / 100) * vwTarget;
            var outputMax = (screenEnd / 100) * vwTarget;
            // Round results, vwTarget 2 decimals, outputMin to nearest integer
            vwTarget = (vwTarget % 1) ? vwTarget.toFixed(2) : vwTarget;
            outputMin = Math.round(outputMin).toString(); 
            outputMax = Math.round(outputMax).toString();
            // CSS clamp() output
            var clampSingleCSS = 'clamp(' + outputMin + 'px, ' + vwTarget + 'vw, ' + outputMax + 'px) /*' + inputSingle + 'px @ ' + screenRef + '*/';
            // Set new value and trigger events to tell Elementor to update changes
            $input.val(clampSingleCSS).trigger('keydown').trigger('keyup').trigger('input').trigger('change');
        //
        // DOUBLE INPUT: Check if it is desktop only, for double input from mobile to desktop scaling calculation
        } else if ( (inputMin !== undefined && inputMax !== undefined) ) {
            // Calculate singular calc() function for all screen sizes, by entering two ',' sep values in any order: 
            // var outputs based on screen
            // Desktop, scale from desktopStart to desktopRef
            if ($('body').hasClass('elementor-device-desktop') ) {
                if ((mdTrue === 'yes')) {
                    var varEnd = 'mobile-ref--desktop-ref';
                    var growthRate = (inputMax - inputMin) / (desktopRef - mobileRef);
                    var vwTarget = growthRate * 100;
                    var baseValue = inputMin - (growthRate * mobileRef);
                    var outputMin = baseValue + ((mobileStart/100) * vwTarget);
                    var outputMax = baseValue + ((desktopRef/100) * vwTarget);
                    var cssComment = ' /*' + inputMin + 'px @ ' + mobileRef + ' : ' + inputMax + 'px @ ' + desktopRef + '*/';
                } else if (mdTrue !== 'yes') {
                    var varEnd = 'desktop-start--desktop-ref';
                    var growthRate = (inputMax - inputMin) / (desktopRef - desktopStart);
                    console.log(growthRate);
                    var vwTarget = growthRate * 100;
                    console.log(vwTarget);
                    var baseValue = inputMin - (growthRate * desktopStart);
                    console.log(baseValue);
                    var outputMin = baseValue + ((desktopStart/100) * vwTarget);
                    console.log(outputMin);
                    var outputMax = baseValue + ((desktopRef/100) * vwTarget);
                    console.log(outputMax);
                    var cssComment = ' /*' + inputMin + 'px @ ' + desktopStart + ' : ' + inputMax + 'px @ ' + desktopRef + '*/';
                } 
            } else if ($('body').hasClass('elementor-device-tablet')) {
                var varEnd = 'none';
                var growthRate = (inputMax - inputMin) / (tabletEnd - tabletStart);
                var vwTarget = growthRate * 100;
                var baseValue = inputMin - (growthRate * tabletStart);
                var outputMin = baseValue + ((tabletStart/100) * vwTarget);
                var outputMax = baseValue + ((tabletEnd/100) * vwTarget);
                var cssComment = ' /*' + inputMin + 'px @ ' + tabletStart + ' : ' + inputMax + 'px @ ' + tabletEnd + '*/';
            } else if ($('body').hasClass('elementor-device-mobile')) {
                var varEnd = 'none';
                var growthRate = (inputMax - inputMin) / (mobileEnd - mobileRef);
                var vwTarget = growthRate * 100;
                var baseValue = inputMin - (growthRate * mobileRef);
                var outputMin = baseValue + ((mobileStart/100) * vwTarget);
                var outputMax = baseValue + ((mobileEnd/100) * vwTarget);
                var cssComment = ' /*' + inputMin + 'px @ ' + mobileRef + ' : ' + inputMax + 'px @ ' + mobileEnd + '*/';
            } else {
                var varEnd = 'none';
                var growthRate;
                var vwTarget;
                var baseValue;
                var outputMin;
                var outputMax;
            }
            // Additional for if @media query is used, when $desktop_ref < $desktop_end
            var vwTargetQuery = (inputMax / desktopRef) * 100;
            var outputMaxQuery = (desktopEnd / 100) * vwTargetQuery;
            //
            // Round to 2 decimal places only if the number is not a whole number
            growthRate = (growthRate % 1) ? growthRate.toFixed(2) : growthRate;
            vwTarget = (vwTarget % 1) ? vwTarget.toFixed(2) : vwTarget;
            baseValue = (baseValue % 1) ? baseValue.toFixed(2) : baseValue;
            outputMin = Math.round(outputMin).toString();
            outputMax = Math.round(outputMax).toString();
            vwTargetQuery = (vwTargetQuery % 1) ? vwTargetQuery.toFixed(2) : vwTargetQuery;
            outputMaxQuery = Math.round(outputMaxQuery).toString();
            // 
            // CSS output
            //
            if (baseValue < 0) {
                baseValue = Math.abs(baseValue);
                var clampMinMaxCSS = 'clamp('+ outputMin + 'px, calc(' + vwTarget + 'vw - ' + baseValue + 'px), ' + outputMax + 'px)' + cssComment;
            } else {
                var clampMinMaxCSS = 'clamp('+ outputMin + 'px, calc(' + vwTarget + 'vw + ' + baseValue + 'px), ' + outputMax + 'px)' + cssComment;
            } 
            var tempStyleQueryOutput = 'min(' + vwTargetQuery + 'vw, ' + outputMaxQuery + 'px) /* max @ w' + desktopEnd + ' */';
            // variable if screen end is larger than screen ref
            if ( $('body').hasClass('elementor-device-desktop') && (desktopRef < desktopEnd) ) {
                var cssOutput = 'var(--range--' + inputMin + 'px--' + inputMax + 'px--' + varEnd + ')';
                var rootRangeVarCSS = '--range--' + inputMin + 'px--' + inputMax + 'px--' + varEnd + ':';
            } else {
                var cssOutput = clampMinMaxCSS;
            }
            $input.val(cssOutput).trigger('keydown').trigger('keyup').trigger('input').trigger('change'); 
        // Error message fallback
        } else {
            // Nothing calculated. Check for errors
            $input.val('Nothing calculated. Check for errors').trigger('keydown').trigger('keyup').trigger('input').trigger('change');
        }
        // Remove 'convert-button' to prevent duplicates
        if (!$(this).hasClass('calcref')) {
            $(this).remove();
        }
        //
        //
        // Add temporary var() css definitions into the <head><style id="brro-variables-css-preview"></style><head> 
        var newCssContent = '';
        // For Double input var
        if ( (inputMin !== undefined && inputMax !== undefined) && (desktopRef < desktopEnd) && varEnd !== 'none' ) {
            newCssContent = ':root {' + rootRangeVarCSS + clampMinMaxCSS + '; }';
            if (desktopRef < desktopEnd) {
                var queryMin = desktopRef + 1;
                var newCssContentMediaQuery = '@media (min-width:' + queryMin + 'px) {:root {' + rootRangeVarCSS + tempStyleQueryOutput + '; }}';
            }   
            newCssContent = newCssContent + newCssContentMediaQuery;
        }
        $('#elementor-preview-iframe').contents().find('#brro-variables-css-preview').append(newCssContent);
    });
});