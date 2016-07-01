<?php
// ******************************************************
// Description: Class to handle all user data, retrieving
// it, inserting, modifying, etc. all the special cases
// This class inherits from database
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
require_once "database.php";
require_once "posts.php";

class users extends database
{
	// PRIVATE
	
	// CONSTRUCTOR
	// Sets the field array based on a parameter, fields are usually not required
	function __construct($fld = array("uid","username","password"))
	{
		// Call parent constructor
		parent::__construct("users",$fld);
	}
	
	// PUBLIC
	
	// Get the login bar information
	// Postcondition: Will return the login bar information as a one dimensional array,
	// you must specify an ID. This will not get the guest login bar
	public function getLoginBarForUser($id)
	{
		parent::changeFields(array("uid","username"));
		$output = $this->dbOutput(array("uid","=$id"));
		return $output[0]; // Return one dimensional array
	}
	
	// Display a user, for a user page
	// Postcondition: Will load the post with the ID given in the parameters, then print out it's contents
	// to the appropriate HTML template
	public function displayUser($id)
	{
		// Re-set the fields
		$this->changeFields(array("username","fname","lname","aboutme","account_type","join_date","birthday",
			"email","avatar","gender","country","skype","msn","yahoo","aim","steam"));
		$replace = parent::getFields(); // Put fields into replace array
		$user = $this->dbOutput(array("uid","=$id"));
		// Check if user does not exist
		if(count($user) == 0)
		{
			htmlHeader("Error");
			displayMessage("User with that ID does not exist","goback");
			htmlFooter();
			// Stop here
			die();
		}
		// Display header
		htmlHeader("Viewing User ".$user[0][0]); // Gotta put the title in there, for the title bar
		
		// Set up data for outputing
		// Change gender ID into character
		switch($user[0][9])
		{
			case 1:
				$user[0][9] = "&#9794;";
				break;
			case 2:
				$user[0][9] = "&#9792;";
				break;
			default:
				$user[0][9] = "Not Telling";
				break;
		}
		// Replace specials in about me
		$user[0][3] = replaceSpecial($user[0][3]);
		// Convert join date and birthday into human readable dates
		$user[0][5] = $this->convertDateTime($user[0][5]);
		$user[0][6] = $this->convertDateTime($user[0][6]);
		// Get latests posts
		$posts = new posts(array("pid","title"));
		$latestPosts = $posts->dbOutput(array("poster_id","=$id"),10,"AND hidden=0 ORDER BY date DESC");
		$replace[] = "latestposts"; // Add entry to replace array
		$postLinks = ""; // Initialize postLinks
		// Loop through posts and add their IDs and titles to links
		for($i = 0; $i < count($latestPosts); $i++)
		{
			$postLinks .= htmlOutput("./tmpl/latestPostsLink.txt",array("num","pid","title")
				,array($i + 1,$latestPosts[$i][0],$latestPosts[$i][1]),true);
		}
		$user[0][] = $postLinks; // Add all this data to end of user info array
		// Add 'PM this user' link, if user is logged in
		$replace[] = "PM";
		if(isset($_COOKIE['userid']))
			$user[0][] = htmlOutput("./tmpl/userPagePmLink.txt",array("username"),array($user[0][0]),true);
		else $user[0][] = "";
		
		// Link to all posts
		$replace[] = "allposts";
		$user[0][] = '<a href="/userposts/'.$id.'/">View all posts</a>';
		
		// Display user
		htmlOutput("./tmpl/userPage.txt",$replace,$user[0]);
		
		// Display footer
		htmlFooter();
	}
	
	// Encrypt password
	// Takes a password, and returns an encrypted form
	public function encryptPass($userid, $pass)
	{
		// Create sha1 input
		$input = dechex($userid) . $this->getNameFromID($userid) . $pass . SALT;
		// encrypt it
		return sha1($input);
	}
	
	// Check login credentials
	// Will return true if user/pass check out, otherwise false
	public function checkLogin($name, $pass)
	{
		// Check if user with that name exists
		if(!$this->checkUserName($name))
			return false; // Early out, no match
		// Get id from name
		$userid = $this->getIdFromName($name);
		// Encrypt the password entered
		$encPass = $this->encryptPass($userid, $pass);
		// Retrieve pass from database and check
		if($this->getPassword($userid) != $encPass)
			return false; // Early out, passwords do not match
		
		// All checks passed
		return true;
	}
	
