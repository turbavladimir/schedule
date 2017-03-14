<?php

class File {
	function __construct($path, $timestamp) {
		$this->path = $path;
		$this->timestamp = $timestamp;
	}
}

class Group {
	function __construct($id, $course, $timestamp) {
		$this->id = intval($id);
		$this->course = $course;
		$this->timestamp = $timestamp;
	}
}

class TableData {
	private $groups = [];
	private $files = [];

	public function appendGroup($group) {
		if (!in_array($group, $this->groups)) {
			$this->groups[$group->id] = $group;
		}
	}

	public function appendFile($file) {
		if (!in_array($file, $this->files)) {
			$this->files[] = $file;
		}
	}

	public function getGroups() {
		return $this->groups;
	}

	public function getFiles() {
		return $this->files;
	}

	public function getFileNames() {
		$files = $this->files;
		foreach ($files as $file) {
			$file->path = basename($file->path);
		}

		return $files;
	}
}

class Scrapper {
	private $rootUrl, $cacheFolder, $page;
	
	function __construct($rootUrl, $timeTable, $cacheFolder) {
		$this->rootUrl = $rootUrl;
		$this->cacheDir = $cacheFolder;
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

	public function updateFiles($files) {
		$xlsFolder = $this->cacheDir . '/xls/';

		$updatedFiles = [];
		//iterate trought all links and check whether file up to date or not
		foreach ($files as $file) {
			/**@var $file File*/
			$tsFile =  $xlsFolder. basename($file->path, '.xls') . '.ts';

			if (file_exists($tsFile)) { //check timestamp of xls file
				$ts = file_get_contents($tsFile); //read timestamp of cache xls file

				if ($ts < $file->timestamp) { //download new file
					copy($this->rootUrl . $file->path, $xlsFolder . basename($file->path));
					file_put_contents($tsFile, $file->timestamp);
					$updatedFiles[] = $file;
				}
			} else { //download new file
				copy($this->rootUrl . $file->path, $xlsFolder . basename($file->path));
				file_put_contents($tsFile, $file->timestamp);
				$updatedFiles[] = $file;
			}
		}

		return $updatedFiles;
	}
}