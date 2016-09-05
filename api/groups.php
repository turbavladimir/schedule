<?php

/*
*It's a part of PTK NovSU schedule site page
*@author Vladimir Turba <turbavladimir@yandex.ru>
*@copyright 2016 Vladimir Turba
*/

if (file_exists('subgroups.php')) {
	require_once 'subgroups.php';
} else {
	require_once 'subgroups-default.php';
}
if (file_exists('settings.php')) {
	require_once 'settings.php';
} else {
	require_once 'settings-default.php';
}

$groups = [];
if (isset($_REQUEST['course']) && $_REQUEST['course'] == 'custom') {
	if (file_exists('../custom')) {
		$customGroups = scandir('../custom');
		$groups = array_slice($customGroups, 2);
	}
	sort($groups);
	die(json_encode($groups, $jsonFlags));
}

$page = file_get_contents($url . $timeTable);
$dom = new DOMDocument();
@$dom->loadHTML($page);
$xpath = new DOMXPath($dom);
$div = $xpath->query('//div[h1[contains(text(),"Политехнический колледж")]]')->item(0);
$nodes = [];
if (isset($_REQUEST['course']) && $_REQUEST['course']) {
	$course = $xpath->query('.//td', $div)->item($_REQUEST['course'] - 1);
	if ($course) {
		$nodes = $xpath->query('.//a[contains(@href,".xls")]', $course);
	}
} else {
	$nodes = $xpath->query('.//a[contains(@href,".xls")]', $div);
}
foreach ($nodes as $node) {
	$groups[] = $node->nodeValue;
}

foreach ($groups as $key => $group) {
	if (in_array($group, array_keys($subGroups))) {
		foreach ($subGroups[$group] as $subGroup) {
			$groups[] = "$group $subGroup";
		}
		unset($groups[$key]);
	}
}
sort($groups);
echo json_encode($groups, $jsonFlags);
