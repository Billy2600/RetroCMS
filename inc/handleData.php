<?php
// ******************************************************
// Description: Class to handle data from a MySQL database
// Some other classes are an extension of this one, all the
// heavy work is done here.
//
// This file is part of RetroCMS.
//
// RetroCMS is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// RetroCMS is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with RetroCMS.  If not, see <http://www.gnu.org/licenses/>.
// Copyright 2016 William McPherson
// *******************************************************
class handleData
{
	// PRIVATE
	
	private $table; // MySQL table to draw data to/from
	private $fields; // Array of fields in said table we're handling
	
	
	// CONSTRUCTOR
	// Sets the table and field variables based on parameters
	function __construct($tbl = "users", $fld = array("uid","username","password"), $ignorePrefix = false)
	{
		if($ignorePrefix) // Ignore database prefix
			$this->table = $tbl;
		else
			$this->table = DB_PREFIX.$tbl;
		$this->fields = $fld;
	}

	
	// PUBLIC
	
	// Return table name
	public function getTable()
	{
		return $this->table;
	}
	
	// Return the fields
	public function getFields()
	{
		return $this->fields;
	}
	
	// Change the fields
	public function changeFields($fld)
	{
		$this->fields = $fld;
	}
	
	// Output data from the database
	// Postcondition: The values specified by $fields will be returned in a multidimensional array in
	// the format [row][field]
	// Optional paramters: $where needs to be an array of two: field name and the condition, both as strings,
	// Example: $where=array("uid","=1") Will add "WHERE $where[0].$where[1]" to the end of the query;
	// limit should just be an integer, will add LIMIT $limit to end of the query
	// Anything extra in the query should be added as an $extra string
	// $start is what row it should start outputting from. NOTE: you *need* to enter a limit to use this
	// Set $dontEscape to true if you don't want to add slashes (but you better sanitize that input!)
	// Debug will make this function instead print out the query it would enter into the database
	public function dbOutput($where = false,$limit = false,$extra = false,$start=false,
		$dontEscape=false,$debug=false)
	{
		$table = $this->table;
		$fields = $this->fields;
		$output = array();
		// Begin mysql query
		$query = "SELECT * FROM $table";
		// Add optional parameters
		// Where
		if($where != false)
		{
			$query .= " WHERE ".$where[0].$where[1];
		}
		// Anything extra
		if($extra != false)
		{
			$query .= " $extra";
		}
		// Limit
		if($limit != false)
		{
			// Start is set
			if($start != false)
				$query .= " LIMIT $start, $limit";
			// Start is not set
			else
				$query .= " LIMIT $limit";
		}
		// Start looping through the rows returned
		if($dontEscape == false) $query = addslashes($query);
		// Print out query if debug is true
		if($debug == true) die($query);
		$result = mysql_query($query) or die(mysql_error());
		$num = mysql_num_rows($result);
		for($i = 0; $i < $num; $i++)
		{
			// Loop through fields, put the data into multidimensional array
			for($o = 0; $o < count($fields); $o++)
			{
				// Add it into the array
				$fieldName = $fields[$o];
				$output[$i][$o] = stripslashes( mysql_result( $result,$i,$fieldName ) );
			}
		}
		return $output;
	}
	
	// Insert data into the database
	// Postcondition: The values in the $data array are inserted into the fields from $fields
	//	into the table $table
	// If debug is true, it will only print out the query entered
	public function dbInput($data,$debug=false)
	{
		$table = $this->table;
		$fields = $this->fields;
		// Error check: Number of fields and number of data should be the same
		$maxFields = count($fields);
		$maxData = count($data);
		if($debug != true)
			if($maxFields != $maxData) die("MySQL query error: Number of data entries being entered does not match number of fields");
		
		// Start of query
		$query = "INSERT INTO $table (";
		// Loop through fields
		for($i = 0; $i < $maxFields; $i++)
		{
			$query .= $fields[$i];
			if($i != $maxFields - 1) $query .= ",";
		}
		$query .= ") VALUES (";
		// Loop through data
		for($i = 0; $i < $maxData; $i++)
		{
			// If data is NULL, don't put into quotes
			if($data[$i] == "NULL") $query .= addslashes($data[$i]);
			else $query .= "'".addslashes($data[$i])."'";
			if($i != $maxData - 1) $query .= ",";
		}
		$query .= ")";
		if($debug == true)
			die($query);
		// Do the query
		mysql_query($query) or die(mysql_error());
	}
	
