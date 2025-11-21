<?php
/**
 * This file is part of the esoBB project, a derivative of esoTalk.
 * It has been modified by several contributors.  (contact@geteso.org)
 * Copyright (C) 2023 esoTalk, esoBB.  <https://geteso.org>
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
if (!defined("IN_ESO")) exit;

/**
 * Database class: handles database actions such as connecting and
 * running queries.  Also contains useful functions for constructing
 * queries.
 */
class Database {

var $eso;
protected $link;
protected $host;
protected $user;
protected $password;
protected $db;
protected $encoding;

// Connect to a MySQL server and database.
public function __construct($host, $user, $password, $db, $encoding = "utf8mb4")
{
	$this->link = @mysqli_connect($host, $user, $password, $db);
	mysqli_set_charset($this->link, $encoding);
}

// Run a query. If $fatal is true, then a fatal error will be displayed and page execution will be halted if the query fails.
public function query($query, $fatal = true)
{
	global $language, $config;
	
	// If the query is empty, don't bother proceeding.
	if (!$query) return false;
	
	$this->eso->callHook("beforeDatabaseQuery", array(&$query));

	// Execute the query. If there is a problem, display a formatted fatal error.
	$result = mysqli_query($this->link, $query);
	if (!$result and $fatal) {
		$error = $this->error();
		$this->eso->fatalError($config["verboseFatalErrors"] ? $error . "<p style='font:100% monospace; overflow:auto'>" . $this->highlightQueryErrors($query, $error) . "</p>" : "", "mysql");
	}
	
	$this->eso->callHook("afterDatabaseQuery", array($query, &$result));
	
	return $result;
}

// Find anything in single quotes in the error and make it red in the query.  Makes debugging a bit easier.
public function highlightQueryErrors($query, $error)
{
	preg_match("/'(.+?)'/", $error, $matches);
	if (!empty($matches[1])) $query = str_replace($matches[1], "<span style='color:#f00'>{$matches[1]}</span>", $query);
	return $query;
}

// Return the number of rows affected by the last query.
public function affectedRows()
{
	return mysqli_affected_rows($this->link);
}

// Fetch an associative array.  $input can be a string or a MySQL result.
public function fetchAssoc($input)
{
	if (is_object($input)) return mysqli_fetch_assoc($input);
	$result = $this->query($input);
	if (!$this->numRows($result)) return false;
	return $this->fetchAssoc($result);
}

// Fetch a sequential array.  $input can be a string or a MySQL result.
public function fetchRow($input)
{
	if ($input instanceof \mysqli_result) return mysqli_fetch_row($input);
	$result = $this->query($input);
	if (!$this->numRows($result)) return false;
	return $this->fetchRow($result);
}

// Fetch an object.  $input can be a string or a MySQL result.
public function fetchObject($input)
{
	if ($input instanceof \mysqli_result) return mysqli_fetch_object($input);
	$result = $this->query($input);
	if (!$this->numRows($result)) return false;
	return $this->fetchObject($result);
}

// Approximated function of mysql_result.
protected function fetchResult($input, $row, $field = 0)
{
//	$result = $this->query($input);
    $input->data_seek($row);
    $datarow = $input->fetch_array();
    return $datarow[$field];
}

// Get a database result.  $input can be a string or a MySQL result.
public function result($input, $row = 0)
{
	if ($input instanceof \mysqli_result) return $this->fetchResult($input, $row);
	$result = $this->query($input);
	if (!$this->numRows($result)) return false;
	return $this->result($result);
}

// Get the last database insert ID.
public function lastInsertId()
{
	return $this->result($this->query("SELECT LAST_INSERT_ID()"), 0);
}

// Return the number of rows in the result.  $input can be a string or a MySQL result.
public function numRows($input)
{
	if (!$input) return false;
	if ($input instanceof \mysqli_result) return mysqli_num_rows($input);
	$result = $this->query($input);
	return $this->numRows($result);
}

// Return the most recent connection error.
public function connectError()
{
	if (!$this->link) return mysqli_connect_error();
}

// Return the most recent MySQL error.
public function error()
{
	return mysqli_error($this->link);
}

// Escape a string for use in a database query.
public function escape($string)
{
	return mysqli_real_escape_string($this->link, $string);
}

// Construct a select query.  $components is an array.  ex: array("select" => array("foo", "bar"), "from" => "members")
public function constructSelectQuery($components)
{
	// Implode the query components.
	$select = isset($components["select"]) ? (is_array($components["select"]) ? implode(", ", $components["select"]) : $components["select"]) : false;
	$from = isset($components["from"]) ? (is_array($components["from"]) ? implode("\n\t", $components["from"]) : $components["from"]) : false;
	$groupBy = isset($components["groupBy"]) ? (is_array($components["groupBy"]) ? implode(", ", $components["groupBy"]) : $components["groupBy"]) : false;
	$where = isset($components["where"]) ? (is_array($components["where"]) ? "(" . implode(")\n\tAND (", $components["where"]) . ")" : $components["where"]) : false;
	$having = isset($components["having"]) ? (is_array($components["having"]) ? "(" . implode(") AND (", $components["having"]) . ")" : $components["having"]) : false;
	$orderBy = isset($components["orderBy"]) ? (is_array($components["orderBy"]) ? implode(", ", $components["orderBy"]) : $components["orderBy"]) : false;
	$limit = isset($components["limit"]) ? $components["limit"] : false;
	
	// Return the constructed query.
	return ($select ? "SELECT $select\n" : "") . ($from ? "FROM $from\n" : "") . ($where ? "WHERE $where\n" : "") . ($having ? "HAVING $having\n" : "") . ($groupBy ? "GROUP BY $groupBy\n" : "") . ($orderBy ? "ORDER BY $orderBy\n" : "") . ($limit ? "LIMIT $limit" : "");
}

// Construct an insert query with an associative array of data.
public function constructInsertQuery($table, $data)
{
	global $config;
	return "INSERT INTO {$config["tablePrefix"]}$table (" . implode(", ", array_keys($data)) . ") VALUES (" . implode(", ", $data) . ")";
}

// Construct an update query with associative arrays of data/conditions.
public function constructUpdateQuery($table, $data, $conditions)
{
	global $config;
	
	$update = "";
	foreach ($data as $k => $v) $update .= "$k=$v, ";
	$update = rtrim($update, ", ");
	
	$where = "";
	foreach ($conditions as $k => $v) $where .= "$k=$v AND ";
	$where = rtrim($where, " AND ");
	
	return "UPDATE {$config["tablePrefix"]}$table SET $update WHERE $where";
}

// Prepare a statement for execution. Returns a PreparedStatement object.
public function prepare($query, $fatal = true)
{
	global $config;
	
	if (!$query) return false;
	
	$stmt = mysqli_prepare($this->link, $query);
	if (!$stmt) {
		if ($fatal) {
			$error = $this->error();
			$this->eso->fatalError($config["verboseFatalErrors"] ? $error . "<p style='font:100% monospace; overflow:auto'>" . $this->highlightQueryErrors($query, $error) . "</p>" : "", "mysql");
		}
		return false;
	}
	
	return new PreparedStatement($stmt, $this, $query);
}

// Execute a prepared query with parameters (convenience method).
// Usage: $db->queryPrepared("SELECT * FROM members WHERE name=? AND password=?", "ss", $name, $hash)
public function queryPrepared($query, $types, ...$params)
{
	$stmt = $this->prepare($query);
	if (!$stmt) return false;
	
	$stmt->bindParams($types, ...$params);
	if (!$stmt->execute()) return false;
	
	return $stmt;
}

// Execute a prepared query and return the result set (for SELECT queries).
// Usage: $result = $db->fetchPrepared("SELECT * FROM members WHERE name=?", "s", $name)
public function fetchPrepared($query, $types, ...$params)
{
	$stmt = $this->queryPrepared($query, $types, ...$params);
	if (!$stmt) return false;
	
	return $stmt->getResult();
}

// Get a single value from a prepared query (convenience method).
// Usage: $memberId = $db->fetchOne("SELECT memberId FROM members WHERE name=?", "s", $name)
public function fetchOne($query, $types, ...$params)
{
	$result = $this->fetchPrepared($query, $types, ...$params);
	return $result ? $this->result($result, 0) : false;
}

// Get a single row as numeric array from a prepared query (convenience method).
// Usage: $row = $db->fetchRowPrepared("SELECT id, name FROM members WHERE id=?", "i", $id)
public function fetchRowPrepared($query, $types, ...$params)
{
	$result = $this->fetchPrepared($query, $types, ...$params);
	return $result ? $this->fetchRow($result) : false;
}

// Get a single row as associative array from a prepared query (convenience method).
// Usage: $data = $db->fetchAssocPrepared("SELECT * FROM members WHERE id=?", "i", $id)
public function fetchAssocPrepared($query, $types, ...$params)
{
	$result = $this->fetchPrepared($query, $types, ...$params);
	return $result ? $this->fetchAssoc($result) : false;
}

// Check if a record exists (returns boolean).
// Usage: $exists = $db->exists("SELECT 1 FROM members WHERE name=?", "s", $name)
public function exists($query, $types, ...$params)
{
	$result = $this->fetchPrepared($query, $types, ...$params);
	return $result ? (bool)$this->result($result, 0) : false;
}

// Helper for IN clauses - builds placeholders and merges parameters.
// Usage: $result = $db->fetchPreparedIn("SELECT * FROM table WHERE id IN (?) AND status=?", "i", [1,2,3], "s", "active")
public function fetchPreparedIn($query, $inTypes, $inValues, $otherTypes = "", ...$otherParams)
{
	if (empty($inValues)) return false;
	
	// Replace ? in IN clause with proper placeholders
	$inPlaceholders = str_repeat("?,", count($inValues) - 1) . "?";
	$query = preg_replace('/\bIN\s*\(\?\)/', "IN ($inPlaceholders)", $query, 1);
	
	// Build types string for IN values
	$types = "";
	foreach ($inValues as $val) {
		if (is_int($val)) $types .= "i";
		elseif (is_float($val)) $types .= "d";
		else $types .= "s";
	}
	$types .= $otherTypes;
	
	// Merge parameters
	$params = array_merge($inValues, $otherParams);
	
	return $this->fetchPrepared($query, $types, ...$params);
}

// Similar helper for queryPrepared with IN clauses
public function queryPreparedIn($query, $inTypes, $inValues, $otherTypes = "", ...$otherParams)
{
	if (empty($inValues)) return false;
	
	// Replace ? in IN clause with proper placeholders
	$inPlaceholders = str_repeat("?,", count($inValues) - 1) . "?";
	$query = preg_replace('/\bIN\s*\(\?\)/', "IN ($inPlaceholders)", $query, 1);
	
	// Build types string for IN values
	$types = "";
	foreach ($inValues as $val) {
		if (is_int($val)) $types .= "i";
		elseif (is_float($val)) $types .= "d";
		else $types .= "s";
	}
	$types .= $otherTypes;
	
	// Merge parameters
	$params = array_merge($inValues, $otherParams);
	
	return $this->queryPrepared($query, $types, ...$params);
}

}

