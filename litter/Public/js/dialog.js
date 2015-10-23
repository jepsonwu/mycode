/**
 * 显示提示框
 * @param msg
 */
function show_dialog(msg) {
    $("#dialog").html(msg).dialog("open");
}

/**
 * 显示确认框
 * @param msg
 */
function show_dialog_confirm(msg) {
    $("#dialog_confirm").html(msg).dialog("open");
}

$(function () {
    //dialog
    $("#dialog").dialog({
        autoOpen: false,
        buttons: [
            {
                text: "确认",
                click: function () {
                    $(this).dialog("close");
                }
            }
        ],
        height:"auto",
        modal:true
    });

    //dialog_confirm
    //maxHeight maxWidth minHeight minWidth
    //title
    $("#dialog_confirm").dialog({
        autoOpen: false,
        buttons: [
            {
                text: "确认",
                click: function () {
                    ajax_post("aaa");
                }
            },
            {
                text: "取消",
                click: function () {
                    $(this).dialog("close");
                }
            }
        ],
        height:"auto",
        width:"auto",
        modal:true
    });
});