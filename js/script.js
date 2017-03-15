function loadSchedule(group) {
	setLoader(true);
	type = $('#type>.active').attr('data-target');
	if (type == 'short') {
		data = {'group': group, 'short': ''};
	} else {
		data = {'group': group};
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
				$('#weekType').html('Нижняя');
			} else {
				$('#weekType').html('Верхняя');
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

function loadGroups(course) {
	setLoader(true);
	$.ajax({
		url: 'api/groups.php',
		data: 'course=' + course,
		success: function(data) {
			var groups = $.parseJSON(data);
			var defaultGroup = $.cookie('group');
			if ($.inArray(defaultGroup, groups) < 0) {
				defaultGroup = groups[0];
				$.cookie('group', defaultGroup, {expires: 365});
			}
			groupsSelect = $('#groups');
			groupsSelect.html('');
			for (var i = 0; i < groups.length; i++) {
				groupsSelect.append('<option value="' + groups[i] + '">' + groups[i] + '</option>');
			}
			$('.dropdown.groups').dropdown('refresh');
			setTimeout(function () {
				$('.dropdown.groups').dropdown('set selected', defaultGroup);
			}, 1);
			loadSchedule(defaultGroup);
		}
	});
}

if ($.cookie('warining') == undefined) {
	$('.ui.basic.modal').modal({
		onVisible: function () {
			$('button.bugreport').addClass('notop');
		},
		onHidden: function () {
			$.cookie('warining', true, {expires: 365});
			$('button.bugreport').removeClass('notop');
		}
	}).modal('show');
}

$('#groups').change(function() {
	loadSchedule($(this).val());
	$.cookie('group', $(this).val(), {expires: 365});
});
$('.dropdown').dropdown();
defaultCourse = $.cookie('course');
if (!defaultCourse) {
	defaultCourse = 1;
}
$('.dropdown.courses').dropdown('set selected', defaultCourse);

courses = $('#courses');
courses.change(function () {
	loadGroups($(this).val());
	$.cookie('course', $(this).val(), {expires: 365});
});
$('#type .button').click(function () {
	$.cookie('type', $(this).attr('data-target'), {expires: 365});
	$('#type .button').removeClass('active');
	$(this).addClass('active');
	loadSchedule($('#groups').val());
});
defaultType = $.cookie('type');
if (!defaultType) {
	defaultType = 'short';
	$.cookie('type', 'short', {expires: 365});
}
$('#type .button[data-target=' + defaultType + ']').addClass('active');

loadGroups(defaultCourse);