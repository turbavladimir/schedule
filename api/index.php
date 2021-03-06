<?php

//check arguments
if ((!isset($_REQUEST['group']) || !$_REQUEST['group'])) {
	die(json_encode(['error' => 'no group specified']));
}

require_once '../php/DBHelper.php';
$db = DBHelper::get();

if (!$db->groupExsist($db->escape($_REQUEST['group']))) {
	die(json_encode(['error' => 'group doesn\'t exist']));
}

if (!@include'../settings/app.php') {
	require_once '../settings/app.default.php';
}

require_once '../php/Utils.php';

function getDay($weekday, $weekTypeNum = false) {
	global $restTime, $classLength;
	$db = DBHelper::get();
	$rawDay = $db->getGroupSchedule($db->escape($_REQUEST['group']), $weekday, $weekTypeNum);

	$day = [];
	foreach ($rawDay as $classes) {
		$class = [];
		foreach ($classes as $type => $item) {
			if (!$weekTypeNum && $type) {
				$typeName = $type == 1 ? 'bottom' : 'top';
				$class[$typeName] = $item['subject'];
			} else {
				$class = $item['subject'];
			}
		}
		$day['schedule'][] = $class;
		$day['time'][] = Utils::formatTime($item['start'], $item['end'], $restTime, $classLength);
	}

	return $day;
}

//in DB: 0 - any, 1 - low, 2 - high
$weekTypeNum = Utils::getWeekTypeNum($invertWeekType);

if (isset($_REQUEST['short'])) {
	$weekdayFromSun = date('w');
	$weekday = Utils::weekDayFromMon($weekdayFromSun);
	$nextDay = Utils::weekDayFromMon($weekdayFromSun, true);

	$json['days'][$weekday] = getDay($weekday, $weekTypeNum);
	if ($nextDay == 0) {
		$weekTypeNum = Utils::getWeekTypeNum(!$invertWeekType);
	}
	$json['days'][$nextDay] = getDay($nextDay, $weekTypeNum);
} else {
	for ($weekday = 0; $weekday < 6; $weekday++) {
		$json['days'][$weekday] = getDay($weekday);
	}
}

$json['lowWeek'] = Utils::getWeekTypeNum($invertWeekType) == 1;
$json['days'] = array_filter($json['days']);
$json['updated']['update'] = date('Y-m-d H:i:s', Utils::cacheTime($_REQUEST['group'], $cacheDir));

if (is_file("$cacheDir/lastgen")) {
	$lastGen = unserialize(file_get_contents("$cacheDir/lastgen"));
	$json['updated']['check'] = date('Y-m-d H:i:s', $lastGen['end']);
	$json['updated']['error'] = $lastGen['error'];
}

echo json_encode($json, JSON_UNESCAPED_UNICODE);
