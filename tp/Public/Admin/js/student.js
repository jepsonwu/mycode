$(function() {	
	if ( $("#btn_reset_pwd")[0] ) {
		// 重置密码
		$("#btn_reset_pwd").click(function(){
			var obj = $("input[name='resetPwd']");
			var resetPwd = $.trim( obj.val() );
			if ( resetPwd == '' ) {
				my_show_dialog("请输入新密码！");
				return false;
			}
			var param = { 'password':resetPwd,'id':$("input[name='id']").val() };
			my_ajax_sub( $(this).attr("func"), param );
		});
		
		// 捕捉回车
		$("input[name='resetPwd']").keydown(function(event){
			if( event.keyCode == 13 ) {
				$("#btn_reset_pwd").trigger("click");
			}
		});
	}

});