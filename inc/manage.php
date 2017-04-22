<?php
// ******************************************************
// Description: Class to handle all functionality of the
// management section.
// NOTE: Because all of this will be accessed from manage.php in the
// root dir, relative paths will be strange
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

require_once "users.php";
require_once "posts.php";
require_once "uv_posts.php";
require_once "func.php";

class manage
{
	function __construct()
	{
		
	}
	
	// Check for a users login information and admin/editor privledges
	// Postcondition: If not logged in and an admin or editor, redirected to main page.
	public function checkLoginAdminEditor()
	{
		$curUser = new users();
		$session = new sessions();
		// Make sure they're logged in, redirect if not
		
		if(!$session->checkValid())
			header("Location: /");
		
		// Make sure they're allowed in
		if(!$curUser->getUserType($session->getUserIdFromSession()) > 0 && $curUser->getUserType($session->getUserIdFromSession()) != 4)
			// Redirect to main page, aka GET OUT
			header("Location: /");
	}

	// Check for and return admin options
	// Checks if currently logged in user is an admin, if so returns html of the admin options
	public function getAdminOptions()
	{
		$curUser = new users();
		$session = new sessions();
		
		if($curUser->getUserType($session->getUserIdFromSession()) == 1)
		{
			// Get number of unvalidated posts
			$uvPosts = new UvPosts();
			$valNum = $uvPosts->GetTotal();
			// Return html
			return htmlOutput("tmpl/man/adminOptions.txt",array("valNum"),array($valNum),true);
		}
		else
			return "";
	}

	// Check if a posts with specified ID exists
	// Post condition: Halts program and displays error if the specified post does not exist,
	//		otherwise, does nothing
	public function checkPostExistence($postID)
	{
		$post = new posts();
		$postExists = $post->checkPostExistsID($postID);
		if($postExists == false)
		{
			htmlHeader("Error");
			displayMessage("Post with that ID does not exist!","goback");
			htmlFooter();
			die();
		}
	}

	// Check if user with $uid owns the specified post with $pid
	// Does nothing if user is admin or they own the post, otherwise
	//		return and error and ends the program
	public function checkPostOwnership($pid,$uid)
	{
		$curUser = new users();
		// Get user type
		$userType = $curUser->getUserType($uid);
		// Check if user is editor, check if they own it
		if($userType == 2)
		{
			// Check if we own this post
			$post = new posts(array("poster_id"));
			$poster = $post->dbOutput(array("pid","=".$pid));
			if($poster[0][0] != $uid)
			{
				htmlHeader("Error");
				displayMessage("That post doesn't belong to you!","goback");
				htmlFooter();
				// stop here
				die();
			}
		}
	}

	// Get the items in a directory
	// Returns an array of filenames
	// You can enter a name to exclude from the listing
	public function getDirectoryList($directory, $exclude = "thumbs") 
	{
		// create an array to hold directory list
		$results = array();

		// create a handler for the directory
		$handler = opendir($directory);

		// open directory and walk through the filenames
		while ($file = readdir($handler))
		{

			// if file isn't this directory or its parent, add it to the results
			if ($file != "." && $file != ".." && $file != $exclude)
			{
				$results[] = $file;
			}
		}

		// tidy up: close the handler
		closedir($handler);

		// done!
		return array_reverse($results);
	}

	// Get ints from a string
	// Created by http://stackoverflow.com/users/75328/thinker
	public function str2int($string)
	{
	  for ($i = 0, $int = ''; $i < strlen($string); $i++) {
	    if (is_numeric($string[$i]))
	        $int .= $string[$i];
	     else break;
	  }

	  return (int)$int;
	}

	// ****************
	// Management pages
	// ****************
	public function ShowIndex()
	{
		// Check for logged in and admin/editor status
		$this->checkLoginAdminEditor();

		// Display manage main page
		htmlHeader("Management Panel");
		// Display admin options if we are one
		$adminOptions = $this->getAdminOptions();
		// Get main page text
		$mainText = htmlOutput("tmpl/man/main_text.txt",NULL,NULL,true);
		htmlOutput("tmpl/man/main.txt",array("title","form","admin"),array("Manage",$mainText,$adminOptions));
		htmlFooter();
	}

	// IP BANS
	public function Bans()
	{
		$curUser = new users();
		// Check for logged in and admin/editor status
		$this->checkLoginAdminEditor();
		// This is an admin only page, so make sure we're an admin, not an editor
		$session = new sessions();
		// Editors get out
		if($curUser->getUserType($session->getUserIdFromSession()) == 2)
			header("Location: /manage.php");

		if(isset($_GET['addban'])) $this->AddBanPage();
		elseif(isset($_POST['delban'])) $this->DeleteBan();
		else $this->ShowBanIndex();
	}

