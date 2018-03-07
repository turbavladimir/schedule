<?php
$cacheDir = '/var/tmp/schedule';
$url = 'http://novsu.ru';
$timeTable = '/univer/timetable/spo';
$invertWeekType = false;
$tgReporterBotToken = '';
$tgChatId = 0; //can be found in https://api.telegram.org/bot{BOT_TOKEN}/getUpdates after sending start to bot
$restTime = 10; //minutes of pause in-between halves of class
$classLength = 100; //minutes of full class including rest