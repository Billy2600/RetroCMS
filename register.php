<?php
/* ***************************************************
// Description: This file submits regisrations, and displays
//	registration form
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
require_once "inc/users.php";
require_once "inc/recaptchalib.php";
require_once "tmpl/strings.php";

// Function to display error
function displayError($text)
{
	htmlHeader("Regisration Error");
	displayMessage($text,"goback");
	htmlFooter();
	die();
}

// Check if user is already logged in, if so redirect them to main page
$session = new sessions();
if($session->checkValid())
{
	header("Location: /");
}
else
{
	// Have they used the form yet or not?
	if(isset($_POST['submit']))
	{
		// Submit regisration //
		
		// Check captcha
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
		// Create user object
		$user = new users();
		// Check for errors
		if(!isset($_POST['name']) || $_POST['name'] == "" || $_POST['name'] == " ") // No username
			displayError("User name not entered!");
		if(!isset($_POST['pass']) || $_POST['pass'] == "" || $_POST['pass'] == " ") // No passs
			displayError("Password not entered!");
		if($_POST['pass'] != $_POST['conpass']) // Passwords do not match
			displayError("Passwords did not match!");
		if(!isset($_POST['email']) || $_POST['email'] == "" || $_POST['email'] == " ") // No email
			displayError("E-mail address was not entered!");
		// Credit to emailregex.com for this monster
		if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
			displayError("Please enter a valid email");
		// Check if username exists
		if($user->checkUserName($_POST['name']))
			displayError("A user with that name already exists!");
		// With that out of the way, submit user info to the database
		// Put user info into array
		$userInfo = array($_POST['name'],$_POST['pass'],$_POST['email'],$_POST['gender'],
			$_POST['day'],$_POST['month'],$_POST['year'],$_POST['country']);
		$user->submitRegisration($userInfo);
		// Success message
		htmlHeader("Regisration Success! ");
		displayMessage("You have been registered! Now redirecting to the login page, or click <a href=\"/li/\">Login</a>","redirect","/li/");
		htmlFooter();
	}
	// Form not used yet, display form
	else
	{
		// Generate birthday options
		// Initialize string variables
		$days = "";
		$months = "";
		$years = "";
		// Days
		for($i = 1; $i <= 31; $i++)
		{
			$value = addZero($i); // Value is i, with preceeding zero
			// Set variable to string from template with relevant info (value no., $i as label)
			$days .= htmlOutput("tmpl/forms/options.txt",array("value","text"),array($value,$i),true);
		}
		// Months
		$monthsArray = array("January","February","March","April","May","June","July","August","September","October","November","December");
		for($i = 0; $i < 12; $i++)
		{
			$value = addZero($i+1); // Value is i plus one, with preceeding zero
			// Set variable to string from template with relevant info (value no., month name)
			$months .= htmlOutput("tmpl/forms/options.txt",array("value","text"),array($value,$monthsArray[$i]),true);
		}
		// Years
		for($i = date("Y"); $i >= 1900; $i--)
		{
			$value = $i; // Capturing $i in $value, so we don't mess with $i
			$years .= htmlOutput("tmpl/forms/options.txt",array("value","text"),array($value,$i),true);
		}
		// Display countries
		require_once "inc/countries.php";
		$countriesString = ""; // Initialize country options string
		for($i = 0; $i < count($countries); $i++)
		{
			$value = key($countries); // Each value is the key of the index we're on
			if($value == "US")
				$selected = "\" selected=\"selected"; // Select US by default
			else
				$selected = "";
			$countriesString .= htmlOutput("tmpl/forms/options.txt",array("value","text"),array(strtolower($value).$selected,$countries[$value]),true);
			next($countries); // Advance countries array, because keys are not numbers
		}
		
		// Captcha
		$error = null; // the error code from reCAPTCHA, if any
		$captcha = recaptcha_get_html(RECAPTCHA_PUBLIC_KEY, $error);
		
		// Finally, display the page
		htmlHeader("Register an account at ");
		htmlOutput("./tmpl/forms/register.txt",array("days","months","years","countries","captcha"),array($days,$months,$years,$countriesString,$captcha));
		htmlFooter();
	}
}
?>