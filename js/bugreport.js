function setReportMode(active) {
	if (active) {
		$('.bugreport.dimmer').dimmer('show');
		$('table').addClass('bugreport');
		$('*').css('cursor', 'pointer');
		$('button.bugreport').addClass('inverted');
	} else {
		$('table').removeClass('bugreport');
		$('.bugreport.dimmer').dimmer('hide');
		$('*').css('cursor', '');
		$('button.bugreport').removeClass('inverted');
		$('table').off('click');
	}
}

function report(event, image) {
	$('button.bugreport').addClass('active loading');
	$('.popup.bugreport').html('<div class="content"><div class="header">Отправка сообщения об ошибке..</div></div>');
	$.post('api/bugreport.php', {
		'x': event.pageX,
		'y': event.pageY,
		'width': function () {
			if (self.innerWidth) return self.innerWidth;
			if (document.documentElement && document.documentElement.clientWidth) return document.documentElement.clientWidth;
			if (document.body) return document.body.clientWidth;
		},
		'height': function () {
			if (self.innerHeight) return self.innerHeight;
			if (document.documentElement && document.documentElement.clientHeight) return document.documentElement.clientHeight;
			if (document.body) return document.body.clientHeight;
		},
		'target': event.target.outerHTML,
		'group': $.cookie('group'),
		'type': $.cookie('type'),
		'image': image
	}).always(function (data) {
		try {
			var success = true;
			data = JSON.parse(data);
			if (!data['success']) {
				success = false;
			}
		} catch (e) {
			success = false;
		} finally {
			$('button.bugreport').removeClass('active loading');
			if (success) {
				$('button.bugreport i').removeClass('warning circle').addClass('send');
				$('.popup.bugreport').html('<div class="success content"><div class="header">Сообщение об ошибке отправлено!</div></div>');
			} else {
				$('button.bugreport i').removeClass('warning circle').addClass('remove');
				$('.popup.bugreport').html('<div class="fail content"><div class="header">Отправка сообщения об ошибке не удалась.</div></div>');
			}
			setTimeout(function () {
				$('button.bugreport').popup('hide');
				setTimeout(function () {
					$('button.bugreport i').removeClass('remove').removeClass('send').addClass('warning circle');
					$('.popup.bugreport').html('<div class="content"><div class="header">Сообщить об ошибке</div><div class="description"><i class="hand pointer icon"></i> Нажмите на строку в расписании с ошибкой</div></div>');
				}, 100);
			}, 2000);
			setReportMode(false);
		}
	});
}

$('.bugreport.dimmer').dimmer({
	opacity: 0.2,
	closable: false
});
$('button.bugreport').popup({
	inline: true,
	on: 'click',
	closable: false,
	position: 'top right'
});

$('button.bugreport').click(function () {
	if ($('button.bugreport').popup('is hidden')) {
		setReportMode(true);
		$('table').click(function (event) {
			$(event.target).addClass('report-target');
			html2canvas($('.ui.grid')[0], {
				onrendered: function (canvas) {
					$(event.target).removeClass('report-target');
					report(event, canvas.toDataURL("image/jpeg"))
				}
			});
		});
	} else {
		setReportMode(false);
	}
});
