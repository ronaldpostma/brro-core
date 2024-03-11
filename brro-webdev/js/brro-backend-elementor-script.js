jQuery(function($) {
    //
    //
    // 0. Basic setup
    // 
    // Access settings from PHP
    var desktopEnd = pluginSettings.desktopEnd;
    var desktopRef = pluginSettings.desktopRef;
    var desktopStart = pluginSettings.desktopStart;
    var tabletEnd = desktopStart - 1;
    var tabletRef = pluginSettings.tabletRef;
    var tabletStart = pluginSettings.tabletStart;
    var mobileEnd = tabletStart - 1;
    var mobileRef = pluginSettings.mobileRef;
    var mobileStart = pluginSettings.mobileStart; 
    var developerMode = pluginSettings.developerMode; 
    var convertClampVar = pluginSettings.convertClampVar; 
    console.log('developerMode: ' + developerMode + ' ( 1 = on) ( 0 = off )');
    console.log('convertClampVar: ' + convertClampVar + ' ( 1 = var(), 0 = clamp() )');
    console.log('desktopEnd: ' + desktopEnd + 'px');
    console.log('desktopRef: ' + desktopRef + 'px');
    console.log('desktopStart: ' + desktopStart + 'px'); 
    console.log('tabletEnd: ' + tabletEnd + 'px'); 
    console.log('tabletRef: ' + tabletRef + 'px'); 
    console.log('tabletStart: ' + tabletStart + 'px');
    console.log('mobileEnd: ' + mobileEnd + 'px'); 
    console.log('mobileRef: ' + mobileRef + 'px');
    console.log('mobileStart :' + mobileStart + 'px');
    // CSS for button and tooltip
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
        .calc-button:hover .tooltip-calc,
        .var-button:hover .tooltip-var {
            visibility:visible;
            opacity:1;
        }
        .calc-button, .var-button {border-bottom:1px solid;border-color:transparent;}
        .calc-button:hover, .var-button:hover {border-color:inherit!important;}
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
    `)
    .appendTo("head");
    setTimeout(function() {
        $('#elementor-preview-iframe').contents().find('head').append('<style id="brro-edt-editor-preview"></style>');
    }, 1000);
    //
    //
    // 1. Print console log message when value has changed:
    //
    $('#elementor-panel').on('change', 'input[type="text"]', function() {
        var newValue = $(this).val(); // Get the new input value
        console.log('Input value:', newValue);
        $('#input-repeater').empty().text('Last input: ' + newValue);
    });
    //
    //
    // 2. Create a 'Calc' button on click in the input
    //
    //
    $('#elementor-panel').on('click', 'input[type="text"]', function() {
        $('.calc-button').remove();
        $('.var-button').remove();
        
        // Add input repeater
        if ($('#input-repeater').length === 0) {
            $('body').prepend('<div id="input-repeater"></div>');
        }
        //
        //
        // 2.1 Add buttons for desktop
        //
        if ( $('body').hasClass('elementor-device-desktop') ) {
            // var() buttons
            if ( convertClampVar == 1 ) {
                // var() button
                $(this).closest('.elementor-control-content').find('.e-units-wrapper').first().before('<div class="var-button" style="cursor: pointer;" data-input-id="' + $(this).attr('id') + '">Convert to var(--)<span class="tooltip-var">Single input: var(--input-single--XXXpx--desktop)<br><br><b>from</b> ref value @ desktopRef(' + desktopRef + 'px)<br><br>input example: 650<br><br>Double input: var(--input-double--mobileref-XXpx--desktopref-XXXpx)<br><br><b>from</b> min value @ mobileRef(' + mobileRef + 'px)<br><b>to</b> max value @ desktopRef(' + desktopRef + 'px)<br><br>input example: 43,80</span></div>');
            }
            // clamp() buttons
            if ( convertClampVar == 0 ) {
                $(this).closest('.elementor-control-content').find('.e-units-wrapper').first().before('<div class="calc-button" style="cursor: pointer;" data-input-id="' + $(this).attr('id') + '">Convert to clamp()<span class="tooltip-calc">Single input: clamp(desktopStart' + desktopStart + ', vw, desktopEnd' + desktopEnd + ')<br><br><b>from</b> ref value @ desktopRef(' + desktopRef + 'px)<br><br>input example: 650<br><br>Double input: clamp(mobileStart' + mobileStart + ', scale, desktopEnd' + desktopEnd + ')<br><br><b>from</b> min value @ mobileRef(' + mobileRef + 'px)<br><b>to</b> max value @ desktopRef(' + desktopRef + 'px)<br><br>input example: 43,80</span></div>');
            }
        //
        // 2.2 Add buttons for tablet
        //
        } else if ( $('body').hasClass('elementor-device-tablet') ) {
            // var() buttons
            if ( convertClampVar == 1 ) {
                $(this).closest('.elementor-control-content').find('.e-units-wrapper').first().before('<div class="var-button" style="cursor: pointer;" data-input-id="' + $(this).attr('id') + '">Convert to var(--)<span class="tooltip-var">var(--input-single--XXXpx--tablet)<br><br><b>from</b> ref value @ tabletRef(' + tabletRef + 'px)<br><br>input example: 520</span></div>');
            }
            // clamp() buttons
            if ( convertClampVar == 0 ) {
                $(this).closest('.elementor-control-content').find('.e-units-wrapper').first().before('<div class="calc-button" style="cursor: pointer;" data-input-id="' + $(this).attr('id') + '">Convert to clamp()<span class="tooltip-calc">clamp(tabletStart' + tabletStart + ', vw, tabletEnd' + tabletEnd + ')<br><br><b>from</b> ref value @ tabletRef(' + tabletRef + 'px)<br><br>input example: 520</span></div>');
            }               
        //
        // 2.2 Add clamp() button for mobile
        //
        } else if ( $('body').hasClass('elementor-device-mobile') ) {
            // var() buttons
            if ( convertClampVar == 1 ) {
                $(this).closest('.elementor-control-content').find('.e-units-wrapper').first().before('<div class="var-button" style="cursor: pointer;" data-input-id="' + $(this).attr('id') + '">Convert to var(--)<span class="tooltip-var">var(--input-single--XXXpx--mobile)<br><br><b>from</b> ref value @ mobileRef(' + mobileRef + 'px)<br><br>input example: 290</span></div>');
            }
            // clamp() buttons
            if ( convertClampVar == 0 ) {
                $(this).closest('.elementor-control-content').find('.e-units-wrapper').first().before('<div class="calc-button" style="cursor: pointer;" data-input-id="' + $(this).attr('id') + '">Convert to clamp()<span class="tooltip-calc">clamp(mobileStart' + mobileStart + ', vw, mobileEnd' + mobileEnd + ')<br><br><b>from</b> ref value @ mobileRef(' + mobileRef + 'px)<br><br>input example: 290</span></div>');
            }
        // 2.2 Fallback error message
        } else {
            console.log('Error: No reference values, no button made');
        }
    });
    $('#elementor-panel').on('click', function(event) {
        if (!$(event.target).closest('input[type="text"]').length) {
            $('.calc-button').remove();
            $('.var-button').remove();
            //$('#input-repeater').remove();
        }
    });
    //
    //
    // 3. Calculation of new values after 'Calc' button click
    //
    $('#elementor-panel').on('click', '.calc-button,.var-button', function() {
        //
        // 3.1 Get input value for manual input
        var inputId = $(this).data('input-id');
        var $input = $('#' + inputId);
        var inputValue = $input.val();
        var outputMin;
        var outputMax;
        var inputSingle;
        var mdTrue;
        // CALC-BUTTON: Check if inputValue is a continuous string (no spaces, no commas, etc.)
        if ($.isNumeric(inputValue) && /^\d+$/.test(inputValue)) { //.................................IF SINGLE INPUT
            var inputSingle = inputValue;
        // CALC-BUTTON: Check if inputValue is a two-part string, separated by a comma
        } else if (/^\d+,\d+$/.test(inputValue.trim())) { //..........................................IF DOUBLE INPUT
            var parts = inputValue.trim().split(',').map(Number);
            var inputMin = Math.min.apply(null, parts);
            var inputMax = Math.max.apply(null, parts);
        } else if (/^md,\d+,\d+$/.test(inputValue.trim())) { //...............md,##,## scale from mobile to desktop
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
            var varComment = '/* min:' + outputMin + 'px */';
        } else if ( $('body').hasClass('elementor-device-tablet') ) {
            var screenEnd = tabletEnd;
            var screenRef = tabletRef;
            var screenStart = tabletStart;
            var screenVar = 'tablet';
            var varComment = '/* min:' + outputMin + 'px max:' + outputMax + 'px */';
        } else if ( $('body').hasClass('elementor-device-mobile') ) {
            var screenEnd = mobileEnd;
            var screenRef = mobileRef;
            var screenStart = mobileStart;
            var screenVar = 'mobile';
            var varComment = '/* min:' + outputMin + 'px max:' + outputMax + 'px */';
        } else {
            console.log('Error: No screensize reference values. Script terminates.');
            return;
        }
        //
        // 3.3 Trigger a focus on the element
        $input.trigger('focus');
        // 
        //
        //
        // 3.4 SINGLE INPUT: Check if the input is a singular numeric string
        if ( inputSingle !== undefined && inputSingle !== '' ) {
            // CALC BUTTON
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
            var clampSingleCSS = 'clamp(' + outputMin + 'px, ' + vwTarget + 'vw, ' + outputMax + 'px) /*input=' + inputSingle + 'px, screenref=' + screenRef + 'px, scale range=' + screenStart + 'px:' + screenEnd + 'px*/';
            // Set new value and trigger events to tell Elementor to update changes
            if ($(this).hasClass('calc-button')) {
                $input.val(clampSingleCSS).trigger('keydown').trigger('keyup').trigger('input').trigger('change');
            }
            // 3.X Comments
            if ( $('body').hasClass('elementor-device-desktop') ) {
                var varComment = '/* min:' + outputMin + 'px */';
            } else if ( $('body').hasClass('elementor-device-tablet') ) {
                var varComment = '/* min:' + outputMin + 'px max:' + outputMax + 'px */';
            } else if ( $('body').hasClass('elementor-device-mobile') ) {
                var varComment = '/* min:' + outputMin + 'px max:' + outputMax + 'px */';
            } else {
                var varComment = '/* error */';
            }
            // VAR BUTTON CSS var() output
            var varSingleCSS = 'var(--single--' + inputSingle + 'px--' + screenVar + '--ref) ' + varComment;
            var RootVarSingleCSS = '--single--' + inputSingle + 'px--' + screenVar + '--ref:';
            // Set new value and trigger events to tell Elementor to update changes
            if ($(this).hasClass('var-button')) {
                $input.val(varSingleCSS).trigger('keydown').trigger('keyup').trigger('input').trigger('change');
            }
        //
        // 3.5 DOUBLE INPUT: Check if it is desktop only, for double input from mobile to desktop scaling calculation
        } else if ( (inputMin !== undefined && inputMax !== undefined) ) {
            // Calculate singular calc() function for all screen sizes, by entering two ',' sep values in any order: 
            // var outputs based on screen
            // Desktop, scale from desktopStart to desktopRef
            if ($('body').hasClass('elementor-device-desktop') && (mdTrue === 'yes') ) {
                var varEnd = 'mobile-ref--desktop-ref';
                var growthRate = (inputMax - inputMin) / (desktopRef - mobileRef);
                var vwTarget = growthRate * 100;
                var baseValue = inputMin - (growthRate * mobileRef);
                var outputMin = baseValue + ((mobileStart/100) * vwTarget);
                var outputMax = baseValue + ((desktopEnd/100) * vwTarget);
            // Desktop, scale from mobile to desktop
            } else if ($('body').hasClass('elementor-device-desktop') && (mdTrue !== 'yes') ) {
                var varEnd = 'desktop-start--desktop-ref';
                var growthRate = (inputMax - inputMin) / (desktopRef - desktopStart);
                var vwTarget = growthRate * 100;
                var baseValue = inputMin - (growthRate * desktopStart);
                var outputMin = baseValue + ((desktopStart/100) * vwTarget);
                var outputMax = baseValue + ((desktopEnd/100) * vwTarget);
            } else if ($('body').hasClass('elementor-device-tablet')) {
                var varEnd = 'tablet--start--end';
                var growthRate = (inputMax - inputMin) / (tabletEnd - tabletStart);
                var vwTarget = growthRate * 100;
                var baseValue = inputMin - (growthRate * tabletStart);
                var outputMin = baseValue + ((tabletStart/100) * vwTarget);
                var outputMax = baseValue + ((tabletEnd/100) * vwTarget);
            } else if ($('body').hasClass('elementor-device-mobile')) {
                var varEnd = 'mobile--ref--end';
                var growthRate = (inputMax - inputMin) / (mobileEnd - mobileRef);
                var vwTarget = growthRate * 100;
                var baseValue = inputMin - (growthRate * mobileRef);
                var outputMin = baseValue + ((mobileRef/100) * vwTarget);
                var outputMax = baseValue + ((mobileEnd/100) * vwTarget);
            } else {
                var varEnd = 'ERROR';
                var growthRate;
                var vwTarget;
                var baseValue;
                var outputMin;
                var outputMax;
            }
            // Additional for if @media query is used, when $desktop_ref < or > than $desktop_end
            var vwTargetQuery = (inputMax / desktopRef) * 100;
            var outputMinQuery = (desktopRef / 100) * vwTargetQuery;
            var outputMaxQuery = (desktopEnd / 100) * vwTargetQuery;
            // Round to 2 decimal places only if the number is not a whole number
            growthRate = (growthRate % 1) ? growthRate.toFixed(2) : growthRate;
            vwTarget = (vwTarget % 1) ? vwTarget.toFixed(2) : vwTarget;
            baseValue = (baseValue % 1) ? baseValue.toFixed(2) : baseValue;
            outputMin = Math.round(outputMin).toString();
            outputMax = Math.round(outputMax).toString();
            vwTargetQuery = (vwTargetQuery % 1) ? vwTargetQuery.toFixed(2) : vwTargetQuery;
            outputMinQuery = Math.round(outputMinQuery).toString();
            outputMaxQuery = Math.round(outputMaxQuery).toString();

            var clampMinMaxCSSMediaQuery = 'clamp(' + outputMinQuery + 'px, ' + vwTargetQuery + 'vw, ' + outputMaxQuery + 'px)';
            // 
            // CALC BUTTON
            // CSS min() output
            if (baseValue < 0) {
                baseValue = Math.abs(baseValue);
                var clampMinMaxCSS = 'clamp('+ outputMin + 'px, calc(' + vwTarget + 'vw - ' + baseValue + 'px), ' + outputMax + 'px) /*ref=' + inputMin + 'px@' + mobileRef + 'px:' + inputMax + 'px@' + desktopRef + 'px, scale range=' + mobileStart + 'px:' + desktopEnd + 'px*/';
            } else {
                var clampMinMaxCSS = 'clamp('+ outputMin + 'px, calc(' + baseValue + 'px + ' + vwTarget + 'vw), ' + outputMax + 'px) /*ref=' + inputMin + 'px@' + mobileRef + 'px:' + inputMax + 'px@' + desktopRef + 'px, scale range=' + mobileStart + 'px:' + desktopEnd + 'px*/';
            }
            if ($(this).hasClass('calc-button')) {
                // Set new value and trigger events to tell Elementor to update changes
                $input.val(clampMinMaxCSS).trigger('keydown').trigger('keyup').trigger('input').trigger('change');
            }
            // VAR BUTTON 
            var varDoubleCSS = 'var(--range--' + inputMin + 'px--' + inputMax + 'px--' + varEnd + ')';
            var RootVarDoubleCSS = '--range--' + inputMin + 'px--' + inputMax + 'px--' + varEnd + ':';
            if ($(this).hasClass('var-button')) {
                // Set new value and trigger events to tell Elementor to update changes
                $input.val(varDoubleCSS).trigger('keydown').trigger('keyup').trigger('input').trigger('change');
            }
        // 3.6 Error message fallback
        } else {
            // Nothing calculated. Check for errors
            $input.val('Nothing calculated. Check for errors').trigger('keydown').trigger('keyup').trigger('input').trigger('change');
        }
        // 3.7 Remove 'calc-button' to prevent duplicates
        $(this).remove();
        // 3.8 Add temporary var() css definitions into the <head><style id="brro-edt-editor-preview"></style><head> 
        if ( convertClampVar == 1 ) {
            var newCssContent = '';
            // For Single input var
            if ( inputSingle !== undefined && inputSingle !== '' ) {
                newCssContent = ':root {' + RootVarSingleCSS + clampSingleCSS + '; }';
            // For Double input var
            } else if ( (inputMin !== undefined && inputMax !== undefined) ) {
                newCssContent = ':root {' + RootVarDoubleCSS + clampMinMaxCSS + '; }';
                if (desktopRef < desktopEnd) {
                    var newCssContentMediaQuery = '@media (min-width:' + desktopRef + 'px) {:root {' + RootVarDoubleCSS + clampMinMaxCSSMediaQuery + '; }}';
                }   
                newCssContent = newCssContent + newCssContentMediaQuery;
            }
            $('#elementor-preview-iframe').contents().find('#brro-edt-editor-preview').append(newCssContent);
        }
    });
});