<?php
/* ***************************************************
// Description: Allows editors and admins to add posts
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
require_once "../inc/func.php"; // Global Functions
require_once "manFunc.php"; // Manage section functions
require_once "../inc/users.php";
require_once "../inc/posts.php";

// Check for logged in and admin/editor status
checkLoginAdminEditor();
$session = new sessions();
$user_id = $session->getUserIdFromSession();

// Check if post is set, if set, submit it into database, display success message
if(isset($_POST['submit']))
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
	$data = array($title,$text,$user_id,$date,$tags,$hidden);
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
	$newPostArray = $post->dbOutput(array("poster_id","=".$user_id),"1","ORDER BY date DESC");
	// Convert outputted array to single variable
	$newPost = $newPostArray[0][0];
	// Display success message/redirect
	htmlHeader("Post Added");
	displayMessage("Post has been added! Now redirecting to it, or click <a href=\"/p/$newPost/\">Here</a>","redirect","/p/$newPost/");
	htmlFooter();
}
// Post not set, Display manage main page
else
{
	htmlHeader("Management Panel - Add Post");
	// Display admin options if we are one
	$adminOptions = getAdminOptions();
	// Retrieve form HTML
	$form = htmlOutput("../tmpl/man/add.txt",array(),array(),true);
	htmlOutput("../tmpl/man/main.txt",array("title","form","admin"),array("Add Post",$form,$adminOptions));
	htmlFooter();
}
?>