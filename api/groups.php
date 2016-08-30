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

$page = file_get_contents($url . $timeTable);
$dom = new DOMDocument();
$dom->loadHTML($page);
$xpath = new DOMXPath($dom);
$div = $xpath->query('//div[h1[contains(text(),"Политехнический колледж")]]')[0];
$nodes = $xpath->query('.//a[contains(@href,".xls")]', $div);
$groups = [];
foreach ($nodes as $node) {
	$groups[] = $node->nodeValue;
}

if (file_exists("../custom")) {
	$customGroups = scandir('../custom');
	$groups = array_merge($groups,
array_slice($customGroups, 2));
}
echo json_encode($groups, $jsonFlags);
