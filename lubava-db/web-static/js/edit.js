var settings = null;

xinha_editors = null;
xinha_init    = null;
xinha_config  = null;
xinha_plugins = null;

// Create Xinha text editor
xinha_init = xinha_init ? xinha_init : function() {
    xinha_plugins = xinha_plugins ? xinha_plugins : [
        
    ];

    if (!Xinha.loadPlugins (xinha_plugins, xinha_init)) {
        return;
    }

    xinha_editors = xinha_editors ? xinha_editors : [
        'teContent'
    ];

    xinha_config  = xinha_config ? xinha_config : new Xinha.Config ();

    xinha_config.width  = '600px';
    xinha_config.height = '480px';
    xinha_config.statusBar   = false;
    xinha_config.showLoading = true;

    xinha_editors = Xinha.makeEditors (xinha_editors, xinha_config);

    Xinha.startEditors (xinha_editors);
    window.onload = null;
}

window.onload = xinha_init;

// Input check script 
function check_fields (form) {
    var h   = null;
    var str = null;
    if (form.group.value == "" && form.group_force.value == "") {h = form.group; str = "Укажите, пожалуйста, категорию.";}
    else if (form.year.value == "") {h = form.year; str = "Укажите, пожалуйста, год написания.";}
    else if (form.author.value == "") {h = form.author; str = "Укажите, пожалуйста, автора.";}
    else if (form.content.value.length < 4) {h = form.content; str = "Неплохо бы и сам текст написать...";}
    if (h) {
        alert (str);
        h.focus();
        return false;
    }

    return true;
}
