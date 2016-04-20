<?php

/*
*It's a part of PTK NovSU schedule site page
*@author Vladimir Turba <turbavladimir@yandex.ru>
*@copyright 2015 Vladimir Turba
*/

require_once "settings.php";
require_once "functions.php";

//check arguments
if (empty($_GET['group']))
{
	echo "Error: no group specified";
	exit;
}

if (strpos($_GET['group'], 'УП') === false) {
	$page = file_get_contents($url . $timeTable);
	preg_match($groupPattern, $_GET['group'], $groupMatch);
	//get match with relative xls file url and last update time
	if (!preg_match("/<a href=\"(.*)\" title=\"(.*)\">" . $groupMatch[0] . "<\/a>/", $page, $match))
	{
		error_log("failed to parse schedule version in timetable");
		exit;
	}
	if (!preg_match("/ptk\/(.*)\?/", $match[1], $filenameMatch))
	{
		error_log("failed to parse xls filename");
		exit;
	}
	$filename = $filenameMatch[1];

	//generate or update cache if needed
	if (isCacheExist())
	{
		//check schedule version
		$timestamp = strtotime($match[2]);
		if ($timestamp > getCacheTimestamp())
		{
			if (!file_exists($tmpDir . "/xls")) {
				mkdir($tmpDir . "/xls", 0755, true);
			}
			file_put_contents($tmpDir . "/xls/" . $filename, fopen($url . "/" . $match[1], "r"));
			updateCache($filename);
			storeTimestamp($match[2]);
		}
	}
	else
	{
		if (!file_exists($tmpDir . "/xls/" . $filename))
		{
			if (!file_exists($tmpDir . "/xls")) {
				mkdir($tmpDir . "/xls", 0755, true);
			}
			file_put_contents($tmpDir . "/xls/" . $filename, fopen($url . "/" . $match[1], "r"));
		}

		updateCache($filename);
		storeTimestamp($match[2]);
	}
}
$json = json_decode(file_get_contents($tmpDir . "/json/" . $_GET['group']));

$weekDay = date("w");
$output = [];
if (count($json->days) < 6)
{
	if ($weekDay == 6)
	{
		$output[] = "Saturday";
	}
	if ($weekDay == 0)
	{
		$output[] = "Sunday";
	}
}
$i = 0;
foreach ($json->days as $item)
{
	if (($i == $weekDay - 1) || ($i == $weekDay))
	{
		if (($weekDay == 0) && ($i == 0))
		{
			appendDay($output[], $item, true);
		}
		else
		{
			appendDay($output[], $item);
		}
	}
	$i++;
}
if (count($output) < 2)
{
	if ($weekDay == 5)
	{
		$output[] = "Saturday";
	}
	if ($weekDay == 6)
	{
		$output[] = "Sunday";
	}
}
echo json_encode($output, $jsonFlags);
?>
