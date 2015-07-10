/**
 * 显示提示框
 * @param msg
 */
function show_dialog(msg) {
    $("#dialog").html(msg);
    $("#dialog").dialog("open");
}

/**
 * 设置cookie
 * @param name
 * @param value
 * @param expire
 */
function setCookie(name, value, expire) {
    var date = new Date();
    date.setDate(date.getDate() + expire);

    document.cookie = name + "=" + decodeURI(value) +
    ((expire == null) ? "" : ";expires=" + date.toUTCString());
}

/**
 * 获取cookie
 * @param name
 * @returns {string}
 */
function getCookie(name) {
    if (document.cookie.length > 0) {
        var start = document.cookie.indexOf(name + "=");
        if (start != -1) {
            start = start + name.length + 1;
            var end = document.cookie.indexOf(";", start);

            if (end == -1) end = document.cookie.length;
            return decodeURIComponent(document.cookie.substring(start, end));
        }
    }

    return "";
}

/**
 * 删除cookie
 */
function delCookie() {

}

$(function () {
    //dialog
    $("#dialog").dialog({
        autoOpen: false
    });

    //刷新验证码
    $("#verify").css("cursor", "pointer");
    $("#verify").click(function () {
        var date = new Date();
        $(this).attr("src", GROUP + "/Public/Verify/" + date.getTime());
    });
});