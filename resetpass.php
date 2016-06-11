<?php
/* ***************************************************
// Description: This will reset everyone's password and email them
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
require_once $incPath."/func.php";
require_once $incPath."/users.php";
require_once $incPath."/sessions.php";

// Connect to mysql
mysql_connect($DATABASE_HOST,$DATABASE_USER,$DATABASE_PASS);
@mysql_select_db($DATABASE_NAME) or die("Unable to select database");

$session = new sessions();

// Get all users
$users = new users(array("uid","username","email"));
$allUsers = $users->dbOutput();

// Loop through all users
for($i = 0; $i < count($allUsers); $i++)
{
	$uid = $allUsers[$i][0];
	$username = $allUsers[$i][1];
	$email = $allUsers[$i][2];
	echo("User #" . $uid . ": " . $username ."<br>");
	// Generate new pass
	$newPass = dechex(mt_rand(10000000000,99999999999));
	$newPass = substr_replace($newPass, "dmc", 5, 0);
	echo("New password is " . $newPass ."<br>");
	
	// Encrypt and insert insert it
	$encPass = $users->encryptPass($uid,$newPass);
	$users->changeFields(array("password"));
	$users->dbUpdate( array($encPass),"uid",$uid  );
	
	// Send e-mail to user
	$from_add = "ENTER AN EMAIL HERE"; 

	$subject = "Retro of the Week Password Change";
	$message = "Hello ".$username.", due to some drastic back-end changes to Retro of the Week, all passwords have been changed\n\n";
	$message .= "Your new password is: " . $newPass . ". Please log in and update your password as soon as possible.\n\n";
	$message .= "You can do this by logging in, clicking 'User CP' up at the top of the page, and then clicking 'Change Password' on the panel to the left.\n\n";
	$message .= "If you have any questions or comments, please reply to this e-mail.\n\nThank you,\n\n Billy McPherson\n\nhttp://retrooftheweek.net";
	
	$headers = "From: $from_add \r\n";
	$headers .= "Reply-To: $from_add \r\n";
	$headers .= "Return-Path: $from_add\r\n";
	$headers .= "X-Mailer: PHP \r\n";
	
	if(mail($email,$subject,$message,$headers)) 
	{
		echo("Email sent to ". $email. "<br><br>");
	} 
	else 
	{
 	   echo("Error sending email to ". $email. "<br><br>");
	}
}

mysql_close();
?>