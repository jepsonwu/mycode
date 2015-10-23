

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
    //日期
    $( "input[id^='datepicker']" ).datepicker();

    //Menu Widget 导航栏
    //Progressbar Widget 进度条
    //Slider Widget 拖动手柄选择一个值
    //Spinner Widget 向上，向下箭头选择值
    //Tabs Widget  多面板

    //刷新验证码
    $("#verify").css("cursor", "pointer");
    $("#verify").click(function () {
        var date = new Date();
        $(this).attr("src", GROUP + "/Public/Verify/" + date.getTime());
    });
});