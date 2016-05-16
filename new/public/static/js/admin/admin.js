$(function () {
    $("*[to='main']").off().on('click', function () {
        openTab(this);

    });
    $("*[to='window']").off().on('click', function () {
        var url = $(this).attr('url');
        var title = $(this).attr('title');
        var to_id = $(this).attr('to_id');
        var width = $(this).attr('width') ? $(this).attr('width') : 600;
        var height = $(this).attr('height') ? $(this).attr('height') : 400;
        if ($("#" + to_id).size() === 0) {
            //当面板不存在时
            $("<div/>").attr('id', to_id).appendTo('body');
            $('#' + to_id).window({
                href: url,
                title: title,
                modal: true,
                width: width,
                height: height,
                resizable: true

            });
        } else {
            $("#" + to_id).window('open');

        }
    });
    $("*[to='left']").off().on('click', function () {
        //寻找自身的layout
        var layout = $(this).parentsUntil('.easyui-layout').parent();
        //$(layout).layout('expand', 'east');
        var east = $(layout).layout('panel', 'east');
        var url = $(this).attr('url');
        if (typeof($(east).attr('title')) != 'undefined') {
            alert('1');
            /*
             $(layout).layout('remove', 'east').layout('add',{
             region: 'east',
             width: 260,
             title: 'West Title',
             split: true,
             href: url
             });
             */
            alert($(layout).layout('options', 'href'));
            alert('2');
        } else {
            $(layout).layout('add', {
                region: 'east',
                width: 260,
                title: 'West Title',
                split: true,
                href: url
            });
            alert('3');
        }
    });

    //主菜单
    $(".memuList").off().on('click', function () {
        //alert('hi');
        var tmpOptions = $(this).linkbutton('options');
        if (tmpOptions.disabled) {
            return false;
        }
        var url = $(this).attr('url');
        $("#p").panel('refresh', url);
        $(".memuList").linkbutton('enable');
        $(this).linkbutton('disable');
    }).eq(0).trigger('click');
});

function openTab(obj) {
    var title = $(obj).attr('title');
    if (!title) {
        title = $(obj).text();
    }
    if (!$("#main-tabs").tabs('exists', title)) {
        $('#main-tabs').tabs('add', {
            title: title,
            closable: true,
            fit: true,
            href: $(obj).attr('url')
        });
    } else {
        $('#main-tabs').tabs('select', title);
    }
    return false;
}

function addOneMore(obj) {
    var title = $(obj).attr('title');
    if (!title) {
        title = $(obj).text();
    }
    $('#main-tabs').tabs('close', title);
    addTab(obj);
}

//初始左侧菜单
function initLeftMenu() {
    $("#menuTree").off().tree({
        onClick: function (node) {
            var nodeText = node.text;

            if (typeof node.attributes == 'undefined' || typeof node.attributes.url == 'undefined') {
                return;
            }

            var url = node.attributes.url;
            if (url) {
                if (!$("#main-tabs").tabs('exists', nodeText)) {
                    $('#main-tabs').tabs('add', {
                        title: nodeText,
                        closable: true,
                        fit: true,
                        href: url
                    });
                } else {
                    $('#main-tabs').tabs('select', nodeText);
                }
            }
        }
    });
}


//添加货币单位
function addCurrencyUnit(value, rowData, rowIndex) {
    var per = '￥';
    if (rowData.Currency == 'USD') {
        per = '＄';
    }
    return per + value;
}
//转换银行名称
function getBankName(value, rowData, rowIndex) {
    var name = value;
    switch (value) {
        case 'ICBC':
            name = '工商银行';
            break;
        case 'CCB':
            name = '建设银行';
            break;
        case 'ABC':
            name = '农业银行';
            break;
        case 'BCM':
            name = '交通银行';
            break;
        case 'BC':
            name = '中国银行';
            break;
        case 'CMBC':
            name = '招商银行';
            break;
    }
    return name;
}

//冻结状态
function tansFreezeStatus(value, rowData, rowIndex) {
    $tip = '已冻结';
    if (value == 'UNFREEZE') {
        $tip = '已解除';
    }
    return $tip;
}

//查看会员详情
function viewMemberInfo($obj, member_id) {
    $obj.viewHandler(member_id);
}

//判断是否已退出，并进行跳转
function checkRequestLogout(data) {
    if (data.substring(0, 18) != '<ul id="menuTree">') {
        //window.location.href=window.location.href;
    }
}
function formatDate(value, rec, index) {
    var unixTimestamp = new Date(value * 1000);
    date = unixTimestamp.toLocaleString().replace(/年|月/g, "-").replace(/日/g, " ");
    return date;
}


$.fn.dataTable = function () {
    var layout = $(this).parentsUntil('.easyui-layout').parent();
    var idField = $(this).attr('idField');
    var toPanel = $(this).attr('go');
    $(this).datagrid({
        onClickRow: function (index, data) {

        }
    });
};

$.fn.myPanel = function () {
    $(this).layout('add', {
        region: 'east',
        width: 260,
        title: 'West Title',
        split: true,
        href: url
    });
};

$.fn.myForm = function (callback) {
    var f = this;
    $(f).ajaxForm({
        dataType: 'json',
        beforeSubmit: function () {
            //alert('请稍等');
        },
        success: function (data) {
            if (data.flag) {
                if (data.msg == null)
                    data.msg = "保存成功";
                f.clearForm().remove();
            }
            else {
                if (data.msg == null)
                    data.msg = "保存失败";
            }
            //alert(data.flag, data.msg, 3);
            if (typeof(callback) != 'undefined') {
                callback(data);
            }
        }
    });
};

var message = {
    'show': function (msg, timeout) {
        $.blockUI({
            message: msg,
            fadeIn: 700,
            fadeOut: 700,
            timeout: 30000,
            showOverlay: false,
            centerY: false,
            css: {
                width: '350px',
                top: '10px',
                left: '',
                right: '10px',
                border: 'none',
                padding: '5px',
                backgroundColor: '#000',
                '-webkit-border-radius': '10px',
                '-moz-border-radius': '10px',
                opacity: '0.6',
                color: '#fff'
            }
        });
        if (typeof(timeout) != 'undefined') {
            setTimeout(function () {
                $.unblockUI();
            }, timeout * 1000);
        }
    },
    'update': function (status, msg, timeout) {
        $('.blockMsg').html(msg);
        if (typeof(timeout) != 'undefined') {
            setTimeout(function () {
                $.unblockUI();
            }, timeout * 1000);
        }
    },
    'hide': function (timeout) {
        if (typeof(timeout) == 'undefined') {
            timeout = 3;
        }
        $.unblockUI();
    },
    'updateTips': function (box_id, msg) {
        $(box_id).find('p.tips')
            .text(msg)
            .addClass("ui-state-highlight");
        setTimeout(function () {
            $(box_id).find('p.tips').removeClass("ui-state-highlight", 1500);
        }, 500);
    }
};

$(document).ready(function () {
    $('.openRightNow').click();
});

$.fn.serializeObject = function () {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function () {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

//add by jepson  ajax function

function getAjax($url, obj) {
    $.ajax({
        type: 'GET',
        url: $url,
        dataType: 'json',
        success: function (data) {
            if (data.flag == 0) {
                alert(data.msg);
            } else {
                alert(data.msg);
                obj.container.datagrid('reload');
            }
        },
        error: function () {
            alert("操作失败！");
        }
    });
}

function postAjax($url, $post) {

}
