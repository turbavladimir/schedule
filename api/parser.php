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
require_once 'PHPExcel.php';

function getGroupCell($sheet) {
	$values = [
		$_GET['group'],
		str_replace(' ', '', $_GET['group'])
	];
	for ($i = 0; $i < $sheet->getHighestRow(); $i++) {
		for ($j = 0; $j < PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn()); $j++) {
			if (in_array($sheet->getCellByColumnAndRow($j, $i)->getValue(), $values)) {
				return [$j, $i];
			}
		}
	}

	return false;
}

function isMerged($sheet, $col, $row) {
	$cell = $sheet->getCellByColumnAndRow($col, $row);

	foreach ($sheet->getMergeCells() as $cells) {
		if ($cell->isInRange($cells)) {
			return $cells;
		}
	}

	return false;
}

function getWeekDayRanges($sheet, $startRow) {
	$lastWeekDay = $sheet->getCellByColumnAndRow(0, $startRow)->getValue();
	$lastRow = $startRow;
	for ($i = $startRow; $i < $sheet->getHighestRow(); $i++) {
		$currentValue = getCellValue($sheet, 0, $i);
		if ($currentValue != $lastWeekDay) {
			if ($i - $lastRow - 1 <= 0) {
				$lastWeekDay = $currentValue;
				$lastRow = $i;
				continue;
			}
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
	if ($check !== false) {
		return $sheet->getCell(explode(':', $check)[0])->getValue();
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
		$item = preg_replace($alias, '', $item);
		if ($item == '') {
			$item = '&nbsp;';
		}
	}
}

function getCallsSchedule($sheet, $timeCol, $groupCol, $range) {
	$output = [];
	for ($i = $range[0]; $i <= $range[1]; $i++) {
		if (getCellValue($sheet, $timeCol, $i) == NULL) {
			continue;
		}
		if (isMerged($sheet, $timeCol, $i) !== false) {
			$output[] = getCellValue($sheet, $timeCol, $i);
			$timeBorders = getBorderRowsOfMergedCell($sheet, $timeCol, $i);
			$i += $timeBorders[1] - $timeBorders[0];
		} else {
			$output[] = getCellValue($sheet, $timeCol, $i);
		}
	}

	return $output;
}

function getScheduleOfRowRange($sheet, $timeCol, $itemCol, $range) {
	$output = [];
	for ($i = $range[0]; $i <= $range[1]; $i++) {
		$topItem = getCellValue($sheet, $itemCol, $i);
		if (isMerged($sheet, $timeCol, $i) !== false) {
			$timeBorders = getBorderRowsOfMergedCell($sheet, $timeCol, $i);
			$itemBorders = getBorderRowsOfMergedCell($sheet, $itemCol, $i);
			if (isMerged($sheet, $itemCol, $i) !== false) {
				if ($topItem == NULL) {
					$output[] = $topItem;
					$i += $timeBorders[1] - $timeBorders[0];
					continue;
				}
				if ($timeBorders == $itemBorders) {
					$output[] = $topItem;
					$i += $timeBorders[1] - $timeBorders[0];
				} else {
					$offset = 1;
					while (getCellValue($sheet, $itemCol, $i + $offset) == $topItem) {
						$offset++;
					}
					$output[] = [
						'top' => $topItem,
						'bottom' => getCellValue($sheet, $itemCol, $i + $offset)
					];
					$i += $offset;
				}
			} else {
				$lowWeekOffset = 1;
				if ($topItem == NULL) {
					$output[] = $topItem;
					$i += $lowWeekOffset;
					continue;
				}
				while (getCellValue($sheet, $itemCol, $i + $lowWeekOffset) == $topItem) {
					$lowWeekOffset++;
				}
				$output[] = [
					'top' => $topItem,
					'bottom' => getCellValue($sheet, $itemCol, $i + $lowWeekOffset)
				];
				$i += $timeBorders[1] - $timeBorders[0];
			}
		} else {
			$output[] = $topItem;
		}

		array_walk_recursive($output, 'replaceEmptinesAliases');
	}

	return $output;
}