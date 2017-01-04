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

$rand = md5(date('r', time()));
$headers = "MIME-Version: 1.0\r\n" .
	"Content-Type: multipart/mixed; boundary=\"PHP-mixed-$rand\"";
$image = chunk_split(preg_replace("/^data:image\/[^;]+;base64,/", '', $_REQUEST['image']));

ob_start();
?>
--PHP-mixed-<?=$rand?>\r\n
Content-Type: multipart/alternative; boundary="PHP-alt-<?=$rand?>"

--PHP-alt-<?=$rand?>\r\n
Content-Type: text/html; charset="iso-8859-1"
Content-Transfer-Encoding: 7bit

IP: <?=$ip?><br>
User Agent: <?=$_SERVER['HTTP_USER_AGENT']?><br>
Click: <?=$_REQUEST['x']?> <?=$_REQUEST['y']?><br>
Screen: <?=$_REQUEST['width']?>x<?=$_REQUEST['height']?><br>
Group: <?=$_REQUEST['group']?><br>
Type: <?=$_REQUEST['type']?><br>
<b><a href='<?=$url . $timeTable?>' target='_blank'>Timetable</a></b><br>

--PHP-alt-<?=$rand?>\r\n

--PHP-mixed-<?=$rand?>\r\n
Content-Type: image/jpeg; name="page.jpeg"
Content-Transfer-Encoding: base64
Content-Disposition: attachment

<?=$image?>
--PHP-mixed-<?=$rand?>
<?
$message = ob_get_clean();
if (mail($bugReportMail, 'Schedule bug report', $message, $headers)) {
	echo json_encode(['success' => true]);
} else {
	echo json_encode(['success' => false, 'error' => 'failed to send mail']);
}