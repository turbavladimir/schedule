<?php
if (!isset($_REQUEST['format']) || !$_REQUEST['format']) {
	die(json_encode(['error' => 'no format specified']));
}
if ((!isset($_REQUEST['group']) || !$_REQUEST['group'])) {
	die(json_encode(['error' => 'no group specified']));
}

require_once '../php/DBHelper.php';
$db = DBHelper::get();

if (!$db->groupExsist($db->escape($_REQUEST['group']))) {
	die(json_encode(['error' => 'group doesn\'t exist']));
}

if (!@include'../settings/app.php') {
	require_once '../settings/app.default.php';
}

require_once '../php/Utils.php';

$days = [];
for ($weekday = 0; $weekday < 6; $weekday++) {
	$days[] = $db->getGroupSchedule($db->escape($_REQUEST['group']), $weekday, 0);
}

$datetime = new DateTime('now', new DateTimeZone('UTC'));
$classes = [];
for ($weekNum = date('W'); $weekNum <= date('W') + 1; $weekNum++) {
	$weekTypeNum = Utils::getWeekTypeNum($invertWeekType, $weekNum);
	foreach ($days as $weekday => $day) {
		$datetime->setISODate(date('Y'), $weekNum, $weekday + 1);
		$datetime->setTime(0, 0);
		$dayStartTimestamp = $datetime->getTimestamp();

		foreach ($day as $class) {
			$classInfo = [];
			if (isset($class[$weekTypeNum])) {
				$classInfo = $class[$weekTypeNum];
			} elseif (isset($class[0])) {
				$classInfo = $class[0];
			}
			if ($classInfo) {
				if (!$classInfo['subject']) continue;

				$classInfo['start'] = $classInfo['start'] * 60 + $dayStartTimestamp;
				$classInfo['end'] = $classInfo['end'] * 60 + $dayStartTimestamp;
				unset($classInfo['weektype']);
				$classes[] = $classInfo;
			}
		}
	}
}

$cacheTime = Utils::cacheTime($_REQUEST['group'], $cacheDir);
if ($_REQUEST['format'] == 'ics') {
	require_once "../php/icalendar/zapcallib.php";
	$icalobj = new ZCiCal();

	foreach ($classes as $id => $class) {
		$eventobj = new ZCiCalNode("VEVENT", $icalobj->curnode);
		$eventobj->addNode(new ZCiCalDataNode("SUMMARY:$class[subject]"));
		$eventobj->addNode(new ZCiCalDataNode("DTSTART:" . ZDateHelper::fromUnixDateTimetoiCal($class['start'])));
		$eventobj->addNode(new ZCiCalDataNode("DTEND:" . ZDateHelper::fromUnixDateTimetoiCal($class['end'])));
		$eventobj->addNode(new ZCiCalDataNode("UID:$_REQUEST[group]-$id-$_SERVER[SERVER_NAME]"));
		$eventobj->addNode(new ZCiCalDataNode("DTSTAMP:" . ZDateHelper::fromUnixDateTimetoiCal($cacheTime)));
	}

	header('Content-type: text/calendar; charset=utf-8');
	echo $icalobj->export();
} else {
	die('format not found');
}
