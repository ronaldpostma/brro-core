jQuery(function($) {
    // Access settings from PHP
    var helpUrl = pluginSettings.helpUrl;
    // Add separator classes
    $('#toplevel_page_brro-separator-core, #toplevel_page_brro-separator-functionality, #toplevel_page_brro-separator-content').addClass('brro-separator');
    $('#toplevel_page_brro-separator-core').nextUntil('#toplevel_page_brro-separator-functionality').addClass('brro-core');
    $('#toplevel_page_brro-separator-functionality').nextUntil('#toplevel_page_brro-separator-content').addClass('brro-functionality');
    $('#toplevel_page_brro-separator-content').nextUntil('#collapse-menu').addClass('brro-content');
    // Brro help link
    $('#toplevel_page_brro-help-link a').attr('href', helpUrl).attr('target', '_blank');
    // CSS calc chromeless popup
    $(document).on('click', '#toplevel_page_brro-calc-popup > a', function(e) {
        e.preventDefault();
        var w = 760, h = 290;
        var y = window.top.outerHeight / 2 + window.top.screenY - ( h / 2);
        var x = window.top.outerWidth / 2 + window.top.screenX - ( w / 2);
        var url = ajaxurl + '?action=brro_css_calc_popup';
        var features = [
            'popup=yes','toolbar=no','location=no','directories=no','status=no','menubar=no',
            'scrollbars=yes','resizable=yes','copyhistory=no',
            'width=' + w,'height=' + h,'top=' + Math.max(0, Math.round(y)),'left=' + Math.max(0, Math.round(x))
        ].join(',');
        window.open(url, 'brroCssCalc', features);
    });
    // Admin menu opacity
    setTimeout(function() {
        $('#adminmenu').css('opacity', '1');
    }, 100);
    // ACF max length
    $('.acf-field input, .acf-field textarea').each(function() {
        var maxLength = $(this).attr('maxlength');
        $(this).parent().attr('brro-acf-data-maxlength', maxLength);
    });
    // Display max length for excerpt while typing (runs whenever the excerpt metabox exists, e.g. new or edit post/page)
    var excerptText = $('#excerpt');
    if (excerptText.length) {
        var maxLength = 141; // Set the maximum length for the excerpt
        var excerptInfo = 'Wordt gebruikt als samenvatting en meta-beschrijving voor zoekmachines. Max ' + maxLength + ' karakters';
        // Add a class and text to the paragraph following the excerpt textarea
        $('textarea#excerpt + p').addClass('cust-excerpt').text(excerptInfo);
        excerptText.attr('maxlength', maxLength); // Set the maxlength attribute for the excerpt textarea
        excerptText.on('input', function() {
            var text = excerptText.val(); // Get the current value of the textarea
            if (text.length > maxLength) {
                excerptText.val(text.substring(0, maxLength));
            }
            $('textarea#excerpt + p').text(excerptInfo + ': ' + text.length + '/' + maxLength);
        });
    }
    // Brro instructions tooltip
    var brroTooltipDrag = null;
    var brroTooltipOffsetX = 0;
    var brroTooltipOffsetY = 0;

    // Clear inline position from drag so CSS defaults apply again
    function brroClearTooltipPosition($tooltip) {
        $tooltip.css({ top: '', left: '', bottom: '', right: '' });
    }

    // Close tooltip and reset position
    function brroCloseTooltip($tooltip) {
        $tooltip.removeClass('open');
        brroClearTooltipPosition($tooltip);
    }

    // Close all open tooltips
    function brroCloseAllTooltips() {
        $('section.brro-tooltip.open').each(function() {
            brroCloseTooltip($(this));
        });
    }

    // Resolve panel from toggle: tooltip-target id or first section after button in DOM
    function brroGetTooltipForToggle($btn) {
        var targetId = $btn.attr('tooltip-target');
        if (targetId) {
            return $('#' + $.escapeSelector(targetId)).filter('section.brro-tooltip');
        }
        // Walk up ancestors: section may follow a wrapper (e.g. ACF puts button inside <p>)
        var $cursor = $btn;
        while ($cursor.length) {
            var $match = $cursor.nextAll('section.brro-tooltip').first();
            if ($match.length) {
                return $match;
            }
            $cursor = $cursor.parent();
        }
        return $();
    }

    // Top-right hit box for CSS :after close control (pseudo-element is not clickable)
    function brroIsTooltipCloseZone($tooltip, clientX, clientY) {
        var rect = $tooltip[0].getBoundingClientRect();
        var hitSize = 40;
        return clientX >= rect.right - hitSize && clientY <= rect.top + hitSize;
    }

    // Pin fixed panel to top/left before drag (avoids jump from bottom/left)
    function brroPinTooltipPosition($tooltip) {
        var rect = $tooltip[0].getBoundingClientRect();
        $tooltip.css({
            top: rect.top + 'px',
            left: rect.left + 'px',
            bottom: 'auto',
            right: 'auto'
        });
    }

    $(document).on('click', 'button.brro-tooltip-toggle', function(e) {
        e.preventDefault();
        var $tooltip = brroGetTooltipForToggle($(this));
        if (!$tooltip.length) {
            return;
        }
        if ($tooltip.hasClass('open')) {
            brroCloseTooltip($tooltip);
            return;
        }
        brroCloseAllTooltips();
        $tooltip.addClass('open');
    });

    $(document).on('click', 'section.brro-tooltip.open', function(e) {
        var $tooltip = $(this);
        if (brroIsTooltipCloseZone($tooltip, e.clientX, e.clientY)) {
            e.preventDefault();
            e.stopPropagation();
            brroCloseTooltip($tooltip);
        }
    });

    $(document).on('mousedown', 'section.brro-tooltip.open', function(e) {
        if (e.which !== 1) {
            return;
        }
        var $tooltip = $(this);
        if (brroIsTooltipCloseZone($tooltip, e.clientX, e.clientY)) {
            return;
        }
        e.preventDefault();
        brroPinTooltipPosition($tooltip);
        var rect = $tooltip[0].getBoundingClientRect();
        brroTooltipDrag = $tooltip;
        brroTooltipOffsetX = e.clientX - rect.left;
        brroTooltipOffsetY = e.clientY - rect.top;
        $('body').css('user-select', 'none');
    });

    $(document).on('mousemove', function(e) {
        if (!brroTooltipDrag) {
            return;
        }
        brroTooltipDrag.css({
            top: (e.clientY - brroTooltipOffsetY) + 'px',
            left: (e.clientX - brroTooltipOffsetX) + 'px'
        });
    });

    $(document).on('mouseup', function() {
        if (!brroTooltipDrag) {
            return;
        }
        brroTooltipDrag = null;
        $('body').css('user-select', '');
    });
});