(function($) {

	// json container
	var container = document.getElementById('jsoneditor');
	// json options
	var options = {
		mode: 'code',
		modes: ['code','text', 'view'],
		error: function (err) {
			alert(err.toString());
		}
	};
	// default value
	var json = {
		"Array":[1,2,3],
		'Boolean':true,
		'Null':null,
		'Number':123,
		'object':{'a':'b','c':'d'},
		'string':'Hello World'
	}
	// set json
	var editor = new JSONEditor(container, options);
	editor.set(json);
	
	// select interface
	$('.menu-level2>li>a').click(function() {
		var input = $(this).parent('li').next('input');
		var interface_info = {
			url : input.attr('url'),
			param : input.attr('param'),
			method : input.attr('method'),
			comment : input.attr('comment')
		};
		var stop = false;

		// check interface info
		$.each(interface_info, function(index, element) {
			if(!element && index != 'param') {
				alert(index + ' is invalid.');
				stop = true;
				return false;
			}
		});
		if(stop) return false;

		// fill interface info
		$('.infor-html>p').text(interface_info.comment); // comment
		$('button.method-value>span.text').text(interface_info.method); // method
		$('input.location').val(interface_info.url); // url

		console.log(interface_info.param);

		// param
		$('dl.post-contant dd:eq(1)').find('tr').remove();
		if(!interface_info.param) {
			$('dl.post-contant dd:eq(1) table').append("<tr><td><input type='text' placeholder='Key' /></td>" +
				"<td><input type='text' placeholder='Value'/></td></tr>");
		} else {
			var param_arr = interface_info.param.split(',');
			for(var i=0; i<param_arr.length; i++) {
				$('dl.post-contant dd:eq(1)').find('table').append("<tr><td><input type='text' value=" + param_arr[i] + " /></td>" +
					"<td><input type='text' placeholder='Value'/></td></tr>");
			}
		}
	});

	// add param list
	$('dl.post-contant dd:eq(1) table').on('focus', 'td:eq(-2)>input', function() {
		$('dl.post-contant dd:eq(1) table').append("<tr><td><input type='text' placeholder='Key' /></td>" +
			"<td><input type='text' placeholder='Value'/></td></tr>");
	});

	// click send button
	$('#send_button').click(function() {
		// achieve interface info
		var url = $('input.location').val();
		var method = $('button.method-value>span.text').text();
		// check url
		if(!url) {
			alert('please select a interface.');
			return false;
		}
		// get param
		var param = {};
		$('dl.post-contant dd:eq(1) table tr').each(function(index, element) {
			var key = $(this).find('td:eq(0)>input').val();
			if(key) param[key] = $(this).find('td:last>input').val();
		});

		console.log(editor.get());

		// send request
		$.ajax({
			url: url,
			type: method,
			data: param,
			dataType: 'json',
			success: function(data) {
				editor.set(data);
			},
			error: function(xhr, textStatus, err) {
				editor.set('http status code : ' + xhr.status);
			}
		});
	});

})(jQuery)
