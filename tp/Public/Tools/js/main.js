$(function(){
	//下拉框
	$('.dropdown-toggle').dropdown();
	//左边页面的高度
	var $leftMain = $(".left-main"),
		Wheight = $(window).height(),
		HeaderHeight = $(".header").height(),
		lmHeight = Wheight - HeaderHeight;
	$leftMain.css({
		height:lmHeight + 'px'
	});
	//菜单栏
	var $menu = $(".menu"),
		$menuLevel1 = $(".menu-level1"),
		_aLevel1List = $menuLevel1.find("a.level1-list"),
		_iIcon = _aLevel1List.find("i.ico");
	if(_iIcon.length < 0) return;
	_iIcon.on("click",function(){
		if($(this).hasClass("ico-down")){
			$(this).removeClass("ico-down");
			$(this).addClass("ico-up");
		}else if($(this).hasClass("ico-up")){
			$(this).removeClass("ico-up");
			$(this).addClass("ico-down");
		}
		$(this).parent().next(".nav").slideToggle();
	});
	//请求的方法
	var $method = $(".method");
	$method.each(function(){
		$(this).find(".dropdown-menu li").click(function(){
			var _aHtml = $(this).find("a").html();
			var _methodValue = $(this).parents(".method").find(".method-value");
			if(_methodValue.find("span.text").length == 1){
				_methodValue.find("span.text").text(_aHtml);
			}else{
				_methodValue.text(_aHtml);
			}
		})
	});
	//实时搜索
	$('#search-navigation').hideseek({
	  nodata: 'No results found',
	  navigation: true
	});
	//请求的信息tab
	var $postHeader = $(".post-header");
	$postHeader.find("li").click(function(){
		var index = $(this).index();
		$(this).addClass("on").siblings().removeClass("on");
		$(".post-contant").find("dd").eq(index).show().siblings().hide();
	});
	//table 自动增加行
	var trHtml = '<tr><td><input type="text" placeholder="Header"/></td><td><input type="text" placeholder="Value"/></td></tr>';
	var $table = $(".table");
	var flg = false;
	$table.each(function(){
		var _this = $(this);
		_this.keyup(function(){
			var value;
			_this.find("input").each(function(){
				value = $(this).val();
				if(value == ''){
					flg = false;
				}else{
					flg = true;
				}
			});
			if(flg){
				_this.append(trHtml);
			}
		});
	});
})
