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
	//throw 'no group' error
	echo "E1";
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
	if ($timestamp < getCacheTimestamp())
	{
		updateCache($filename);
		storeTimestamp($match[2]);
	}
}
else
{
	if (!file_exists($tmpDir . "/xls/" . $filename))
	{
		mkdir($tmpDir . "/xls", 0755, true);
		file_put_contents($tmpDir . "/xls/" . $filename, fopen($url . "/" . $match[1], "r"));
	}

	updateCache($filename);
	storeTimestamp($match[2]);
}

$json = json_decode(file_get_contents($tmpDir . "/json/" . $_GET['group']));

$weekDay = date("w") - 1;
$output = [];
if ($weekDay == -1)
{
	$output[] = "Sunday";
}
if ($weekDay == 5)
{
	$output[] = "Saturday";
}
for ($i = 0; $i < count($json); $i++)
{
	if (($i == $weekDay - 1) || ($i == $weekDay))
	{
		if (($weekDay == 0) && ($i == 0))
		{
			appendDay($output[], $json[$i], true);
		}
		else
		{
			appendDay($output[], $json[$i]);
		}
	}
}
if (count($output) < 2)
{
	if ($weekDay == 4)
	{
		$output[] = "Saturday";
	}
	if ($weekDay == 5)
	{
		$output[] = "Sunday";
	}
}


echo json_encode($output, $jsonFlags);
?>
