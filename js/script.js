function setLoader(state) {
	if (state) {
		$('button.bugreport').addClass('notop');
		$('#loader').addClass('active');
	} else {
		$('button.bugreport').removeClass('notop');
		$('#loader').removeClass('active');
	}
}

function parserFull(data) {
	var days = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
	var response = $.parseJSON(data);
	if (response.lowWeek) {
		$('#weekType').html('нижняя');
	} else {
		$('#weekType').html('верхняя');
	}

	container = $('#container');
	var content = '';
	for (var i = 0; i < response.days.length; i++) {
		if (!response.days[i].schedule.length) {
			continue;
		}
		content += '<table class="ui celled table unstackable"><thead><tr><th colspan=2>' + days[i] + '</th></tr></thead><tbody>';
		time = response.days[i].time[0].split('-');
		if (typeof(response.days[i].schedule[0]) == 'string') {
			content += '<td class="time"><p class="time">' + time[0] + '</p><p class="time">' +
				time[1] + '</p></td><td>' + response.days[i].schedule[0] + '</tr>';
		} else {
			content += '<td class="time" rowspan="2"><p class="time">' + time[0] + '</p><p class="time">' +
				time[1] + '</p></td><td class="mergedTop">' + response.days[i].schedule[0].top +
				'</tr><tr><td class="mergedBottom">' + response.days[i].schedule[0].bottom + '</td>';
		}
		content += '</tr>';

		for (var j = 1; j < response.days[i].schedule.length; j++) {
			content += '<tr>';
			time = response.days[i].time[j].split('-');
			if (typeof(response.days[i].schedule[j]) == 'string') {
				content += '<td class="time"><p class="time">' + time[0] + '</p><p class="time">' + time[1] +
					'</p></td><td>' + response.days[i].schedule[j] + '</td>';
			} else {
				content += '<td class="time" rowspan=2><p class="time">' + time[0] + '</p><p class="time">' +
					time[1] + '</p></td><td  class="mergedTop">' + response.days[i].schedule[j].top +
					'</td></tr><tr><td  class="mergedBottom">' + response.days[i].schedule[j].bottom + '</td>';
			}
			content += '</tr>'
		}
		content += '</tbody></table>';
	}
	$('#container').html(content);
	setLoader(false);
}

function parserShort(data) {
	var days = ['Сегодня', 'Завтра'];
	var response = $.parseJSON(data);
	if (response.lowWeek) {
		$('#weekType').html('нижняя');
	} else {
		$('#weekType').html('верхняя');
	}

	container = $('#container');
	var content = '';
	for (var i = 0 ; i < response.days.length; i++) {
		content += '<table class="ui celled table unstackable"><thead><tr><th colspan=2>' + days[i] + '</th></tr></thead><tbody>';
		if (typeof(response.days[i]) == 'string') {
			if (response.days[i] == 'Saturday') {
				day = 'субботу';
			} else {
				day = 'воскресенье';
			}
			content += '<tr><td colspan=2 align="center">' + 'Ничего на ' + day + '</td></tr>';
		} else {
			for (var j = 0; j < response.days[i].schedule.length; j++) {
				time = response.days[i].time[j].split('-');
				content += '<tr><td class="time"><p class="time">' + time[0] + '</p><p class="time">' +
					time[1] + '</p></td><td>' + response.days[i].schedule[j] + '</td></tr>';
			}
		}
		content += '</tbody></table>';
	}
	container.html(content);
	setLoader(false);
}

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
		success: (type == 'short') ? parserShort : parserFull
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
	defaultType = 'short'
}
$('#type .button[data-target=' + defaultType + ']').addClass('active');

loadGroups(defaultCourse);