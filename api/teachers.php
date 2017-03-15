<?php

require_once __DIR__ . '/../php/DBHelper.php';

$teachers = DBHelper::get()->getTeachers();
echo json_encode($teachers, JSON_UNESCAPED_UNICODE);
