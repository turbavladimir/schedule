<?php

require_once 'PHPExcel.php';
require_once 'PHPExcel/IOFactory.php';

class Parser {
	/**@var $sheet PHPExcel_Worksheet*/
	private $sheet;
	private $cacheFolder;

	function __construct($cacheFolder) {
		$this->cacheFolder = $cacheFolder;
	}

	private function getGroupCourse($groups, $groupId) {
		foreach ($groups as $group) {
			if ($group->id == $groupId) {
				return $group;
			}
		}

		throw new Exception('Failed to find group by id');
	}

	public function updateDbData($groups, $files) {
		foreach ($files as $file) {
			/**@var $file File*/
			$this->loadSheet($file->path);

			$groupsRow = $this->findGroupsRow();
			$groupList = $this->getGroupList($groupsRow);

			$weekDayRanges = $this->getWeekDayRanges($groupsRow + 1);
			foreach ($groupList as $group) {
				DBHelper::get()->clearGroupSchedule($group['name']);
				$course = $groups[intval($group['name'])]->course;
				$timeCol = $this->getTimeCol($group['col'], $groupsRow + 1);

				foreach ($weekDayRanges as $weekday => $range) {
					$schedule = $this->getSchedule($timeCol, $group['col'], $range['start'], $range['end']);
					if ($schedule) {
						$calls = $this->getCallsSchedule($timeCol, $range['start'], $range['end']);
						DBHelper::get()->insertDay($schedule, $calls, $group, $course, $weekday);
					}
				}
			}
			return;//TODO: remove
		}
	}

	private function loadSheet($path) {
		$xls = PHPExcel_IOFactory::load($this->cacheFolder . "/xls/$path");
		$this->sheet = $xls->getActiveSheet();
	}

	private function findGroupsRow() {
		for ($i = 0; $i < $this->sheet->getHighestRow(); $i++) {
			if ($this->sheet->getCellByColumnAndRow(0, $i)->getValue() == 'ПОНЕДЕЛЬНИК') {
				return $i - 1;
			}
		}

		throw new Exception('Failed to find row with group numbers');
	}

	private function getGroupList($row) {
		$groups = [];

		$maxColumn = PHPExcel_Cell::columnIndexFromString($this->sheet->getHighestColumn());
		for ($i = 0; $i < $maxColumn; $i++) {
			$value = $this->sheet->getCellByColumnAndRow($i, $row)->getValue();
			if (!in_array($value, $groups) && str_replace(' ', '', $value)) {
				$groups[] = ['col' => $i, 'name' => $value];
			}
		}

		return $groups;
	}

	private function isMerged($col, $row) {
		$cell = $this->sheet->getCellByColumnAndRow($col, $row);

		foreach ($this->sheet->getMergeCells() as $cells) {
			if ($cell->isInRange($cells)) {
				return $cells;
			}
		}

		return false;
	}

	private function getWeekDayRanges($startRow) {
		$lastWeekDay = $this->sheet->getCellByColumnAndRow(0, $startRow)->getValue();
		$lastRow = $startRow;
		$ranges = [];
		for ($i = $startRow; $i < $this->sheet->getHighestRow(); $i++) {
			$currentValue = $this->getCellValue(0, $i);
			if ($currentValue != $lastWeekDay) {
				if ($i - $lastRow - 1 <= 0) {
					$lastWeekDay = $currentValue;
					$lastRow = $i;
					continue;
				}
				$ranges[] = ['start' => $lastRow, 'end' => $i - 1,  'name' => $lastWeekDay];
				$lastWeekDay = $currentValue;
				$lastRow = $i;
			}
		}

		return $ranges;
	}

	private function getTimeCol($startCol, $row) {
		for ($i = $startCol; $i > 0; $i--) {
			if (preg_match('/\d+\.\d+\-\d+\.\d+/', $this->getCellValue($i, $row))) {
				return $i;
			}
		}

		throw new Exception('Failed to find time column');
	}

	private function getCellValue($col, $row) {
		$check = $this->isMerged($col, $row);
		if ($check !== false) {
			return $this->sheet->getCell(explode(':', $check)[0])->getValue();
		}

		return $this->sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
	}

	private function getBorderRowsOfMergedCell($col, $row) {
		$cell = $this->sheet->getCellByColumnAndRow($col, $row);

		foreach ($this->sheet->getMergeCells() as $cells) {
			if ($cell->isInRange($cells)) {
				preg_match("/[A-Z](\d*):[A-Z](\d*)/" , $cells, $match);
				return [$match[1], $match[2]];
			}
		}

		return [-1];
	}

	private function replaceEmptinesAliases(&$item, $key) {
		$emptinessAliases = ["/^\_+$/", "/^\-+$/"];

		foreach ($emptinessAliases as $alias) {
			$item = preg_replace($alias, '', $item);
		}
	}

	private function timeCellToArray($data) {
		$parts = explode('-', $data);
		$start = explode('.', $parts[0]);
		$end = explode('.', $parts[1]);

		return ['start' => $start[0] * 60 + $start[1], 'end' => $end[0] * 60 + $end[1]];
	}

	private function getCallsSchedule($timeCol, $startRow, $endRow) {
		$output = [];
		for ($i = $startRow; $i <= $endRow; $i++) {
			if ($this->getCellValue($timeCol, $i) == NULL) {
				continue;
			}
			if ($this->isMerged($timeCol, $i) !== false) {
				$output[] = $this->timeCellToArray($this->getCellValue($timeCol, $i));
				$timeBorders = $this->getBorderRowsOfMergedCell($timeCol, $i);
				$i += $timeBorders[1] - $timeBorders[0];
			} else {
				$output[] = $this->timeCellToArray($this->getCellValue($timeCol, $i));
			}
		}

		return $output;
	}

	private function getSchedule($timeCol, $itemCol, $startRow, $endRow) {
		$output = [];
		for ($i = $startRow; $i <= $endRow; $i++) {
			$topItem = $this->getCellValue($itemCol, $i);
			if ($this->isMerged($timeCol, $i) !== false) {
				$timeBorders = $this->getBorderRowsOfMergedCell($timeCol, $i);
				$itemBorders = $this->getBorderRowsOfMergedCell($itemCol, $i);
				if ($this->isMerged($itemCol, $i) !== false) {
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
						while ($this->getCellValue($itemCol, $i + $offset) == $topItem) {
							$offset++;
						}
						$output[] = [
							'top' => $topItem,
							'bottom' => $this->getCellValue($itemCol, $i + $offset)
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
					while ($this->getCellValue($itemCol, $i + $lowWeekOffset) == $topItem) {
						$lowWeekOffset++;
					}
					$output[] = [
						'top' => $topItem,
						'bottom' => $this->getCellValue($itemCol, $i + $lowWeekOffset)
					];
					$i += $timeBorders[1] - $timeBorders[0];
				}
			} else {
				$output[] = $topItem;
			}

			array_walk_recursive($output, [$this, 'replaceEmptinesAliases']);
		}

		return array_filter($output);//TODO: ensure that it's not gonna remove middle-day classes, implements insert of empty classes
	}
}