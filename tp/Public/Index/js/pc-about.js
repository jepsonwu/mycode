// 添加事件函数，酷炫的做好了兼容和重写以提升性能
function addEvent(obj, type, fn) {
	if (obj.attachEvent) {
		obj.attachEvent(type, fn);
		addEvent = function(obj, type, fn) {
			obj.attachEvent(fn);
		}
	} else {
		addEvent = function(obj, type, fn) {
			obj.addEventListener(type, fn);
		}
		obj.addEventListener(type, fn)
	}
}

// 添加关于我们的跳转
var aboutUs = document.getElementById('about-us');
addEvent(aboutUs,'click',function(){
	window.location.href="about-us.html";
})