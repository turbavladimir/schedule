<?php

class Utils {
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