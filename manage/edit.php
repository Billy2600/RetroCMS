<?php
/* ***************************************************
// Description: Allows editors and admins to edit posts
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

// Required files
require_once "../config.php";
require_once $incPath."/func.php"; // Global Functions
require_once "manFunc.php"; // Manage section functions
require_once $incPath."/users.php";
require_once $incPath."/posts.php";

// Connect to mysql
mysql_connect($DATABASE_HOST,$DATABASE_USER,$DATABASE_PASS);
@mysql_select_db($DATABASE_NAME) or die("Unable to select database");

// Check for logged in and admin/editor status
checkLoginAdminEditor();
$session = new sessions();
$user_id = $session->getUserIdFromSession();

// Check if ID is set, if not display enter ID form/latest posts
if(!isset($_GET['id']) && !isset($_POST['submit']))
{
	htmlHeader("Enter Post ID");
	// Display admin options if we are one
	$adminOptions = getAdminOptions();
	// Get current user's latest posts
	$postObj = new posts();
	$latestPosts = $postObj->getUsersLatestPosts($user_id);
	
	// Build latest post links
	$postLinks = "";
	for($i = 0; $i < count($latestPosts); $i++)
	{
		// Add destination string to array
		$latestPosts[$i][] = "edit";
		$postLinks .= htmlOutput("../tmpl/man/postlink.txt",array("id","name","dest"),$latestPosts[$i],true);
	}
	// Retrieve form HTML
	$form = htmlOutput("../tmpl/man/enterID.txt",array("dest","type","postlinks"),array("edit","post",$postLinks),true);
	htmlOutput("../tmpl/man/main.txt",array("title","form","admin"),array("Enter Post ID",$form,$adminOptions));
	htmlFooter();
	// Do not continue beyond this point
	die();
}

// Check if post is set, if set, update data in the database for this post, display success message
if(isset($_POST['submit']))
{
	// Check if this post exists
	checkPostExistence($_POST['pid']);
	// Check if we own post/are admin
	checkPostOwnership($_POST['pid'],$user_id);
	
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
// Post not set, Display edit post form
else
{
	// Initialize post object
	$post = new posts();
	// Check if this post exists
	checkPostExistence($_GET['id']);
	// Check if we own post/are admin
	checkPostOwnership($_GET['id'],$user_id);
	
	// Get information for this post
	$fields = array("title","img","thumb","tags","text","hidden");
	// Change fields to information we want
	$post->changeFields($fields);
	$data = $post->dbOutput(array("pid","=".$_GET['id']));
	// Display form
	htmlHeader("Management Panel - Edit Post #".$_GET['id']."");
	// Display admin options if we are one
	$adminOptions = getAdminOptions();
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
	$form = htmlOutput("../tmpl/man/edit.txt",$fields,$data[0],true);
	htmlOutput("../tmpl/man/main.txt",array("title","form","admin"),array("Editing Post ID ".$_GET['id'],$form,$adminOptions));
	htmlFooter();
}

// Close mysql
mysql_close();
?>