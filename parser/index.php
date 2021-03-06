<?php
global $lastGen;
$lastGen['start'] = time();
$lastGen['error'] = false;

//load app settings
if (! @include'../settings/app.php') {
	require_once '../settings/app.default.php';
	$debug_group = '.'; //use dot to force cache update for all groups
}

if (!isset($debug_group)) {
	$debug_group = '';
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
$updatedFiles = $scrapper->updateFiles($tableData->getFiles(), $debug_group);

require_once '../php/DBHelper.php';
DBHelper::get()->mergeGroups($tableData->getGroups());

if ($updatedFiles) {
	require_once 'Parser.php';
	$parser = new Parser($cacheDir);

	$groups = $tableData->getGroups();
	foreach ($updatedFiles as $file) {
		if ($debug_group && $debug_group != '.' && strpos($file->path, $debug_group) !== false) {
			echo 'File debug ready' . PHP_EOL;
		}

		$parser->loadSheet($file->path);

		try {
			$groupsRow = $parser->findGroupsRow();
		} catch (Exception $e) {
			echo "{$e->getMessage()} in {$file->path}" . PHP_EOL;
			continue;
		}
		$groupList = $parser->getGroupList($groupsRow);

		$weekDayRanges = $parser->getWeekDayRanges($groupsRow + 1);
		foreach ($groupList as $group) {
			if (strpos($group['name'], $debug_group) !== false) {
				echo 'Group debug ready' . PHP_EOL;
			}
			if ($debug_group) {
				echo "Processing group: $group[name]" . PHP_EOL;
			}

			DBHelper::get()->clearGroupSchedule($group['name']);

			if (!isset($groups[intval($group['name'])])) continue;
			$course = $groups[intval($group['name'])]->course;
			$timeCol = $parser->getTimeCol($group['col'], $groupsRow + 1);

			try {
				foreach ($weekDayRanges as $weekday => $range) {
					$classes = $parser->getSchedule($timeCol, $group['col'], $range['start'], $range['end']);
					if ($classes) {
						$calls = $parser->getCallsSchedule($timeCol, $range['start'], $range['end']);
						DBHelper::get()->updateDay(array_values($classes), array_values($calls), $group['name'], $course, $weekday);
					}
				}
			} catch (Exception $e) {
				$lastGen['error'] = true;

				$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
				echo "<pre>Failed to parse $group[name] at $days[$weekday]: {$e->getMessage()}</pre>\n";
				DBHelper::get()->clearGroupSchedule($group['name']);
				DBHelper::get()->removeGroup($group['name']);
			}
		}
	}
}

$lastGen['end'] = time();
file_put_contents("$cacheDir/lastgen", serialize($lastGen));