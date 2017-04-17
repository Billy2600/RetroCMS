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
require_once "../inc/func.php"; // Global Functions
require_once "manFunc.php"; // Manage section functions
require_once "../inc/users.php";
require_once "../inc/posts.php";

// Check for logged in and admin/editor status
checkLoginAdminEditor();
$session = new sessions();
$user_id = $session->getUserIdFromSession();
$user = new users();

// Admin check
if($user->getUserType($session->getUserIdFromSession()) != 1)
{
	header("Location: /");
}

// Check if post is set, if set, update data in the database for this post, display success message
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	// Insert data
	$fields = array("username", "fname", "lname", "aboutme", "email", "avatar", "gender", "country");
	$data = array($_POST["username"], $_POST["fname"], $_POST["lname"], $_POST["aboutme"], $_POST["email"], $_POST["avatar"], $_POST["gender"], $_POST["country"]);
	// Set up data that needs formatting
	if(isset($_POST["pass"]) && $_POST["pass"] != "")
	{
		if($_POST["pass"] != $_POST["confirm_pass"])
		{
			htmlHeader("Error");
			displayMessage("Passwords did not match!","goback");
			htmlFooter();
			die();
		}

		array_push($fields, "password");
		array_push($data, $user->encryptPass($_POST['uid'], $_POST['pass']));
	}

	if(isset($_POST["account_type"]))
	{
		array_push($fields, "account_type");
		array_push($data, (int)$_POST["account_type"]);
	}

	array_push($fields, "birthday");
	array_push($data, $_POST["year"] . "-" . $_POST["month"] . "-" . $_POST["day"]);

	$user->changeFields($fields);
	$user->dbUpdate($data,"uid",$_POST['uid']);
	// Display success message/redirect
	htmlHeader("User Edited");
	displayMessage("User has been Edited! Now redirecting to user edit page, or click <a href=\"/manage/users.php\">Here</a>","redirect","/manage/users.php");
	htmlFooter();
}
else if(isset($_GET['id']))
{
	if($user->CheckUserExists($_GET['id']) == false)
	{
		htmlHeader("Error");
		displayMessage("User with that ID does not exist!","goback");
		htmlFooter();
		die();
	}
	
	$fields = array("username", "password", "fname", "lname", "aboutme", "account_type", "DAY(birthday)", "MONTH(birthday)", "YEAR(birthday)", "email", "avatar", "gender", "country");
	$user->changeFields($fields);
	$data = $user->dbOutput(array("uid=",$_GET['id']));
	
	// Generate selection menus
	$days = "";
	$months = "";
	$years = "";
	$accTypesString = "";
	$gendersString = "";
	// Days
	for($i = 1; $i <= 31; $i++)
	{
		$value = addZero($i); // Value is i, with preceeding zero
		$selected = "";
		if($i == $data[0][6])
			$selected = "\" selected=\"selected";
		// Set variable to string from template with relevant info (value no., $i as label)
		$days .= htmlOutput("../tmpl/forms/options.txt",array("value","text"),array($value.$selected,$i),true);
	}
	// Months
	$monthsArray = array("January","February","March","April","May","June","July","August","September","October","November","December");
	for($i = 0; $i < 12; $i++)
	{
		$value = addZero($i+1);
		$selected = "";
		if($i+1 == $data[0][7])
			$selected = "\" selected=\"selected";
		$months .= htmlOutput("../tmpl/forms/options.txt",array("value","text"),array($value.$selected,$monthsArray[$i]),true);
	}
	// Years
	for($i = date("Y"); $i >= 1900; $i--)
	{
		$value = $i; // Capturing $i in $value, so we don't mess with $i
		$selected = "";
		if($i == $data[0][8])
			$selected = "\" selected=\"selected";
		$years .= htmlOutput("../tmpl/forms/options.txt",array("value","text"),array($value.$selected,$i),true);
	}
	// Display countries
	require_once "../inc/countries.php";
	$countriesString = ""; // Initialize country options string
	for($i = 0; $i < count($countries); $i++)
	{
		$value = key($countries); // Each value is the key of the index we're on
		$selected = "";
		if(strtolower($value) == $data[0][12])
			$selected = "\" selected=\"selected";
			
		$countriesString .= htmlOutput("../tmpl/forms/options.txt",array("value","text"),array(strtolower($value).$selected,$countries[$value]),true);
		next($countries); // Advance countries array, because keys are not numbers
	}
	// Account types
	$accTypes = array("Admin", "Editor", "User", "Banned");
	for($i = 0; $i < count($accTypes); $i++)
	{
		$value = $i+1;
		$selected = "";
		if($i+1 == (int)$data[0][5])
			$selected = "\" selected=\"selected";
		$accTypesString .= htmlOutput("../tmpl/forms/options.txt",array("value","text"),array(strtolower($value).$selected,$accTypes[$i]),true);
	}
	$genders = array("Not Telling", "Male", "Female");
	for($i = 0; $i < count($genders); $i++)
	{
		$value = $i;
		$selected = "";
		if($i == (int)$data[0][11])
			$selected = "\" selected=\"selected";
		$gendersString .= htmlOutput("../tmpl/forms/options.txt",array("value","text"),array(strtolower($value).$selected,$genders[$i]),true);
	}

	// Don't let admins lock themself out of the admin panel
	// or let root admin be disabled
	$accTypeDisable = "";
	if($session->getUserIdFromSession() == $_GET['id'] || $_GET['id'] == 1)
	{
		$accTypeDisable = "disabled";
	}

	array_push($fields, "days");
	array_push($data[0], $days);
	array_push($fields, "months");
	array_push($data[0], $months);
	array_push($fields, "years");
	array_push($data[0], $years);
	array_push($fields, "countries");
	array_push($data[0], $countriesString);
	array_push($fields, "account_type_selection");
	array_push($data[0], $accTypesString);
	array_push($fields, "gender_selection");
	array_push($data[0], $gendersString);
	array_push($fields, "acc_type_disable");
	array_push($data[0], $accTypeDisable);
	array_push($fields, "uid");
	array_push($data[0], $_GET['id']);

	htmlHeader("Editting User ID " . $_GET['id']);
	$adminOptions = getAdminOptions();
	$form = htmlOutput("../tmpl/man/user.txt",$fields,$data[0],true);
	htmlOutput("../tmpl/man/main.txt",array("title","form","admin"),array("Editing User ID ".$_GET['id'],$form,$adminOptions));
	htmlFooter();
}
else
{
	htmlHeader("Enter User ID");
	// Display admin options if we are one
	$adminOptions = getAdminOptions();
	$users = $user->GetUserList(20);
	// Show some users
	$userLinks = "";
	for($i = 0; $i < count($users); $i++)
	{
		$users[$i][] = "users";
		$userLinks .= htmlOutput("../tmpl/man/postlink.txt",array("id","name","dest"),$users[$i],true);
	}

	// Retrieve form HTML
	$form = htmlOutput("../tmpl/man/enterID.txt",array("dest","type","postlinks"),array("users","user",$userLinks),true);
	htmlOutput("../tmpl/man/main.txt",array("title","form","admin"),array("Enter User ID",$form,$adminOptions));
	htmlFooter();
}
?>