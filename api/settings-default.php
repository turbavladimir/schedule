<?php

/*
*It's a part of PTK NovSU schedule site page
*@author Vladimir Turba <turbavladimir@yandex.ru>
*@copyright 2016 Vladimir Turba
*/

$tmpDir = "/tmp/schedule";
$url = "http://novsu.ru";
$timeTable = "/univer/timetable/spo";
$emptinessAliases = ["/\_*/", "/\-*/"];
$jsonFlags = JSON_UNESCAPED_UNICODE;
$groupPattern = "/\d*/";
$timePattern = "/\d*.\d*-\d*.\d*/";
$invertWeekType = True;