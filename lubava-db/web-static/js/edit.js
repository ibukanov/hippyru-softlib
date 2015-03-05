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

CKEDITOR.replace('text_content', {
  language: 'ru',
});

document.getElementById('edit_form').onsubmit = function() {
    return check_fields(this) && set_post_key(this);
}
