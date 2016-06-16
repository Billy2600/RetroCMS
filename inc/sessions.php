<?php
// ******************************************************
// Description: Class to handle all login sessions
// Will create and delete sessions in the database, and
// assign cookie(s) to match those sessions
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

// Required files
require_once "handleData.php";
require_once "users.php";

class sessions extends handleData
{
	// CONSTRUCTOR
	function __construct($fld = array("sid","userid","ip_address","date"))
	{
		// Call parent constructor
		parent::__construct("sessions",$fld);
	}
	
	// Create a session
	// Will show an error message and die if required
	public function create($name, $pass)
	{
		$user = new users(array("uid"));
		// Check login
		if($user->checkLogin($name,$pass) == false)
		{
			htmlHeader("Error");
			displayMessage("User name or password is incorrect","goback");
			htmlFooter();
			die();
		}
		// Create session
		else
		{
			$userid = $user->getIdFromName($name);
			// Check for existing
			$this->checkExisting($userid, $_SERVER['REMOTE_ADDR']);
			// Insert into database
			$this->changeFields(array("userid","ip_address","date"));
			$this->dbInput( array($userid,$_SERVER['REMOTE_ADDR'],
				$this->getDateForMySQL()) );
			// Set cookie
			setcookie("session", $this->getSessionFromUserId($userid),
				time()+60*60*24*360,"/"); // Expires in a year
			// Update IP address of user
			$user->changeFields(array("ip_address"));
			$user->dbUpdate(array($_SERVER['REMOTE_ADDR']),'uid',$userid);
		}
	}
	
	// Remove session
	public function remove()
	{
		// Early out if no cookie
		if(!isset($_COOKIE['session']))
			return;
		
		// Delete from database
		$this->deleteData("sid",(int)$_COOKIE['session']);
		// Delete cookie
		setcookie("session", "0", time()-3600,"/");
	}
	
	// Check valid session
	// Will return true if session is valid, otherwise returns false
	public function checkValid()
	{
		// Early out if no cookie
		if(!isset($_COOKIE['session']))
			return false;
		
		// Check if session matches one in database
		$this->changeFields(array("ip_address"));
		$output = $this->dbOutput(array("sid"," = ". $_COOKIE['session']));
		if(count($output) < 1)
			return false;
		else
			return true;
	}
	
	// Check for existing session
	// Will check for existing session for user, and delete it if true
	public function checkExisting($userid, $ip)
	{
		// Check for existing
		$this->changeFields(array("sid","ip_address"));
		$output = $this->dbOutput(array("userid","=$userid"));
		// Early out if nothing found
		if(count($output) < 1)
			return;
		// Early out if ip addresses don't match
		if($output[0][1] != $ip)
			return;
		// All tests pass, delete it
		$this->deleteData("sid",$output[0][0]);
	}
	
	// Get session from user id
	public function getSessionFromUserId($userid)
	{
		$this->changeFields(array("sid"));
		$output = $this->dbOutput(array("userid","=$userid"));
		return $output[0][0];
	}
	
	// Get userid from session
	public function getUserIdFromSession()
	{
		// Return 0 if no cookie
		if(!isset($_COOKIE['session']))
			return 0;
		
		$this->changeFields(array("userid"));
		$output = $this->dbOutput(array("sid","=".$_COOKIE['session']));
		return $output[0][0];
	}
}
?>