<?php
if (!@include '../settings/app.php') {
	require_once '../settings/app.default.php';
}
require '../php/DBHelper.php';
require '../php/Utils.php';

$weekdayNames = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];

$db = DBHelper::get();

//get or set course cookie
if (isset($_REQUEST['course'])) {
	$selectedCourse = intval($_REQUEST['course']);
	setcookie('course', $selectedCourse);
} elseif (isset($_COOKIE['course'])) {
	$selectedCourse = intval($_COOKIE['course']);
} else {
	$selectedCourse = 0;
}

//load groups and selected group
$groups = $db->getGroups($selectedCourse);

if (isset($_REQUEST['group'])) {
	$selectedGroup = $db->escape($_REQUEST['group']);
	setcookie('group', $selectedGroup);
} elseif (isset($_COOKIE['group'])) {
	$selectedGroup = $db->escape($_COOKIE['group']);
} else {
	$selectedGroup = $groups[0];
}
if (!in_array($selectedGroup, $groups)) {
	$selectedGroup = $groups[0];
}

//get or set type cookie
if (isset($_REQUEST['type'])) {
	$selectedType = $_REQUEST['type'];
	setcookie('type', $selectedType);
} elseif (isset($_COOKIE['type'])) {
	$selectedType = $_COOKIE['type'];
} else {
	$selectedType = 'short';
}

//prepare days data
//in DB: 0 - any, 1 - low, 2 - high
$weekTypeNum = Utils::getWeekTypeNum($invertWeekType);

$days = [];
if ($selectedType == 'short') {
	$weekdayFromSun = date('w');
	$weekday = Utils::weekDayFromMon($weekdayFromSun);
	$nextDay = Utils::weekDayFromMon($weekdayFromSun, true);

	$days[$weekday] = $db->getGroupSchedule($selectedGroup, $weekday, $weekTypeNum, true);
	if ($nextDay == 0) {
		$weekTypeNum = Utils::getWeekTypeNum(!$invertWeekType);
	}
	$days[$nextDay] = $db->getGroupSchedule($selectedGroup, $nextDay, $weekTypeNum, true);
} else {
	for ($weekday = 0; $weekday < 6; $weekday++) {
		$days[$weekday] = $db->getGroupSchedule($selectedGroup, $weekday, 0, true);
	}
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Расписание студентов ПТК НовГУ</title>
	<link rel="stylesheet" href="style.css">
</head>
<body>
<form id="form-control" method="post">
	<select class="form-control" name="type" onchange="reload()">
		<option value="full" <?=$selectedType == 'full' ? 'selected' : ''?>>Полное</option>
		<option value="short" <?=$selectedType == 'short' ? 'selected' : ''?>>Сокращенное</option>
	</select>
	<select class="form-control" name="course" onchange="reload()">
		<?php for ($i = 0; $i < $db->getCoursesCount(); $i++):?>
			<option value="<?=$i?>" <?=intval($selectedCourse) == $i ? 'selected' : ''?>><?=$i + 1?> курс</option>
		<?php endfor?>
	</select>
	<select class="form-control" name="group" onchange="reload()">
		<?php foreach ($groups as $group):?>
			<option value="<?=$group?>" <?=$selectedGroup == $group ? 'selected' : ''?>><?=$group?></option>
		<?php endforeach;?>
	</select>
</form>
<p class="weektype"><?=$weekTypeNum == 1 ? 'Нижняя' : 'Верхняя'?> неделя</p>
<?php foreach ($days as $weekdayNum => $day):
	if (!$day) continue;?>
	<table>
		<thead>
		<tr><th colspan="2"><?=$weekdayNames[$weekdayNum]?></th></tr>
		</thead>
		<tbody>
		<?php foreach ($day as $class):?>
			<?php if ($selectedType == 'short'):
				$item = array_values($class)[0]?>
				<tr>
					<td class="time">
						<p class="time"><?=Utils::formatMinutesOfDay($item['start'])?></p>
						<p class="time"><?=Utils::formatMinutesOfDay($item['end'])?></p>
					</td>
					<td><?=$item['subject']?></td>
				</tr>
			<?php elseif ($selectedType == 'full'):?>
				<?
				$isMerged = count($class) > 1;
				$firstItem = $isMerged ? (isset($class[2]) ? $class[2] : false) : $class[0];
				$secondItem = isset($class[1]) ? $class[1] : false?>
				<tr>
					<td class="time" <?=$isMerged ? 'rowspan="2"' : ''?>>
						<p class="time"><?=Utils::formatMinutesOfDay($firstItem ? $firstItem['start'] : $secondItem['start'])?></p>
						<p class="time"><?=Utils::formatMinutesOfDay($firstItem ? $firstItem['end'] : $secondItem['end'])?></p>
					</td>
					<td <?=$isMerged ? 'class="mergedTop"' : ''?>><?=$firstItem ? $firstItem['subject'] : "&nbsp;"?></td>
				</tr>
				<?php if ($isMerged):?>
					<tr>
						<td class="mergedBottom"><?=$secondItem['subject']?></td>
					</tr>
				<?php endif;?>
			<?php endif?>
		<?php endforeach;?>
		</tbody>
	</table>
<?php endforeach?>
<script>
	function reload() {
		document.getElementById('form-control').submit();
	}
</script>
</body>
</html>