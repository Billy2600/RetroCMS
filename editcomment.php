<?php
/****************************************************
// Description: This file allows users to edit their comments
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
require_once "config.php";
require_once "inc/func.php";
require_once "inc/comments.php";
require_once "tmpl/strings.php";

// Connect to mysql
mysql_connect($DATABASE_HOST,$DATABASE_USER,$DATABASE_PASS);
@mysql_select_db($DATABASE_NAME) or die("Unable to select database");

// Make sure we have an id set
if(!isset($_GET['id']))
{
	htmlHeader($globalStrings["error_title"]);
	displayMessage($globalStrings["error_no_id"],"goback");
	htmlFooter();
	die();
}

// Function to make sure this comment belongs to this user
function checkPermission()
{
	global $globalStrings;
	$commentObj = new comments(array("poster_id"));
	$cUserID = $commentObj->dbOutput(array("cid=",$_GET['id']));
	$session = new sessions();
	$loggedIn = $session->checkValid();
	$sUserID = $session->getUserIdFromSession();

	if(!$loggedIn || $sUserID != (int)$cUserID[0][0])
	{
		htmlHeader($globalStrings["error_title"]);
		displayMessage($globalStrings["error_com_not_allowed"],"goback");
		htmlFooter();
		return false;
	}
	else
		return true;
}

// Form was used, input data
if(isset($_POST['text']))
{
	if(checkPermission() == false) die(); // Do not continue if not allowed
	// Set up data array
	$commentObj = new comments(array("text","msg_reply","ip_address"));
	if(isset($_POST['msg_reply']))
		$msg_reply = 1;
	else
		$msg_reply = 0;
	$data = array($_POST['text'],$msg_reply,$_SERVER['REMOTE_ADDR']);
	$commentObj->dbUpdate($data,"cid",$_GET['id']);
	
	// Success
	htmlHeader($globalStrings["header_edit_success"]);
	$url = "/p/".$commentObj->getPostIdFromComment($_GET['id'])."/#comment_".$_GET['id'];
	displayMessage($globalStrings["edit_success"].str_replace("[url]",$url,$globalStrings["or_click_here"]),
		"redirect",$url);
	htmlFooter();
}
// Display edit form
else
{
	if(checkPermission() == false) die(); // Do not continue if not allowed
	// Get comment information
	$commentObj = new comments(array("text","msg_reply","poster_id"));
	$commentInfo =  $commentObj->dbOutput(array("cid=",$_GET['id']));
	// Display form //
	
	// Get message reply checkbox, make it checked if msg_reply = 1 in DB
	if((int)$commentInfo[0][1] == 1)
		$checked = htmlOutput("tmpl/forms/checked.txt",array(),array(),true);
	else
		$checked = "";
	$msg_reply = htmlOutput("tmpl/forms/msg_reply.txt",array("checked"),array($checked),true);
	// Output
	htmlHeader($globalStrings["header_editcom"]);
	htmlOutput("tmpl/forms/edit_com.txt",array("id","text","msg_reply"),array($_GET['id'],stripslashes($commentInfo[0][0]),$msg_reply));
	htmlFooter();
}

mysql_close();
?>