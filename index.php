<?php
if (! @include'settings/app.php') {
	require_once 'settings/app.default.php';
}
require 'php/DBHelper.php';
$db = DBHelper::get();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Расписание стдентов ПТК НовГУ</title>
	<link rel="apple-touch-icon-precomposed" sizes="144x144" href="icons/apple-touch-icon-144x144.png" />
	<link rel="apple-touch-icon-precomposed" sizes="152x152" href="icons/apple-touch-icon-152x152.png" />
	<link rel="icon" type="image/png" href="icons/favicon-196x196.png" sizes="196x196" />
	<link rel="icon" type="image/png" href="icons/favicon-32x32.png" sizes="32x32" />
	<link rel="icon" type="image/png" href="icons/favicon-16x16.png" sizes="16x16" />
	<link rel="stylesheet" href="css/semantic.min.css">
	<link rel="stylesheet" href="css/dropdown.min.css">
	<link rel="stylesheet" href="css/loader.min.css">
	<link rel="stylesheet" href="css/modal.min.css">
	<link rel="stylesheet" href="css/popup.min.css">
	<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="ui text container">
	<div class="bugreport ui page dimmer"></div>
	<div class="ui basic modal">
		<div class="image content">
			<div class="image">
				<i class="code icon"></i>
			</div>
			<div class="description">
				<p>Сайт работает на основе расписаний <a href="<?=$url . $timeTable?>" target="_blank">НовГУ</a>, получаемых из .xls файлов</p>
				<p>При несовпадении данных в таблицах и на этом сайте сообщите об этом, кликнув кнопку с восклицательным знаком в правом нижнем углу.</p>
				<p>Замены не являются ошибками и не указываются в расписании.</p>
			</div>
		</div>
	</div>
	<div id="loader" class="ui active inverted dimmer">
		<div class="ui text loader">Загрузка</div>
	</div>
	<div class="ui grid">
		<div class="sixteen wide column">
			<div id="type" class="ui buttons fluid large">
				<button class="ui button" data-target="short">Сокращенное</button>
				<button class="ui button" data-target="full">Полное</button>
			</div>
		</div>
		<div class="eight wide column">
			<select id="courses" class="ui dropdown courses fluid">
				<?php for ($i = 0; $i < $db->getCoursesCount(); $i++):?>
					<option value="<?=$i?>"><?=$i + 1?> курс</option>
				<?php endfor?>
			</select>
		</div>
		<div class="eight wide column">
			<select id="groups" class="ui dropdown groups fluid"></select>
		</div>
		<div class="weektype sixteen wide column">
			<div class="ui segment no-shadow week">
				<p class="segment"><span id="clock"></span>, <span id="weekType"></span> неделя</p>
			</div>
			<div class="ui segment no-shadow error">
				<p class="segment">Ошибка: <span id="error"></span></p>
			</div>
		</div>
		<div id="container" class="sixteen wide column"></div>
	</div>
</div>
<div class="genteration-info">
	<p id="check-time">Последняя проверка: <span></span></p>
	<p id="update-time">Последнее обновление: <span></span></p>
	<p id="update-error">Ошибка при обновлении: <span></span></p>
</div>
<button class="bugreport circular ui large icon button">
	<i class="icon large warning circle"></i>
</button>
<div class="bugreport ui card popup transition">
	<div class="content">
		<div class="header">Сообщить об ошибке</div>
		<div class="description"><i class="hand pointer icon"></i> Нажмите на строку в расписании с ошибкой</div>
	</div>
</div>
<script src="js/jquery-2.2.4.min.js"></script>
<script src="js/jquery.cookie.min.js"></script>
<script src="js/semantic.min.js"></script>
<script src="js/dropdown.min.js"></script>
<script src="js/modal.min.js"></script>
<script src="js/popup.min.js"></script>
<script src="js/html2canvas.min.js"></script>
<script src="js/common.js?t=<?=filemtime('js/common.js')?>"></script>
<script src="js/bugreport.js?t=<?=filemtime('js/bugreport.js')?>"></script>
<script type="text/javascript"> (function (d, w, c) { (w[c] = w[c] || []).push(function() { try { w.yaCounter36039930 = new Ya.Metrika({ id:36039930, clickmap:true, trackLinks:true, accurateTrackBounce:true }); } catch(e) { } }); var n = d.getElementsByTagName("script")[0], s = d.createElement("script"), f = function () { n.parentNode.insertBefore(s, n); }; s.type = "text/javascript"; s.async = true; s.src = "https://mc.yandex.ru/metrika/watch.js"; if (w.opera == "[object Opera]") { d.addEventListener("DOMContentLoaded", f, false); } else { f(); } })(document, window, "yandex_metrika_callbacks"); </script> <noscript><div><img src="https://mc.yandex.ru/watch/36039930" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
</body>
</html>