	// Log a user in
	// Postcondition: This function will check the username and password entered with what's in the database
	// 	if it matches, cookies will be set, if not an error will be thrown to the user
	/* public function loginUser($name,$pass)
	{
		$this->changeFields(array("uid","password"));
		// sanitize and/or lowercase fields
		$name = addslashes(strtolower($name));
		$pass = addslashes($pass);
		$userInfo = $this->dbOutput(array("`username`","='".$name."'"),
			false,false,false,true); // Don't escape the string, since we already did that
		// Throw error if we get nothing back
		if(empty($userInfo))
		{
			htmlHeader("Error");
			displayMessage("No user with that name was found","goback");
			htmlFooter();
			return false;
		}
		// Now let's check the password in the database with the one entered
		if(md5($pass) == $userInfo[0][1])
		{
			// Set cookies
			setcookie("userid", $userInfo[0][0], time()+60*60*24*360,"/"); // Expires in a year
			setcookie("upass", $userInfo[0][1], time()+60*60*24*360,"/"); // Expires in a year
			// Update IP address of user
			parent::changeFields(array("ip_address"));
			$this->dbUpdate(array($_SERVER['REMOTE_ADDR']),'uid',$userInfo[0][0]);
			return true;
		}
		// Password did not match
		else
		{
			htmlHeader("Error");
			displayMessage("Password was incorrect","goback");
			htmlFooter();
			return false;
		}
	} */
	
	// Check if username already exists
	// Postcondition: If a user with the name entered already exists, then returns true,
	//	otherwise returns false
	public function checkUserName($name)
	{
		parent::changeFields(array("uid"));
		$name = addslashes($name);
		$output = $this->dbOutput(array("`username`","='".$name."'"),false,false,false,true); // Don't escape the string
		// If we got a hit, return true
		if(count($output) >= 1)
			return true;
		else
			return false;
	}
	
	// Submit user regisration
	// Postcondition: The info provided as an array will be inserted into the database
	// Array must contain these fields, in this order: user name, password, email address, gender, bday day,
	//	bday month, bday year, country (as two-letter code)
	public function submitRegisration($userInfo)
	{
		// Convert birthday into mysql-friendly format
		$birthday = $userInfo[6]."-".$userInfo[5]."-".$userInfo[4];
		// Change fields
		$this->changeFields(array("username","password","birthday","email","gender","country","account_type",
			"join_date"));
		// Insert data
		$this->dbInput(array($userInfo[0],"",$birthday,$userInfo[2],$userInfo[3],$userInfo[7],3,
			$this->getDateForMySQL()));
			
		// Now encrypt and insert password
		$userid = getIdFromName($userInfo[0]);
		$encPass = encryptPass($userid, $userInfo[1]);
		changeFields(array("password"));
		// Update user we just entered with pass
		$this->dbUpdate(array($encPass,"uid",$userid));
	}
	
	// Get the type of user
	// Checks the user with $id against the database of user types, returns what type the user belongs to
	// Will return: 1 for admin, 2 for editor, or 0 if neither
	public function getUserType($id)
	{
		//$id = addslashes($id); // Sanitize input
		// Change fields to just account type
		parent::changeFields(array("account_type"));
		// Get account for user with the id of $id
		$userType = $this->dbOutput(array("`uid`","='".$id."'"),false,false,false,true);
		// Check what permissions that type has from account types database
		$types = new database("account_types",array("admin","editor"));
		$typeInfo = $types->dbOutput(array("`tid`","='".$userType[0][0]."'"),false,false,false,true);
		// Return what we got
		if($typeInfo[0][0] == "1") // Admin
			return 1;
		if($typeInfo[0][1] == "1") // Editor
			return 2;
		else // Anything else
			return 0;
	}
	
	// Get the users e-mail
	// Postcondition: Will retrieve and return the e-mail address of the user with the ID $userID
	public function getEmail($userID)
	{
		parent::changeFields(array("email"));
		$email = $this->dbOutput(array("uid","=$userID"));
		return $email[0][0];
	}
	
	// Get user's name from their id
	// Postcondition: Will return the username associated with $uid
	public function getNameFromID($uid)
	{
		parent::changeFields(array("username"));
		$uname = $this->dbOutput(array("uid","=$uid"));
		return $uname[0][0];
	}
	
	// Get user's ID from their name
	// Postcondition: Will return the user ID associated with $uname
	public function getIdFromName($uname)
	{
		parent::changeFields(array("uid"));
		$uname = addslashes($uname); // Sanitize username
		$uid = $this->dbOutput(array("`username`","='$uname'"),false,false,false,true);
		return $uid[0][0];
	}
	
	// Get password
	// Will return the user's encrypted password from the database
	public function getPassword($uid)
	{
		parent::changeFields(array("password"));
		$upass = $this->dbOutput(array("uid","=$uid"));
		return $upass[0][0];
	}
	
	// Get contributors
	// Postcondition: Will print out a list of all the admins/editors, for the sidebar
	public function getContributors()
	{
		global $tmplPath;
		parent::changeFields(array("uid","username"));
		$output = $this->dbOutput(array("account_type=1"," OR account_type=2"),false,"ORDER BY uid ASC");
		$outputHtml = "";
		for($i=0; $i < count($output); $i++)
		{
			$outputHtml .= htmlOutput($tmplPath."/sidebarListItem.txt",array("pid","title"),$output[$i],true);
		}
		return str_replace( "/p/","/u/",$outputHtml );
	}
}