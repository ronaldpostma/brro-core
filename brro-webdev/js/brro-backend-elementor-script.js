jQuery(function($) {
    //
    // 0. Basic setup 
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
        .tooltip-calc,
        .tooltip-var {
            position: absolute;
            bottom: calc(100% + 14px);
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--e-a-bg-default);
            box-shadow:var(--e-a-popover-shadow);
            padding: 10px;
            border-radius: var(--e-a-border-radius);
            visibility: hidden;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 10001;
            width: 300px;
            font-size: 12px
        }
        .convert-button:hover .tooltip-calc,
        .var-button:hover .tooltip-var {
            visibility:visible;
            opacity:1;
        }
        .convert-button, .var-button {border-bottom:1px solid;border-color:transparent;}
        .convert-button:hover, .var-button:hover {border-color:inherit!important;}
        #input-repeater {
            width: auto;
            min-width: 50px;
            position: fixed;
            top: 3px;
            left: 210px;
            font-size: 15px;
            padding: 6px 24px;
            line-height: 27px;
            background-color: rgb(12, 13, 14);
            z-index: 9999;
        }
        @media (min-width:1921px) {
            body.e-is-device-mode:not(.elementor-device-desktop) #elementor-preview-responsive-wrapper {
                max-width: calc(var(--e-editor-preview-width) - .5px) !important;
            }
        }
    `).appendTo("head");
    // Append a style to hold temporary calculations in the head
    setTimeout(function() {
        $('#elementor-preview-iframe').contents().find('head').append('<style id="brro-variables-css-preview"></style>');
    }, 1000);
    //
    // 1. Print console log and Elementor window message when value has changed:
    //
    $('#elementor-panel').on('change', 'input[type="text"]', function() {
        var newValue = $(this).val(); // Get the latest input value
        console.log('Input value:', newValue);
        $('#input-repeater').empty().text(newValue);
    });
    // Print Elementor window message when value is double clicked
    $('#elementor-panel').on('dblclick', 'input[type="text"]', function() {
        var repeatValue = $(this).val(); // Get the current input value
        $('#input-repeater').empty().text(repeatValue);
    })
    //
    // 2. Create a 'Convert input' button on click in the input
    //
    $('#elementor-panel').on('click', 'input[type="text"]', function() {
        $('.convert-button').remove();        
        // Add input repeater
        if ($('#input-repeater').length === 0) {
            $('body').prepend('<div id="input-repeater"></div>');
        }
        // Add "Convert input" button
        $(this).closest('.elementor-control-content').find('.e-units-wrapper').first().before('<div class="convert-button" style="cursor: pointer;" data-input-id="' + $(this).attr('id') + '">Convert input</div>');
    });
    //
    // 3. Remove the button when clicking outside the input field
    $('#elementor-panel').on('click', function(event) {
        if (!$(event.target).closest('input[type="text"]').length) {
            $('.convert-button').remove();
        }
    });
    //
    // 3. Calculation of new values after 'Calc' button click
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
        // convert-button: Check if inputValue is a two-part string, separated by a comma
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
        // 3.2 Set correct reference values based on device mode
        if ( $('body').hasClass('elementor-device-desktop') ) {
            var screenEnd = desktopEnd;
            var screenRef = desktopRef;
            var screenStart = desktopStart;
            var screenVar = 'desktop';
        } else if ( $('body').hasClass('elementor-device-tablet') ) {
            var screenEnd = tabletEnd;
            var screenRef = tabletRef;
            var screenStart = tabletStart;
            var screenVar = 'tablet';
        } else if ( $('body').hasClass('elementor-device-mobile') ) {
            var screenEnd = mobileEnd;
            var screenRef = mobileRef;
            var screenStart = mobileStart;
            var screenVar = 'mobile';
        } else {
            console.log('Error: No screensize reference values. Script terminates.');
            return;
        }
        //
        // 3.3 Trigger a focus on the element
        $input.trigger('focus');
        // 
        // 3.4 SINGLE INPUT: Check if the input is a singular numeric string, and not empty
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
        // 3.5 DOUBLE INPUT: Check if it is desktop only, for double input from mobile to desktop scaling calculation
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
        // 3.6 Error message fallback
        } else {
            // Nothing calculated. Check for errors
            $input.val('Nothing calculated. Check for errors').trigger('keydown').trigger('keyup').trigger('input').trigger('change');
        }
        // 3.7 Remove 'convert-button' to prevent duplicates
        $(this).remove();
        //
        //
        // 3.8 Add temporary var() css definitions into the <head><style id="brro-variables-css-preview"></style><head> 
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