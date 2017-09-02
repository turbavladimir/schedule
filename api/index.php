<?php
//TODO: check whether group exist before making queries

//check arguments
if ((!isset($_REQUEST['group']) || !$_REQUEST['group'])) {
	die(json_encode(['error' => 'no group specified']));
}

if (!@include'../settings/app.php') {
	require_once '../settings/app.default.php';
}

require_once '../php/DBHelper.php';

function cacheTime() {
	global $cacheDir;
	$fileName = glob("$cacheDir/xls/*" . intval($_REQUEST['group']) . "*.ts")[0];
	return file_get_contents($fileName);
}

function formatMinutesOfDay($minutes) {
	$hours = intval($minutes / 60);
	$minutes = $minutes % 60;
	return "$hours:" . sprintf("%02d", $minutes);
}

function getDay($weekday, $weekTypeNum = false) {
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
		$day['time'][] = formatMinutesOfDay($item['start']) . '-' . formatMinutesOfDay($item['end']);
	}

	return $day;
}

//in DB: 0 - any, 1 - low, 2 - high
$weekTypeNum = date('W') % 2 + 1;
if ($invertWeekType) {
	if ($weekTypeNum == 1) $weekTypeNum = 2;
	if ($weekTypeNum == 2) $weekTypeNum = 1;
}
$json['lowWeek'] = $weekTypeNum == 1;

if (isset($_REQUEST['short'])) {
	//convert to 'weekday from monday'
	$weekday = date('w') - 1;
	if ($weekday == -1) $weekday = 6;
	$nextDay = $weekday + 1;
	if ($nextDay == 7) $nextDay = 0;

	$json['days'][$weekday] = getDay($weekday, $weekTypeNum);
	$json['days'][$nextDay] = getDay($nextDay, $weekTypeNum);
} else {
	for ($weekday = 0; $weekday < 6; $weekday++) {
		$json['days'][$weekday] = getDay($weekday);
	}
}

$json['days'] = array_filter($json['days']);
$json['updated']['update'] = date('Y-m-d H:i:s', cacheTime());

if (is_file("$cacheDir/lastgen")) {
	$lastGen = unserialize(file_get_contents("$cacheDir/lastgen"));
	$json['updated']['check'] = date('Y-m-d H:i:s', $lastGen['end']);
	$json['updated']['error'] = $lastGen['error']; //TODO: implement showing of error notification
}

echo json_encode($json, JSON_UNESCAPED_UNICODE);
