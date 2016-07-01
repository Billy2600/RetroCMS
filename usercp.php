<?php
/* ***************************************************
// Description: This file is the user control panel,
// users edit their settings and information here
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
require_once $incPath."/messages.php";
require_once $incPath."/sessions.php";

// Get user info from session
$session = new sessions();
$loggedIn = $session->checkValid();
$user_id = $session->getUserIdFromSession();

// Redirect to main page if userid cookie is not set
if(!$loggedIn)
{
	header("Location: /");
	die();
}

// Check if $_POST['section'] is set (we're submitting a form)
if(isset($_POST['section']))
{
	switch($_POST['section'])
	{
		// General
		case "general":
			$user = new users(array("fname","lname","gender","birthday","country"));
			// Set up bday
			$bday = $_POST['year']."-".$_POST['month']."-".$_POST['day'];
			$user->dbUpdate(array($_POST['fname'],$_POST['lname'],$_POST['gender'],$bday,$_POST['country']),
				"uid",$user_id);
			break;
		// About me
		case "aboutme":
			$user = new users(array("aboutme"));
			$user->dbUpdate(array($_POST['text']),"uid",$user_id);
			break;
		// contact info
		case "contact":
			$user = new users(array("skype","msn","yahoo","aim","steam"));
			$user->dbUpdate(array($_POST['skype'],$_POST['msn'],$_POST['yahoo'],$_POST['aim'],$_POST['steam']),
				"uid",$user_id);
			break;
		// Change avatar
		case "avatar":
			// Select input type (via url or upload)
			switch($_POST['itype'])
			{
			// Use image URL
			case "url";
				// Make sure image isn't too big
				$size = getimagesize($_POST['avatar_url']);
				if($size[0] > 200 || $size[1] > 200)
				{
					htmlHeader("User CP - Error");
					displayMessage("Error: Image is too large!","goback");
					htmlFooter();
					die();
				}
				// New avatar is the URL
				$new_avatar = $_POST['avatar_url'];
				break;
			// Upload a file
			case "upload";
				$img = uploadImage($_FILES['av_file'],true,200,200,AVATAR_UPLOAD_DIR,AVATAR_UPLOAD_DIR,AVATAR_EXTERN_DIR,AVATAR_EXTERN_DIR); // Returns the locations of the image/thumb in an array
				$new_avatar = $img[0]; // This will give us the smallest one, because the upload/thumb dir are the same, the original always gets overwritten with the thumb
				break;
			default:
				// Unknown input type, display error and die
				htmlHeader("User CP - Error");
				displayMessage("Error: Unknown input type.","goback");
				htmlFooter();
				die();
			}
			$user = new users(array("avatar"));
			// Delete old avatar, if it exists on the server
			$oldAvatar = $user->dbOutput(array("uid","=".$user_id));
			if(file_exists(".".$oldAvatar[0][0]))
				unlink(".".$oldAvatar[0][0]);
			$user->dbUpdate(array($new_avatar),"uid",$user_id);
			break;
		// Change password
		case "password":
			// Make sure passwords match
			if($_POST['pass'] != $_POST['conpass'])
			{
				htmlHeader("User CP - Error");
				displayMessage("Error: Passwords did not match!","goback");
				htmlFooter();
				die();
			}
			$user = new users();
			$encPass = $user->encryptPass($user_id,$_POST['pass']);
			$user->changeFields(array("password"));
			$user->dbUpdate(array($encPass),"uid",$user_id);
			htmlHeader("User CP - Success!");
			displayMessage("Password has been changed. Redirecting back to user control panel, or click <a href=\"/ucp/\">here</a>","redirect","/logout/");
			htmlFooter();
			// Do not continue
			die();
			break;
		// Change e-mail
		case "email":
			// Make sure emails match
			if($_POST['email'] != $_POST['conemail'])
			{
				htmlHeader("User CP - Error");
				displayMessage("Error: E-mails did not match!","goback");
				htmlFooter();
				die();
			}
			$user = new users(array("email"));
			$user->dbUpdate(array($_POST['email']),"uid",$user_id);
			break;
		// Send message
		case "addmsg":
			$message = new messages();
			$message->sendMessage($_POST['text'],$_POST['title'],$_POST['to'],intval($user_id),intval($_POST['reply']));
			// Display success message
			htmlHeader("User CP - Message Sent");
			displayMessage("Message has been sent! Now redirecting to inbox, or click <a href=\"/ucp/msg/\">here</a>.","redirect","/ucp/msg/");
			htmlFooter();
			// Do not continue
			die();
			break;
		// Delete message
		case "delmsg":
			$message = new messages();
			// Will return error message if post was not deleted, otherwise returns true
			$result = $message->deleteMessage($_POST['mid']);
			if($result == true)
			{
				htmlHeader("User CP - Message Deleted");
				displayMessage("Message deleted! Redirecting back to message list, or click <a href=\"/ucp/msg/\">Here</a>","redirect","/ucp/msg/",0);
				htmlFooter();
			}
			else // Display error
			{
				htmlHeader("User CP - Error");
				displayMessage($result,"goback");
				htmlFooter();
			}
			// Do not continue
			die();
			break;
		// Default: error statement
		default:
			htmlHeader("User CP - Error");
			displayMessage("Invalid User CP section!","goback");
			htmlFooter();
			die();
	}
	// General success message, must specify die() if you don't want this to show up.
	htmlHeader("User CP - Success!");
	displayMessage("User info has been updated. Redirecting to UCP main page, or click <a href=\"/ucp/\">here</a>","redirect","/ucp/");
	htmlFooter();
	// Do not continue
	die();
}

// Is do set
if(!isset($_GET['do'])) // Do is not set, display main page
{
	htmlHeader("User CP");
	htmlOutput("tmpl/ucp/main.txt",array("title","form"),array("User Control Panel","Welcome to the User CP! It finally works! Select a section from the left panel."));
	htmlFooter();
}
// Do is set, display appropriate page
else
{
	switch($_GET['do'])
	{
		// General
		case "general":
			htmlHeader("User CP - General Information");
			// Set up the form
			$user = new users(array("fname","lname","gender","birthday","country"));
			$userInfo = $user->dbOutput(array("uid","=".$user_id));
			// Replace array, all of these will be replaced in the template
			$replace = array("fname","lname","male","female","nogender","days","months","years","countries");
			// Begin setting up general info array
			$generalInfo = array($userInfo[0][0],$userInfo[0][1]);
			// Set which gender should be selected
			// Default all of them to nothing
			for($i = 0; $i < 3; $i++)
				$generalInfo[] = "";
			
			if($userInfo[0][2] == "1")
				$generalInfo[2] = " selected=\"selected\"";
			if($userInfo[0][2] == "2")
				$generalInfo[3] = " selected=\"selected\"";
			if($userInfo[0][2] == "0")
				$generalInfo[4] = " selected=\"selected\"";
			// Initialize birthday and country fields
			for($i = 0; $i < 4; $i++)
				$generalInfo[] = "";
			// Generate birthday selections
			$bday = explode("-",$userInfo[0][3]); // Explode: year-month-day
			// Days
			for($i = 1; $i <= 31; $i++)
			{
				$value = addZero($i); // Value is i, with preceeding zero
				// Check if this one should be selected
				if($bday[2] == $i)
					$value .= "\" selected=\"selected";
				$generalInfo[5] .= htmlOutput("tmpl/ucp/options.txt",array("value","text"),array($value,$i),true);
			}
			// Months
			$months = array("January","February","March","April","May","June","July","August","September","October","November","December");
			for($i = 0; $i < 12; $i++)
			{
				$value = addZero($i+1); // Value is i plus one, with preceeding zero
				// Check if this one should be selected
				if($bday[1] == $i+1)
					$value .= "\" selected=\"selected";
				$generalInfo[6] .= htmlOutput("tmpl/ucp/options.txt",array("value","text"),array($value,$months[$i]),true);
			}
			// Years
			for($i = date("Y"); $i >= 1900; $i--)
			{
				$value = $i; // Capturing $i in $value, so we don't mess with $i
				// Check if this one should be selected
				if($bday[0] == $i)
					$value .= "\" selected=\"selected";
				$generalInfo[7] .= htmlOutput("tmpl/ucp/options.txt",array("value","text"),array($value,$i),true);
			}
			// Display countries
			require_once "inc/countries.php";
			for($i = 0; $i < count($countries); $i++)
			{
				$value = key($countries); // Each value is the key of the index we're on
				if($countries[strtoupper($userInfo[0][4])] == $countries[$value])
					$selected = "\" selected=\"selected\"";
				else
					$selected = "";
				
				$generalInfo[8] .= htmlOutput("tmpl/ucp/options.txt",array("value","text"),array(strtolower($value).$selected,$countries[$value]),true);
				next($countries); // Advance countries array, because keys are not numbers
			}
			// Output form
			$form = htmlOutput("tmpl/ucp/general.txt",$replace,$generalInfo,true);
			// display the page
			htmlOutput("tmpl/ucp/main.txt",array("title","form"),array("User CP - General Information",$form));
			htmlFooter();
			break;
		// About me
		case "aboutme":
			htmlHeader("User CP - About Me");
			// Set up the form
			$user = new users(array("aboutme"));
			$text = $user->dbOutput(array("uid","=".$user_id));
			$form = htmlOutput("tmpl/ucp/aboutme.txt",array("text"),$text[0],true);
			// display the page
			htmlOutput("tmpl/ucp/main.txt",array("title","form"),array("User CP - About Me",$form));
			htmlFooter();
			break;
		// contact info
		case "contact":
			htmlHeader("User CP - About Me");
			// Set up the form
			$user = new users(array("skype","msn","yahoo","aim","steam"));
			$contactInfo = $user->dbOutput(array("uid","=".$user_id));
			$form = htmlOutput("tmpl/ucp/contact.txt",array("skype","msn","yahoo","aim","steam"),$contactInfo[0],true);
			// display the page
			htmlOutput("tmpl/ucp/main.txt",array("title","form"),array("User CP - Contact info",$form));
			htmlFooter();
			break;
		// Change avatar
		case "avatar":
			htmlHeader("User CP - Change Avatar");
			// Set up the form
			$user = new users(array("avatar"));
			$text = $user->dbOutput(array("uid","=".$user_id));
			$form = htmlOutput("tmpl/ucp/avatar.txt",array("avatar"),$text[0],true);
			// display the page
			htmlOutput("tmpl/ucp/main.txt",array("title","form"),array("User CP - Change Avatar",$form));
			htmlFooter();
			break;
		// Change password
		case "password":
			htmlHeader("User CP - Change Password");
			$form = htmlOutput("tmpl/ucp/password.txt",false,false,true);
			htmlOutput("tmpl/ucp/main.txt",array("title","form"),array("User CP - Change Password",$form));
			htmlFooter();
			break;
		// Change e-mail
		case "email":
			htmlHeader("User CP - Change E-mail Address");
			// Set up the form
			$user = new users(array("email"));
			$text = $user->dbOutput(array("uid","=".$user_id));
			$form = htmlOutput("tmpl/ucp/email.txt",array("email"),$text[0],true);
			// display the page
			htmlOutput("tmpl/ucp/main.txt",array("title","form"),array("User CP - Change E-mail Address",$form));
			htmlFooter();
			break;
		// Messaging
		case 'msg':
			// Display single message
			if(isset($_GET['mid']))
			{
				// Display specific message
				htmlHeader("User CP - Display Message");
				$message = new messages();
				$form = $message->displayMessage($_GET['mid']);
				htmlOutput("tmpl/ucp/main.txt",array("title","form"),array("User CP - Display Message",$form));
				htmlFooter();
			}
			// Display Message list
			else 
			{
				htmlHeader("User CP - Message Inbox");
				$messages = new messages();
				// Display messages for current user
				$form = $messages->displayMessageList($user_id);
				htmlOutput("tmpl/ucp/main.txt",array("title","form"),array("User CP - Inbox",$form));
				htmlFooter();
			}
			break;
		// Add message
		case 'addmsg':
			// Display compose message form
			htmlHeader("User CP - Compose Message");
			// Put in to if the get is set
			if(isset($_GET['to']))
				$to = $_GET['to'];
			else
				$to = NULL;
			$form = htmlOutput("tmpl/ucp/composeMessage.txt",array("reply","to"),array(1,$to),true);
			htmlOutput("tmpl/ucp/main.txt",array("title","form"),array("User CP - Compose Message",$form));
			htmlFooter();
			break;
		// Change skin
		case 'changeskin':
			// Display change skin form
			htmlHeader("User CP - Change Skin");
			// Put in to if the get is set
			if(isset($_GET['to']))
				$to = $_GET['to'];
			else
				$to = NULL;
			$form = htmlOutput("tmpl/ucp/changeskin.txt",array("reply","to"),array(1,$to),true);
			htmlOutput("tmpl/ucp/main.txt",array("title","form"),array("User CP - Change Skin",$form));
			htmlFooter();
			break;
		// Default: error statement
		default:
			htmlHeader("User CP - Error");
			displayMessage("Invalid User CP section!","goback");
			htmlFooter();
	}
}
?>