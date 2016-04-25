$(document).ready(function() {
	// click button
	$('a.btn').click(function() {
		// get mobile
		var mobile = $('.tel').val();
		if (mobile == '') {
			alert('请输入手机号码');
			return false;
		}

		// mobile regex
		var mobile_reg = /^1[34578]{1}\d{9}$/;
		if (!mobile_reg.test(mobile)) {
			alert('请输入正确的手机号码');
			return false;
		}

		// post request
		var data = {
			uuid: $('#uuid').val(),
			mobile: mobile
		};
		$.post('/V3/share/record', data, function(result) {
			if (result.code == 1101) {
				alert('请输入正确的手机号码');
			} else if (result.code == 2601) {
				alert('分享链接有误');
			} else if (result.code == 2602) {
				alert('该手机号已注册口语聊，只有新用户才能领取红包，点击确定下载口语聊App');
			} else if (result.code == 2603 || result.code === 0) {
				alert('15元红包已放入您的帐号马上下载口语聊使用');
			}
		});
	});
});