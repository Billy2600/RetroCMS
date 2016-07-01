<?php
/* ***************************************************
// Description: This file inserts comments into the database
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
require_once "inc/posts.php";
require_once "inc/messages.php";
require_once "inc/users.php";
require_once "tmpl/strings.php";
require_once("inc/recaptchalib.php");

// Login info
$session = new sessions();
$loggedIn = $session->checkValid();
$user_id = $session->getUserIdFromSession();

// Make sure form was used
if(isset($_POST['post_id']))
{
	// Don't post to this id's
	if((int)$_POST['post_id'] == 72)
	{
		die();
	}
	// If user is not logged in, check captcha
	if(!$loggedIn)
	{
		$resp = recaptcha_check_answer (RECAPTCHA_PRIVATE_KEY,
                                        $_SERVER["REMOTE_ADDR"],
                                        $_POST["recaptcha_challenge_field"],
                                        $_POST["recaptcha_response_field"]);
		// Captcha not correct, display error and die
        if(!$resp->is_valid)
		{
			htmlHeader($globalStrings["error_title"]);
			displayMessage($globalStrings["captcha_fail"],"goback");
			htmlFooter();
			die();
		}         
	}
	// If email field was entered into, we got a bot
	if( $_POST["email"] != "" )
	{
		htmlHeader($globalStrings["error_title"]);
		displayMessage($globalStrings["captcha_fail"],"goback");
		htmlFooter();
		die();
	}
	// Begin inserting data into mysql
	$comment = new comments(array("name","text","poster_id","date","post_id","reply","ip_address","msg_reply"));
	// Get current date
	$date = $comment->getDateForMySQL();
	
	// Insert //
	
	// Check if name field was empty
	if(trim($_POST['name']) == "")
		$posterName = $globalStrings["anon"];
	else
		$posterName = $_POST['name'];
	// Did we want to be notified of replies?
	if(isset($_POST['msg_reply']))
		$msg_reply = 1;
	else
		$msg_reply = 0;
	// Insert into database
	$comment->dbInput(array($posterName,$_POST['text'],$_POST['user_id'],$date,$_POST['post_id'],$_POST['reply'],$_SERVER['REMOTE_ADDR'],$msg_reply));
	
	// Message the owner of the post, if the setting was set
	$usr = new users();
	$msg = new messages();
	$pst = new posts();
	
	// Do we pass along a name, or an ID? Used in next two if blocks
	if($loggedIn)
		$uname = $usr->getNameFromID($_POST['user_id']);
	else
		$uname = $posterName;
	
	// Notify poster that someone has commented, if we're not the author
	$posterID = $pst->getPosterID($_POST['post_id']); // Get poster ID
	if(!$loggedIn || $user_id != $posterID)
	{
		$pname = $usr->getNameFromID($posterID); // Get poster name
		// Set up text
		$text = $uname.' has left a comment on your post! <a href="/p/'.$_POST['post_id'].'/">Click here</a> '."to view the comments of your post.<br />\nHere's a preview of the comment:";
		$text .= "\n<blockquote>".substr($_POST['text'],0,400)."</blockquote>";
		// Send it
		$msg->sendMessage($text,"Comment added to your post",$pname);
	}
	
	// Let the person we're replying to know we replied, if this is a reply, and they want it that way
	if((int)$_POST['reply'] != 0 && $comment->wantReplyNotify($_POST['reply']))
	{
		$posterID = $comment->getPosterID($_POST['reply']); // Get ID of the person who posted the comment
		$pname = $usr->getNameFromID($posterID); // Get user name of that person
		// Set up text
		$text = $uname.' has replied to your comment. <a href="/p/'.$_POST['post_id'].'/#comments">Click here</a> '."to view the reply tree.<br />\nHere's a preview of the comment:";
		$text .= "\n<blockquote>".substr($_POST['text'],0,400)."</blockquote>";
		// Send it
		$msg->sendMessage($text,"Reply to your comment",$pname);
	}
	
	// Redirect back to post page comments section
	header("Location: /p/".$_POST['post_id']."/#r0");
}
// No post id, throw error
else
{
	htmlHeader($globalStrings["error_title"]);
	displayMessage($globalStrings["error_no_id"],"goback");
	htmlFooter();
}
?>