	// Update data in the database
	// Postcondition: The fields in $fields with the key name of $key and id of $id is updated with the data
	//from $data array, in the table $table
	public function dbUpdate($data, $key, $id)
	{
		$table = $this->table;
		$fields = $this->fields;
		// Error check: Number of fields and number of data should be the same
		$maxFields = count($fields);
		$maxData = count($data);
		if($maxFields != $maxData) die("MySQL query error: Number of data entries being entered does not match number of fields");
		
		$query = "UPDATE $table SET ";
		// Loop through both fields and data
		for($i = 0; $i < $maxFields; $i++)
		{
			$query .= $fields[$i]." = ";
			// If it's set to NULL, don't quote it
			if($data[$i] == "NULL") $query .= addslashes($data[$i]);
			else $query .= "'".addslashes($data[$i])."'";
			// Add commas, except to last one
			if($i != $maxFields - 1) $query .= ", ";
		}
		// Need to make sure we're updating a specific entry
		$query .= " WHERE $key = '$id'";
		// Do the query
		mysql_query($query) or die(mysql_error());
	}
	
	// Delete data from table
	// Postcondition: Will delete data from the table where the key $key matches $id
	// Extra paramters to add onto the end of the mysql query should be specified as $extra
	public function deleteData($key,$id,$extra = "")
	{
		// Sanitize imput
		/* $key = addslashes($key);
		$id = addslashes($id);
		$extra = addslashes($extra); */
		$table = $this->table;
		$query = "DELETE FROM $table WHERE $key='$id'$extra";
		mysql_query($query) or die(mysql_error());
	}
	
	// Get the current date in a format MySQL likes
	// Postcondition: Returns today's date and time in a MySQL-friendly format
	public function getDateForMySQL()
	{
		return date('Y-m-d H:i:s');
	}
	
	// The opposite of the above function: Convert from mysql format to normal
	// Postcondition: Will take a MySQL formatted date and converted into a more human readable format
	// Also converts time if $time is set to true
	public function convertDateTime($date,$time = false)
	{
		$months = array("January","February","March","April","May","June","July","August","September","October","November","December");
		$dateArray = explode("-",$date); // 0 - year, 1 - month, 2 - day
		// If time is set, then we've got to seperate the day from the time
		if($time == true)
		{	
			// Replace the space with a colon, for ease of exploding
			$dateArray[2] = str_replace(" ",":",$dateArray[2]);
			$timeArray = explode(":",$dateArray[2]); // 0 = day, 1 = hours, 2 = minutes, 3 = seconds
			// First entry in time array is the day, pass that on
			$dateArray[2] = $timeArray[0];
			// Convert hours into 12 hour time
			if($timeArray[1] > 12)
			{
				$timeArray[1] = $timeArray[1] - 12;
				$postMediadiem = true;
			}
			else $postMediadiem = false;
		}
		// Get int value to remove leading zeroes
		$dateArray[1] = intval($dateArray[1]);
		$dateArray[2] = intval($dateArray[2]);
		// Subract 1 from month for use with months array
		$dateArray[1] -= 1;
		// Day in nice text format
		// Example 1st of February, 2012
		
		$date = $dateArray[2]." ".$months[$dateArray[1]].", ".$dateArray[0];
		// return time too?
		if($time == true)
		{
			$date .= " at ".$timeArray[1].":".$timeArray[2]; // Not really concerned with seconds, but we could put them in there
			// AM or PM?
			if($postMediadiem == true) $date .= "PM";
			else $date .= "AM";
		}
		return $date;
	}
}
?>