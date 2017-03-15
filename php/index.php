<?php
//TODO: implement important errors handling(try catch)

global $lastGen;
$lastGen['start'] = time();
$lastGen['error'] = false;

//load app settings
$debug = false;
if (! @include'../settings/app.php') {
	require_once '../settings/app.default.php';
	$debug = true;
}

//create cache folders if they do not exist
if (!is_dir($cacheDir)) {
	mkdir($cacheDir);
	mkdir("$cacheDir/xls/");
}

//fetch timetable data and update cached xls files
require_once 'Scrapper.php';
$scrapper = new Scrapper($url, $timeTable, $cacheDir);
$tableData = $scrapper->fetchTableData();
$updatedFiles = $scrapper->updateFiles($tableData->getFiles());

require_once 'DBHelper.php';
DBHelper::get()->mergeGroups($tableData->getGroups());

if ($updatedFiles || $debug) {
	require_once 'Parser.php';
	$parser = new Parser($cacheDir);
	$parser->updateDbData($tableData->getGroups(), $tableData->getFileNames());
}

$lastGen['end'] = time();
file_put_contents("$cacheDir/lastgen", serialize($lastGen));