	private function ShowBanIndex()
	{
		// Get contents of .htaccess file
		$contents = file_get_contents(".htaccess");
		// Filter out anything before the banlist
		$start = strpos($contents,"order allow,deny");
		$contents = substr($contents,$start);
		// Remove other htaccess stuff
		$contents = str_replace("order allow,deny","",$contents);
		$contents = str_replace("allow from all","",$contents);
		$contents = str_replace("deny from ","",$contents);
		// Put what's left over into an array
		$banned_ips = explode("\n",$contents);
		
		htmlHeader("Bans");
		// Display admin options if we are one
		$adminOptions = $this->getAdminOptions();
		$form = htmlOutput("tmpl/man/addBan.txt",NULL,NULL,true);; // Initialize form string
		// Generate ban list, ignore first and last entry in array
		foreach($banned_ips as $ip)
		{
			// Remove whitespace, linebreaks, etc.
			$ip = trim($ip);
			if(!empty($ip)) // Don't display empty ones
				$form .= htmlOutput("tmpl/man/ban.txt",array("ip"),array($ip),true);
		}
		// Display it all
		htmlOutput("tmpl/man/main.txt",array("title","form","admin"),array("Current Bans",$form,$adminOptions));
		htmlFooter();
	}

	// Add a ban
	// Adds a ban to the .htaccess file
	public function AddBan($ip)
	{
		// Get contents of .htaccess file
		$contents = file_get_contents(".htaccess");
		// Replace end of file with our ban
		$contents = str_replace("allow from all","deny from ".$ip."\n",$contents);
		// Re-add the end of file
		$contents .= "allow from all";
		// Write to file
		$fp = fopen('.htaccess', 'w');
		fwrite($fp, $contents);
	}

	private function AddBanPage()
	{
		// Check for empty IP input
		if(empty($_GET['ip']))
		{
			htmlHeader("Error");
			displayMessage("No IP address specified!","goback");
			htmlFooter();
			die();
		}
		$this->AddBan($_GET['ip']);
		// Display success message
		htmlHeader("Ban Added");
		$txt = 'Ban successfully added, now redirecting back to ban page or click <a href="/manage.php?section=bans">here</a>.';
		displayMessage($txt,"redirect","/manage.php?section=bans");
		htmlFooter();
	}

	private function DeleteBan()
	{
		// Get contents of .htaccess file
		$contents = file_get_contents(".htaccess");
		// Replace ban with nothing
		$contents = str_replace("deny from ".$_POST['ip']."\n","",$contents);
		// Write to file
		$fp = fopen('.htaccess', 'w');
		fwrite($fp, $contents);
		// Display success message
		htmlHeader("Ban Removed");
		$txt = 'Ban successfully removed, now redirecting back to ban page or click <a href="/manage.php?section=bans">here</a>.';
		displayMessage($txt,"redirect","/manage.php?section=bans/");
		htmlFooter();
	}

	// USERS
	public function Users()
	{
		// Check for logged in and admin/editor status
		$this->checkLoginAdminEditor();
		$session = new sessions();
		$user = new users();

		// Admin check
		if($user->getUserType($session->getUserIdFromSession()) != 1)
		{
			header("Location: /");
		}

		if($_SERVER['REQUEST_METHOD'] == 'POST') $this->UpdateUser();
		else if(isset($_GET['id'])) $this->EditUser();
		else $this->ShowUserIndex();
	}

	private function ShowUserIndex()
	{
		$user = new users();
		htmlHeader("Enter User ID");
		// Display admin options if we are one
		$adminOptions = $this->getAdminOptions();
		$users = $user->GetUserList(20);
		// Show some users
		$userLinks = "";
		for($i = 0; $i < count($users); $i++)
		{
			$users[$i][] = "users";
			$userLinks .= htmlOutput("tmpl/man/postlink.txt",array("id","name","dest"),$users[$i],true);
		}

		// Retrieve form HTML
		$form = htmlOutput("tmpl/man/enterID.txt",array("dest","type","postlinks"),array("users","user",$userLinks),true);
		htmlOutput("tmpl/man/main.txt",array("title","form","admin"),array("Enter User ID",$form,$adminOptions));
		htmlFooter();
	}

