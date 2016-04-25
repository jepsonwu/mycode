$(function(){
	
	// 头部操作按钮
	$("#div_operater input").click(function(){
		var url = $(this).attr("url");
		var ids = '';
		var param = '';
		if ( $(this).attr('param') != undefined ) {
			param = $(this).attr('param');
		}
		//
		switch ( $(this).attr('id') ) {
			case 'btn_moveto':	// 转移
				ids = get_chekced_ids();
				if ( ids == '' ) {
					my_show_dialog('请选择需操作的记录！');
				}
				else {
					param = '/id/' + ids;
					my_show_dialog_confirm( "<div style='height:150px;'>转移到：<select name='cat_id' id='moveto' class='cfm_param'>"+$("#frm_sch select[name='cat_id']").html()+"</select></div>", url, param );
					$("#moveto").children("option").eq(0).remove();
					var my_width = $("#moveto").width() + 18 + "px";
					$("#moveto").chosen({width:my_width, disable_search_threshold:20});
				}
				break;
			default:
				ids = get_chekced_ids();
				if ( ids == '' ) {
					my_show_dialog('请选择需操作的记录！');
				}
				else {
					var act = $(this).val().replace(' ', '');
					param = '/id/' + ids;
					my_show_dialog_confirm( '确定要'+act+'么？', url, param );
				}
				break;
		}
	});
	
	// 当前查询参数
	if ($("#frm_sch")[0]) {
		$("#frm_sch input[name='order']").val(sch_prams.order);
		$("#frm_sch input[name='sort']").val(sch_prams.sort);
	}

	// 排序图标
	$("table.list .img_sort").each(function() {
		if (sch_prams.order != undefined) {
			if ($(this).parent('a').attr('name') == sch_prams.order) {
				if (sch_prams.sort == 'asc') {
					$(this).css("background-position", "left -215px");
				} else {
					$(this).css("background-position", "-9px -215px");
				}
			}
		}
	});
	
	// 按栏目排序
	$(".list thead td.title a,.list thead th.title a").click(function() {
		var url = CONTROLLER + "/index";
		sch_prams.p = 1;
		var order_old = sch_prams.order;
		var sort_old = sch_prams.sort;
		//
		sch_prams.order = $(this).attr('name');
		sch_prams.sort = 'asc';
		if (order_old == $(this).attr('name') && sort_old == 'asc') {
			sch_prams.sort = 'desc';
		}
		//
		window.location.href = url + json_to_param_str(sch_prams);
	});
	
	/* 快速编辑 */
	if ( $('#quick_datepicker')[0] ) {
		$('#quick_datepicker').datepicker({
			showAnim: 'slideDown',
			onClose: function(){ $('#quick_datepicker').focus(); }
		});
	}
	//
	if ( $('#quick_timepicker')[0] ) {
		$('#quick_timepicker').datetimepicker({
			showAnim: 'slideDown',
			onClose: function(){ $('#quick_timepicker').focus(); }
		});
	}
	// 弹出快速编辑框
	$("tbody tr td").dblclick(function(){
		// 文本
		if ( $(this).attr('aqe')=='1' ) {
			$('.td_qe_now').removeClass('td_qe_now');
			$(this).addClass('td_qe_now');
			$("#quick_edit").val($(this).text());
			var leftNow = $(this).offset().left,
				topNow = $(this).offset().top + $('document').scrollTop();
			$("#quick_edit").css( { 'left':leftNow,'top':topNow,'width':$(this).width()+3,'height':$(this).height()+3,'display':'block'} );
			$("#quick_edit").focus();
		}
		// 日期
		if ( $(this).attr('aqdpk')=='1' ) {
			$('.td_qdpk_now').removeClass('td_qdpk_now');
			$(this).addClass('td_qdpk_now');
			$('#quick_datepicker').val($(this).text());
			var leftNow = $(this).offset().left,
				topNow = $(this).offset().top + $('document').scrollTop();
			$('#quick_datepicker').css( { 'left':leftNow,'top':topNow,'width':$(this).width()+3,'height':$(this).height()+3,'display':'block'} );
			$('#quick_datepicker').focus();
		}
		// 时间
		if ( $(this).attr('aqtpk')=='1' ) {
			$('.td_qtpk_now').removeClass('td_qtpk_now');
			$(this).addClass('td_qtpk_now');
			$('#quick_timepicker').val($(this).text());
			var leftNow = $(this).offset().left,
				topNow = $(this).offset().top + $('document').scrollTop();
			$('#quick_timepicker').css( { 'left':leftNow,'top':topNow,'width':$(this).width()+3,'height':$(this).height()+3,'display':'block'} );
			$('#quick_timepicker').focus();
		}
	});
	// 保存快速编辑文本
	$("#quick_edit").keydown(function(event){
		// 回车键提交
		if ( event.keyCode == 13 ) {
			var tdNow = $(".td_qe_now");
			var url = CONTROLLER + "/quickUpdateText/";
			var param = {};
			param['id'] = tdNow.attr('idnow');
			param[tdNow.attr('colm')] = $(this).val();
			my_ajax_sub(url, param);
		}
		// Esc键取消
		if ( event.keyCode == 27 ) {
			$(".td_qe_now").removeClass('td_qe_now');
			$(this).val('');
			$(this).css("display","none");
		}
	});
	// 保存快速编辑日期
	$('#quick_datepicker').keydown(function(event){
		// 回车键提交
		if ( event.keyCode == 13 ) {
			var tdNow = $(".td_qdpk_now");
			var url = CONTROLLER + "/quickUpdateDate/";
			var param = {};
			param['id'] = tdNow.attr('idnow');
			param[tdNow.attr('colm')] = $(this).val();
			my_ajax_sub(url, param);
		}
		// Esc键取消
		if ( event.keyCode == 27 ) {
			$(".td_qdpk_now").removeClass('td_qdpk_now');
			$(this).val('');
			$(this).css("display","none");
		}
	});
	// 保存快速编辑时间
	$('#quick_timepicker').keydown(function(event){
		// 回车键提交
		if ( event.keyCode == 13 ) {
			var tdNow = $(".td_qtpk_now");
			var url = CONTROLLER + "/quickUpdateDate/";
			var param = {};
			param['id'] = tdNow.attr('idnow');
			param[tdNow.attr('colm')] = $(this).val();
			my_ajax_sub(url, param);
		}
		// Esc键取消
		if ( event.keyCode == 27 ) {
			$(".td_qtpk_now").removeClass('td_qtpk_now');
			$(this).val('');
			$(this).css("display","none");
		}
	});
	
	// 图片展示
	if ( $(".fancybox")[0] ) {
		//
		$(".fancybox").fancybox({
			fitToView : false,
			autoSize	 : true,
			closeClick : false,
			closeEffect : 'none'
		});
	}
	
	// 弹出明细
	$("td a.pop").click(function(){
		my_show_dialog($(this).attr('cnt'));
	});
	
	// 鼠标经过变色
//	$("table.list tr").mouseover(function(){
//		if ( !$(this).hasClass('checked') ) {
//			$(this).css('background','#DDEEFF');
//		}
//	});
	//
//	$("table.list tr").mouseout(function(){
//		if ( !$(this).hasClass('checked') ) {
//			$(this).css('background','#FFFFFF');
//		}
//	});
	
	// 表格内链接（提交）
	$(".list tbody td a.a_sub").click(function(){
		var url = $(this).attr('url');
		var param = {};
		if ( $(this).attr('param') != undefined ) {
			param = anaParams($(this).attr('param'));
		}
		my_ajax_sub( url, param );
	});
	
	// 表格内链接（确认）
	$(".list tbody td a.a_confirm").click(function(){
		var msg = $(this).attr('msg');
		var url = $(this).attr('url');
		var param = $(this).attr('param');
		my_show_dialog_confirm(msg, url, param);
	});

	// 表格内链接(Note)
	$(".list tbody td a.a_note").click(function(){
		var url = $(this).attr('url');
		var name = $(this).attr('name');
		my_show_dialog_note(url, name);
	});

	$(".list tbody td a.a_img_show").click(function(){
		var url = $(this).attr('url');
		my_show_dialog_img(url);
	});
	
	/* 翻页 */
	// 页码单击
	$("#page a").click(function(){
		var url = CONTROLLER + "/index";
		sch_prams.p = $(this).attr('p');
		window.location.href = url + json_to_param_str(sch_prams);
	});
	// 直达某页
	$(".pg_right input").keydown(function(event){
		if ( event.keyCode == 13 ) {
			var url = CONTROLLER + "/index";
			sch_prams.p = $(this).val();
			window.location.href = url + json_to_param_str(sch_prams);
		}
	});
	// 每页记录条数
	$(".pg_left input").keydown(function(event){
		if ( event.keyCode == 13 ) {
			var url = CONTROLLER + "/index";
			sch_prams.p = 1;
			sch_prams.listRows = $(this).val();
			window.location.href = url + json_to_param_str(sch_prams);
		}
	});

});