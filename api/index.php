<?php

/*
*It's a part of PTK NovSU schedule site page
*@author Vladimir Turba <turbavladimir@yandex.ru>
*@copyright 2016 Vladimir Turba
*/

if (file_exists('settings.php')) {
	require_once "settings.php";
} else {
	require_once 'settings-default.php';
}
require_once "functions.php";

//check arguments
if (empty($_GET['group'])) {
	die(json_encode(['error' => 'no group specified']));
}

$customGroups = [];
if (file_exists("../custom")) {
	$customGroups = scandir('../custom');
	$customGroups = array_slice($customGroups, 2);
}
if (in_array($_GET['group'], $customGroups)) {
	$json =  json_decode(file_get_contents("../custom/" . $_GET['group']), true);
} else {
	$page = file_get_contents($url . $timeTable);
	preg_match($groupPattern, $_GET['group'], $groupMatch);
	//get match with relative xls file url and last update time
	if (!preg_match("/<a href=\"(.*)\" title=\"(.*)\">" . $groupMatch[0] . "<\/a>/", $page, $match)) {
		die(json_encode(['error' => 'failed to parse schedule version in timetable']));
	}
	if (!preg_match("/ptk\/(.*)\?/", $match[1], $filenameMatch)) {
		die(json_encode(['error' => 'failed to parse xls filename']));
	}
	$filename = $filenameMatch[1];

	//generate or update cache if needed
	if (isCacheExist()) {
		//check schedule version
		$timestamp = strtotime($match[2]);
		if ($timestamp > getCacheTimestamp()) {
			if (!file_exists($tmpDir . "/xls")) {
				mkdir($tmpDir . "/xls", 0755, true);
			}
			file_put_contents($tmpDir . "/xls/" . $filename, fopen($url . "/" . $match[1], "r"));
			updateCache($filename);
			storeTimestamp($match[2]);
		}
	}
	else {
		if (!file_exists($tmpDir . "/xls/" . $filename)) {
			if (!file_exists($tmpDir . "/xls")) {
				mkdir($tmpDir . "/xls", 0755, true);
			}
			file_put_contents($tmpDir . "/xls/" . $filename, fopen($url . "/" . $match[1], "r"));
		}

		updateCache($filename);
		storeTimestamp($match[2]);
	}
	$json =  json_decode(file_get_contents($tmpDir . "/json/" . $_GET['group']), true);
}

if (isset($_GET['short'])) {
	$weekDay = date("w");
	$output = [];
	if (count($json['days']) < 6) {
		if ($weekDay == 6) {
			$output['days'][] = "Saturday";
		}
		if ($weekDay == 0) {
			$output['days'][] = "Sunday";
		}
	}
	$i = 0;
	foreach ($json['days'] as $item) {
		if (($i == $weekDay - 1) || ($i == $weekDay)) {
			$output['days'][] = [];
			$lastIndex = count($output['days']) - 1;
			if (($weekDay == 0) && ($i == 0)) {
				appendDay($output['days'][$lastIndex], $item, true);
			}
			else {
				appendDay($output['days'][$lastIndex], $item);
			}
		}
		$i++;
	}
	if (count($output) < 2) {
		if ($weekDay == 5) {
			$output['days'][] = "Saturday";
		}
		if ($weekDay == 6) {
			$output['days'][] = "Sunday";
		}
	}
	$json = $output;
}

$isLowWeek = date("W") % 2 == 0;
if ($invertWeekType) {
	$isLowWeek = !$isLowWeek;
}
$json["lowWeek"] = $isLowWeek;

echo json_encode($json, $jsonFlags);