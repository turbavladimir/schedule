<?php
//TODO: implement caching
//TODO: check if group exist before making queries

if (! @include'../settings/app.php') {
	require_once '../settings/app.default.php';
}

require_once '../php/DBHelper.php';

//check arguments
if (empty($_REQUEST['group'])) {
	die(json_encode(['error' => 'no group specified']));
}

function getStr($weektypes) {
	global $teacher;

	$str = '';
	foreach ($weektypes as $subjects) {
		$str .= current($subjects)['subject'] . ', ';
		foreach ($subjects as $teacher) {
			$str .= "$teacher[teacher], $teacher[hall], ";
		}
		$str .= "\n";
	}

	return rtrim($str, ", \n");
}

function formatMinutesOfDay($minutes) {
	$hours = intval($minutes / 60);
	$minutes = $minutes % 60;

	return "$hours:" . sprintf("%02d", $minutes);
}

function getDay($weekday, $weekTypeNum = false) {
	global $teacher;
	$db = DBHelper::get();
	$rawDay = $db->getGroupSchedule($db->escape($_REQUEST['group']), $weekday, $weekTypeNum);

	$day = [];
	foreach ($rawDay as $classes) {
		$class = [];
		foreach ($classes as $type => $weektypes) {
			if (!$weekTypeNum && $type) {
				$typeName = $type == 1 ? 'bottom' : 'top';
				$class[$typeName] = getStr($weektypes);
			} else {
				$class = getStr($weektypes);
			}
		}
		if (isset($class)) {
			$day['schedule'][] = $class;
			$day['time'][] = formatMinutesOfDay($teacher['start']) . '-' . formatMinutesOfDay($teacher['end']);
		}
	}

	return $day;
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

$fileName = glob("$cacheDir/xls/*" . intval($_REQUEST['group']) . "*.ts")[0];
$lastCacheUpdate = file_get_contents($fileName);

$json['updated'] = [
	'check' => date('Y-m-d H:i:s', unserialize(file_get_contents("$cacheDir/lastgen"))['end']),
	'update' => date('Y-m-d H:i:s', $lastCacheUpdate)
];

echo json_encode($json, JSON_UNESCAPED_UNICODE);