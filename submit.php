<?php
// *******************************************
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
// *********************************************

// Required files
require_once "config.php";
require_once "inc/func.php";
require_once "inc/posts.php";
require_once "inc/recaptchalib.php";
require_once "tmpl/strings.php";

htmlHeader("Submit");

// Submit form
if(!empty($_POST))
{
	// Check captcha, if we need to
	if(!isset($_COOKIE['userid']))
	{
		$resp = recaptcha_check_answer (RECAPTCHA_PRIVATE_KEY,
                                        $_SERVER["REMOTE_ADDR"],
                                        $_POST["recaptcha_challenge_field"],
                                        $_POST["recaptcha_response_field"]);
		// Captcha not correct, display error and die
        if(!$resp->is_valid)
		{
			displayMessage($globalStrings["captcha_fail"],"goback");
			htmlFooter();
			die();
		}
	}
	$postObj = new database("unvalidated_posts", array("title","text","user","name","email","tags","img","thumb"));
	
	// Check for valid input
	if(CheckEmptyInput($_POST['title']))
	{
		displayMessage("You did not enter a title for the post","goback");
		die();
	}
	if(CheckEmptyInput($_POST['tags']))
	{
		displayMessage("You did not enter any tags","goback");
		die();
	}
	if(CheckEmptyInput($_POST['text']))
	{
		displayMessage("You did not enter any tags","goback");
		die();
	}
	// Not logged in stuff
	if(!isset($_COOKIE['userid']))
	{
		if(CheckEmptyInput($_POST['name']))
		{
			displayMessage("You did not enter your name","goback");
			die();
		}
		if(CheckEmptyInput($_POST['email']))
		{
			displayMessage("You did not enter your e-mail address","goback");
			die();
		}
		$userId = 0;
		$name = $_POST['name'];
		$email = $_POST['email'];
	}
	else
	{
		$userId = $_COOKIE['userid'];
		$name = "";
		$email = "";
	}
		
	// Upload image if not empty
	if(!empty($_FILES['image']['name']))
	{
		// Upload image
		$img = uploadImage($_FILES['image']); // Returns the locations of the image/thumb in an array
	}
	else
		$img = array("","");
	
	// Insert content
	$postObj->dbInput(array( $_POST['title'],$_POST['text'],$userId,
		$name,$email,$_POST['tags'], $img[0], $img[1] ));
		
	// Display success message
	displayMessage('Success! Your post has been added to our system and is awaiting validation. '.
		'If your post is added, you will be notified by e-mail. You will be redirected to the home page, '.
		'or <a href="/">Click here</a>',"redirect","/",100);
}
// Display form
else
{
	// Captcha
	if(!isset($_COOKIE['userid']))
	{
		$error = null; // the error code from reCAPTCHA, if any
		$captcha = recaptcha_get_html(RECAPTCHA_PUBLIC_KEY, $error);
	}
	else
		$captcha = "";
		
	// Store form HTML
	$form = htmlOutput($tmplPath."/forms/submit.txt",array("captcha"),array($captcha),true);
	// Hide name/e-mail if logged in
	if(isset($_COOKIE['userid']))
	{
		$form = str_replace("[REMOVE1] -->","",$form);
		$form = str_replace("<!-- [REMOVE2]","",$form);
	}
	// Print it out
	echo $form;
}

htmlFooter();
?>