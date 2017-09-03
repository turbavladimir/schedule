var days = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
var months = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь',	'Ноябрь','Декабрь'];
var clock = $('#clock');

function updateClock() {
	d = new Date();
	hour = d.getHours();
	min = d.getMinutes();
	day = d.getDay() - 1;
	if (day === -1) day = 6;
	if (min <= 9) min = "0" + min;
	if (hour <= 9) hour = "0" + hour;

	clock.html(days[day]+', '+months[d.getMonth()]+' '+d.getDate()+', '+hour+':'+min);
	setInterval(updateClock, (60 - d.getSeconds()) * 1000);
}

updateClock();

function setLoader(state) {
	if (state) {
		$('button.bugreport').addClass('notop');
		$('#loader').addClass('active');
	} else {
		if (!$('.ui.basic.modal').modal('is active')) {
			$('button.bugreport').removeClass('notop');
		}
		$('#loader').removeClass('active');
	}
}

function wrap(string) {
	if (string) {
		return string;
	} else {
		return '&nbsp;';
	}
}

function parserFull(response) {
	container = $('#container');
	var content = '';

	for (var day in response.days) {
		content += '<table class="ui celled table unstackable"><thead><tr><th colspan=2>' + days[day] + '</th></tr></thead><tbody>';

		for (var subject in response.days[day].schedule) {
			content += '<tr>';
			time = response.days[day].time[subject].split('-');
			if (typeof(response.days[day].schedule[subject]) === 'string') {
				content += '<td class="time"><p class="time">' + time[0] + '</p><p class="time">' + time[1] +
					'</p></td><td>' + response.days[day].schedule[subject] + '</td>';
			} else {
				content += '<td class="time" rowspan=2><p class="time">' + time[0] + '</p><p class="time">' +
					time[1] + '</p></td><td  class="mergedTop">' + wrap(response.days[day].schedule[subject].top) +
					'</td></tr><tr><td  class="mergedBottom">' + wrap(response.days[day].schedule[subject].bottom) + '</td>';
			}
			content += '</tr>'
		}
		content += '</tbody></table>';
	}

	$('#container').html(content);
	setLoader(false);
}

function parserShort(response) {
	container = $('#container');
	var content = '';

	for (var day in response.days) {
		content += '<table class="ui celled table unstackable"><thead><tr><th colspan=2>' + days[day] + '</th></tr></thead><tbody>';
		for (var j = 0; j < response.days[day].schedule.length; j++) {
			time = response.days[day].time[j].split('-');
			content += '<tr><td class="time"><p class="time">' + time[0] + '</p><p class="time">' +
				time[1] + '</p></td><td>' + response.days[day].schedule[j] + '</td></tr>';
		}
		content += '</tbody></table>';
	}

	container.html(content);
	setLoader(false);
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

function loadSchedule(key) {
	setLoader(true);
	data = {'group': key};

	type = $('#type>.active').attr('data-target');
	if (type === 'short') {
		data['short'] = true;
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

			$('#check-time span').html(response.updated.check);
			$('#update-time span').html(response.updated.update);
			if (response.updated.error) {
				$('#update-error span').html('да');
			} else {
				$('#update-error span').html('нет');
			}

			//TODO: implement important errors handling
			if (type === 'short') {
				parserShort(response)
			} else {
				parserFull(response);
			}
		}
	});
}

//show message to first time users
if ($.cookie('warining') === undefined) {
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

$('.dropdown').dropdown();

//initialaize type changer
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

$('#groups').change(function() {
	loadSchedule($(this).val());
	$.cookie('group', $(this).val(), {expires: 365});
});

loadGroups(defaultCourse);