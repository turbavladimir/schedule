var days = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];

function sizeOf(obj) {
	var size = 0;
	for (var i = 0; i < obj.length; i++) {
		if (typeof(obj[i]) == 'string') {
			size++;
		} else {
			size += 2;
		}
	}
	return size;
}

function parserFull(data) {
	var response = $.parseJSON(data);
	if (response.lowWeek) {
		$('#weekType').html('нижняя');
	} else {
		$('#weekType').html('верхняя');
	}

	$('#container').html('');
	for (var i = 0; i < response.days.length; i++) {
		if (!response.days[i].schedule.length) {
			continue;
		}
		var content = '<tr><th rowspan=' + sizeOf(response.days[i].schedule) +
			'><p class="rotate">' + days[i] + '</p></th>';
		time = response.days[i].time[0].split('-');
		if (typeof(response.days[i].schedule[0]) == 'string') {
			content += '<td class="simpleCell"><p class="time">' + time[0] + '</p><p class="time">' +
				time[1] + '</p></td><td class="simpleCell">' + response.days[i].schedule[0] + '</tr>';
		} else {
			content += '<td rowspan="2"><p class="time">' + time[0] + '</p><p class="time">' +
				time[1] + '</p></td><td  class="mergedTop">' + response.days[i].schedule[0].top +
				'</tr><tr><td  class="mergedBottom">' + response.days[i].schedule[0].bottom + '</td>';
		}
		content += '</tr>'

		for (var j = 1; j < response.days[i].schedule.length; j++) {
			content += '<tr>'
			time = response.days[i].time[j].split('-');
			if (typeof(response.days[i].schedule[j]) == 'string') {
				content += '<td class="simpleCell"><p class="time">' + time[0] + '</p><p class="time">' + time[1] +
					'</p></td><td class="simpleCell">' + response.days[i].schedule[j] + '</td>';
			} else {
				content += '<td rowspan=2><p class="time">' + time[0] + '</p><p class="time">' +
					time[1] + '</p></td><td  class="mergedTop">' + response.days[i].schedule[j].top +
					'</td></tr><tr><td  class="mergedBottom">' + response.days[i].schedule[j].bottom + '</td>';
			}
			content += '</tr>'
		}
		$('#container').append(content);
	}
}

function parserShort(data) {
	var response = $.parseJSON(data);
	$('#container').html('<table>');
	for (var i = 0 ; i < response.days.length; i++) {
		var content = '<tr><td colspan=2 align="center">' + days[i] + '</td></tr>';
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
				content += '<tr><td><p class="time">' + time[0] + '</p><p class="time">' +
					time[1] + '</p></td><td>' + response.days[i].schedule[j] + '</td></tr>';
			}
		}
		$('#container').append(content);
	}
	$('#container').append('</table>');
}

function loadSchedule(group) {
	$.ajax({
		url: 'http://schedule.weith.ru/api/index.php',
		data: {'group': group},
		success: parserFull
	});
}

$.ajax({
	url: 'http://schedule.weith.ru/api/groups.php',
	success: function(data) {
		var groups = $.parseJSON(data);
		var defaultGroup = $.cookie('group');
		if ($.inArray(defaultGroup, groups) < 0) {
			defaultGroup = groups[0];
			$.cookie('group', defaultGroup, {expires: 365});
		}
		groups = $('#groups');
		for (var i = 0; i < groups.length; i++) {
			groups.append('<option value="' + groups[i] + '">' + groups[i] + '</option>');
		}
		groups.val(defaultGroup);
		$('.dropdown').dropdown('set text', defaultGroup);
		loadSchedule(defaultGroup);
	}
});

$('#groups').change(function() {
	loadSchedule($(this).val());
	$.cookie('group', $(this).val(), {expires: 365});
});

$('.dropdown').dropdown({transition: 'drop'});

$('.ui.checkbox').checkbox();