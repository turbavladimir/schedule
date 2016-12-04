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

$message  = "<html>
<body>
IP: $ip<br>
User Agent: $_SERVER[HTTP_USER_AGENT]<br>
Click: $_REQUEST[x] $_REQUEST[y]<br>
Screen: $_REQUEST[width]x$_REQUEST[height]<br>
Group: $_REQUEST[group]<br>
Type: $_REQUEST[type]<br>
<b><a href='http://novsu.ru/univer/timetable/spo/' target='_blank'>Timetable</a></b><br>

<img src='$_REQUEST[image]'>
</body>";

if (mail($bugReportMail, 'Schedule bug report', $message, "MIME-Version: 1.0\r\nContent-type: text/html; charset=utf-8\r\n")) {
	echo json_encode(['success' => true]);
} else {
	echo json_encode(['success' => false, 'error' => 'failed to send mail']);
}