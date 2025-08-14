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
    setTimeout(function() {
        $('#adminmenu').css('opacity', '1');
    }, 100);
    $('.acf-field input, .acf-field textarea').each(function() {
        var maxLength = $(this).attr('maxlength');
        $(this).parent().attr('brro-acf-data-maxlength', maxLength);
    });
});