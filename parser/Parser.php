<?php

require_once 'PHPExcel.php';
require_once 'PHPExcel/IOFactory.php';

class Parser {
	/**@var $sheet PHPExcel_Worksheet*/
	private $sheet;

	function __construct() {
	}

	public function getGroupCourse($groups, $groupId) {
		foreach ($groups as $group) {
			if ($group->id == $groupId) {
				return $group;
			}
		}

		throw new Exception('unable to find group by id');
	}

	public function loadSheet($path) {
		$xls = PHPExcel_IOFactory::load($path);
		$this->sheet = $xls->getActiveSheet();
	}

	public function findGroupsRow() {
		for ($i = 0; $i < $this->sheet->getHighestRow(); $i++) {
			if ($this->sheet->getCellByColumnAndRow(0, $i)->getValue() == 'ПОНЕДЕЛЬНИК') {
				return $i - 1;
			}
		}

		throw new Exception('unable to find row with group numbers');
	}

	public function getGroupList($row) {
		$groups = [];

		$maxColumn = PHPExcel_Cell::columnIndexFromString($this->sheet->getHighestColumn());
		for ($i = 0; $i < $maxColumn; $i++) {
			if (!$this->isColVisible($i)) {
				continue;
			}
			$value = $this->sheet->getCellByColumnAndRow($i, $row)->getValue();
			if (!in_array($value, $groups) && str_replace(' ', '', $value)) {
				$groups[] = ['col' => $i, 'name' => $value];
			}
		}

		return $groups;
	}

	public function getWeekDayRanges($startRow) {
		//find first visible column
		$highestColumn = PHPExcel_Cell::columnIndexFromString($this->sheet->getHighestColumn());
		$firstCol = 0;
		for ($i = 0; $i < $highestColumn; $i++) {
			if ($this->isColVisible($i)) {
				$firstCol = $i;
				break;
			}
		}

		$lastWeekDay = $this->sheet->getCellByColumnAndRow($firstCol, $startRow)->getValue();
		$ranges = [];
		$visibleRows = [];
		for ($i = $startRow; $i <= $this->sheet->getHighestRow(); $i++) {
			if (!$this->isRowVisible($i)) {
				continue;
			}

			$currentValue = $this->getCellValue($firstCol, $i);
			if ($currentValue != $lastWeekDay) {
				if ($visibleRows) {
					$ranges[] = ['start' => $visibleRows[0], 'end' => end($visibleRows),  'name' => $lastWeekDay];
					$lastWeekDay = $currentValue;
				}
				$visibleRows = [];
			}

			$visibleRows[] = $i;
		}

		return $ranges;
	}

	public function getTimeCol($startCol, $row) {
		for ($i = $startCol; $i > 0; $i--) {
			if (preg_match('/\d+\.\d+\-\d+\.\d+/', $this->getCellValue($i, $row))) {
				return $i;
			}
		}

		throw new Exception('unable to find time column');
	}

	public function getCallsSchedule($timeCol, $startRow, $endRow) {
		$output = [];
		for ($i = $startRow; $i <= $endRow; $i++) {
//			if ($this->getCellValue($timeCol, $i) == NULL) {//TODO: remove later if everything works
//				continue;
//			}
			if ($this->isMerged($timeCol, $i) !== false) {
				$output[] = $this->timeCellToArray($this->getCellValue($timeCol, $i));
				$timeBorders = $this->getBorderRowsOfMergedCell($timeCol, $i);
				if (!$timeBorders) {
					throw new Exception('unable to find time borders of ' . (count($output) + 1) . ' class');
				}
				$i += $timeBorders[1] - $timeBorders[0];
			} else {
				$output[] = $this->timeCellToArray($this->getCellValue($timeCol, $i));
			}
		}

		return $output;
	}

	public function getSchedule($timeCol, $itemCol, $startRow, $endRow) {
		$output = [];
		for ($i = $startRow; $i <= $endRow; $i++) {
			$topItem = $this->getCellValue($itemCol, $i);
			if ($this->isMerged($timeCol, $i) !== false) {
				$timeBorders = $this->getBorderRowsOfMergedCell($timeCol, $i);
				if (!$timeBorders) {
					throw new Exception('unable to find time borders of ' . (count($output) + 1) . ' class');
				}
				if ($this->isMerged($itemCol, $i) !== false) {
					$itemBorders = $this->getBorderRowsOfMergedCell($itemCol, $i);
					if (!$itemBorders) {
						throw new Exception('unable to find item borders of ' . (count($output) + 1) . ' class');
					}
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
						$i += $timeBorders[1] - $timeBorders[0];
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
		}

		array_walk_recursive($output, [$this, 'replaceEmptinesAliases']);

		while (!end($output) && $output) {//trim empty array elements from the end of day
			array_pop($output);
		}
		return $output;
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
				$visibleRows = [];
				for ($i = $match[1]; $i <= $match[2]; $i++) {
					if ($this->isRowVisible($i)) {
						$visibleRows[] = $i;
					}
				}
				if ($visibleRows) {
					return [$visibleRows[0], end($visibleRows)];
				} else {
					return false;
				}
			}
		}

		return false;
	}

	private function replaceEmptinesAliases(&$item, $key) {
		$emptinessAliases = ["/^\_+$/", "/^\-+$/"];

		foreach ($emptinessAliases as $alias) {
			$item = preg_replace($alias, '', $item);
		}
	}

	private function timeCellToArray($data) {
		if (!$data) {
			return ['start' => 0, 'end' => 0];
		}

		$out = [];
		set_error_handler(function () use ($data, $out) {
			echo "Notice when parsing time cell: $data" . PHP_EOL;
		}, E_WARNING | E_NOTICE);

		$parts = explode('-', $data);
		if (count($parts) < 2) {
			$parts = explode(' ', $data);
		}
		$start = explode('.', $parts[0]);
		$end = explode('.', $parts[1]);

		$out =  ['start' => $start[0] * 60 + $start[1], 'end' => $end[0] * 60 + $end[1]];
		restore_error_handler();
		return $out;
	}

	private function isRowVisible($row) {
		$dimensions = $this->sheet->getRowDimension($row);

		if ($dimensions->getVisible() && $dimensions->getRowHeight() > 5) {
			return true;
		} else {
			return false;
		}
	}

	private function isColVisible($col) {
		$dimensions = $this->sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col));

		if ($dimensions->getVisible() && $dimensions->getWidth() > 0.5) {
			return true;
		} else {
			return false;
		}
	}

	public function getCellRange($startCol, $startRow, $endCol, $endRow) {
		$startCell = PHPExcel_Cell::stringFromColumnIndex($startCol) . $startRow;
		$endCell = PHPExcel_Cell::stringFromColumnIndex($endCol) . $endRow;
		return $this->sheet->rangeToArray("$startCell:$endCell");
	}
}