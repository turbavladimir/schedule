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

$groups = [
	'5901',
	'5902',
	'5911 а',
	'5911 б',
	'5911 в',
	'4911 а',
	'4911 б',
	'4911 в',
	'5921',
	'5931',
	'4941',
	'5951',
	'5961'
];

if (file_exists("../custom")) {
	$customGroups = scandir('../custom');
	$groups = array_merge($groups,
array_slice($customGroups, 2));
}
echo json_encode($groups, $jsonFlags);
