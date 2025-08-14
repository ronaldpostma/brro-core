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
    // Chromeless Popup mock link (no functionality yet)
    $('#toplevel_page_brro-calc-popup a').attr('href', '#').attr('target', '_blank');
    setTimeout(function() {
        $('#adminmenu').css('opacity', '1');
    }, 100);
    $('.acf-field input, .acf-field textarea').each(function() {
        var maxLength = $(this).attr('maxlength');
        $(this).parent().attr('brro-acf-data-maxlength', maxLength);
    });
});