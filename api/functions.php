<?php

/*
*It's a part of PTK NovSU schedule site page
*@author Vladimir Turba <turbavladimir@yandex.ru>
*@copyright 2016 Vladimir Turba
*/

if (file_exists('settings.php')) {
	require_once 'settings.php';
} else {
	require_once 'settings-default.php';
}
require_once 'parser.php';
require_once 'PHPExcel.php';
require_once 'PHPExcel/IOFactory.php';


function isCacheExist() {
	global $cacheDir;
	if ((file_exists("$cacheDir/json/$_GET[group]")) &
		(file_exists("$cacheDir/timestamp/$_GET[group]"))) {
		return true;
	}

	return false;
}

function getCacheTimestamp() {
	global $cacheDir;
	return file_get_contents("$cacheDir/timestamp/$_GET[group]");
}

function isDayEmty($day) {
	for ($i = 0; $i < count($day['schedule']) - 1; $i++) {
		if (gettype($day['schedule'][$i]) == 'string') {
			if (!in_array($day['schedule'][$i], [NULL, '', '&nbsp;'])) {
				return false;
			}
		} else {
			if (!in_array($day['schedule'][$i]['top'], [NULL, '', '&nbsp;']) ||
				!in_array($day['schedule'][$i]['bottom'], [NULL, '', '&nbsp;'])) {
				return false;
			}
		}
	}
	return true;
}

function removeEmptyDays(&$days) {
	while (isDayEmty($days[count($days) - 1])) {
		array_pop($days);
	}
}

function removeEmptyEndings(&$days) {
	foreach ($days as &$day) {
		while (($day['schedule'][count($day['schedule']) - 1] == NULL ||
			   $day['schedule'][count($day['schedule']) - 1] == '&nbsp;') && count($day['schedule'])) {
			array_pop($day['schedule']);
			if (count($day['schedule']) < count($day['time'])) {
				array_pop($day['time']);
			}
		}
		$timeScheduleDiff = count($day['schedule']) - count($day['time']);
		if ($timeScheduleDiff < 0) {
			$day['time'] = array_slice($day['time'], 0, $timeScheduleDiff);
		}
		if ($timeScheduleDiff > 0) {
			$day['schedule'] = array_slice($day['schedule'], 0, -$timeScheduleDiff);
		}
	}

}

function updateCache($filename) {
	global $cacheDir, $jsonFlags, $timePattern;
	$xls = PHPExcel_IOFactory::load("$cacheDir/xls/$filename");
	$sheet = $xls->getActiveSheet();
	if (($groupCell = getGroupCell($sheet)) === false) {
		die(json_encode(['error' => 'Failed to find group in xls file']));
	}

	$ranges = getWeekDayRanges($sheet, $groupCell[1] + 1);

	$timeCol = $groupCell[0] - 1;
	if (!empty($_GET['timeCol'])) {
		$timeCol = $_GET['timeCol'];
	} else {
		if (!preg_match($timePattern, getCellValue($sheet, $groupCell[0] - 1, $groupCell[1] + 1))) {
			$timeCol = getTimeCol($sheet, $timeCol, $groupCell[1] + 1);
		}
	}

	foreach ($ranges as $range) {
		$output['days'][]['schedule'] = getScheduleOfRowRange($sheet, $timeCol, $groupCell[0], $range);
		$output['days'][count($output['days']) - 1]['time'] = getCallsSchedule($sheet, $timeCol, $groupCell[0], $range);
	}

	removeEmptyDays($output['days']);
	removeEmptyEndings($output['days']);

	if (!file_exists("$cacheDir/json")) {
		mkdir("$cacheDir/json", 0755, true);
	}
	$jsonFile = "$cacheDir/json/$_GET[group]";
	file_put_contents($jsonFile, json_encode($output, $jsonFlags));
}

function storeTimestamp($timeString) {
	global $cacheDir;
	if (!file_exists("$cacheDir/timestamp")) {
		mkdir("$cacheDir/timestamp", 0755, true);
	}
	file_put_contents("$cacheDir/timestamp/$_GET[group]", strtotime($timeString));
}

function appendDay(&$list, $day, $nextWeek = false) {
	global $invertWeekType;
	$isLowWeek = date('W') % 2 == 0;
	if ($invertWeekType) {
		$isLowWeek = !$isLowWeek;
	}
	if ($nextWeek) {
		$isLowWeek = !$isLowWeek;
	}
	foreach ($day['schedule'] as $item) {
		if (gettype($item) == 'array') {
			if ($isLowWeek) {
				$list['schedule'][] = $item['bottom'];
			}
			else {
				$list['schedule'][] = $item['top'];
			}
		}
		else {
			$list['schedule'][] = $item;
		}
	}
	$list['time'] = $day['time'];

	while ($list['schedule'][count($list['schedule']) - 1] == '&nbsp;') {
		array_pop($list['schedule']);
		array_pop($list['time']);
	}
}
