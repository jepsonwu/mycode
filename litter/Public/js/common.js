function show_dialog(msg) {
    $("#dialog").html(msg);
    $("#dialog").dialog("open");
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