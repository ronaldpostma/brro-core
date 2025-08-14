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
});