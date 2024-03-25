jQuery(function ($) {
    if ($('body').hasClass('webadmin')) {
        console.log('Brro Elementor Devtools Frontend Script Runs');
        // 1. Function to update the viewport width
        function updateDevScreenWidth() {
            $('.viewport-width').remove(); // 1.1 Remove existing viewport width display
            devScreenWidth = $('body').width(); // 1.2 Get the current width of the body
            console.log('Updated devScreenWidth:', devScreenWidth); // 1.3 Log the updated width
            $('.inspector-container').append('<div class="viewport-width">' + devScreenWidth + 'px</div>'); // 1.4 Display the new width
        }
        // 1.1 Initial viewport width setup
        var devScreenWidth = $('body').width(); 
        console.log('Initial devScreenWidth:', devScreenWidth); 
        // 1.2 Attach resize event handler to update width
        $(window).on('resize', updateDevScreenWidth); 
        // 1.3 Observe body for size changes
        var bodyElement = document.querySelector('body');
        if (bodyElement) {
            new ResizeObserver(updateDevScreenWidth).observe(bodyElement); 
        }
        // 2. Additional functionality for 'webadmin' class
        // 2.1 Define toggleable circles with colors
        var toggleCircles = [
            { class: 'inspect-parent inspect-child inspect-child-child inspect-widget', color: 'darkviolet' },
            { class: 'inspect-parent', color: 'red' },
            { class: 'inspect-child', color: 'gold' },
            { class: 'inspect-child-child', color: 'pink' },
            { class: 'inspect-widget', color: 'yellowgreen' },
        ];
        if (!$('body').hasClass('elementor-editor-active')) {
            toggleCircles.push({ class: 'hide-admin-bar', color: 'black' });
        }
        // 2.2 Create container for inspector buttons
        var circleContainer = $('<div class="inspector-container"></div>');
        var circleElements = [];
        $.each(toggleCircles, function (index, circleData) {
            var circleElement = $('<div class="inspector-button"></div>').css('background-color', circleData.color);
            circleElements.push(circleElement);
        });
        circleContainer.append(circleElements);
        $('body').append(circleContainer);
        $('.inspector-container').append('<div class="viewport-width" >' + devScreenWidth + 'px</div>');
        // Activate inspector state in Elementor editor by default
        $('body.elementor-editor-active').addClass('inspect-parent inspect-child inspect-child-child inspect-widget');
        $('body.elementor-editor-active .inspector-button').addClass('inspector-active');
        $('html:not(.hide-admin-bar) header.brro-sticky').css('top','32px');
        $('html.hide-admin-bar header.brro-sticky').css('top','0px');
        //
        // 2.3 Event handling for individual element inspector buttons on click
        $('.inspector-button:not(:first-of-type)').on('click', function () {
            // if the clicked button is active
            if ($(this).hasClass('inspector-active')) {
                $(this).removeClass('inspector-active');
                // sub check for admin bar check
                if($(this).is(':nth-child(6)')) {
                    setTimeout(function() {
                        $('html').removeClass('hide-admin-bar');
                        $('#wpadminbar').slideDown();
                        $('header.brro-sticky').css('top','32px');
                    }, 50);
                }
                var index = $('.inspector-button').index($(this));
                if (index >= 0 && index < toggleCircles.length) {
                    $('body').removeClass(toggleCircles[index].class);
                }
                var bodyHasAllClasses = toggleCircles.slice(1).every(function(circle) {
                    return $('body').hasClass(circle.class);
                });
                if (!bodyHasAllClasses) {
                    $('.inspector-button:first-of-type').removeClass('inspector-active');
                }
            // if the clicked button is not active, add 'active' class and and add the corresponding inspector class to body
            } else {
                $(this).addClass('inspector-active');
                // sub check for admin bar check
                if($(this).is(':nth-child(6)')) {
                    setTimeout(function() {
                        $('html').addClass('hide-admin-bar');
                        $('#wpadminbar').slideUp();
                        $('header.brro-sticky').css('top','0px');
                    }, 50);
                }
                var index = $('.inspector-button').index($(this));
                if (index >= 0 && index < toggleCircles.length) {
                    $('body').addClass(toggleCircles[index].class);
                }
                var bodyHasAllClasses = toggleCircles.slice(1).every(function(circle) {
                    return $('body').hasClass(circle.class);
                });
                if (bodyHasAllClasses) {
                    $('.inspector-button:first-of-type').addClass('inspector-active');
                }
            }
        });
        // 2.4 Event handling for all elements inspector button on click
        $('.inspector-button:first-of-type').on('click', function () {
            // if the clicked button is active
            if ($(this).hasClass('inspector-active')) {
                $('.inspector-button').removeClass('inspector-active');
                var index = $('.inspector-button').index($(this));
                if (index >= 0 && index < toggleCircles.length) {
                    $('body').removeClass(toggleCircles[index].class);
                }
            // if the clicked button is not active, add 'active' class and and add the corresponding inspector class to body
            } else {
                $('.inspector-button').removeClass('inspector-active');
                $('.inspector-button').addClass('inspector-active');
                var index = $('.inspector-button').index($(this));
                if (index >= 0 && index < toggleCircles.length) {
                    $('body').removeClass(toggleCircles[index].class);
                }
                var index = $('.inspector-button').index($(this));
                if (index >= 0 && index < toggleCircles.length) {
                    $('body').addClass(toggleCircles[index].class);
                }
            }
        });
    }
});