<?php

if (! @include'../settings/app.php') {
	require_once '../settings/app.default.php';
}

if (!isset($_REQUEST['group']) || !$_REQUEST['group']) {
	echo json_encode(['success' => false, 'error' => 'group not specified']);
}

$fileName = glob("$cacheDir/xls/*" . intval($_REQUEST['group']) . "*.xls");
if (isset($fileName[0])) {
	require_once '../parser/Parser.php';
	$parser = new Parser($cacheDir);
	$parser->loadSheet(basename($fileName[0]));
	try {
		$groupsRow = $parser->findGroupsRow();
	} catch (Exception $e) {

	}
	if (isset($groupsRow)) {
		$ranges = $parser->getWeekDayRanges($groupsRow + 1);
		$groups = $parser->getGroupList($groupsRow);

		foreach ($groups as $group) {
			if ($group['name'] == $_REQUEST['group']) {
				$schedule = [];
				foreach ($ranges as $range) {
					$schedule[] = $parser->getCellRange($group['col'], $range['start'], $group['col'], $range['end']);
				}
			}
		}
	}
}

require_once '../php/tg-api/BaseType.php';
require_once '../php/tg-api/TypeInterface.php';
foreach (glob('../php/tg-api/Types/*.php') as $filename) {
	require_once $filename;
}
require_once '../php/tg-api/Exception.php';
require_once '../php/tg-api/HttpException.php';
require_once '../php/tg-api/BotApi.php';

$ip = !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

$rand = md5(date('r', time()));
preg_match('#^data\:(image/[^;]+);#', $_REQUEST['image'], $imageMime);
$image = chunk_split(preg_replace("/^data:image\/[^;]+;base64,/", '', $_REQUEST['image']));
$temp = tmpfile();
fwrite($temp, base64_decode($image));

$message = "IP: $ip\nUser Agent: $_SERVER[HTTP_USER_AGENT]\n$url$timeTable";

try {
	$bot = new \TelegramBot\Api\BotApi($tgReporterBotToken);
	$photo = new \CURLFile(stream_get_meta_data($temp)['uri'], $imageMime[1]);
	$bot->sendPhoto($tgChatId, $photo, $message);
	if (isset($schedule)) {
		$days = [];
		foreach ($schedule as $day) {
			$day = call_user_func_array('array_merge', $day);
			$days[] = implode("\n", $day);
		}
		$bot->sendMessage($tgChatId, implode("\n---------------\n", $days), null, false, null, null, true);

		$xls = new \CURLFile($fileName[0]);
		$bot->sendDocument($tgChatId, $xls, null, null, null, true);
	}
} catch (Exception $e) {
	echo json_encode(['success' => false, 'error' => $e->getMessage()]);
	exit();
}
echo json_encode(['success' => true]);