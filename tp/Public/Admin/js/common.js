
// 显示提示框
function my_show_dialog(msg, delay, url) {
	$("#dialog").html( '<div style="min-width:230px;">'
			+ '<dl style="float:left;"><span class="ui-icon ui-icon-alert" style="float:left; margin:2px 7px 0 0;"></span></dl>'
			+ '<dl style="float:left;">' + msg + '<dl></div>');
	$("#dialog").dialog("open");
	if (delay > 0) {
		window.setTimeout(function() {
			$("#dialog").dialog("close");
			if ( url != undefined ) {
				window.location.href = url;
			}
		}, delay);
	}
}

// 显示Note框
function my_show_dialog_note(url, name) {
	$("#dialog_note").html(
		'<textarea id="note_content" style="width:300px; height:100px;"></textarea>' +
		'<input type="hidden" id="note_url" value="' + url + '" />' +
		'<input type="hidden" id="note_name" value="' + name + '" />'
	);
	$("#dialog_note").dialog("open");
}

function my_show_dialog_img(url) {
    $("#dialog_img").html('<img src="' + url + '" width="250px" height="250px"/>');
    $("#dialog_img").dialog("open");
}

// 显示确认框
function my_show_dialog_confirm(msg, url, param, ajax) {
	if ( ajax == undefined ) {
		ajax = 1; // 默认ajax提交
	}
	$("#dialog_confirm").html( '<div style="min-width:230px;">'
			+ '<span class="ui-icon ui-icon-info" style="float:left; margin:2px 7px 0 0;"></span><dl style="padding-left:30px;">'
			+ msg
			+ '</dl></div><input type="hidden" id="cfm_url" value="'
			+ url
			+ '" /><input type="hidden" id="cfm_param" value="'
			+ param
			+ '" /><input type="hidden" id="ajax_sub" value="'
			+ ajax + '" />');
	$("#dialog_confirm").dialog("open");
}

// 显示进度框
function my_show_prograssbar() {
	$("#dialog_prograssbar").dialog("open");
}
// 关闭进度框
function my_close_prograssbar() {
	$("#dialog_prograssbar").dialog("close");
}

// 组合param字符串
function json_to_param_str ( jsonObj ) {
	var str = '';
	for ( var prm in jsonObj) {
		str += '/' + prm + '/' + jsonObj[prm];
	}
	return str;
}

// 复选框单击
function my_checkbox_click(obj, inList) {
	if (obj.children("span").children("input:checked").size() == 1) {
		obj.children("span").addClass("checked");
		if (inList) {
			obj.parents("tr").css("background-color", "#EEE").addClass("checked");
		}
	} else {
		obj.children("span").removeClass("checked");
		if (inList) {
			obj.parents("tr").css("background-color", "#FFF").removeClass("checked");
		}
	}
}

// 单选框单击
function my_radio_click(obj) {
	obj.children("span").addClass("checked");
	obj.parent("label").siblings("label").children(".radio").children("span").removeClass("checked");
}

// 选中所有
function my_check_all(obj) {
	if (obj.children("span").hasClass("checked")) {
		$("tbody tr").find(".checker").children("span").removeClass("checked");
		$("tbody tr").find(".checker").children("span").children("input").attr( "checked", false);
		$("tbody tr").css("background-color", "#FFF");
	} else {
		$("tbody tr").find(".checker").children("span").addClass("checked");
		$("tbody tr").find(".checker").children("span").children("input").attr( "checked", true);
		$("tbody tr").css("background-color", "#EEE");
	}
}

// 获取所有选中项id
function get_chekced_ids() {
	var ids = '';
	$("tbody tr .checker span input:checked").each(function(idx, domEle) {
		if ($(domEle).val() != '') {
			if (ids == '')
				ids = $(domEle).val();
			else
				ids += '|' + $(domEle).val();
		}
	});
	return ids;
}

//分析param
function anaParams( param ) {
	var result = {};
	if ( param != undefined ) {
		var temp = "";
		var ary = param.split("&");
		for (var x in ary) {
			temp = ary[x].split("=");
			result[temp[0]] = temp[1];
		}
	}
	return result;
}

//ajax获取页面
function my_ajax_get(url, param) {
	//
	if ( param == undefined ) {
		param = {};
	}
	//
	url += 'vt/' + new Date().getTime() + "/";
	//
	$.post(url, param, function(data){
		my_show_dialog(data.info, 0);
	});
}

