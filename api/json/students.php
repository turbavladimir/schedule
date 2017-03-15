<?php
function getStr($weektypes) {
	global $teacher;

	$str = '';
	foreach ($weektypes as $subjects) {
		$str .= current($subjects)['subject'] . ', ';
		foreach ($subjects as $teacher) {
			$str .= "$teacher[teacher], $teacher[hall], " . ($teacher['comments'] ? "$teacher[comments], " : '');;
		}
		$str .= "\n";
	}

	return rtrim($str, ", \n");
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

function cacheTime() {
	global $cacheDir;
	$fileName = glob("$cacheDir/xls/*" . intval($_REQUEST['group']) . "*.ts")[0];
	return file_get_contents($fileName);
}