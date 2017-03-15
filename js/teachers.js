function loadSchedule(surname) {
	setLoader(true);
	type = $('#type>.active').attr('data-target');
	if (type == 'short') {
		data = {'surname': surname, 'short': ''};
	} else {
		data = {'surname': surname};
	}
	$.ajax({
		url: 'api/index.php',
		data: data,
		success: function (data) {
			var response = $.parseJSON(data);

			if (response.hasOwnProperty('error')) {
				$('#container').html('');
				$('.ui.segment.week').hide();
				$('#error').html(response.error);
				$('.ui.segment.error').show();
				setLoader(false);
				$('button.bugreport').prop('disabled', true);
				return;
			} else {
				$('.ui.segment.week').show();
				$('.ui.segment.error').hide();
				$('button.bugreport').prop('disabled', false);
			}

			if (response.lowWeek) {
				$('#weekType').html('нижняя');
			} else {
				$('#weekType').html('верхняя');
			}

			//TODO: implement important errors handling
			if (type == 'short') {
				parserShort(response)
			} else {
				parserFull(response);
			}
		}
	});
}

$('#teachers').change(function() {
	loadSchedule($(this).val());
	$.cookie('surname', $(this).val(), {expires: 365});
});

loadSchedule($('#teachers').val());