/**
 * PreparedStatement class: wraps mysqli prepared statements
 * for secure parameterized queries.
 */
class PreparedStatement {
	
	protected $stmt;
	protected $db;
	protected $query;
	protected $types;
	protected $params;
	protected $result;
	protected $executed = false;
	
	public function __construct($stmt, $db, $query)
	{
		$this->stmt = $stmt;
		$this->db = $db;
		$this->query = $query;
	}
	
	// Bind parameters to the prepared statement.
	// $types: string of type characters (i, d, s, b) for integer, double, string, blob
	// $params: variable number of parameters or array of parameters
	public function bindParams($types, ...$params)
	{
		// If first param is array, assume it's [types, param1, param2, ...]
		if (is_array($types) && count($types) > 0 && is_string($types[0])) {
			$this->types = $types[0];
			$this->params = array_slice($types, 1);
		} else {
			// If only one param and it's an array, it's the params array
			if (count($params) == 1 && is_array($params[0])) {
				$this->types = $types;
				$this->params = $params[0];
			} else {
				$this->types = $types;
				$this->params = $params;
			}
		}
		
		// Bind parameters by reference (required by mysqli_stmt_bind_param)
		if (!empty($this->params)) {
			$refs = array();
			foreach ($this->params as $key => $value) {
				$refs[$key] = &$this->params[$key];
			}
			array_unshift($refs, $this->types);
			call_user_func_array(array($this->stmt, 'bind_param'), $refs);
		}
		
		return $this;
	}
	
