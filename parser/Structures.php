<?php

class File {
	public $path;
	public $timestamp;

	function __construct($path, $timestamp) {
		$this->path = $path;
		$this->timestamp = $timestamp;
	}
}

class Group {
	public $id;
	public $course;
	public $timestamp;

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
