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
	private $session;
	private $user_id;
	private $curUser;

	function __construct()
	{
		$this->session = new sessions();	
		$this->user_id = $this->session->getUserIdFromSession();
		$this->curUser = new users();
		$this->checkLoginAdminEditor();
	}
	
	// Check for a users login information and admin/editor privledges
	// Postcondition: If not logged in and an admin or editor, redirected to main page.
	public function checkLoginAdminEditor()
	{
		// Make sure they're logged in, redirect if not
		
		if(!$this->session->checkValid())
			header("Location: /");
	
		// Make sure they're allowed in
		if(!$this->curUser->getUserType($this->session->getUserIdFromSession()) > 0 && $this->curUser->getUserType($this->session->getUserIdFromSession()) != 4)
			// Redirect to main page, aka GET OUT
			header("Location: /");
	}

	// Check for and return admin options
	// Checks if currently logged in user is an admin, if so returns html of the admin options
	public function getAdminOptions()
	{
		if($this->curUser->getUserType($this->session->getUserIdFromSession()) == 1)
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
		
		// Get user type
		$userType = $this->curUser->getUserType($uid);
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
		// Editors get out
		if($this->curUser->getUserType($this->session->getUserIdFromSession()) == 2)
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
		$user = new users();

		// Admin check
		if($user->getUserType($this->session->getUserIdFromSession()) != 1)
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
		
		if($this->session->getUserIdFromSession() == $_GET['id'] || $_GET['id'] == 1)
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
		
		
		
		$files = $this->getDirectoryList(IMG_UPLOAD_DIR,"thumbs"); // Get file listing
		// Begin form with image uploader
		$form = htmlOutput("tmpl/forms/imgupload.txt",false,false,true);
		// Offer show all link to admins
		if($this->curUser->getUserType($this->user_id) == 1 && !isset($_GET['showall']))
			$form .= htmlOutput("tmpl/forms/imgupload_showall.txt",false,false,true);
		else if($this->curUser->getUserType($this->user_id) == 1 && isset($_GET['showall']))
			$form .= htmlOutput("tmpl/forms/imgupload_showmine.txt",false,false,true);
		// Loop through and display
		for($i = 0; $i < sizeof($files); $i++)
		{
			// Check if this is our image, if not admin that wants to see all images
			if((!isset($_GET['showall']) || $this->curUser->getUserType($this->user_id) != 1)
				&& $this->str2int($files[$i]) != $this->user_id)
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
		
		$filename = $_GET['del'];
		
		
		// Delete file if it exists
		if(file_exists(IMG_UPLOAD_DIR.$filename))
		{
			// Check that we own file (if not admin)
			if($this->curUser->getUserType($this->user_id) == 1 || $this->str2int($filename) == $this->user_id)
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

	// ADD POST
	public function AddPost()
	{
		if(isset($_POST['submit'])) $this->SubmitPost();
		else $this->ShowPostForm();
	}

	private function ShowPostForm()
	{
		htmlHeader("Management Panel - Add Post");
		// Display admin options if we are one
		$adminOptions = $this->getAdminOptions();
		// Retrieve form HTML
		$form = htmlOutput("tmpl/man/add.txt",array(),array(),true);
		htmlOutput("tmpl/man/main.txt",array("title","form","admin"),array("Add Post",$form,$adminOptions));
		htmlFooter();
	}

	private function SubmitPost()
	{
		
		
		// Set up our fields
		$fields = array("title","text","poster_id","date","tags","hidden");
		// Create new posts object
		$post = new posts($fields);
		// Set up data
		// Get date
		$date = $post->getDateForMySQL();
		// Default values if certain things are empty
		if($_POST['title'] == "" || $_POST['title'] == " ")
			$title = "Untitled";
		else
			$title = $_POST['title'];
		if($_POST['tags'] == "" || $_POST['tags'] == " ")
			$tags = "No tags";
		else
			$tags = $_POST['tags'];
		// Do not allow user to enter no text
		if($_POST['text'] == "" || $_POST['text'] == " ")
		{
			htmlHeader("Error");
			displayMessage("No text entered!","goback");
			htmlFooter();
			die();
		}
		// Set hidden to 1 if box was checked
		if(isset($_POST['hidden']))
			$hidden = 1;
		else
			$hidden = 0;
		
		// Change incorrect image paths
		$text = str_replace("../img/","/img/",$_POST['text']);
		
		// Create data array
		$data = array($title,$text,$this->user_id,$date,$tags,$hidden);
		// Upload image if not empty
		if(!empty($_FILES['image']['name']))
		{
			// Add img and thumb fields
			$fields[] = "img";
			$fields[] = "thumb";
			$post->changeFields($fields);
			// Upload image
			$img = uploadImage($_FILES['image']); // Returns the locations of the image/thumb in an array
			// Add to data
			$data[] = $img[0];
			$data[] = $img[1];
		}
		// Insert data
		$post->dbInput($data);
		// Get latest post for this user
		$post->changeFields(array("pid"));
		$newPostArray = $post->dbOutput(array("poster_id","=".$this->user_id),"1","ORDER BY date DESC");
		// Convert outputted array to single variable
		$newPost = $newPostArray[0][0];
		// Display success message/redirect
		htmlHeader("Post Added");
		displayMessage("Post has been added! Now redirecting to it, or click <a href=\"/p/$newPost/\">Here</a>","redirect","/p/$newPost/");
		htmlFooter();
	}

	// EDIT POST
	public function EditPost()
	{
		if(isset($_GET['id']) && !isset($_POST['submit'])) $this->ShowEditPostForm();
		else if(isset($_POST['submit'])) $this->UpdatePost();
		else $this->ShowPostIDForm();
	}

	private function ShowPostIDForm()
	{
		
		
		htmlHeader("Enter Post ID");
		// Display admin options if we are one
		$adminOptions = $this->getAdminOptions();
		// Get current user's latest posts
		$postObj = new posts();
		$latestPosts = $postObj->getUsersLatestPosts($this->user_id);
		
		// Build latest post links
		$postLinks = "";
		for($i = 0; $i < count($latestPosts); $i++)
		{
			// Add destination string to array
			$latestPosts[$i][] = "edit";
			$postLinks .= htmlOutput("tmpl/man/postlink.txt",array("id","name","dest"),$latestPosts[$i],true);
		}
		// Retrieve form HTML
		$form = htmlOutput("tmpl/man/enterID.txt",array("dest","type","postlinks"),array("edit","post",$postLinks),true);
		htmlOutput("tmpl/man/main.txt",array("title","form","admin"),array("Enter Post ID",$form,$adminOptions));
		htmlFooter();
	}

	private function ShowEditPostForm()
	{
		// Initialize post object
		$post = new posts();
		// Check if this post exists
		$this->checkPostExistence($_GET['id']);
		// Check if we own post/are admin
		$this->checkPostOwnership($_GET['id'],$this->user_id);
		
		// Get information for this post
		$fields = array("title","img","thumb","tags","text","hidden");
		// Change fields to information we want
		$post->changeFields($fields);
		$data = $post->dbOutput(array("pid","=".$_GET['id']));
		// Display form
		htmlHeader("Management Panel - Edit Post #".$_GET['id']."");
		// Display admin options if we are one
		$adminOptions = $this->getAdminOptions();
		// Retrieve form HTML
		// Add id into arrays for replacement
		$fields[] = "id";
		$data[0][] = $_GET['id'];
		// Strip slashes from post text
		$data[0][4] = stripslashes($data[0][4]);
		// Check hidden checkbox if this post was hidden
		if((int)$data[0][5] == 1)
			$data[0][5] = ' checked="true" ';
		else
			$data[0][5] = '';
		$form = htmlOutput("tmpl/man/edit.txt",$fields,$data[0],true);
		htmlOutput("tmpl/man/main.txt",array("title","form","admin"),array("Editing Post ID ".$_GET['id'],$form,$adminOptions));
		htmlFooter();
	}

	private function UpdatePost()
	{
		// Check if this post exists
		$this->checkPostExistence($_POST['pid']);
		// Check if we own post/are admin
		$this->checkPostOwnership($_POST['pid'],$this->user_id);
		
		// Set up our fields
		$fields = array("title","text","tags","hidden");
		// Create new posts object
		$post = new posts($fields);
		// Set hidden to 1 if they checked the box
		if(isset($_POST['hidden']))
			$hidden = 1;
		else
			$hidden = 0;
			
		// Change incorrect image paths
		$text = str_replace("../img/","/img/",$_POST['text']);
		
		// Create data array
		$data = array($_POST['title'],$text,$_POST['tags'],$hidden);
		
		// Update to today's date if applicable
		if(isset($_POST['date']))
		{
			$fields[] = "date";
			$data[] = $post->getDateForMySQL();
			$post->changeFields($fields);
		}
		
		// Remove old image if box was checked and/or we're uploading a file
		if(!empty($_FILES['image']['name']) || isset($_POST['noimg']))
		{
			// Add img and thumb fields
			$fields[] = "img";
			$fields[] = "thumb";
			$post->changeFields($fields);
			
			// Delete old images, if applicable
			$oldImgs = $post->dbOutput(array("pid=",$_POST['pid']));
			deleteFile( str_replace(IMG_EXTERN_DIR,IMG_UPLOAD_DIR,$oldImgs[0][4]) );
			deleteFile( str_replace(IMG_EXTERN_DIR,IMG_UPLOAD_DIR,$oldImgs[0][5]) );
				
			// Upload image
			if( !empty($_FILES['image']['name']) )
			{
				$img = uploadImage($_FILES['image']); // Returns the locations of the image/thumb in an array
				// Add to data
				$data[] = $img[0];
				$data[] = $img[1];
			}
			else
			{
				$data[] = "";
				$data[] = "";
			}
		}
		// Insert data
		$post->dbUpdate($data,"pid",$_POST['pid']);
		// Display success message/redirect
		htmlHeader("Post Edited");
		displayMessage("Post has been Edited! Now redirecting to it, or click <a href=\"/p/".$_POST['pid']."/\">Here</a>","redirect","/p/".$_POST['pid']."/");
		htmlFooter();
	}

	// DELETE POST
	public function DeletePost()
	{
		if(isset($_GET['id']) && !isset($_POST['confirmed'])) $this->ShowDeleteConfirm();
		else if(isset($_POST['confirmed'])) $this->SubmitDelete();
		else $this->ShowDeleteIDForm();
	}

	private function ShowDeleteIDForm()
	{
		
		
		htmlHeader("Enter Post ID");
		// Display admin options if we are one
		$adminOptions = $this->getAdminOptions();
		// Get current user's latest posts
		$postObj = new posts();
		$latestPosts = $postObj->getUsersLatestPosts($this->user_id);
		
		// Build latest post links
		$postLinks = "";
		for($i = 0; $i < count($latestPosts); $i++)
		{
			// Add destination string to array
			$latestPosts[$i][] = "delete";
			$postLinks .= htmlOutput("tmpl/man/postlink.txt",array("id","name","dest"),$latestPosts[$i],true);
		}
		// Retrieve form HTML
		$form = htmlOutput("tmpl/man/enterID.txt",array("dest","type","postlinks"),array("delete","post",$postLinks),true);
		htmlOutput("tmpl/man/main.txt",array("title","form","admin"),array("Enter Post ID",$form,$adminOptions));
		htmlFooter();
	}

	private function ShowDeleteConfirm()
	{
		
		
		// Initialize post object
		$post = new posts();
		// Check if this post exists
		$this->checkPostExistence($_GET['id']);
		// Check if we own post/are admin
		$this->checkPostOwnership($_GET['id'],$this->user_id);
		
		// Display form
		htmlHeader("Management Panel - Delete Post #".$_GET['id']."");
		
		// Are you sure you want to delete this post?
		$text = 'WARNING: Deleting this post will <strong>permanently</strong> delete this post and all the comments attched to it. Are you sure you want to delete this?';
		displayMessage($text,"confirm","/manage.php?section=delete&pid=".$_GET['id']);
		
		htmlFooter();
	}

	private function SubmitDelete()
	{
		
		
		// Check if this post exists
		$this->checkPostExistence($_GET['pid']);
		// Check if we own post/are admin
		$this->checkPostOwnership($_GET['pid'],$this->user_id);
		
		// Delete the post specified
		$post = new posts(array("pid"));
		$post->deletePost($_GET['pid']);
		
		htmlHeader("Post Deleted");
		displayMessage("Post has been deleted! Now redirecting back to manage home or click <a href=\"/manage/\">here</a>","redirect","/manage/");
		htmlFooter();
	}

	// DELETE COMMENTS
	public function DeleteComment()
	{
		if(isset($_POST['com_id'])) $this->SubmitDeleteComment();
		elseif(isset($_GET['id'])) $this->ShowComments();
		else $this->ShowCommentIDForm();
	}

	private function ShowCommentIDForm()
	{
		htmlHeader("Enter Post ID");
		// Display admin options if we are one
		$adminOptions = $this->getAdminOptions();
		// Get current user's latest posts
		$postObj = new posts();
		$latestPosts = $postObj->getUsersLatestPosts($this->user_id);
		
		// Build latest post links
		$postLinks = "";
		for($i = 0; $i < count($latestPosts); $i++)
		{
			// Add destination string to array
			$latestPosts[$i][] = "delete_com";
			$postLinks .= htmlOutput("tmpl/man/postlink.txt",array("id","name","dest"),$latestPosts[$i],true);
		}
		// Retrieve form HTML
		$form = htmlOutput("tmpl/man/enterID.txt",array("dest","type","postlinks"),array("delete_com","post",$postLinks),true);
		htmlOutput("tmpl/man/main.txt",array("title","form","admin"),array("Enter Post ID",$form,$adminOptions));
		htmlFooter();
	}

	private function ShowComments()
	{
		// Make sure this posts exists
		$post = new posts();
		if(!$post->checkPostExistsID($_GET['id']))
		{
			htmlHeader("Error");
			displayMessage("Post with that ID does not exist!","goback");
			htmlFooter();
			die();
		}
		htmlHeader("Showing Comments for post ID ".$_GET['id']."");
		// Display admin options if we are one
		$adminOptions = $this->getAdminOptions();
		// Beginning of form, top of the table
		$form = htmlOutput("tmpl/man/comTable.txt",NULL,NULL,true);
		// Get comments for this post
		$comments = new comments(array("cid","name","poster_id","text"));
		$comData = $comments->dbOutput(array("post_id","=".$_GET['id']));
		// Display comments in table
		for($i = 0; $i < count($comData); $i++)
		{
			// Set up data array
			$data = array();
			// ID
			$data[] = $comData[$i][0];
			// If there's no name, get the poster ID's name
			if( $comData[$i][1] == "Anonymous" || empty( $comData[$i][1] ) )
			{
				$user = new users( array( "username" ) );
				$userName = $user->dbOutput( array( "uid","=".$comData[$i][2] ) );
				$data[] = $userName[0][0];
			}
			else
			{
				$data[] = $comData[$i][1];
			}
			// Cut down the text
			$data[] = substr($comData[$i][3],0,255)."...";
			// Post ID
			$data[] = $_GET['id'];
			$form .= htmlOutput("tmpl/man/comTableRow.txt",array("cid","name","text","pid"),$data,true);
		}
		
		// End of table, display page
		$form .= htmlOutput("tmpl/man/comTableEnd.txt",NULL,NULL,true);
		htmlOutput("tmpl/man/main.txt",array("title","form","admin"),array("Comments for post ".$_GET['id'],$form,$adminOptions));
		htmlFooter();
	}

	private function SubmitDeleteComment()
	{
		$comments = new comments(array("ip_address")); // Comments object
		// Loop through and delete comments
		foreach($_POST['com_id'] as $cid)
		{
			// Ban IP, if that was selected
			if(isset($_POST['ban']))
			{
				// Get IP from comment
				$ip = $comments->dbOutput(array("cid","=$cid"));
				// Ban it
				$this->AddBan($ip[0][0]);
			}
			
			// Delete the comment
			$comments->deleteData("cid",$cid);
		}
		// Display success message
		$txt = "Comment(s) deleted, ";
		if(isset($_POST['ban']))
			$txt .= "and users banned, ";
		$txt .=  'now redirecting to manage main page, or click <a href="/manage/">here</a>.';
		htmlHeader("Comments Deleted");
		displayMessage($txt,"redirect","/manage/");
		htmlFooter();
	}

	// TINY URL
	public function Tinyurl()
	{
		if(isset($_POST['url'])) $this->CreateTinyurl();
		else $this->ShowTinyurlForm();
	}

	private function ShowTinyurlForm()
	{
		htmlHeader(" - Generate a Tiny URL");
		// Display admin options if we are one
		$adminOptions = $this->getAdminOptions();
		// Get form
		$form = htmlOutput("tmpl/man/tinyurl.txt",array(),array(),true);
		// Display
		htmlOutput("tmpl/man/main.txt",array("title","form","admin"),array("Create a TinyURL",$form,$adminOptions));
		htmlFooter();
	}

	private function CreateTinyurl()
	{
		htmlHeader(" - Tiny URL Generated");
		$tinyUrl = GetTinyURL($_POST['url']);
		// Display admin options if we are one
		$adminOptions = $this->getAdminOptions();
		// Display result
		$form = htmlOutput("tmpl/man/tinyurl_result.txt",array("tinyurl"),array($tinyUrl),true);
		// Display
		htmlOutput("tmpl/man/main.txt",array("title","form","admin"),array("TinyURL Created",$form,$adminOptions));
		htmlFooter();
	}
}