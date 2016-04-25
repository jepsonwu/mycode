$(function(){
	// jquery.form
	var options_bd = {
        beforeSubmit:  showRequest,  // pre-submit callback
        success:       showResponse_bd	 // post-submit callback
    };
	var options_fj = {
	    beforeSubmit:  showRequest,  // pre-submit callback
	    success:       showResponse_fj	 // post-submit callback
	};
	// pre-submit callback
	function showRequest(formData, jqForm, options) {
	    var queryString = $.param(formData);
//	    alert('About to submit: \n\n' + queryString);
	    return true;
	}
	// post-submit callback 表单
	function showResponse_bd(responseText, statusText, xhr, $form)  {
		//
		if ( responseText.status ) {
			if ( responseText.info != '' && responseText.info != undefined ) {
				alert(responseText.info);
			}
			else {
				if ( responseText.url != undefined ) {
					window.location.href = responseText.url;
				}
			}
		}
		else {
			alert(responseText.info);
		}
	}
	// post-submit callback 附件
	function showResponse_fj(responseText, statusText, xhr, $form) {
		//
		var result = responseText.split('|');
		//
		if ( result[0] ) {
			//
			var idNow = result[2];
			var picNow = result[3];
			$("#"+idNow).val(picNow);
			$("#file_"+idNow).val('');
			$("#file_"+idNow).blur();
			if ( $("#img_"+idNow)[0] ) {	// 图片
				$("#img_"+idNow).attr("src", UPLOAD+"/"+$(".file_frm input[name='folder']").val()+"/"+picNow).css("display", "inline-block");
			}
		}
		else {
			alert(result[1]);
		}
	}

	// 验证
	$("#frm1").validate({
		errorPlacement: function(error, element) { 
		    error.appendTo( element.parents('td').next() ); 
		},
		submitHandler:function(form) { 
			$(form).ajaxSubmit(options_bd);
			return false;
		}
	});
	
	// ajax图文上传
	if ( $(":file")[0] ) {
		// ajax提交
		$(".file_frm").submit(function() {
			//
			$(this).ajaxSubmit(options_fj); 
			return false;
		});
		// 自动提交
		$(":file").change(function(){
	    	if ( $(this).val() != '' ) {
	    		$(this).parent(".file_frm").submit();
	    	}
	    });
	}
	
	// 提交
	$("#btn_submit").click(function(){
		$("#frm1").submit();
	});


});