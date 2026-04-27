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
    if ( desktopRef !== desktopEnd) {
        console.log('desktopRef and desktopEnd are not equal. Can not use range calc for desktop');
    }
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
    // Append a style tag for css in the head for preview width panel
    setTimeout(function() {
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
    $(document).on('click', '.MuiAppBar-root button:not([aria-label="Desktop"]), #elementor-panel .elementor-responsive-switcher:not(.elementor-responsive-switcher-desktop)', function() {
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
    // Print to input repeater when value is double clicked twice in 800ms
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

    function brro_getElementorDevice() {
        if ($('body').hasClass('elementor-device-desktop')) {
            return 'desktop';
        }

        if ($('body').hasClass('elementor-device-tablet')) {
            return 'tablet';
        }

        if ($('body').hasClass('elementor-device-mobile')) {
            return 'mobile';
        }

        return '';
    }

    function brro_triggerElementorInput($input, value) {
        $input.val(value).trigger('keydown').trigger('keyup').trigger('input').trigger('change');
    }
    //
    // Calculation of new values after 'Convert' button click
    //
    $('#elementor-panel').on('click', '.convert-button', function() {
        var $button = $(this);
        var inputId = $button.data('input-id');
        var $input = $('#' + inputId);
        var inputValue = $input.val();

        function brro_removeConvertButton() {
            if (!$button.hasClass('calcref')) {
                $button.remove();
            }
        }

        if (!window.brroCssCalculator) {
            console.log('Error: Calculator library could not be loaded. Script terminates.');
            brro_removeConvertButton();
            return;
        }

        var device = brro_getElementorDevice();

        if (!device) {
            brro_triggerElementorInput($input, 'No device mode class detected in preview panel');
            brro_removeConvertButton();
            return;
        }

        $input.trigger('focus');

        var result = window.brroCssCalculator.calculateForDevice(inputValue, device, pluginSettings);

        if (!result.valid) {
            console.log('Error: ' + result.error);
            brro_removeConvertButton();
            return;
        }

        if (!result.css) {
            console.log('No CSS output for this input on the active device.');
            brro_removeConvertButton();
            return;
        }

        brro_triggerElementorInput($input, result.css);

        // Remove 'convert-button' to prevent duplicates
        brro_removeConvertButton();
    });
});