<?php
/* ***************************************************
// Description: Global functions for all the manage pages
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
// **************************************************/

// Set up current user variable
include_once $incPath."/users.php";
include_once $incPath."/posts.php";
include_once $incPath."/uv_posts.php";
$curUser = new users();

// Check for a users login information and admin/editor privledges
// Postcondition: If not logged in and an admin or editor, redirected to main page.
function checkLoginAdminEditor()
{
	global $curUser;
	$session = new sessions();
	// Make sure they're logged in, redirect if not
	
	if(!$session->checkValid())
		header("Location: /");
	
	// Make sure they're allowed in
	if(!$curUser->getUserType($session->getUserIdFromSession()) > 0)
		// Redirect to main page, aka GET OUT
		header("Location: /");
}

// Check for and return admin options
// Checks if currently logged in user is an admin, if so returns html of the admin options
function getAdminOptions()
{
	global $curUser;
	$session = new sessions();
	
	if($curUser->getUserType($session->getUserIdFromSession()) == 1)
	{
		// Get number of unvalidated posts
		$uvPosts = new UvPosts();
		$valNum = $uvPosts->GetTotal();
		// Return html
		return htmlOutput("../tmpl/man/adminOptions.txt",array("valNum"),array($valNum),true);
	}
	else
		return "";
}

// Check if a posts with specified ID exists
// Post condition: Halts program and displays error if the specified post does not exist,
//		otherwise, does nothing
function checkPostExistence($postID)
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
function checkPostOwnership($pid,$uid)
{
	global $curUser;
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

// Add a ban
// Adds a ban to the .htaccess file
function addBan($ip)
{
	// Get contents of .htaccess file
	$contents = file_get_contents("../.htaccess");
	// Replace end of file with our ban
	$contents = str_replace("allow from all","deny from ".$ip."\n",$contents);
	// Re-add the end of file
	$contents .= "allow from all";
	// Write to file
	$fp = fopen('../.htaccess', 'w');
	fwrite($fp, $contents);
}

// Get the items in a directory
// Returns an array of filenames
// You can enter a name to exclude from the listing
function getDirectoryList($directory, $exclude = "thumbs") 
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
function str2int($string)
{
  for ($i = 0, $int = ''; $i < strlen($string); $i++) {
    if (is_numeric($string[$i]))
        $int .= $string[$i];
     else break;
  }

  return (int)$int;
}
?>