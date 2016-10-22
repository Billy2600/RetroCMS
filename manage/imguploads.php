<?php
/* ***************************************************
// Description: Allows editors and admins to upload images
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

// Upload file
if(isset($_POST['submit']))
{
	// Check if file input not empty
	if(!empty($_FILES['image']['name']))
	{
		// Upload image
		uploadImage($_FILES['image']);
	}
}
// Delete image
if(isset($_GET['del']))
{
	$filename = $_GET['del'];
	// Delete file if it exists
	if(file_exists(IMG_UPLOAD_DIR.$filename))
	{
		// Check that we own file (if not admin)
		if($curUser->getUserType($user_id) == 1 || str2int($filename) == $user_id)
		{
			unlink(IMG_UPLOAD_DIR.$filename);
			// Delete thumbnail if it exists
			if(file_exists(THUMB_UPLOAD_DIR.$filename))
				unlink(THUMB_UPLOAD_DIR.$filename);
		}
	}
	header("Location: /manage/imguploads.php");
}
// Display listing/upload form
else
{
	$files = getDirectoryList(IMG_UPLOAD_DIR,"thumbs"); // Get file listing
	// Begin form with image uploader
	$form = '<strong>Upload Image:</strong> <form name="upload" action="/manage/imguploads.php" method="post" enctype="multipart/form-data">';
	$form .= '<input type="hidden" name="submit" value="true">'."\n";
	$form .= '<input type="file" name="image" size="40" id="image" /> <input type="submit" value="Submit" />';
	$form .= '</form>';
	// Offer show all link to admins
	if($curUser->getUserType($user_id) == 1 && !isset($_GET['showall']))
		$form .= "<br />\n<a href=\"/manage/imguploads.php?showall=1\">Show all uploads</a><br />\n";
	else if($curUser->getUserType($user_id) == 1 && isset($_GET['showall']))
		$form .= "<br />\n<a href=\"/manage/imguploads.php\">Show only my uploads</a><br />\n";
	// Loop through and display
	for($i = 0; $i < sizeof($files); $i++)
	{
		// Check if this is our image, if not admin that wants to see all images
		if((!isset($_GET['showall']) || $curUser->getUserType($user_id) != 1)
			&& str2int($files[$i]) != $user_id)
				continue; // Not ours, send back through loop
		$form .= "<span style=\"float: left; padding: 3px; height: 250px; overflow: auto;\">\n";
		$thumbExists = file_exists(THUMB_UPLOAD_DIR.$files[$i]); // Bool if thumb exists
		// Check if thumb exists
		if($thumbExists)
		{
			$form .= "<a href=\"".IMG_EXTERN_DIR.$files[$i]."\"><img src=\"".THUMB_EXTERN_DIR.$files[$i]."\" alt=\"".$files[$i]."\" /></a>";
		}
		// No thumb, HTML resize image
		else
		{
			$form .= "<a href=\"".IMG_EXTERN_DIR.$files[$i]."\"><img src=\"".IMG_EXTERN_DIR.$files[$i]."\" alt=\"".$files[$i]."\" style=\"width: 150px\" /></a>";
		}
		$form .= "<br /><br />\nImage Path: <input type=\"text\" size=\"20\" value=\"".IMG_EXTERN_DIR.$files[$i]."\">";
		$form .= '<br />
			<form action="/manage/imguploads.php" method="get">';
			if($thumbExists) $form .= "<br />Thumb Path: <input type=\"text\" size=\"20\" value=\"".THUMB_EXTERN_DIR.$files[$i]."\">";
		$form .= '<input type="submit" value="Delete" />
			<input type="hidden" name="del" value="'.$files[$i].'" />
			</form>';
		$form .= "\n</span>";
	}
	
	// Print everything out
	htmlHeader("Management Panel - Upload Images");
	if(isset($_GET['id']))
		$id = $_GET['id']; // Prevent undefined ID error
	else
		$id = "";
	htmlOutput("../tmpl/man/main.txt",array("title","form","admin"),array("Upload Images ".$id,$form,getAdminOptions()));
	htmlFooter();
}
?>