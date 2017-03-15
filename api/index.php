<?php
//TODO: implement caching
//TODO: check if group exist before making queries

//check arguments
if ((!isset($_REQUEST['group']) || !$_REQUEST['group']) &&
	(!isset($_REQUEST['surname']) || !$_REQUEST['surname'])) {
	die(json_encode(['error' => 'no group or surname specified']));
}

if (! @include'../settings/app.php') {
	require_once '../settings/app.default.php';
}
require_once '../php/DBHelper.php';
if (isset($_REQUEST['surname']) && $_REQUEST['surname']) {
	require_once 'json/teachers.php';
} else {
	require_once 'json/students.php';
}


function formatMinutesOfDay($minutes) {
	$hours = intval($minutes / 60);
	$minutes = $minutes % 60;

	return "$hours:" . sprintf("%02d", $minutes);
}

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

$lastGen = unserialize(file_get_contents("$cacheDir/lastgen"));
$json['updated'] = [
	'check' => date('Y-m-d H:i:s', $lastGen['end']),
	'update' => date('Y-m-d H:i:s', cacheTime()),
	'error' => $lastGen['error'] //TODO: implement showing of error notification
];

echo json_encode($json, JSON_UNESCAPED_UNICODE);