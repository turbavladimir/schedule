function report() {
	if (!$(this).hasClass('inverted')) {
		$('.bugreport.dimmer').dimmer('show');
		$('table').addClass('bugreport');
		$(this).children('i').removeClass('hand pointer');
		$(this).children('i').addClass('ban');
		$(this).children('span').html('Отменить');
		$('*').css('cursor', 'pointer');
		$(this).addClass('inverted');

		$('table').click(function (event) {
			$('*').css('cursor', '');
			$('button.bugreport').addClass('loading');
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
				'type': $.cookie('type')
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
					if (success) {
						$('button.bugreport').removeClass('loading');
						$('button.bugreport i').removeClass('warning circle').addClass('send');
						$('.popup.bugreport').html('<div class="success content"><div class="header">Сообщение об ошибке отправлено!</div></div>');
					} else {
						$('button.bugreport').removeClass('loading');
						$('button.bugreport i').removeClass('warning circle').addClass('remove');
						$('.popup.bugreport').html('<div class="fail content"><div class="header">Отправка сообщения об ошибке не удалась.</div></div>');
					}
					setTimeout(function () {
						$('button.bugreport').popup('toggle');
						setTimeout(function () {
							$('button.bugreport i').removeClass('remove').removeClass('send').addClass('warning circle');
							$('.popup.bugreport').html('<div class="content"><div class="header">Сообщить об ошибке</div><div class="description"><i class="hand pointer icon"></i> Нажмите на строку в расписании с ошибкой</div></div>');
						}, 100);
					}, 2000);
					$('.bugreport.dimmer').dimmer('hide');
					$('button.bugreport').removeClass('inverted');
					$('table').removeClass('bugreport');
					$('table').off('click');
				}
			});
		});
	} else {
		$('.bugreport.dimmer').dimmer('hide');
		$('table').removeClass('bugreport');
		$(this).children('i').addClass('hand pointer');
		$(this).children('i').removeClass('ban');
		$(this).children('span').html('Показать');
		$('*').css('cursor', '');
		$(this).removeClass('inverted');
		$('table').off('click');
	}
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
$('button.bugreport').click(report);
