<?php

require_once '../php/DBHelper.php';

if (isset($_REQUEST['course'])) {
	$groups = DBHelper::get()->getGroups(intval($_REQUEST['course']));
} else {
	$groups = DBHelper::get()->getGroups();
}

echo json_encode($groups, JSON_UNESCAPED_UNICODE);
