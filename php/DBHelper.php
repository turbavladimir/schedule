<?php


/**
 * Class DBHelper singleton
 */
class DBHelper {
	/**@var $db mysqli*/
	private $db;

	public static function get() {
		static $instance = null;
		if ($instance === null) {
			$instance = new DBHelper();
		}

		return $instance;
	}

	private function __construct() {
		//load db settings
		if (! @include __DIR__ . '/../settings/db.php') {
			require '../settings/db.default.php';
		}

		//connect to database
		$this->db = new mysqli($host, $user, $password, $database, $port);
		if ($this->db->connect_errno) {
			throw new Exception('Failed to connect to MYSQL: ' . $this->db->connect_error);
		}
	}

	function __destruct() {
		$this->db->close();
	}

	public function escape($str) {
		return mysqli_real_escape_string($this->db, $str);
	}

	public function mergeGroups($groups) {
		$this->db->query('DELETE FROM groups WHERE CAST(name as INT) not in (' . implode(',', array_keys($groups)) . ');');
	}

	public function clearGroupSchedule($groupName) {
		$this->db->query("DELETE FROM schedule WHERE group_id = (SELECT id FROM groups WHERE name = '$groupName')");
	}

	public function insertDay($sclasses, $calls, $group, $course, $weekday) {
		//add group if not exist
		$this->db->query("INSERT IGNORE INTO groups (name, course) VALUES ('$group[name]', $course)");

		$parsedClasses = [];
		$query = 'INSERT INTO schedule (group_id,subject_id,weekday,teacher_id,hall,weektype,start,end,comments) VALUES ';

		foreach ($sclasses as $id => $class) {
			if (gettype($class) == 'string') {
				$subClasses = $this->parseClass($class);
				foreach ($subClasses as $subClass) {
					$parsedClasses[] = $subClass;
					$query .= $this->buildValues($subClass, $group['name'], $weekday, 0, $calls[$id]['start'], $calls[$id]['end'], '');
				}
			} else { //separated week type
				foreach ($class as $typeName => $type) {
					$subClasses = $this->parseClass($type);
					foreach ($subClasses as $subClass) {
						$parsedClasses[] = $subClass;
						$typeNum = $typeName == 'bottom' ? 1 : 2;
						$query .= $this->buildValues($subClass, $group['name'], $weekday, $typeNum, $calls[$id]['start'], $calls[$id]['end'], '');
					}
				}
			}
		}
		$this->addTeachers($parsedClasses);
		$this->addSubjects($parsedClasses);

		$query = rtrim($query,',') . ';';
		$res = $this->db->query($query);
		if ($res === false) {
			throw new Exception('Failed to execute query: ' . $this->db->error);
		}
	}

	public function getGroups($course = null) {
		if ($course) {
			$res = $this->db->query("SELECT name FROM groups WHERE course=$course");
		} else {
			$res = $this->db->query("SELECT name FROM groups");
		}
		$groups = [];
		while ($row = $res->fetch_row()) {
			$groups[] = $row[0];
		}

		return $groups;
	}

	public function getTeachers() {
		$res = $this->db->query("SELECT surname FROM teachers");

		$teachers = [];
		while ($row = $res->fetch_row()) {
			$teachers[] = $row[0];
		}

		return $teachers;
	}


	/**
	 * returns full schedule when weektype is null
	 */
	public function getGroupSchedule($group, $weekday, $weektype) {
		global $weekType;
		$weekType = $weektype;

		$res = $this->db->query(
			"SELECT subject_id,teacher_id,hall,start,end,comments,weektype,surname AS teacher,name AS subject " .
			"FROM schedule,subjects,teachers " .
			"WHERE group_id = (SELECT id FROM groups WHERE name='$group') " .
				"AND weekday=$weekday " .
				($weektype ? "AND weektype in (0,$weektype) " : '') .
				"AND teacher_id=teachers.id " .
				"AND subject_id=subjects.id " .
			"ORDER BY start ASC;"
		);
		if ($res === false) {
			throw new Exception('Failed to execute query: ' . $this->db->error);
		}

		$day = [];
		$lastRow = ['subject_id' => -1, 'start' => -1];

		while ($row = $res->fetch_assoc()) {
			if ($lastRow['start'] == $row['start']) {
				$day[count($day) - 1][$row['weektype']][$row['subject_id']][] = $row;
			} else {
				$day[][$row['weektype']][$row['subject_id']][] = $row;
			}
			$lastRow = $row;
		}

		return $day;
	}

	public function getTeacherSchedule($weekday, $weektype, $teacher) {
		//TODO: implement
	}

	private function buildValues($class, $group, $weekday, $weektype, $start, $end, $comments) {
		return "((SELECT id FROM groups WHERE name='$group')," .
			"(SELECT id FROM subjects WHERE name='$class[subject]')," .
			"$weekday," .
			"(SELECT id FROM teachers WHERE surname='$class[teacher]')," .
			"'$class[hall]'," .
			"$weektype," .
			"$start," .
			"$end," .
			"'$comments'),";
	}

	private function parseClass($class) {
		//TODO: implement comment filling
		if (!$class) {
			return [];
		}

		$subject = '\s{0,3}([\w ]*)\s{0,3}';
		$surName = '\s{0,3}(\w*)\s{0,3}';
		$hall = '\s{0,3}(ауд\. \d*.*|СП зал)\s{0,3}';

		$lines = explode("\n", $class); //different line - different subject
		if (count($lines) < 2) {
			$lines = preg_split('/ {4,}/', $class);
		}

		foreach ($lines as $line) {
			$res = preg_match("/^$subject,$surName,$hall(,|$)/Uiu", $line, $matches);
			if (!$res) {
				break;
			}
			if ($matches[4] == '') {
				$matches = preg_replace('/\s{2,}/', ' ', $matches);
				return [[
					'subject' => $matches[1],
					'teacher' => $matches[2],
					'hall' => $matches[3]
				]];
			} else {
				if (preg_match("/^$subject,$surName,$hall,$surName,$hall$/Uiu", $line, $matches)) {
					$matches = preg_replace('/\s{2,}/', ' ', $matches);
					return [
						['subject' => $matches[1], 'teacher' => $matches[2], 'hall' => $matches[3]],
						['subject' => $matches[1], 'teacher' => $matches[4], 'hall' => $matches[5]]
					];
				}
			}
		}

		throw new Exception('Unknown class fromat: ' . print_r($class, true));
	}

	private function addTeachers($classes) {
		$teachers = [];
		foreach ($classes as $class) {
			$teachers[] = '(\'' . $class['teacher'] . '\')';
		}

		$this->db->query('INSERT IGNORE INTO teachers (surname) VALUES ' . implode(',', $teachers));
	}

	private function addSubjects($classes) {
		$subjects = [];
		foreach ($classes as $class) {
			$subjects[] = '(\'' . $class['subject'] . '\')';
		}

		$this->db->query('INSERT IGNORE INTO subjects (name) VALUES ' . implode(',', $subjects));
	}
}