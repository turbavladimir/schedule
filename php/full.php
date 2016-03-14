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

//load cache and send to user
$cache =  json_decode(file_get_contents($tmpDir . "/json/" . $_GET['group']), true);


$isLowWeek = date("W") % 2 == 0;
if ($invertWeekType)
{
	$isLowWeek = !$isLowWeek;
}
$cache["lowWeek"] = $isLowWeek;

echo json_encode($cache, $jsonFlags);

?>
