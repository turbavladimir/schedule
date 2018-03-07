<?php

class Utils {
	static public function weekDayFromMon($weekday, $nextDay = false) {
		$weekday -= 1;
		if ($weekday == -1) {
			$weekday = 6;
		}
		if ($nextDay) {
			$weekday += 1;
		}
		if ($weekday == 7) $weekday = 0;

		return $weekday;
	}

	static public function formatMinutesOfDay($minutes) {
		$hours = intval($minutes / 60);
		$minutes = $minutes % 60;
		return "$hours:" . sprintf("%02d", $minutes);
	}

	static public function formatTime($start, $end, $rest = false, $classLength = false) {
		$startStr = self::formatMinutesOfDay($start);
		$endStr = self::formatMinutesOfDay($end);

		if ($rest && $classLength && $end - $start == $classLength) {
			$halfLength = ($end - $start - $rest) / 2;
			$startStr .= '-' . self::formatMinutesOfDay($start + $halfLength);
			$endStr = self::formatMinutesOfDay($end - $halfLength) . '-' . $endStr;
		}

		return ['start' => $startStr, 'end' => $endStr];
	}

	static public function getWeekTypeNum($invert = false, $weekNum = false) {
		if ($weekNum === false) {
			$weekNum = date('W');
		}

		$weekTypeNum = $weekNum % 2 + 1;
		if ($invert) {
			if ($weekTypeNum == 1) {
				$weekTypeNum = 2;
			} elseif ($weekTypeNum == 2) {
				$weekTypeNum = 1;
			}
		}
		return $weekTypeNum;
	}

	static public function cacheTime($group, $cacheDir) {
		$fileName = glob("$cacheDir/xls/*" . intval($group) . "*.ts")[0];
		return file_get_contents($fileName);
	}
}