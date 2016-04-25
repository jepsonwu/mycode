	/**
	 * 更改单位长度
	 */
	var SIZE = document.body.clientWidth * 40/960;
	SIZE = SIZE+"px";
	document.getElementsByTagName("html")[0].style.fontSize=SIZE;

	/**
	 * 客户端检测并显示apple store 或者 google play
	 */
	function IsIphone() {
		var userAgentInfo = navigator.userAgent;
		var Agents = ["iPhone","iPad", "iPod"];
		var flag = false;
		for (var v = 0; v < Agents.length; v++) {
			if (userAgentInfo.indexOf(Agents[v]) > 0) {
				flag = true;
				break;
			}
		}
		return flag;
	}
	if(IsIphone()){
		appleStore = document.getElementsByClassName('store')[0].style.display='block';
		appleStore = document.getElementsByClassName('store')[1].style.display='none';
	}else{
		appleStore = document.getElementsByClassName('store')[0].style.display='none';
		appleStore = document.getElementsByClassName('store')[1].style.display='block';
	}

	/**
	 * 添加about－us
	 */
	 var s = document.getElementsByClassName('store')[0];
	 console.log(s);
	 s.onclick = function(){
	 	window.location.href="https://appsto.re/cn/GbP_8.i";
	 }
	 var t = document.getElementsByClassName('store')[1];
	 t.onclick = function(){
		window.location.href="https://play.google.com/store/apps/details?id=com.abc360.coolchat";
	 }