	private function UpdateUser()
	{
		$user = new users();
		// Insert data
		$fields = array("username", "fname", "lname", "aboutme", "email", "avatar", "gender", "country");
		$data = array($_POST["username"], $_POST["fname"], $_POST["lname"], $_POST["aboutme"], $_POST["email"], $_POST["avatar"], $_POST["gender"], $_POST["country"]);
		// Set up data that needs formatting
		if(isset($_POST["pass"]) && $_POST["pass"] != "")
		{
			if($_POST["pass"] != $_POST["confirm_pass"])
			{
				htmlHeader("Error");
				displayMessage("Passwords did not match!","goback");
				htmlFooter();
				die();
			}

			array_push($fields, "password");
			array_push($data, $user->encryptPass($_POST['uid'], $_POST['pass']));
		}

		if(isset($_POST["account_type"]))
		{
			array_push($fields, "account_type");
			array_push($data, (int)$_POST["account_type"]);
		}

		array_push($fields, "birthday");
		array_push($data, $_POST["year"] . "-" . $_POST["month"] . "-" . $_POST["day"]);

		$user->changeFields($fields);
		$user->dbUpdate($data,"uid",$_POST['uid']);
		// Display success message/redirect
		htmlHeader("User Edited");
		displayMessage("User has been Edited! Now redirecting to user edit page, or click <a href=\"/manage.php?section=users\">Here</a>","redirect","/manage.php?section=users");
		htmlFooter();
	}

	private function EditUser()
	{
		$user = new users();
		if($user->CheckUserExists($_GET['id']) == false)
		{
			htmlHeader("Error");
			displayMessage("User with that ID does not exist!","goback");
			htmlFooter();
			die();
		}
		
		$fields = array("username", "password", "fname", "lname", "aboutme", "account_type", "DAY(birthday)", "MONTH(birthday)", "YEAR(birthday)", "email", "avatar", "gender", "country");
		$user->changeFields($fields);
		$data = $user->dbOutput(array("uid=",$_GET['id']));
		
		// Generate selection menus
		$days = "";
		$months = "";
		$years = "";
		$accTypesString = "";
		$gendersString = "";
		// Days
		for($i = 1; $i <= 31; $i++)
		{
			$value = addZero($i); // Value is i, with preceeding zero
			$selected = "";
			if($i == $data[0][6])
				$selected = "\" selected=\"selected";
			// Set variable to string from template with relevant info (value no., $i as label)
			$days .= htmlOutput("tmpl/forms/options.txt",array("value","text"),array($value.$selected,$i),true);
		}
		// Months
		$monthsArray = array("January","February","March","April","May","June","July","August","September","October","November","December");
		for($i = 0; $i < 12; $i++)
		{
			$value = addZero($i+1);
			$selected = "";
			if($i+1 == $data[0][7])
				$selected = "\" selected=\"selected";
			$months .= htmlOutput("tmpl/forms/options.txt",array("value","text"),array($value.$selected,$monthsArray[$i]),true);
		}
		// Years
		for($i = date("Y"); $i >= 1900; $i--)
		{
			$value = $i; // Capturing $i in $value, so we don't mess with $i
			$selected = "";
			if($i == $data[0][8])
				$selected = "\" selected=\"selected";
			$years .= htmlOutput("tmpl/forms/options.txt",array("value","text"),array($value.$selected,$i),true);
		}
		// Display countries
		require_once "countries.php";
		$countriesString = ""; // Initialize country options string
		for($i = 0; $i < count($countries); $i++)
		{
			$value = key($countries); // Each value is the key of the index we're on
			$selected = "";
			if(strtolower($value) == $data[0][12])
				$selected = "\" selected=\"selected";
				
			$countriesString .= htmlOutput("tmpl/forms/options.txt",array("value","text"),array(strtolower($value).$selected,$countries[$value]),true);
			next($countries); // Advance countries array, because keys are not numbers
		}
		// Account types
		$accTypes = array("Admin", "Editor", "User", "Banned");
		for($i = 0; $i < count($accTypes); $i++)
		{
			$value = $i+1;
			$selected = "";
			if($i+1 == (int)$data[0][5])
				$selected = "\" selected=\"selected";
			$accTypesString .= htmlOutput("tmpl/forms/options.txt",array("value","text"),array(strtolower($value).$selected,$accTypes[$i]),true);
		}
		$genders = array("Not Telling", "Male", "Female");
		for($i = 0; $i < count($genders); $i++)
		{
			$value = $i;
			$selected = "";
			if($i == (int)$data[0][11])
				$selected = "\" selected=\"selected";
			$gendersString .= htmlOutput("tmpl/forms/options.txt",array("value","text"),array(strtolower($value).$selected,$genders[$i]),true);
		}

		// Don't let admins lock themself out of the admin panel
		// or let root admin be disabled
		$accTypeDisable = "";
		$session = new sessions();
		if($session->getUserIdFromSession() == $_GET['id'] || $_GET['id'] == 1)
		{
			$accTypeDisable = "disabled";
		}

		array_push($fields, "days");
		array_push($data[0], $days);
		array_push($fields, "months");
		array_push($data[0], $months);
		array_push($fields, "years");
		array_push($data[0], $years);
		array_push($fields, "countries");
		array_push($data[0], $countriesString);
		array_push($fields, "account_type_selection");
		array_push($data[0], $accTypesString);
		array_push($fields, "gender_selection");
		array_push($data[0], $gendersString);
		array_push($fields, "acc_type_disable");
		array_push($data[0], $accTypeDisable);
		array_push($fields, "uid");
		array_push($data[0], $_GET['id']);

		htmlHeader("Editting User ID " . $_GET['id']);
		$adminOptions = $this->getAdminOptions();
		$form = htmlOutput("tmpl/man/user.txt",$fields,$data[0],true);
		htmlOutput("tmpl/man/main.txt",array("title","form","admin"),array("Editing User ID ".$_GET['id'],$form,$adminOptions));
		htmlFooter();
	}

