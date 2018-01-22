<?php

require_once 'Structures.php';

class Scrapper {
	private $rootUrl, $cacheFolder, $page;
	
	function __construct($rootUrl, $timeTable, $cacheFolder) {
		$this->rootUrl = $rootUrl;
		$this->cacheFolder = $cacheFolder;
		$this->page = file_get_contents($rootUrl . $timeTable);
	}

	public function fetchTableData() {
		$dom = new DOMDocument();
		@$dom->loadHTML($this->page);
		$xpath = new DOMXPath($dom);

		//find tables with schedule
		$table = $xpath->query('//table[tr[1]/th="1 курс"]')[0];
		//find td with courses
		$courses = $xpath->query('./tr[2]/td', $table);

		$data = new TableData();

		foreach ($courses as $courseId => $course) {
			#find links to xls in course td
			$links = $xpath->query('./a', $course);

			foreach ($links as $link) {
				/**@var $link DOMElement*/
				$updateTime = strtotime($link->getAttribute('title'));

				$data->appendGroup(new Group($link->nodeValue, $courseId, $updateTime));
				$data->appendFile(new File(strtok($link->getAttribute('href'), '?'), $updateTime));
			}
		}

		return $data;
	}

	private function copyXls($file, $tsFile, $xlsFolder) {
		copy($this->rootUrl . $file->path, $xlsFolder . basename($file->path));
		file_put_contents($tsFile, $file->timestamp);

		$file->path = $xlsFolder . basename($file->path);
		return $file;
	}

	public function updateFiles($files, $force = '') {
		$xlsFolder = $this->cacheFolder . '/xls/';

		$updatedFiles = [];
		//iterate trought all links and check whether file up to date or not
		foreach ($files as $file) {
			/**@var $file File*/
			$tsFile =  $xlsFolder. basename($file->path, '.xls') . '.ts';

			if ($force && strpos($file->path, $force) !== false) { //is forced to update
				$updatedFiles[] = $this->copyXls($file, $tsFile, $xlsFolder);
			} elseif (file_exists($tsFile)) { //check timestamp of xls file
				$ts = file_get_contents($tsFile); //read timestamp of cache xls file

				if ($ts < $file->timestamp) { //download new file
					$updatedFiles[] = $this->copyXls($file, $tsFile, $xlsFolder);
				}
			} else { //download new file
				$updatedFiles[] = $this->copyXls($file, $tsFile, $xlsFolder);
			}
		}

		return $updatedFiles;
	}
}