$(function() {
	var obj_arr = new Array('#audio','#contents','#feedbacks','#answer','#audios','#content','#enContent','#classifications','#explanation','#chContent');
	var obj_arr2 = new Array('#title','#intro','#picture');
	
	function inArray(needle, haystack) {
	    var length = haystack.length;
	    for(var i = 0; i < length; i++) {
	        if(haystack[i] == needle) return true;
	    }
		return false;
	}
	
	// 元素的显示和隐藏
	function hideOrShow(arr) {
		$.each ( obj_arr, function( k, v) {
			if ( inArray (v, arr) ) {
				$(v).show();
			} else {
				$(v).hide();
			}
		});
	}
	
	// 元素的显示和隐藏
	function hideOrShow2(arr) {
		$.each ( obj_arr2, function( k, v) {
			if ( inArray (v, arr) ) {
				$(v).show();
			} else {
				$(v).hide();
			}
		});
	}
	
	function clearVal() {
		$('#_audio,#_contents,#_feedbacks,#_answer,#_audios,#_content,#_enContent,#_classifications,#_explanation,#_chContent').val('');
	}
	
	function clearVal2() {
		$('#_title,#_intro,#pic').val('');
	}
	
	function attrHandle(val) {
		switch (parseInt(val)) {
			case 1:
				$('#label_val').val('选图');
                $('#audio .tRight').html('题目音频：');
                $('#answer .tRight').html('正确答案：');
                $('#answer .tLeft').html('<select  name="answer">' +
                '<option value="A">A&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp</option>' +
                '<option value="B">B&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp</option>' +
                '<option value="C">C&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp</option>' +
                '<option value="D">D&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp</option>' +
                '</select>');
                $('#contents .tRight').html('选项图片：');
                $('#contents .tLeft').html('选项A：<input type="file" required="1" class="large" name="content_a" /><br/>' +
                '选项B：<input type="file" required="1" class="large" name="content_b" /><br/>' +
                '选项C：<input type="file" required="1" class="large" name="content_c" /><br/>' +
                '选项D：<input type="file" required="1" class="large" name="content_d" /><br/>');
                $('#feedbacks .tRight').html('反馈音频：');
                $('#picture,#type6,#type7').hide();
				hideOrShow(new Array('#audio','#contents','#feedbacks','#answer'));
				break;
			case 2:
                $('#label_val').val('连线');
                $('#classifications .tRight').html('内容1：');
                $('#contents .tRight').html('内容2：');
                $('#contents .tLeft').html('<textarea name="contents" id="_contents" class="area_60"></textarea>');
                $('#picture,#type6,#type7').hide();
				hideOrShow(new Array('#contents','#classifications'));
				break;
			case 3:
				$('#label_val').val('分类');
                $('#answer .tRight').html('答案：');
                $('#answer .tLeft').html('答案1&nbsp&nbsp：<input type="text" required="1" class="small" name="answer_0" /> ' +
                '答案2&nbsp&nbsp：<input type="text" required="1" class="small" name="answer_1" /> ' +
                '答案3&nbsp&nbsp：<input type="text" required="1" class="small" name="answer_2" /> ' +
                '答案4&nbsp&nbsp：<input type="text" required="1" class="small" name="answer_3" /><br/>');
                $('#contents .tRight').html('词组：');
                $('#contents .tLeft').html('组1&nbsp&nbsp：<input type="text" required="1" class="small" name="content_0" /> ' +
                '&nbsp&nbsp<input type="text" required="1" class="small" name="content_1" /> ' +
                '&nbsp&nbsp<input type="text" required="1" class="small" name="content_2" /> ' +
                '&nbsp&nbsp<input type="text" required="1" class="small" name="content_3" /><br/><br/>' +
                '组2&nbsp&nbsp：<input type="text" required="1" class="small" name="content_4" /> ' +
                '&nbsp&nbsp<input type="text" required="1" class="small" name="content_5" /> ' +
                '&nbsp&nbsp<input type="text" required="1" class="small" name="content_6" /> ' +
                '&nbsp&nbsp<input type="text" required="1" class="small" name="content_7" /><br/><br/>' +
                '组3&nbsp&nbsp：<input type="text" required="1" class="small" name="content_8" /> ' +
                '&nbsp&nbsp<input type="text" required="1" class="small" name="content_9" /> ' +
                '&nbsp&nbsp<input type="text" required="1" class="small" name="content_10" /> ' +
                '&nbsp&nbsp<input type="text" required="1" class="small" name="content_11" /><br/><br/>' +
                '组4&nbsp&nbsp：<input type="text" required="1" class="small" name="content_12" /> ' +
                '&nbsp&nbsp<input type="text" required="1" class="small" name="content_13" /> ' +
                '&nbsp&nbsp<input type="text" required="1" class="small" name="content_14" /> ' +
                '&nbsp&nbsp<input type="text" required="1" class="small" name="content_15" />');
                $('#picture .tRight').html('内容图片：');
                $('#picture .tLeft').html('<input type="file" class="large" id="pic" name="picture" required="1" />');
                $('#picture').show();
                $('#type6,#type7').hide();
				hideOrShow(new Array('#contents','#answer'));
				break;
			case 4:
				$('#label_val').val('短对话');
                $('#audios .tRight').html('音频：');
                $('#contents .tRight').html('内容：');
                $('#contents .tLeft').html('对话1：<input type="text" required="1" class="large" name="content_1" /><br/>' +
                '对话2：<input type="text" required="1" class="large" name="content_2" /><br/>');
                $('#picture .tRight').html('头像：');
                $('#picture .tLeft').html('老师1：<input type="file" required="1" class="large" name="pic1" /><br/>' +
                '老师2：<input type="file" required="1" class="large" name="pic2" /><br/>');
                $('#picture').show();
                $('#type6,#type7').hide();
                $('#audios_area').html('<input type="file" required="1" class="large"  name="audio1" /><br/> ' +
                '<input type="file" required="1" class="large"  name="audio2" />');
				hideOrShow(new Array('#audios','#contents'));
				// \n 换行符一定要双引号
				break;
			case 5:
				$('#label_val').val('造句');
                $('#audios .tRight').html('题目音频：');
                $('#contents .tRight').html('断句内容：');
                $('#contents .tLeft').html('<textarea name="contents" id="_contents" class="area_60"></textarea>');
                $('#enContent .tRight').html('英文句子：');
                $('#chContent .tRight').html('中文句子：');
                $('#picture,#type6,#type7').hide();
				hideOrShow(new Array('#audio','#enContent','#chContent','#contents'));
				break;
			case 6:
				$('#label_val').val('填空');
                $('#audio .tRight').html('题目音频：');
                $('#explanation .tRight').html('单词/反馈：');
                $('#explanation .tLeft').html('');
                //$('#answer .tRight').html('空格单词/重点单词：');
                $('#answer .tLeft').html('');
                $('#enContent .tRight').html('英文句子：');
                $('#chContent .tRight').html('中文句子：');
                $('#picture,#type7').hide();
                $('#type6').show();
				hideOrShow(new Array('#audio','#enContent','#chContent','#explanation'));
				break;
			case 7:
				$('#label_val').val('长对话');
                //$('#audios .tRight').html('音频：');
                $('#contents .tRight').html('文本/音频：');
                $('#contents .tLeft').html('<div id="contents_area"></div>');
                $('#picture .tRight').html('头像：');
                $('#picture .tLeft').html('老师1：<input type="file" required="1" class="large" name="pic1" /><br/>' +
                '老师2：<input type="file" required="1" class="large" name="pic2" /><br/>');
                $('#picture,#type7').show();
                $('#audios_area').html('');
                $('#type6').hide();
				hideOrShow(new Array('#contents'));
				break;
			case 8:
				$('#label_val').val('跟读');
                $('#audio .tRight').html('题目音频：');
                $('#content .tRight').html('句子：');
                $('#explanation .tRight').html('句子解释：');
                $('#picture,#type6,#type7').hide();
				hideOrShow(new Array('#audio','#content','#explanation'));
				break;
			default:
				break;
		}
	}
	
	function attrHandle2(val) {
		switch (parseInt(val)) {
            case 1:
                $('#title .tRight').html('单词：');
                $('#intro .tRight').html('释义：');
                $('#picture .tRight').html('图片：');
                $('input[name="time"]').val('');
                $('input[name="time"]').attr("disabled",false);
                $('#picture .tLeft').html('<input type="file" class="large" id="pic" name="picture" required="1" />');
                $('#title,#intro,#picture').show();
                $("#folder_name,#etype,#label,#guide,#guide,#audio,#audios,#content,#classifications,#contents,#explanation,#feedbacks,#answer,#enContent,#chContent").hide();
                break;
			case 2:
				$('#title,#intro').hide();
                $('input[name="time"]').val('');
                $('input[name="time"]').attr("disabled",false);
                $("#folder_name,#etype,#label,#guide,#guide,#audio,#audios,#content,#classifications,#contents,#explanation,#feedbacks,#answer,#enContent,#chContent").show();
                var this_val = $(".frame_add_form select[name='etype']").val();
                attrHandle(this_val);
				break;
            case 3:
                $('#title .tRight').html('标题：');
                $('#intro .tRight').html('简介：');
                $('#picture .tRight').html('图片：');
                $('#picture .tLeft').html('<input type="file" class="large" id="pic" name="picture" required="1" />');
                $('#title,#intro,#picture').show();
                $('#picture .tRight').html('图片：');
                $('input[name="time"]').val(0);
                $('input[name="time"]').attr("disabled",true);
                $("#folder_name,#etype,#label,#guide,#guide,#audio,#audios,#content,#classifications,#contents,#explanation,#feedbacks,#answer,#enContent,#chContent").hide();
                break;
            default :
                break;
		}
	}
	
	// 新增练习题控制元素的显示和隐藏
	$('.ex_add_form select[name="type"]').on('change', function() {
		clearVal(); // 清除之前填入的值
		var this_val = $(this).val();
		attrHandle(this_val);
	});

    // 长对话时添加新的音频上传位和文本位
    $('#audios_add').click(function() {
        var num = $('#contents_area').children('input').length/2;
        if(num>=0&&num<20) {
            $('#contents_area').append('对话' + (num + 1) + '：<input type="text" class="large"  name="contents' + num + '" />' +
            '<input type="file" class="large"  name="audios' + num + '" /><br/>');
        }else{
            alert('最多新增20个哦');
        }
    });

    // 长对话时清除音频上传位和文本位
    $('#audios_clear').click(function() {
        $('#audios_default_area,#contents_area').html('');
    });

    // 填空题时添加新的填空和反馈文本位
    $('#type6_add').click(function() {
        var num = $('#explanation .tLeft').children('input').length/2;
        if(num>=0&&num<4) {
            $('#explanation .tLeft').append('单词' + (num + 1) + '：<input type="text" class="large"  name="answer_' + num + '" />' +
            '反馈' + (num + 1) + '：<input type="text" class="large"  name="explanation_' + num + '" /><br/>');
        }else{
            alert('最多新增4个哦');
        }
    });

    // 填空题时清除新的填空和反馈文本位
    $('#type6_clear').click(function() {
        $('#explanation .tLeft').html('');
    });
	
	// 新增练习题控制元素的显示和隐藏
	if ($('.ex_add_form') ) {
		var this_val = $('.ex_add_form #type').val();
		attrHandle(this_val);
	}



    // 合并后新增练习题type选择
    $('.frame_add_form select[name="etype"]').on('change', function() {
        clearVal(); // 清除之前填入的值
        var this_val = $(this).val();
        attrHandle(this_val);
    });

	
	// 编辑练习题控制元素的显示和隐藏
	if ($('.ex_edit_form') ) {
		var obj_val = $('.ex_edit_form #now_type').val();
		attrHandle(obj_val);
	}
	
	// 新增frame控制元素的显示和隐藏
	$('.frame_add_form select[name="type"]').on('change', function() {
		clearVal2(); // 清除之前填入的值
		var this_val = $(this).val();
		attrHandle2(this_val);
	});
	
	// 新增frame控制元素的显示和隐藏
	if ($('.frame_add_form') ) {
		var this_val = $('.frame_add_form select[name="type"] option:selected').val();

		attrHandle2(this_val);
	}

    //合并后引导音可选
    $('#is_need_guide').click(function() {
        if($('#is_need_guide').is(':checked')) {
            $('#_guide').show();
        }else{
            $('#_guide').hide();
        }
    });

	
	 //编辑frame控制元素的显示和隐藏
	if ($('.frame_edit_form') ) {
        if($('#is_need_guide').is(':checked')) {
            $('#_guide').show();
        }else{
            $('#_guide').hide();
        }
	}
	
	//
	$('#folder_name').keyup(function() {
		$('#guide input').val('exercises/' + $("#folder_name").val() + '/guide.mp3');
		var this_val = $('.ex_add_form #type').val();
		switch (parseInt(val)) {
		case 1:
			$('#audio input').val('exercises/' + $("#folder_name").val() + '/audio.mp3');
			break;
		case 2:
			break;
		case 3:
			$('#pic input').val('exercises/' + $("#folder_name").val() + '/pic.jpg');
			break;
		case 4:
			// \n 换行符一定要双引号
			$('#audios #_audios').val("exercises/" + $("#folder_name").val() + "/audio1.mp3\nexercises/" + $("#folder_name").val() + "/audio2.mp3");
			break;
		case 5:
			$('#audio input').val('exercises/' + $("#folder_name").val() + '/audio.mp3');
			break;
		case 6:
			$('#audio input').val('exercises/' + $("#folder_name").val() + '/audio.mp3');
			break;
		case 7:
			$('#audios #_audios').val("exercises/" + $("#folder_name").val() + "/audio2.mp3\nexercises/" + $("#folder_name").val() + "/audio1.mp3\nexercises/" + $("#folder_name").val() + "/audio3.mp3\nexercises/" + $("#folder_name").val() + "/audio4.mp3");
			break;
		case 8:
			$('#audio input').val('exercises/' + $("#folder_name").val() + '/audio.mp3');
			break;
		default:
			break;
	}
	});
	
});