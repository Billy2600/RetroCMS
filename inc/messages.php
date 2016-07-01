<?php
// ******************************************************
// Description: Class to handle all message data, outputing
// it, inputing, modifying, etc. all the special cases
// This class inherits from database
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
// *******************************************************

// Required files
require_once "database.php";
require_once "users.php";

class messages extends database
{
	// CONSTRUCTOR
	// Sets the field array based on a parameter, fields are usually not required
	function __construct($fld = array("mid","title","text"))
	{
		// Call parent constructor
		parent::__construct("messages",$fld);
	}
	
	// PUBLIC
	
	// Send a message
	// Postcondition: Sends a message with the text $msg and title $title to the user with id $to,
	// from user with id $from, optionally; if from ID isn't specified it'll just be a system message
	// If this is a reply, include set $reply to the id of the reply
	// Set $from to "cookie" if you just want to use the current user as the 
	public function sendMessage($msg,$title,$to,$from=0,$reply=0)
	{
		// Make sure the person we're sending it to exists, if this is from someone
		if($from != 0)
		{
			$checkUser = new users();
			if($checkUser->checkUserName($to) == false)
			{
				htmlHeader("Error");
				displayMessage("No user with that name exists!","goback");
				htmlFooter();
				die();
			}
		}
		// Set up fields
		parent::changeFields(array("title","text","date","`to`","`from`","reply"));
		// Get the date and time
		$date = $this->getDateForMySQL();
		// Get the id of the person we're sending it to
		$sendTo = new users();
		$tid = $sendTo->getIdFromName($to);
		// Insert data into database
		$this->dbInput(array($title,$msg,$date,$tid,$from,$reply));
	}
	
	// Set message to read
	// Postcondition: Table `read` will be set to '1' for message with id of $mid
	public function setMessageRead($mid)
	{
		parent::changeFields(array("`read`"));
		$this->dbUpdate(array("1"),"mid",$mid);
	}
	
	// Check unread messages
	// Postcondition: Will return true if user with id $uid has unread messages, otherwise returns false
	public function checkUnreadMsg($uid)
	{
		parent::changeFields(array("mid"));
		$messages = $this->dbOutput(array("`read`","=0"),false,"AND `to`=$uid");
		if(count($messages) > 0)
			return true;
		else
			return false;
	}
	
	// Display messages
	// Postconditin: Will return all the messages for user $uid with the appropriate userCP template
	public function displayMessageList($uid)
	{
		$list = ""; // Initialize list
		// Set up fields
		parent::changeFields(array("mid","title","text","date","to","from","read"));
		// Get messages for user specified
		$messages = $this->dbOutput(array("`to`","=$uid"),false,"ORDER BY `date` DESC");
		// Return here if no messages were found
		if(count($messages) < 1)
			return "No Messages found";
		// Set up strings to replace in the HTML template; fid and fname are the from-user info
		$replace = array("mid","title","text","date","fid","fname","read");
		// Display messages
		for($i = 0; $i < count($messages); $i++)
		{
			$fuser = new users();
			// Get the from user's name, if id is not zero
			$fid = $messages[$i][5];
			if($fid != 0)
				$fname = $fuser->getNameFromID($fid);
			else // If it is zero give it the name "System"
				$fname = "System";
			// Add above into data array
			$messages[$i][4] = $fid;
			$messages[$i][5] = $fname;
			// If if the name of the post is empty, give it a generic name
			if($messages[$i][1] == "" || $messages[$i][1] == " " || $messages[$i][1] == NULL)
				$messages[$i][1] = "[No title]";
			// Format date
			$messages[$i][3] = $this->convertDateTime($messages[$i][3],true);
			// Diffrent templates if this has been read or not
			$read = intval($messages[$i][6]);
			if($read == 1)
				$template = "./tmpl/ucp/messageLink.txt";
			else
				$template = "./tmpl/ucp/messageLinkUnread.txt";
			// Output data
			$list .= htmlOutput($template,$replace,$messages[$i],true);
		}
		return $list;
	}
	
	// Display a single message
	// Postcondition: Will return the message with the ID $mid with the approrpiate HTML
	public function displayMessage($mid)
	{
		// Login info
		$session = new sessions();
		$user_id = $session->getUserIdFromSession();

		// Set message to read
		$this->setMessageRead($mid);
		// Set up fields
		parent::changeFields(array("title","text","date","to","from","reply"));
		// Get message with this ID
		$message = $this->dbOutput(array("`mid`","=$mid"));
		// Check if message exist
		if(count($message) < 1)
			return "Invalid message.";
		// Make sure this message belongs to currently logged in user
		if(intval($message[0][3]) != intval($user_id))
			return "Invalid message";
		// Set up replace strings
		$replace = array("title","text","date","fid","fname","read");
		$fuser = new users();
		// Get the from user's name, if the ID is not zero
		$fid = $message[0][4];
		if($fid != 0)
			$fname = $fuser->getNameFromID($fid);
		else // If it is zero give it the name "System"
			$fname = "System";
		// Add above into data array
		$message[0][3] = $fid;
		$message[0][4] = $fname;
		// Replace special characters in the text, if message is not from the system
		if($fid != 0)
			$message[0][1] = replaceSpecial($message[0][1]);
		// Format date
		$message[0][2] = $this->convertDateTime($message[0][2],true);
		// Get previous post's text if this is a reply
		$reply = $message[0][5];
		if(intval($reply) != 0)
		{
			parent::changeFields(array("text"));
			$text = $this->dbOutput(array("mid","=$reply"));
			$message[0][1] .= htmlOutput("./tmpl/ucp/prevMessage.txt",array("text"),array($text[0][0]),true);
		}
		
		$output = htmlOutput("./tmpl/ucp/message.txt",$replace,$message[0],true);
		// Include compose message reply form, replace [to] with who we got the message from
		// 	and replace [reply] with the ID of this post
		$output .= htmlOutput("tmpl/ucp/composeMessageReply.txt",array("to","reply"),array($fname,$mid),true);
		// Finally, return the output
		return $output;
	}
	
	// Delete a message
	// Deletes the message with id $mid, if it was sent to logged in user
	// Returns true if a message was deleted, otherwise returns error string.
	public function deleteMessage($mid)
	{
		$session = new sessions();
		$user_id = $session->getUserIdFromSession();
		// Make sure we own this post
		parent::changeFields(array("to"));
		$mid = intval($mid);
		$message = $this->dbOutput(array("mid","=$mid")); // Post with this id
		// If no message found, display error
		if(count($message) < 1)
			return "Invalid message";
		// User match? if not, display error
		if(intval($message[0][0]) != intval($user_id))
			return "Invalid message";
		// Proceed to delete message
		$this->deleteData("mid",$mid);
		return true;
	}
}