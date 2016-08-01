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
require_once "PHPExcel.php";

function getGroupCell($sheet) {
	for ($i = 0; $i < $sheet->getHighestRow(); $i++) {
		for ($j = 0; $j < PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn()); $j++) {
			if ($sheet->getCellByColumnAndRow($j, $i)->getValue() == $_GET['group']) {
				return [$j, $i];
			}
		}
	}

	return [-1];
}

function isMerged($sheet, $col, $row) {
	$cell = $sheet->getCellByColumnAndRow($col, $row);

	foreach ($sheet->getMergeCells() as $cells) {
		if ($cell->isInRange($cells)) {
			return [true, $cells];
		}
	}

	return [false];
}

function getWeekDayRanges($sheet, $startRow) {
	$ranges = [];

	$lastWeekDay = $sheet->getCellByColumnAndRow(0, $startRow)->getValue();
	$lastRow = $startRow;
	for ($i = $startRow; $i < $sheet->getHighestRow(); $i++) {
		$currentValue = getCellValue($sheet, 0, $i);
		if ($currentValue != $lastWeekDay) {
			$ranges[] = [$lastRow, $i - 1, $lastWeekDay];
			$lastWeekDay = $currentValue;
			$lastRow = $i;
		}
	}

	return $ranges;
}

function getTimeCol($sheet, $startCol, $row) {
	global $timePattern;
	for ($i = $startCol; $i > 0; $i--) {
		if (preg_match($timePattern, getCellValue($sheet, $i, $row))) {
			return $i;
		}
	}

	return -1;
}

function getCellValue($sheet, $col, $row) {
	$check = isMerged($sheet, $col, $row);
	if ($check[0]) {
		return $sheet->getCell(explode(":", $check[1])[0])->getValue();
	}

	return $sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
}

function getBorderRowsOfMergedCell($sheet, $col, $row) {
	$cell = $sheet->getCellByColumnAndRow($col, $row);

	foreach ($sheet->getMergeCells() as $cells) {
		if ($cell->isInRange($cells)) {
			preg_match("/[A-Z](\d*):[A-Z](\d*)/" , $cells, $match);
			return [$match[1], $match[2]];
		}
	}

	return [-1];
}

function replaceEmptinesAliases(&$item, $key) {
	global $emptinessAliases;
	foreach ($emptinessAliases as $alias) {
		$item = preg_replace($alias, "", $item);
		if ($item == "") {
			$item = "&nbsp;";
		}
	}
}

function getCallsSchedule($sheet, $timeCol, $range) {
	$output = [];

	for ($i = $range[0]; $i <= $range[1]; $i++) {
		if (getCellValue($sheet, $timeCol, $i) == NULL) {
			continue;
		}
		if (isMerged($sheet, $timeCol, $i)[0]) {
			$output[] = getCellValue($sheet, $timeCol, $i);
			$timeBorders = getBorderRowsOfMergedCell($sheet, $timeCol, $i);
			$i += $timeBorders[1] - $timeBorders[0];
		}
		else {
			$output[] = getCellValue($sheet, $timeCol, $i);
		}
	}

	return $output;
}

function getScheduleOfRowRange($sheet, $timeCol, $itemCol, $range) {
	$output = [];

	for ($i = $range[0]; $i <= $range[1]; $i++) {
		if (getCellValue($sheet, $itemCol, $i) == NULL) {
			continue;
		}
		if (isMerged($sheet, $timeCol, $i)[0]) {
			if (isMerged($sheet, $itemCol, $i)[0]) {
				$timeBorders = getBorderRowsOfMergedCell($sheet, $timeCol, $i);
				$itemBorders = getBorderRowsOfMergedCell($sheet, $itemCol, $i);
				if ($timeBorders == $itemBorders) {
					$output[] = getCellValue($sheet, $itemCol, $i);
					$i += $timeBorders[1] - $timeBorders[0];
				}
				else {
					$topItem = getCellValue($sheet, $itemCol, $i);
					$offset = 1;
					while (getCellValue($sheet, $itemCol, $i + $offset) == $topItem) {
						$offset++;
					}
					$output[] = [
						"top" => $topItem,
						"bottom" => getCellValue($sheet, $itemCol, $i + $offset)
					];
					$i += $offset;
				}
			}
			else {
				$topItem = getCellValue($sheet, $itemCol, $i);
				$lowWeekOffset = 1;
				while (getCellValue($sheet, $itemCol, $i + $lowWeekOffset) == $topItem) {
					$lowWeekOffset++;
				}
				$output[] = [
					"top" => $topItem,
					"bottom" => getCellValue($sheet, $itemCol, $i + $lowWeekOffset)
				];
				$timeBorders = getBorderRowsOfMergedCell($sheet, $timeCol, $i);
				$i += $timeBorders[1] - $timeBorders[0];
			}
		}
		else {
			$output[] = getCellValue($sheet, $itemCol, $i);
		}

		array_walk_recursive($output, "replaceEmptinesAliases");
	}

	return $output;
}