	// Execute the prepared statement.
	public function execute($fatal = true)
	{
		global $config;
		
		// Call hook with query string representation (for compatibility)
		$queryString = $this->getQueryString();
		$this->db->eso->callHook("beforeDatabaseQuery", array(&$queryString));
		
		// Execute the statement
		if (!mysqli_stmt_execute($this->stmt)) {
			if ($fatal) {
				$error = mysqli_stmt_error($this->stmt);
				$this->db->eso->fatalError($config["verboseFatalErrors"] ? $error . "<p style='font:100% monospace; overflow:auto'>" . $this->db->highlightQueryErrors($queryString, $error) . "</p>" : "", "mysql");
			}
			return false;
		}
		
		// Get result set if this is a SELECT query (returns false for INSERT/UPDATE/DELETE)
		$this->result = mysqli_stmt_get_result($this->stmt);
		$this->executed = true;
		
		// Call hook after execution
		$this->db->eso->callHook("afterDatabaseQuery", array($queryString, &$this->result));
		
		// Return true on success (result can be false for non-SELECT queries, which is fine)
		return true;
	}
	
	// Get a string representation of the query for hooks/debugging.
	protected function getQueryString()
	{
		$query = $this->query;
		if (!empty($this->params)) {
			foreach ($this->params as $param) {
				// Replace first ? with escaped parameter
				$escaped = is_null($param) ? "NULL" : (is_int($param) ? $param : "'" . $this->db->escape($param) . "'");
				$query = preg_replace('/\?/', $escaped, $query, 1);
			}
		}
		return $query;
	}
	
