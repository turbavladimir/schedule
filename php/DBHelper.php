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
			require __DIR__ . '/../settings/db.default.php';
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
		$this->db->query("DELETE FROM students_schedule WHERE group_id = (SELECT id FROM groups WHERE name = '$groupName')");
	}

	public function removeGroup($groupName) {
		$this->db->query("DELETE FROM groups WHERE name = '$groupName'");
	}

	public function updateDay($classes, $calls, $group, $course, $weekday) {
		//add group if not exist
		$this->db->query("INSERT IGNORE INTO groups (name, course) VALUES ('$group', $course)");

		$query = 'INSERT INTO students_schedule (group_id, subject, weekday, weektype, start, end) VALUES ';

		foreach ($classes as $id => $class) {
			if (gettype($class) == 'string') {
				$query .= "((SELECT id FROM groups WHERE name='$group'),'$class',$weekday,0,{$calls[$id]['start']},{$calls[$id]['end']}),";
			} else { //separated week type
				foreach ($class as $weekTypeName => $type) {
					$weekTypeNum = $weekTypeName == 'bottom' ? 1 : 2;
					$query .= "((SELECT id FROM groups WHERE name='$group'),'$class[$weekTypeName]',$weekday,$weekTypeNum,{$calls[$id]['start']},{$calls[$id]['end']}),";
				}
			}
		}

		$query = rtrim($query,',') . ';';
		$res = $this->db->query($query);
		if ($res === false) {
			throw new Exception('Failed to execute query: ' . $this->db->error);
		}
	}

	public function getCoursesCount() {
		$res = $this->db->query("SELECT course FROM groups GROUP BY course");
		return $res->num_rows;
	}

	public function getGroups($course = false) {
		if ($course === false) {
			$res = $this->db->query("SELECT name FROM groups");
		} else {
			$res = $this->db->query("SELECT name FROM groups WHERE course=$course");
		}
		$groups = [];
		while ($row = $res->fetch_row()) {
			$groups[] = $row[0];
		}

		return $groups;
	}

	/**
	 * @param $group integer group_id in database
	 * returns full schedule when weektype is false
	 */
	public function getGroupSchedule($group, $weekday, $weektype, $replaceEmptinesNbsp = false) {
		global $weekType;
		$weekType = $weektype;

		$res = $this->db->query(
			"SELECT subject,start,end,weektype " .
			"FROM students_schedule " .
			"WHERE group_id = (SELECT id FROM groups WHERE name='$group') " .
				"AND weekday=$weekday " .
				($weektype ? "AND weektype in (0,$weektype) " : '') .
			"ORDER BY start ASC;"
		);
		if ($res === false) {
			throw new Exception('Failed to execute query: ' . $this->db->error);
		}

		$day = [];
		$lastStartTime = ['start' => -1];

		while ($row = $res->fetch_assoc()) {
			if ($replaceEmptinesNbsp && !$row['subject']) {
				$row['subject'] = "&nbsp;";
			}
			if ($lastStartTime == $row['start']) {
				$day[count($day) - 1][$row['weektype']] = $row;
			} else {
				$day[][$row['weektype']] = $row;
			}
			$lastStartTime = $row['start'];
		}

		return $day;
	}
}