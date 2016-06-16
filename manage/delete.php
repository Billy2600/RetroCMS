<?php
/* ***************************************************
// Description: Allows an editor to delete his/her own
//		post.
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
require_once $incPath."/func.php";
require_once $incPath."/users.php";
require_once "manFunc.php"; // Manage section functions

// Connect to mysql
mysql_connect($DATABASE_HOST,$DATABASE_USER,$DATABASE_PASS);
@mysql_select_db($DATABASE_NAME) or die("Unable to select database");

// Check for logged in and admin/editor status
checkLoginAdminEditor();
$session = new sessions();
$user_id = $session->getUserIdFromSession();

// Check if ID is set, if not display enter ID form/latest posts
if(!isset($_GET['id']) && !isset($_POST['confirmed']))
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
		$latestPosts[$i][] = "delete";
		$postLinks .= htmlOutput("../tmpl/man/postlink.txt",array("id","name","dest"),$latestPosts[$i],true);
	}
	// Retrieve form HTML
	$form = htmlOutput("../tmpl/man/enterID.txt",array("dest","type","postlinks"),array("delete","post",$postLinks),true);
	htmlOutput("../tmpl/man/main.txt",array("title","form","admin"),array("Enter Post ID",$form,$adminOptions));
	htmlFooter();
	// Do not continue beyond this point
	die();
}

// Check if post is set, if set, delete the post and display success message
if(isset($_POST['confirmed']))
{
	// Check if this post exists
	checkPostExistence($_GET['pid']);
	// Check if we own post/are admin
	checkPostOwnership($_GET['pid'],$user_id);
	
	// Delete the post specified
	$post = new posts(array("pid"));
	$post->deletePost($_GET['pid']);
	
	htmlHeader("Post Deleted");
	displayMessage("Post has been deleted! Now redirecting back to manage home or click <a href=\"/manage/\">here</a>","redirect","/manage/");
	htmlFooter();
}
// Post not set, display delete conformation
else
{
	// Initialize post object
	$post = new posts();
	// Check if this post exists
	checkPostExistence($_GET['id']);
	// Check if we own post/are admin
	checkPostOwnership($_GET['id'],$user_id);
	
	// Display form
	htmlHeader("Management Panel - Delete Post #".$_GET['id']."");
	
	// Are you sure you want to delete this post?
	$text = 'WARNING: Deleting this post will <strong>permanently</strong> delete this post and all the comments attched to it. Are you sure you want to delete this?';
	displayMessage($text,"confirm","/manage/delete.php?pid=".$_GET['id']);
	
	htmlFooter();
}

// Close mysql
mysql_close();
?>