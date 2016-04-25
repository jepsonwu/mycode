$(function(){
	// 下拉列表框
	if ( $("select").length ) {
		$("select").each(function(idx, dom) {
			var dom_width = $(dom).width();
			if ( dom_width <= 0 ) {
				dom_width = '120px';
			}
			else {
				dom_width = dom_width + 18 + "px";
			}
			$(dom).chosen({
				width : dom_width,
				disable_search_threshold : 20
			});
		});
	}
	
	/* 复选框 */
	// 全选
	$(".btn_all").click(function(){
		my_check_all( $(this) );
	});
	// 列表复选框点击
	$(".chkbox .checker").click(function(){
		my_checkbox_click( $(this), true );
	});
	// 表单复选框点击
	$("label .checker").click(function(){
		my_checkbox_click( $(this), false );
	});
	// 表单单选框点击
	$("label .radio").click(function(){
		my_radio_click( $(this) );
	});
	
	// 固定位日期控件
	for (i = 1; i <= 6; i++) {
			if ( $("#datepicker"+i)[0] ) {
			$("#datepicker"+i).datepicker({
				showAnim: 'slideDown',
				onClose: function(){ $("#datepicker"+i).focus(); }
			});
		} 
	}
	// ajax获取
	$(".a_get").click( function(){
		var url = $(this).attr('url');
		var param = anaParams( $(this).attr('param') );
		//
		my_ajax_get(url, param);
	});

});