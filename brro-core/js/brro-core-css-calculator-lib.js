(function(window) {
    'use strict';

    function brro_toNumber(value, fallback) {
        var number = Number(value);
        return Number.isFinite(number) ? number : fallback;
    }

    function brro_normalizeSettings(settings) {
        var source = settings || {};
        var desktopStart = brro_toNumber(source.desktopStart, 1024);
        var tabletStart = brro_toNumber(source.tabletStart, 768);

        return {
            desktopEnd: brro_toNumber(source.desktopEnd, 0),
            desktopRef: brro_toNumber(source.desktopRef, 1440),
            desktopStart: desktopStart,
            tabletEnd: desktopStart - 1,
            tabletRef: brro_toNumber(source.tabletRef, 1024),
            tabletStart: tabletStart,
            mobileEnd: tabletStart - 1,
            mobileRef: brro_toNumber(source.mobileRef, 360),
            mobileStart: brro_toNumber(source.mobileStart, 320)
        };
    }

    function brro_isNumericString(str) {
        return /^-?\d+(?:\.\d+)?$/.test(str);
    }

    function brro_validateInput(raw) {
        return /^\s*(?:(?:min,-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?)|(?:(?:md,)?-?\d+(?:\.\d+)?(?:,-?\d+(?:\.\d+)?)?))\s*$/.test(raw || '');
    }

    function brro_parseInput(raw) {
        var trimmed = (raw || '').trim();

        if (trimmed.toLowerCase().indexOf('min,') === 0) {
            var rest = trimmed.slice(4);
            var partsMin = rest.split(',');

            if (partsMin.length === 2 && brro_isNumericString(partsMin[0]) && brro_isNumericString(partsMin[1])) {
                return { type: 'min', minPx: Number(partsMin[0]), value: Number(partsMin[1]) };
            }

            return null;
        }

        var mdPrefix = false;

        if (trimmed.toLowerCase().indexOf('md,') === 0) {
            mdPrefix = true;
            trimmed = trimmed.slice(3);
        }

        if (brro_isNumericString(trimmed)) {
            return { type: 'single', value: Number(trimmed), negative: Number(trimmed) < 0, md: mdPrefix };
        }

        if (/^-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?$/.test(trimmed)) {
            var parts = trimmed.split(',');
            return { type: 'range', a: Number(parts[0]), b: Number(parts[1]), md: mdPrefix };
        }

        return null;
    }

    function brro_roundIfNeeded(number) {
        return (number % 1) ? Number(number.toFixed(2)) : number;
    }

    function brro_formatFormulaNumber(number) {
        return String(brro_roundIfNeeded(number));
    }

    function brro_formatClampBound(number) {
        return String(Math.ceil(brro_roundIfNeeded(number)));
    }

    function brro_buildClampSingle(inputPx, screenRef, screenStart, screenEnd, isDesktopOpenEnded) {
        var vwTarget = (inputPx / screenRef) * 100;
        var outputMin = (screenStart / 100) * vwTarget;
        var outputMax = (screenEnd / 100) * vwTarget;
        var minBound = brro_formatClampBound(outputMin);
        var maxBound = brro_formatClampBound(outputMax);
        var vwOutput = brro_formatFormulaNumber(vwTarget);

        if (isDesktopOpenEnded) {
            return 'max(' + (inputPx < 0 ? maxBound : minBound) + 'px, ' + vwOutput + 'vw) /*' + inputPx + 'px @ ' + screenRef + '*/';
        }

        return 'clamp(' + (inputPx < 0 ? maxBound : minBound) + 'px, ' + vwOutput + 'vw, ' + (inputPx < 0 ? minBound : maxBound) + 'px) /*' + inputPx + 'px @ ' + screenRef + '*/';
    }

    function brro_buildClampRange(inputA, inputB, screenStart, screenEnd, screenRefStart, screenRefEnd) {
        var growthRate = (inputB - inputA) / (screenRefEnd - screenRefStart);
        var vwTarget = growthRate * 100;
        var baseValue = inputA - (growthRate * screenRefStart);
        var valueAtStart = (growthRate * screenStart) + baseValue;
        var valueAtEnd = (growthRate * screenEnd) + baseValue;
        var minPx = Math.min(valueAtStart, valueAtEnd);
        var maxPx = Math.max(valueAtStart, valueAtEnd);
        var baseOutput = brro_roundIfNeeded(baseValue);
        var baseSign = (baseOutput < 0) ? '- ' + Math.abs(baseOutput) : '+ ' + baseOutput;
        var comment = ' /*' + inputA + 'px @ ' + screenRefStart + ' : ' + inputB + 'px @ ' + screenRefEnd + '*/';

        return 'clamp(' + brro_formatClampBound(minPx) + 'px, calc(' + brro_formatFormulaNumber(vwTarget) + 'vw ' + baseSign + 'px), ' + brro_formatClampBound(maxPx) + 'px)' + comment;
    }

    function brro_calculateParsedForDevice(parsed, device, settings) {
        var config = brro_normalizeSettings(settings);

        if (!parsed) {
            return '';
        }

        if (parsed.md && device !== 'desktop') {
            return '';
        }

        if (device === 'desktop') {
            if (parsed.type === 'single') {
                var isOpenEnded = (config.desktopEnd === 0);
                var desktopEnd = isOpenEnded ? config.desktopRef : config.desktopEnd;
                return brro_buildClampSingle(parsed.value, config.desktopRef, config.desktopStart, desktopEnd, isOpenEnded);
            }

            if (parsed.type === 'min') {
                var vwTarget = brro_formatFormulaNumber((parsed.value / config.desktopRef) * 100);

                if (config.desktopEnd === 0) {
                    return 'max(' + brro_formatClampBound(parsed.minPx) + 'px, ' + vwTarget + 'vw) /*' + parsed.value + 'px @ ' + config.desktopRef + '*/';
                }

                var screenEndPx = (config.desktopEnd / 100) * ((parsed.value / config.desktopRef) * 100);
                var endPx = brro_roundIfNeeded(screenEndPx);
                var lowerBound = Math.min(parsed.minPx, endPx);
                var upperBound = Math.max(parsed.minPx, endPx);
                return 'clamp(' + brro_formatClampBound(lowerBound) + 'px, ' + vwTarget + 'vw, ' + brro_formatClampBound(upperBound) + 'px) /*' + parsed.value + 'px @ ' + config.desktopRef + '*/';
            }

            if (parsed.type === 'range') {
                if (parsed.md) {
                    return brro_buildClampRange(parsed.a, parsed.b, config.desktopStart, config.desktopEnd || config.desktopRef, config.mobileRef, config.desktopRef);
                }

                if (config.desktopRef !== config.desktopEnd) {
                    return 'Invalid: desktopRef is unequal to desktopEnd';
                }

                return brro_buildClampRange(parsed.a, parsed.b, config.desktopStart, config.desktopEnd || config.desktopRef, config.desktopStart, config.desktopRef);
            }
        }

        if (device === 'tablet') {
            if (parsed.type === 'single') {
                return brro_buildClampSingle(parsed.value, config.tabletRef, config.tabletStart, config.tabletEnd, false);
            }

            if (parsed.type === 'range') {
                return brro_buildClampRange(parsed.a, parsed.b, config.tabletStart, config.tabletEnd, config.tabletStart, config.tabletEnd);
            }
        }

        if (device === 'mobile') {
            if (parsed.type === 'single') {
                return brro_buildClampSingle(parsed.value, config.mobileRef, config.mobileStart, config.mobileEnd, false);
            }

            if (parsed.type === 'range') {
                return brro_buildClampRange(parsed.a, parsed.b, config.mobileStart, config.mobileEnd, config.mobileRef, config.mobileEnd);
            }
        }

        return '';
    }

    function brro_buildError(message) {
        return {
            valid: false,
            error: message,
            css: '',
            desktop: '',
            tablet: '',
            mobile: ''
        };
    }

    function brro_calculateForDevice(raw, device, settings) {
        if (!brro_validateInput(raw)) {
            return brro_buildError('Invalid input. Use 300 or 300,600. Prefix with md, for mobile->desktop ranges, or min,16,32 for minimum clamp. Negatives allowed.');
        }

        var parsed = brro_parseInput(raw);

        if (!parsed) {
            return brro_buildError('Could not parse input.');
        }

        return {
            valid: true,
            error: '',
            parsed: parsed,
            css: brro_calculateParsedForDevice(parsed, device, settings)
        };
    }

    function brro_calculateAll(raw, settings) {
        if (!brro_validateInput(raw)) {
            return brro_buildError('Invalid input. Use 300 or 300,600. Prefix with md, for mobile->desktop ranges, or min,16,32 for minimum clamp. Negatives allowed.');
        }

        var parsed = brro_parseInput(raw);

        if (!parsed) {
            return brro_buildError('Could not parse input.');
        }

        return {
            valid: true,
            error: '',
            parsed: parsed,
            desktop: brro_calculateParsedForDevice(parsed, 'desktop', settings),
            tablet: (!parsed.md && parsed.type !== 'min') ? brro_calculateParsedForDevice(parsed, 'tablet', settings) : '',
            mobile: (!parsed.md && parsed.type !== 'min') ? brro_calculateParsedForDevice(parsed, 'mobile', settings) : ''
        };
    }

    window.brroCssCalculator = {
        validateInput: brro_validateInput,
        parseInput: brro_parseInput,
        calculateAll: brro_calculateAll,
        calculateForDevice: brro_calculateForDevice
    };
})(window);
