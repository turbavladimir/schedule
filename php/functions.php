<?php

/*
*It's a part of PTK NovSU schedule site page
*@author Vladimir Turba <turbavladimir@yandex.ru>
*@copyright 2015 Vladimir Turba
*/

require_once "settings.php";
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
	return file_get_contents($tmpDir . "/timestamp/" . $_GET['group']);//WARNING: not sure about data types
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
		while ($day["schedule"][count($day) - 1] == NULL)
		{
			array_pop($day["schedule"]);
			array_pop($day["time"]);
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
		echo "E2";
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

		$output[]["schedule"] = getScheduleOfRowRange($sheet, $timeCol, $groupCell[0], $range);
		$output[count($output) - 1]["time"] = getCallsSchedule($sheet, $timeCol, $range);
	}

	removeEmptyDays($output);
	removeEmptyEndings($output);

	mkdir($tmpDir . "/json", 0755, true);
	$jsonFile = $tmpDir . "/json/" . $_GET['group'];
	file_put_contents($jsonFile, json_encode($output, $jsonFlags));
}

function storeTimestamp($timeString)
{
	global $tmpDir;
	mkdir($tmpDir . "/timestamp", 0755, true);
	file_put_contents($tmpDir . "/timestamp/" . $_GET['group'], strtotime($timeString));
}

function appendDay(&$list, $day)
{
	global $invertWeekType;
	$isLowWeek = date("W") % 2 == 0;
	if ($invertWeekType)
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