	// Fetch an associative array from the result.
	public function fetchAssoc()
	{
		if (!$this->executed) return false;
		if (!$this->result) return false;
		return mysqli_fetch_assoc($this->result);
	}
	
	// Fetch a sequential array from the result.
	public function fetchRow()
	{
		if (!$this->executed) return false;
		if (!$this->result) return false;
		return mysqli_fetch_row($this->result);
	}
	
	// Fetch an object from the result.
	public function fetchObject()
	{
		if (!$this->executed) return false;
		if (!$this->result) return false;
		return mysqli_fetch_object($this->result);
	}
	
	// Get a single result value (similar to Database::result()).
	public function result($row = 0, $field = 0)
	{
		if (!$this->executed) return false;
		if (!$this->result) return false;
		
		mysqli_data_seek($this->result, $row);
		$datarow = mysqli_fetch_array($this->result);
		return $datarow ? $datarow[$field] : false;
	}
	
	// Return the number of rows in the result.
	public function numRows()
	{
		if (!$this->executed) return false;
		if (!$this->result) return false;
		return mysqli_num_rows($this->result);
	}
	
	// Return the number of affected rows.
	public function affectedRows()
	{
		if (!$this->executed) return false;
		return mysqli_stmt_affected_rows($this->stmt);
	}
	
	// Get the last insert ID (for INSERT queries).
	public function insertId()
	{
		if (!$this->executed) return false;
		return mysqli_insert_id($this->db->link);
	}
	
	// Get the result set (for compatibility with existing code).
	public function getResult()
	{
		return $this->result;
	}
	
	// Close the statement.
	public function close()
	{
		if ($this->stmt) {
			mysqli_stmt_close($this->stmt);
			$this->stmt = null;
		}
	}
	
	// Destructor: close statement if not already closed.
	public function __destruct()
	{
		$this->close();
	}
	
}

?>
