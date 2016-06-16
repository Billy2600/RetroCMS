<?php
/* ***************************************************
// Description: Allows editors to delete comments from
//		one of their posts
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
require_once "manFunc.php"; // Manage section functions
require_once $incPath."/comments.php";
require_once $incPath."/posts.php";
require_once $incPath."/users.php";

// Connect to mysql
mysql_connect($DATABASE_HOST,$DATABASE_USER,$DATABASE_PASS);
@mysql_select_db($DATABASE_NAME) or die("Unable to select database");

// Check for logged in and admin/editor status
checkLoginAdminEditor();
$session = new sessions();
$user_id = $session->getUserIdFromSession();

// Delete comment(s), display success message
if(isset($_POST['com_id']))
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
			addBan($ip[0][0]);
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
// Display comments for a post
elseif(isset($_GET['id']))
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
	$adminOptions = getAdminOptions();
	// Beginning of form, top of the table
	$form = htmlOutput("../tmpl/man/comTable.txt",NULL,NULL,true);
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
		$form .= htmlOutput("../tmpl/man/comTableRow.txt",array("cid","name","text","pid"),$data,true);
	}
	
	// End of table, display page
	$form .= htmlOutput("../tmpl/man/comTableEnd.txt",NULL,NULL,true);
	htmlOutput("../tmpl/man/main.txt",array("title","form","admin"),array("Comments for post ".$_GET['id'],$form,$adminOptions));
	htmlFooter();
}
// Display enter ID form
else
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
		$latestPosts[$i][] = "delete_com";
		$postLinks .= htmlOutput("../tmpl/man/postlink.txt",array("id","name","dest"),$latestPosts[$i],true);
	}
	// Retrieve form HTML
	$form = htmlOutput("../tmpl/man/enterID.txt",array("dest","type","postlinks"),array("delete_com","post",$postLinks),true);
	htmlOutput("../tmpl/man/main.txt",array("title","form","admin"),array("Enter Post ID",$form,$adminOptions));
	htmlFooter();
}

// Close mysql
mysql_close();
?>