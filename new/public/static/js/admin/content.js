function contentCommon(obj, __CONTROLLER__) {
    var search = "#" + __CONTROLLER__ + "_list_toolbar";
    //var obj = eval(__CONTROLLER__ + "Obj");

//键盘事件
    $(search + " input[name]").keydown(function (event) {
        if (event.keyCode == 13) {
            toSearchViewList();
        }
    });

    $(search + " select[name]").change(function () {
        toSearchViewList();
    });

//刷新
    $("#" + __CONTROLLER__ + "_list_toolbar #refresh").click(function () {
        obj.container.edatagrid('reload');
    });

//搜索
    $("#" + __CONTROLLER__ + "_list_toolbar #search").click(function () {
        toSearchViewList();
    });

//添加
    $("#" + __CONTROLLER__ + "_list_toolbar #add").click(function () {
        obj.addHandler();
    });

//查询事件
    function toSearchViewList(flag) {
        //获取元素
        var load_param = {};
        $(search + " :input,select").each(function (key) {
            load_param[this.name] = this.value;
        });

        obj.container.datagrid('load', load_param);
    }

//新增事件
    /**
     *
     * @param name
     * @param width
     * @param height
     * @param title
     */
    function addHandlerObj(name, width, height, title) {
        var url = name + "Url";
        var hand = name + "Handler";
        if (typeof obj[hand] == 'undefined') {
            obj[hand] = function (param) {
                var _THIS_ = this;
                _THIS_.view.window({
                    'href': _THIS_[url] + (typeof param !== 'undefined' ? param : ""),
                    'width': width,
                    'height': height,
                    'modal': true,
                    'resizable': false,
                    'title': title,
                    'onLoad': function () {

                        $("#" + __CONTROLLER__ + "_" + name + "_form_save").off().on('click', function () {
                            $("#" + __CONTROLLER__ + "_" + name + "_form").attr('action', _THIS_[url]).submit();
                        });

                        $("#" + __CONTROLLER__ + "_" + name + "_form_close").on('click', function () {
                            _THIS_.view.window('close');
                        });

                        $("#" + __CONTROLLER__ + "_" + name + "_form").myForm(function (ret) {
                            console.info(ret);
                            if (ret.flag) {
                                //$.messager.alert("提示信息", '新增成功！', 'info');
                                _THIS_.view.window('close');
                                _THIS_.container.datagrid('reload');
                            } else {
                                $.messager.alert("提示信息", ret.msg, 'error');
                            }
                        });
                    }
                });
            };
        }
    }

//定义对象
    obj.init = function () {
        this.showData();
    };


//显示图片 自定义
    obj['img_dialog'] = $("#img_dialog");
    if (typeof obj.showImg == 'undefined') {
        obj.showImg = function (url) {
            obj.img_dialog.html('<img src="' + url + '"/>').dialog('open');
        };
    }

//
    obj['container'] = $("#" + __CONTROLLER__ + "_list");
    obj['view'] = $("#" + __CONTROLLER__ + "_view");
    obj['selfName'] = __CONTROLLER__ + 'Obj';

//生成对象属性_feature后缀
    /**
     *
     */
    function addFeatureHandler() {
        for (var ele in obj) {
            pos = ele.indexOf("_feature");
            if (pos !== -1) {
                name = ele.substring(0, pos);
                addHandlerObj(name, obj[ele]['width'], obj[ele]['height'], obj[ele]['title']);
            }
        }
    }

//alert()

//初始化
    addFeatureHandler();
    obj.init();
    return obj;
}