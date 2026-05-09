jQuery(function($) {
    // Access calculation settings from popup window
    var settings = window.brroSettings || window.pluginSettings || {};

    var $form = $('#brro-calc-form');
    var $input = $('#brro-calc-input');
    var $error = $('#brro-calc-error');
    var $outDesktop = $('#brro-out-desktop');
    var $outTablet = $('#brro-out-tablet');
    var $outMobile = $('#brro-out-mobile');

    function handleSubmit(e) {
        if (e) { e.preventDefault(); }
        var raw = $input.val();
        $error.text('');
        $outDesktop.text('');
        $outTablet.text('');
        $outMobile.text('');

        if (!window.brroCssCalculator) {
            $error.text('Calculator library could not be loaded.');
            return;
        }

        var results = window.brroCssCalculator.calculateAll(raw, settings);

        if (!results.valid) {
            $error.text(results.error);
            return;
        }

        $outDesktop.text(results.desktop);
        $outTablet.text(results.tablet);
        $outMobile.text(results.mobile);
    }

    $form.on('submit', handleSubmit);

    // Copy-to-clipboard
    $(document).on('click', '.copy', function() {
        var sel = $(this).attr('data-copy');
        var txt = $(sel).text();
        if (!txt) { return; }
        var $temp = $('<textarea readonly style="position:absolute;left:-9999px;top:-9999px"></textarea>').val(txt);
        $('body').append($temp);
        $temp[0].select();
        try { document.execCommand('copy'); } catch(e) {}
        $temp.remove();
        var self = $(this);
        var prev = self.text();
        self.text('Copied').addClass('copied');
        setTimeout(function(){ self.text(prev).removeClass('copied'); }, 900);
    });
});