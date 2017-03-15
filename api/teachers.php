<?php

require_once '../php/DBHelper.php';

echo json_encode(DBHelper::get()->getTeachers(), JSON_UNESCAPED_UNICODE);
