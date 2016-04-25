// 获取高度
function resizeMain() {
	var doc_height = document.documentElement.clientHeight;
	var header_height = $("div.header").height();
	var footer_height = $("div.footer").height() + 16;
	var main_height = doc_height - header_height - footer_height - 6;
	//
	$("div.lefter").css('height', main_height);
	$("div.main").css('height', main_height);
	$("#ifrm_main").css("height", main_height);
}

$(function() {

	//
	// $(".main").css("height", mainHeight+"px");
	// $(".lefter_area").css("height", mainHeight+"px");
	// $(".handle_bar").css("height", mainHeight+"px");
	
	// 设置高度
	$(window).resize(function() {
		resizeMain();
	});
	
	// 
	resizeMain();

	// 退出后台
	$("#btn_logout").click(function() {
		my_show_dialog_confirm("确定要退出后台么？", null, null);
	});
	
	// 菜单点击
	$(".menu_item").click(function() {
		//
		$(".lefter .cur").removeClass("cur");
		$(this).addClass("cur");
	});

	// 设置左栏拉手
	function setHandleBar() {
		if ($(".lefter").is(":visible"))
			$(".handle_bar").css("background-position", "right 230px").attr(
					"title", "收起左栏菜单");
		else
			$(".handle_bar").css("background-position", "-488px 230px").attr(
					"title", "打开左栏菜单");
	}

	// 左栏菜单隐现
	$(".handle_bar").click(function() {
		//
		if ($(".lefter").is(":visible")) {
			$(".lefter_area").animate({
				width : "1px"
			}, 300);
			$(".lefter").hide('slide', null, 300, setContentWidth);
		} else {
			$(".content").animate({
				width : contentWidth1 + "px"
			}, 300, function() {
				$(".lefter_area").animate({
					width : "200px"
				}, 300);
				$(".lefter").show('slide', null, 300, setHandleBar);
			});
		}
	});

	// 菜单滑动
	$("#accordion").accordion({
		header : "> div > h3",
		collapsible: true,
//		heightStyle: "content"
		heightStyle: "fill"
	}).sortable({
		axis : "y",
		handle : "h3",
		stop : function(event, ui) {
			ui.item.children("h3").triggerHandler("focusout");
		}
	});
	
	// 刷新后打开当前菜单
	if ( !$(".cur:visible")[0] ) {
		$(".cur").parents("div.group").children("h3").trigger("click");
	}

});