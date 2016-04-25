/**
 * ajax提交
 * @param url 提交地址
 * @param params 参数json
 */
function my_ajax_post(url, params) {
	$.post(url, params, function(rtn) {
		if (rtn.info != '' && rtn.info != undefined) {
			alert(rtn.info);
		}
		if (rtn.url != '' && rtn.url != undefined) {
			window.location.href = rtn.url;
		}
		switch (rtn.act) {
		case 'reload':
			window.location.reload();
			break;
		case 'top_reload':
			window.top.location.reload();
			break;
		case 'fancy_close':
			$(".fancybox-overlay").trigger('click');
			break;
		case 'close':
			myClose();
			break;
		case 'opener_reload_close':
			window.opener.location.reload();
			myClose();
			break;
		}
	});
}

/**
 * 无提示关闭本页
 */
function myClose() {
	var ua = navigator.userAgent;
	var ie = navigator.appName == "Microsoft Internet Explorer" ? true : false;
	if (ie) {
		var IEversion = parseFloat(ua.substring(ua.indexOf("MSIE ") + 5, ua.indexOf(";", ua.indexOf("MSIE "))));
		if (IEversion < 5.5) {
			var str = '<object id=noTipClose classid="clsid:ADB880A6-D8FF-11CF-9377-00AA003B7A11">';
			str += '<param name="Command" value="Close"></object>';
			document.body.insertAdjacentHTML("beforeEnd", str);
			document.all.noTipClose.Click();
		} else {
			window.opener = null;
			window.open('', '_self', '');// for IE7
			window.close();
		}
	} else {
		window.close();
	}
}

function dateAdd( date, days ) {
	var dateVal = Date.parse(date) + days * 86400000;
	var dateNow = new Date( dateVal );
	var dateStr = dateNow.getFullYear() + '-' + (dateNow.getMonth()+1) + '-' + dateNow.getDate();
	return dateStr;
}


/**
 * 页面载入后
 */
$(function() {
	
	/**
	 * tab切换
	 */
	var tab_dd = $("div.tab_c_nav dl dd");
	tab_dd.click(function() {
		$(this).addClass("tab_light").siblings().removeClass("tab_light");
		var index = tab_dd.index(this);
		$("div.tab_c_box > div").eq(index).show().siblings().hide();
	}).hover(function() {
		$(this).addClass("tab_hover");
	}, function() {
		$(this).removeClass("tab_hover");
	});
	
	/**
	 * 下拉列表框
	 */
	$('.search_select').each(function(index, domEle) {
		$(domEle).selectbox({
			onChangeCallback : myFunction
		});
	});
	function myFunction(args) {
		// alert(args.selectedVal);
	}
	
});