	// IMAGE UPLOADS
	public function ImageUploads()
	{
		if(isset($_POST['submit'])) $this->UploadImagePage();
		else if(isset($_GET['del'])) $this->DeleteImage();
		else $this->ShowImageIndex();
	}

	private function ShowImageIndex()
	{
		$this->checkLoginAdminEditor();
		$session = new sessions();
		$user_id = $session->getUserIdFromSession();
		$curUser = new users();
		$files = $this->getDirectoryList(IMG_UPLOAD_DIR,"thumbs"); // Get file listing
		// Begin form with image uploader
		$form = htmlOutput("tmpl/forms/imgupload.txt",false,false,true);
		// Offer show all link to admins
		if($curUser->getUserType($user_id) == 1 && !isset($_GET['showall']))
			$form .= htmlOutput("tmpl/forms/imgupload_showall.txt",false,false,true);
		else if($curUser->getUserType($user_id) == 1 && isset($_GET['showall']))
			$form .= htmlOutput("tmpl/forms/imgupload_showmine.txt",false,false,true);
		// Loop through and display
		for($i = 0; $i < sizeof($files); $i++)
		{
			// Check if this is our image, if not admin that wants to see all images
			if((!isset($_GET['showall']) || $curUser->getUserType($user_id) != 1)
				&& $this->str2int($files[$i]) != $user_id)
					continue; // Not ours, send back through loop

			$thumbExists = file_exists(THUMB_UPLOAD_DIR.$files[$i]); // Bool if thumb exists
			$thumb = IMG_EXTERN_DIR.$files[$i];
			$width = "width: 150px";
			if($thumbExists)
			{
				$thumb = THUMB_EXTERN_DIR.$files[$i];
				$width = "";
			}

			$form .= htmlOutput("tmpl/man/imgupload_image.txt",
				array("img","thumb","filename","width"),
				array(IMG_EXTERN_DIR.$files[$i], $thumb, $files[$i], $width),
				true);
		}
		
		// Print everything out
		htmlHeader("Management Panel - Upload Images");
		if(isset($_GET['id']))
			$id = $_GET['id']; // Prevent undefined ID error
		else
			$id = "";
		htmlOutput("tmpl/man/main.txt",array("title","form","admin"),array("Upload Images ".$id,$form,$this->getAdminOptions()));
		htmlFooter();
	}

	private function DeleteImage()
	{
		$curUser = new users();
		$filename = $_GET['del'];
		$session = new sessions();
		$user_id = $session->getUserIdFromSession();
		// Delete file if it exists
		if(file_exists(IMG_UPLOAD_DIR.$filename))
		{
			// Check that we own file (if not admin)
			if($curUser->getUserType($user_id) == 1 || $this->str2int($filename) == $user_id)
			{
				unlink(IMG_UPLOAD_DIR.$filename);
				// Delete thumbnail if it exists
				if(file_exists(THUMB_UPLOAD_DIR.$filename))
					unlink(THUMB_UPLOAD_DIR.$filename);
			}
		}
		header("Location: /manage.php?section=imguploads");
	}

	private function UploadImagePage()
	{
		// Check if file input not empty
		if(!empty($_FILES['image']['name']))
		{
			// Upload image
			uploadImage($_FILES['image']);
		}
		header("Location: /manage.php?section=imguploads");
	}
}