//ajax提交执行
function my_ajax_sub(url, param) {
	//
	if ( param == undefined ) {
		param = {};
	}
	//
	$.post(url, param, function( rtn ){
		// 页面重载
		if ( rtn.act == 'reload' ) {
			window.location.reload();
			return;
		}
		// 弹出确认框
		if ( rtn.act == 'confirm' ) {
			window.setTimeout(function(){
				my_show_dialog_confirm( rtn.data, rtn.url );
			}, 300);
			return;
		}
		//
		if ( rtn.info != null && rtn.info != undefined ) {
			if ( rtn.status )
				my_show_dialog(rtn.info, 1000, rtn.url);
			else {
				my_show_dialog(rtn.info);
			}
		}
		else {
			if ( rtn.url != undefined ) {
				window.location.href = rtn.url;
			}
		}
	});
}


// loaded
$(function() {

	// 提示框
	$("#dialog").dialog({
		modal : true,
		autoOpen : false,
		width : 'auto',
		show : {
			effect : "drop",
			duration : 300
		},
		hide : {
			effect : "drop",
			duration : 300
		},
		buttons : {
			Ok : function() {
				$(this).dialog("close");
				$("#dialog").html('');
			}
		}
	});

	// 确认框
	$("#dialog_confirm").dialog({
		modal : true,
		autoOpen : false,
		width : 'auto',
		show : {
			effect : "drop",
			duration : 300
		},
		hide : {
			effect : "drop",
			duration : 300
		},
		buttons : {
			Ok : function() {
				//
				var cfm_url = $("#cfm_url").val();
				//
				if (cfm_url == 'null') { // 退出
					window.location.href = MODULE + "/Public/logout/";
				} else {
					var params = $("#cfm_param").val();
					$(this).find(".cfm_param").each(function(index, domEle) {
						params += '/' + $(domEle).attr('name') + '/' + $(domEle).val();
					});
					if(params =="undefined")
						params="";

					//
					if ( $("#ajax_sub").val() == '1' ) {
						my_ajax_sub ( cfm_url + params );
					}
					else {
						window.location.href = cfm_url + params;
					}
				}
				//
				$(this).dialog("close");
				$("#dialog_confirm").html('');
			},
			Cancel : function() {
				$(this).dialog("close");
				$("#dialog_confirm").html('');
			}
		}
	});

    //图片
    $("#dialog_img").dialog({
        modal : true,
        autoOpen : false,
        width : 'auto',
        show : {
            effect : "drop",
            duration : 300
        },
        hide : {
            effect : "drop",
            duration : 300
        }
    });

	// Note框
	$("#dialog_note").dialog({
		modal : true,
		autoOpen : false,
		width : 'auto',
		show : {
			effect : "drop",
			duration : 300
		},
		hide : {
			effect : "drop",
			duration : 300
		},
		buttons : {
			Ok : function() {
				// 获取url,name,content
				var note_url = $("#note_url").val();
				var note_name = $("#note_name").val();
				var note_content = $("#note_content").val();

				// url未定义时,退出系统
				if(note_url == 'undefined' || !note_url) window.location.href = MODULE + "/Public/logout/";
				// name未定义时,默认值为note
				if(note_name == 'undefined' || !note_name) note_name = 'note';
				// content未定义时,默认值为空
				if(note_content == 'undefined' || !note_content) note_content = '';
				// param
				var param= '/' + note_name + '/' + note_content;

				// 提交处理
				my_ajax_sub(note_url + param);
			},
			Cancel : function() {
				$(this).dialog("close");
				$("#dialog_note").html('');
			}
		}
	});

	// 进度框
	var progressTimer, progressbar = $("#progressbar"), progressLabel = $(".progress-label"), dialog_pgb = $(
			"#dialog_prograssbar").dialog({
		modal : true,
		autoOpen : false,
		closeOnEscape : false,
		resizable : false,
		width : '300px',
		show : {
			effect : "drop",
			duration : 300
		},
		hide : {
			effect : "drop",
			duration : 300
		},
		buttons : {
			Cancel : function() {
				$(this).dialog("close");
			}
		}
	});
	//
	progressbar.progressbar({
		value : false,
		change : function() {
			progressLabel.text("Current Progress: "
					+ progressbar.progressbar("value") + "%");
		},
		complete : function() {
			progressLabel.text("Complete!");
			dialog_pgb.dialog("option", "buttons", [ {
				text : "Close",
				click : closeDownload
			} ]);
			$(".ui-dialog button").last().focus();
		}
	});

});