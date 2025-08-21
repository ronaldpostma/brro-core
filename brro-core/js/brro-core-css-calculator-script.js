jQuery(function($) {
    // Access calculation settings from popup window
    var settings = (window.brroSettings || {});
    var desktopEnd = Number(settings.desktopEnd);
    var desktopRef = Number(settings.desktopRef);
    var desktopStart = Number(settings.desktopStart);
    var tabletEnd = desktopStart - 1;
    var tabletRef = Number(settings.tabletRef);
    var tabletStart = Number(settings.tabletStart);
    var mobileEnd = tabletStart - 1;
    var mobileRef = Number(settings.mobileRef);
    var mobileStart = Number(settings.mobileStart);

    var $form = $('#brro-calc-form');
    var $input = $('#brro-calc-input');
    var $error = $('#brro-calc-error');
    var $outDesktop = $('#brro-out-desktop');
    var $outTablet = $('#brro-out-tablet');
    var $outMobile = $('#brro-out-mobile');

    function isNumericString(str) {
        return /^-?\d+(?:\.\d+)?$/.test(str);
    }

    function parseInput(raw) {
        var trimmed = (raw || '').trim();
        // New: explicit min prefix → min,[minPx],[valueAtRef]
        if (trimmed.toLowerCase().indexOf('min,') === 0) {
            var rest = trimmed.slice(4);
            var partsMin = rest.split(',');
            if (partsMin.length === 2 && isNumericString(partsMin[0]) && isNumericString(partsMin[1])) {
                return { type: 'min', minPx: Number(partsMin[0]), value: Number(partsMin[1]) };
            }
            return null;
        }
        var mdPrefix = false;
        if (trimmed.toLowerCase().indexOf('md,') === 0) {
            mdPrefix = true;
            trimmed = trimmed.slice(3);
        }
        // Support negatives and two-part inputs: "-12" or "-12,34" etc
        if (isNumericString(trimmed)) {
            return { type: 'single', value: Number(trimmed), negative: Number(trimmed) < 0, md: mdPrefix };
        }
        if (/^-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?$/.test(trimmed)) {
            var parts = trimmed.split(',');
            return { type: 'range', a: Number(parts[0]), b: Number(parts[1]), md: mdPrefix };
        }
        return null;
    }

    function roundIfNeeded(n) { return (n % 1) ? Number(n.toFixed(2)) : n; }

    function buildClampSingle(inputPx, screenRef, screenStart, screenEnd, isDesktopOpenEnded) {
        var vwTarget = (inputPx / screenRef) * 100;
        var outputMin = (screenStart / 100) * vwTarget;
        var outputMax = (screenEnd / 100) * vwTarget;
        vwTarget = roundIfNeeded(vwTarget);
        outputMin = Math.round(outputMin);
        outputMax = Math.round(outputMax);
        if (isDesktopOpenEnded) {
            return 'max(' + (inputPx < 0 ? outputMax : outputMin) + 'px, ' + vwTarget + 'vw) /*' + inputPx + 'px @ ' + screenRef + '*/';
        }
        return 'clamp(' + (inputPx < 0 ? outputMax : outputMin) + 'px, ' + vwTarget + 'vw, ' + (inputPx < 0 ? outputMin : outputMax) + 'px) /*' + inputPx + 'px @ ' + screenRef + '*/';
    }

    function buildClampRange(inputA, inputB, screenStart, screenEnd, screenRefStart, screenRefEnd) {
        var growthRate = (inputB - inputA) / (screenRefEnd - screenRefStart);
        var vwTarget = growthRate * 100;
        var baseValue = inputA - (growthRate * screenRefStart);
        growthRate = roundIfNeeded(growthRate);
        vwTarget = roundIfNeeded(vwTarget);
        baseValue = roundIfNeeded(baseValue);
        var minPx = Math.min(inputA, inputB);
        var maxPx = Math.max(inputA, inputB);
        var baseSign = (baseValue < 0) ? '- ' + Math.abs(baseValue) : '+ ' + baseValue;
        var comment = ' /*' + inputA + 'px @ ' + screenRefStart + ' : ' + inputB + 'px @ ' + screenRefEnd + '*/';
        return 'clamp(' + minPx + 'px, calc(' + vwTarget + 'vw ' + baseSign + 'px), ' + maxPx + 'px)' + comment;
    }

    function calcForDesktop(parsed) {
        if (parsed.type === 'single') {
            var isOpenEnded = (desktopEnd === 0);
            return buildClampSingle(parsed.value, desktopRef, desktopStart, (isOpenEnded ? (desktopStart + (desktopRef - desktopStart)) : desktopEnd), isOpenEnded);
        }
        if (parsed.type === 'min') {
            var vwTarget = (parsed.value / desktopRef) * 100;
            vwTarget = roundIfNeeded(vwTarget);
            if (desktopEnd === 0) {
                // Open-ended desktop: follow single-input rule using the forced minimum bound
                return 'max(' + parsed.minPx + 'px, ' + vwTarget + 'vw) /*' + parsed.value + 'px @ ' + desktopRef + '*/';
            }
            var screenEndPx = (desktopEnd / 100) * ((parsed.value / desktopRef) * 100);
            var endPx = Math.round(screenEndPx);
            var lowerBound = Math.min(parsed.minPx, endPx);
            var upperBound = Math.max(parsed.minPx, endPx);
            return 'clamp(' + lowerBound + 'px, ' + vwTarget + 'vw, ' + upperBound + 'px) /*' + parsed.value + 'px @ ' + desktopRef + '*/';
        }
        if (parsed.type === 'range') {
            // If md prefix: span from mobileRef -> desktopRef, else desktopStart -> desktopRef
            if (parsed.md) {
                return buildClampRange(parsed.a, parsed.b, desktopStart, desktopEnd || desktopRef, mobileRef, desktopRef);
            }
            // desktop range
            if (desktopRef !== desktopEnd) {
                return 'Invalid: desktopRef is unequal to desktopEnd';
            }
            return buildClampRange(parsed.a, parsed.b, desktopStart, (desktopEnd || desktopRef), desktopStart, desktopRef);
        }
        return '';
    }

    function calcForTablet(parsed) {
        if (parsed.type === 'single') {
            return buildClampSingle(parsed.value, tabletRef, tabletStart, tabletEnd, false);
        }
        if (parsed.type === 'range') {
            return buildClampRange(parsed.a, parsed.b, tabletStart, tabletEnd, tabletStart, tabletEnd);
        }
        return '';
    }

    function calcForMobile(parsed) {
        if (parsed.type === 'single') {
            return buildClampSingle(parsed.value, mobileRef, mobileStart, mobileEnd, false);
        }
        if (parsed.type === 'range') {
            return buildClampRange(parsed.a, parsed.b, mobileStart, mobileEnd, mobileStart, mobileEnd);
        }
        return '';
    }

    function validateInput(raw) {
        var ok = /^\s*(?:(?:min,-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?)|(?:(?:md,)?-?\d+(?:\.\d+)?(?:,-?\d+(?:\.\d+)?)?))\s*$/.test(raw || '');
        return ok;
    }

    function handleSubmit(e) {
        if (e) { e.preventDefault(); }
        var raw = $input.val();
        $error.text('');
        $outDesktop.text('');
        $outTablet.text('');
        $outMobile.text('');
        if (!validateInput(raw)) {
            $error.text('Invalid input. Use 300 or 300,600. Prefix with md, for mobile->desktop ranges, or min,16,32 for minimum clamp. Negatives allowed.');
            return;
        }
        var parsed = parseInput(raw);
        if (!parsed) {
            $error.text('Could not parse input.');
            return;
        }
		var outD = calcForDesktop(parsed);
		var outT = '';
		var outM = '';
		// If input uses md prefix, only output Desktop and leave Tablet/Mobile empty
        if (!parsed.md && parsed.type !== 'min') {
			outT = calcForTablet(parsed);
			outM = calcForMobile(parsed);
		}
        $outDesktop.text(outD);
        $outTablet.text(outT);
        $outMobile.text(outM);
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