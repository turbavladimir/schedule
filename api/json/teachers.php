<?php
function getDay($weekday, $weekTypeNum = false) {
	global $teacher;
	$db = DBHelper::get();
	$rawDay = $db->getTeacherSchedule($db->escape($_REQUEST['surname']), $weekday, $weekTypeNum);

	$day = [];
	foreach ($rawDay as $classes) {
		$class = [];
		foreach ($classes as $type => $weektype) {
			if (!$weekTypeNum && $type) {
				$typeName = $type == 1 ? 'bottom' : 'top';
				$class[$typeName] = "$weektype[subject], $weektype[hall]"
					. ($weektype['comments'] ? ", $weektype[comments]" : '');
			} else {
				$class = "$weektype[subject], $weektype[hall]" . ($weektype['comments'] ? ", $weektype[comments]" : '');
			}
		}
		if (isset($class)) {
			$day['schedule'][] = $class;
			$day['time'][] = formatMinutesOfDay($weektype['start']) . '-' . formatMinutesOfDay($weektype['end']);
		}
	}

	return $day;
}

function cacheTime() {
	global $cacheDir;
	$fileNames = glob("$cacheDir/xls/*.ts");
	usort($fileNames, function($a, $b) {
		return filemtime($b) - filemtime($a);
	});

	return file_get_contents($fileNames[0]);
}