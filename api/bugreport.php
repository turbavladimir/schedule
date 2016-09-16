<?php

/*
*It's a part of PTK NovSU schedule site page
*@author Vladimir Turba <turbavladimir@yandex.ru>
*@copyright 2016 Vladimir Turba
*/

if (file_exists('settings.php')) {
	require_once 'settings.php';
} else {
	require_once 'settings-default.php';
}

$ip = !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
$message = "From: $ip\nUA: $_SERVER[HTTP_USER_AGENT]\nData: " . print_r($_REQUEST, true);
if (mail($bugReportMail, 'Schedule bug report', $message)) {
	echo json_encode(['success' => true]);
} else {
	echo json_encode(['success' => false, 'error' => 'failed to send mail']);
}