<?php
/* ***************************************************
// Description: Allows an admin to add or remove IP bans
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
require_once $incPath."/uv_posts.php";
require_once $incPath."/users.php";
require_once "manFunc.php"; // Manage section functions

// Check for logged in and admin/editor status
checkLoginAdminEditor();
// This is an admin only page, so make sure we're an admin, not an editor
$session = new sessions();
$userType = $curUser->getUserType($session->getUserIdFromSession());
// Editors get out
if($userType == 2)
	header("Location: /manage/");
	
// UvPosts object
$uvPosts = new UvPosts();

// Delete post
if(isset($_GET['deny']))
{
	// Display confirmation
	if(!isset($_POST['confirmed']))
	{
		htmlHeader("Deny confirmation");
		// Are you sure you want to delete this post?
		$text = 'WARNING: Denying this post will delete it, and it <strong>cannot</strong> be recovered. Are you sure?';
		displayMessage($text,"confirm","/manage/validate.php?deny=".$_GET['deny']);
		htmlFooter();
	}
	// Delete post
	else
	{
		// Delete the image/thumb if they exist
		$uvPosts->changeFields(array("img","thumb"));
		$img = $uvPosts->dbOutput(array("pid","=".(int)$_GET['deny']));
		// Delete images
		//$path_array = explode( $img[0],"/" );
		if(file_exists($_SERVER['DOCUMENT_ROOT'].$img[0][0]))
			unlink($_SERVER['DOCUMENT_ROOT'].$img[0][0]);
		if(file_exists($_SERVER['DOCUMENT_ROOT'].$img[0][1]))
			unlink($_SERVER['DOCUMENT_ROOT'].$img[0][1]);
			
		// Delete post
		$uvPosts->deleteData("pid",(int)$_GET['deny']);		
			
		// Redirect back to validate page
		header("Location: /manage/validate.php");
	}
}
// Approve post
else if(isset($_GET['approve']))
{
	// Display confirmation
	if(!isset($_POST['confirmed']))
	{
		htmlHeader("Approval confirmation");
		// Are you sure you want to approve this post?
		displayMessage('Are you sure you want to approve this post?',"confirm","/manage/validate.php?approve=".$_GET['approve']);
		htmlFooter();
	}
	// Approve post (copy it over)
	else
	{
		// Get approval info
		$approvInfo = $uvPosts->GetApprovalInfo( (int)$_GET['approve'] );
		// Put it into a post
		$postObj = new Posts(array("title","text","img","tags","poster_id","name","email","date"));
		// Get date
		$approvInfo[7] = $postObj->getDateForMySQL();
		// Insert
		$postObj->dbInput($approvInfo);
		// E-mail the author
		if( (int)$approvInfo[4] != 0) // User e-mail
		{
			$userObj = new Users( array("username","email") );
			$output = $userObj->dbOutput( array("uid=",(int)$approvInfo[4] ) );
			$name = $output[0][0];
			$email = $output[0][1];
		}
		else // Guest e-mail
		{
			$name = $approvInfo[5];
			$email = $approvInfo[6];
		}
		
		$extra = "";
		// Send the e-mail
		try
		{
			mail($email,
				"Your post has been approved on QualityRoms",
				htmlOutput( "../tmpl/approval_email.txt",array("user"),array($name),true ),
				"From: noreply@qualityretro.net" 
				);
		}
		// Catch exception
		catch(Exception $e)
		{
			// Note error in the message
			$extra = "<strong>However, the e-mail to the user could not be sent.</strong>";
		}
		
		// Delete entry from uvposts table
		$uvPosts->deleteData( "pid",(int)$_GET['approve'] );
		// Print success message
		htmlHeader("Success");
		displayMessage("Post has been successfully validated. $extra Redirecting, or click <a href=\"/manage/validate.php\">here<a/>.","redirect","/manage/validate.php", 5);
		htmlFooter();
	}
}
// View post
else if(isset($_GET['view']))
{
	htmlHeader("Preview Post - ",true);
	$uvPosts->DisplayPost($_GET['view']);
	htmlFooter(true);
}
// Display unvalidated posts
else
{
	htmlHeader("Unvalidated Posts");
	// Display admin options if we are one
	$adminOptions = getAdminOptions();
	
	// Get contents
	$form = $uvPosts->ListPosts();
	
	// Display it all
	htmlOutput("../tmpl/man/main.txt",array("title","form","admin"),array("Unvalidated Posts",$form,$adminOptions));
	htmlFooter();
}
?>