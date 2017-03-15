var days = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
var months = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь',	'Ноябрь','Декабрь']
var clock = $('#clock');

function updateClock() {
	var d = new Date();
	var hour = d.getHours();
	var min = d.getMinutes();
	var day = d.getDay() - 1;
	if (day == -1) day = 6;
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
			if (typeof(response.days[day].schedule[subject]) == 'string') {
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

//show message to first time users
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