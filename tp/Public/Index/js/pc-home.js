var lead1 = document.getElementById('lead1');
var lead2 = document.getElementById('lead2');
var lead3 = document.getElementById('lead3');
var lead4 = document.getElementById('lead4');
var leadCanvas = document.getElementById('lead-canvas');
var applyNow = document.getElementById('apply-now');
var applyContent = document.getElementsByClassName('.content');


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
// 全部引导界面隐藏
function hideAll() {
	lead1.style.display = 'none';
	lead2.style.display = 'none';
	lead3.style.display = 'none';
	lead4.style.display = 'none';
	leadCanvas.style.display = 'none';
}

// 点击其他阴影部分，返回默认页面状态，隐藏所有引导页面
// 通过实践委托的方法判断用户点击的是空白区域还是内容区域，如果是内容区域，那么不全部隐藏，如果是空白区域，则全部隐藏
// 刚刚学了事件委托就来炫技的感觉真爽
addEvent(lead1, 'click', function(event) {
	var event = event || window.event;
	console.log(event.target);
	if ((event.target.id === "lead1") || (event.target.id === "lead2") || (event.target.id === "lead3") || (event.target.id === "lead4")) {
		hideAll();
	}
});
addEvent(lead2, 'click', function(event) {
	var event = event || window.event;
	console.log(event.target);
	if ((event.target.id === "lead1") || (event.target.id === "lead2") || (event.target.id === "lead3") || (event.target.id === "lead4")) {
		hideAll();
	}
});
addEvent(lead3, 'click', function(event) {
	var event = event || window.event;
	console.log(event.target);
	if ((event.target.id === "lead1") || (event.target.id === "lead2") || (event.target.id === "lead3") || (event.target.id === "lead4")) {
		hideAll();
	}
});
addEvent(lead4, 'click', function(event) {
	var event = event || window.event;
	console.log(event.target);
	if ((event.target.id === "lead1") || (event.target.id === "lead2") || (event.target.id === "lead3") || (event.target.id === "lead4")) {
		hideAll();
	}
});

// 跳转到关于我们
var aboutUs = document.getElementById('about-us');
addEvent(aboutUs,'click',function(){
	window.location.href="about-us.html";
})


// 添加点击事件,引导第一步
addEvent(applyNow, 'click', function(event) {
	leadCanvas.style.display = 'block';
	lead1.style.display = 'block';
})

// 让引导界面居中显示
var bodyHeight = document.body.clientHeight;
var marginTop = (bodyHeight - 653) / 2 + 'px';
console.log(bodyHeight);
var leadContent = document.getElementsByClassName('lead-content');
for (var i = 0; i < leadContent.length; i++) {
	leadContent[i].style.marginTop = marginTop;
}


// 点击右，到下一步
var stepRight = document.getElementsByClassName('step-right');
addEvent(stepRight[0], 'click', function() {
	lead2.style.display = "block";
});
addEvent(stepRight[1], 'click', function() {
	lead3.style.display = "block";
});
addEvent(stepRight[2], 'click', function() {
	lead4.style.display = "block";
});

// 点击左，到上一步
var stepLeft = document.getElementsByClassName('step-left');
addEvent(stepLeft[2], 'click', function() {
	lead4.style.display = "none";
});
addEvent(stepLeft[1], 'click', function() {
	lead3.style.display = "none";
});
addEvent(stepLeft[0], 'click', function() {
	lead2.style.display = "none";
});