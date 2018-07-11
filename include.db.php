<?php
$dbAdd = '127.0.0.1';
$dbName = 'osu';
$dbUser = 'root';
$dbPass = 'root';
function connectDb()
{
	global $conn,$dbAdd,$dbUser,$dbPass,$dbName;
	$conn = new mysqlp($dbAdd,$dbUser,$dbPass,$dbName);
	return $conn;
}

function connectDb2($allowFallback = true)
{
return connectDb();
}

function connectDb1($allowFallback = true)
{
return connectDb();
}

function sqlstr($string)
{
	global $conn;
	if ($conn instanceof MySQLi)
		return $conn->real_escape_string($string);
	else
		return addslashes($string);
}

class mysqlp extends mysqli
{
	function useUtc()
	{
		$this->exec("SET time_zone = '+0:00';");
	}

	function queryOne($query)
	{
		$result = $this->query($query);

		if ($result === FALSE)
			return 0;

		if ($result === TRUE)
			return true;

		if ($row = $result->fetch_row())
			$returnValue = array_pop($row);
		$result->close();

		if (isset($returnValue))
			return $returnValue;
		return 0;
	}

	function queryRow($query, $ordered = false)
	{
		$result = $this->query($query);

		if ($result === FALSE)
			return 0;

		if ($ordered)
		{
			if ($row = $result->fetch_row())
				$returnValue = $row;
		}
		else
		{
			if ($row = $result->fetch_assoc())
				$returnValue = $row;
		}
		$result->close();

		if (isset($returnValue))
			return $returnValue;
		return 0;
	}

	function queryObject($query)
	{
		$result = $this->query($query);

		if ($result === FALSE)
			return null;

		if ($row = $result->fetch_object())
			$returnValue = $row;
		$result->close();

		if (isset($returnValue))
			return $returnValue;

		return null;
	}

	function queryAll($query, $ordered = false)
	{
		$result = $this->query($query);

		$arr = array();

		if ($result == null)
			return null;

		if ($ordered)
		{
			while ($row = $result->fetch_row()) {
				array_push($arr,$row);
	 		}
		}
		else
		{
			while ($row = $result->fetch_assoc()) {
				array_push($arr,$row);
	 		}
		}


		$result->close();

		return $arr;
	}

	function queryMany($query)
	{
		$results = $this->queryAll($query, true);

		$arr = array();

		foreach ($results as &$r)
			array_push($arr, $r[0]);

		return $arr;
	}

	function exec($query, $debug = false)
	{
		if ($debug) echo $query;

		$success = $this->real_query($query);

		if (!$success && $debug)
			die("DB Error: {$this->error} on query '{$query}'");

		return $success;
	}

	function batch($query, $batchSize = 500, $delay = 200000)
	{
		$limit = " LIMIT $batchSize";
		$totalProcessed = 0;

		if (defined('MYSQLP_VERBOSE'))
			echo "Batch running query: $query\n";
		while (true)
		{
			$this->exec($query . $limit);
			$totalProcessed += $this->affected_rows;
			if (defined('MYSQLP_VERBOSE'))
				echo "Processed {$totalProcessed} rows...\r";

			if ($this->affected_rows < max(2, $batchSize / 4))
				break;

			usleep($delay);
		}

		if (defined('MYSQLP_VERBOSE')) echo "\n";
	}
}

class mysqlpl extends mysqlp
{
	var $availableHosts = [];
	var $c_user;
	var $c_password;
	var $c_database;

	var $hasConnected = false;

	public function __construct($host, $user, $password, $database)
	{
		parent::__construct();

		$this->addHost($host);
		$this->c_user = $user;
		$this->c_password = $password;
		$this->c_database = $database;
	}

	public function addHost($host)
	{
		array_push($this->availableHosts, $host);
		if (count($this->availableHosts) > 1)
			$this->options(MYSQLI_OPT_CONNECT_TIMEOUT, 1);
	}

	function ensureConnected()
	{
		if ($this->hasConnected) return;

		foreach ($this->availableHosts as $host)
		{
			if ($this->real_connect($host, $this->c_user, $this->c_password, $this->c_database))
			{
				$this->hasConnected = true;
				return;
			}
		}
	}

	function query($query, $resultMode = MYSQLI_STORE_RESULT)
	{
		$this->ensureConnected();
		return parent::query($query, $resultMode);
	}

	function real_query($query)
	{
		$this->ensureConnected();
		return parent::real_query($query);
	}

	function prepare($query)
	{
		$this->ensureConnected();
		return parent::prepare($query);
	}

	function real_escape_string($string)
	{
		$this->ensureConnected();
		return parent::real_escape_string($string);
	}
}

function luceneEscape($string)
{
	return str_replace(
		['+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '/', '\\'],
		[ '', '', '&', '|', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
		$string);
}

global $conn;

if ($conn == null)
	connectDb();

?>
