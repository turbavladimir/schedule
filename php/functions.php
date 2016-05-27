<?php

/*
*It's a part of PTK NovSU schedule site page
*@author Vladimir Turba <turbavladimir@yandex.ru>
*@copyright 2015 Vladimir Turba
*/

if (file_exists('settings.php')) {
	require_once "settings.php";
} else {
	require_once 'settings-default.php';
}
require_once "parser.php";
require_once "PHPExcel.php";
require_once "PHPExcel/IOFactory.php";


function isCacheExist()
{
	global $tmpDir;
	if ((file_exists($tmpDir . "/json/" . $_GET['group'])) &
		(file_exists($tmpDir . "/timestamp/" . $_GET['group'])))
	{
		return true;
	}

	return false;
}

function getCacheTimestamp()
{
	global $tmpDir;
	return file_get_contents($tmpDir . "/timestamp/" . $_GET['group']);
}

function removeEmptyDays(&$days)
{
	foreach ($days as $day)
	{
		$isEmpty = true;
		for ($i = 0; $i < count($day["schedule"]) - 1; $i++)
		{
			if ($day["schedule"][$i] != "")
			{
				$isEmpty = false;
			}
		}
		if ($isEmpty)
		{
			array_pop($days);
		}
	}
}

function removeEmptyEndings(&$days)
{
	foreach ($days as &$day)
	{
		while ($day["schedule"][count($day["schedule"]) - 1] == NULL ||
			   $day["schedule"][count($day["schedule"]) - 1] == "&nbsp;")
		{
			array_pop($day["schedule"]);
			array_pop($day["time"]);
		}
		$timeScheduleDiff = count($day["schedule"]) - count($day["time"]);
		if ($timeScheduleDiff < 0) {
			$day["time"] = array_slice($day["time"], 0, $timeScheduleDiff);
		}
		if ($timeScheduleDiff > 0) {
			$day["schedule"] = array_slice($day["schedule"], 0, -$timeScheduleDiff);
		}
	}

}

function updateCache($filename)
{
	global $tmpDir, $jsonFlags, $timePattern, $invertWeekType;
	$xls = PHPExcel_IOFactory::load($tmpDir . "/xls/" . $filename);
	$sheet = $xls->getActiveSheet();
	$groupCell = getGroupCell($sheet);
	if ($groupCell[0] == -1)
	{
		error_log("failed to find group " . $_GET['group'] . " in xls file");
		echo "Failed to find group in xls file";
		exit;
	}

	$ranges = getWeekDayRanges($sheet, $groupCell[1] + 1);

	$timeCol = $groupCell[0] - 1;
	if (!empty($_GET['timeCol']))
	{
		$timeCol = $_GET['timeCol'];
	}
	else
	{
		if (!preg_match($timePattern, getCellValue($sheet, $groupCell[0] - 1, $groupCell[1] + 1)))
		{
			$timeCol = getTimeCol($sheet, $timeCol, $groupCell[1] + 1);
		}
	}

	foreach ($ranges as $range)
	{
		if ($range[1] - $range[0] <= 0)
		{
			continue;
		}

		$output["days"][]["schedule"] = getScheduleOfRowRange($sheet, $timeCol, $groupCell[0], $range);
		$output["days"][count($output["days"]) - 1]["time"] = getCallsSchedule($sheet, $timeCol, $range);
	}

	removeEmptyDays($output["days"]);
	removeEmptyEndings($output["days"]);

	if (!file_exists($tmpDir . "/json")) {
		mkdir($tmpDir . "/json", 0755, true);
	}
	$jsonFile = $tmpDir . "/json/" . $_GET['group'];
	file_put_contents($jsonFile, json_encode($output, $jsonFlags));
}

function storeTimestamp($timeString)
{
	global $tmpDir;
	if (!file_exists($tmpDir . "/timestamp")) {
		mkdir($tmpDir . "/timestamp", 0755, true);
	}
	file_put_contents($tmpDir . "/timestamp/" . $_GET['group'], strtotime($timeString));
}

function appendDay(&$list, $day, $nextWeek = false)
{
	global $invertWeekType;
	$isLowWeek = date("W") % 2 == 0;
	if ($invertWeekType)
	{
		$isLowWeek = !$isLowWeek;
	}
	if ($nextWeek)
	{
		$isLowWeek = !$isLowWeek;
	}
	foreach ($day->schedule as $item)
	{
		if (gettype($item) == "object")
		{
			if ($isLowWeek)
			{
				$list["schedule"][] = $item->bottom;
			}
			else
			{
				$list["schedule"][] = $item->top;
			}
		}
		else
		{
			$list["schedule"][] = $item;
		}
	}
	$list["time"] = $day->time;

	while ($list["schedule"][count($list["schedule"]) - 1] == "&nbsp;")
	{
		array_pop($list["schedule"]);
		array_pop($list["time"]);
	}
}

?>