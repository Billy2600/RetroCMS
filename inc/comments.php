<?php
// ******************************************************
// Description: Class to handle all comments, outputing
// it, inputing, modifying, etc. all the special cases
// This class inherits from handleData
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
require_once "handleData.php";
require_once "users.php";
require_once "votes.php";
require_once "sessions.php";

class comments extends handleData
{
	// PRVIATE
	
	// CONSTRUCTOR
	// Sets the field array based on a parameter, fields are usually not required
	function __construct($fld = array("name","text","date"))
	{
		// Call parent constructor
		parent::__construct("comments",$fld);
	}
	
	// Function to calculate margin for comments, based on # of parents
	// Postcondition: Returns int value of the margin percentage
	private function getMargin($parents)
	{
		$margin = (($parents + 1) * 5);
		
		return $margin;
	}
	
	// PUBLIC
	
	// Get number of comments for a post
	// Postcondition: Number of comments for the post $id will be returned
	public function getNoOfComments($id)
	{
		parent::changeFields(array("cid"));
		return count($this->dbOutput(array("post_id","=$id")));
	}
	
	// Display comments
	// Postcondition: Will display comments for post $pid
	// NOTE: This function is a recursive function, it calls itself to get a comment's replies (children)
	// Set $rid should be the id of the comment it's replying to, $parents should be the amount of parents
	// the function has gone through, for tabbing purposes
	public function displayComments($pid,$rid = false,$parents = 0)
	{
		// Logged in info
		$session = new sessions();
		$loggedIn = $session->checkValid();
		$user_id = $session->getUserIdFromSession();
		
		// These fields are required
		parent::changeFields(array("cid","name","text","date","poster_id"));
		// Is this a child or not
		if($rid == false)
			$replyID = 0;
		else
			$replyID= $rid;
		$comments = $this->dbOutput(array("post_id","=$pid"),false," AND reply=$replyID");
		// Display comments
		for($i = 0; $i < count($comments); $i++)
		{
			// Get the fields array, we need this later
			$replace = parent::getFields();
			// Give the comment a margin if it's a reply, based on how many parents
			$replace[] = "margin"; // Add margin to replacements
			if($rid != false)
				// Add a margin
				$comments[$i][] = "margin-left: ".$this->getMargin($parents)."%";
			else
				$comments[$i][] = "";
			
			// Get user info
			if($comments[$i][4] != NULL && $comments[$i][4] != "NULL" && $comments[$i][4] != "0")
			{
				$user = new users(array("username","avatar"));
				$uid = $comments[$i][4];
				$userInfo = $user->dbOutput(array("uid","=$uid"));
				// Replace name with their name as a link to their profile
				$comments[$i][1] = htmlOutput("./tmpl/com/userLink.txt",array("id","name"),array($uid,htmlspecialchars($userInfo[0][0])),true);
				// Replace avatar
				$replace[] = "avatar";
				$comments[$i][] = $userInfo[0][1];
			}
			// Put in guest info
			else
			{
				$uid = 0;
				// Set default avatar
				$replace[] = "avatar";
				$comments[$i][] = "/img/default-av.png";
			}
			// Replace special characters in the text.
			$comments[$i][2] = replaceSpecial($comments[$i][2]);
			// Strip slashes
			$comments[$i][2] = stripslashes($comments[$i][2]);
			// Convert date/time
			$comments[$i][3] = $this->convertDateTime($comments[$i][3],true);
			
			// Edit link, if the user owns this comment
			$replace[] = "edit";
			if($loggedIn && $user_id == $comments[$i][4])
			{
				$comments[$i][] = htmlOutput("./tmpl/com/editLink.txt",array("id"),array($comments[$i][0]),true);
			}
			else
				$comments[$i][] = "";
			
			// Add thumbs up/down
			$replace[] = "rating"; // Add to list of replacements
			if( $loggedIn )
				$ratingHtml = "./tmpl/forms/ratinglinks.txt";
			else
				$ratingHtml = "./tmpl/forms/ratingtext.txt";
			$voteObj = new votes();
			$comments[$i][] = htmlOutput( $ratingHtml,
				array( "pid","up","down","type" ),
				array( $comments[$i][0],$voteObj->GetNoThumbsUp( $comments[$i][0],1 ),$voteObj->GetNoThumbsDown( $comments[$i][0],1 ), 1 ),
				true );
			
			// Finally output the comment
			htmlOutput("./tmpl/com/displayComment.txt",$replace,$comments[$i]);
			// Now display the reply form
			// Hide the name part if user is logged in
			if($loggedIn)
			{
				$hidename = "display: none";
				$user = $user_id;
			}
			else 
			{
				$hidename = "";
				$user = 0;
			}
			// If no parents, no margin
			if($parents >= 1)
				$margin = "margin-left: ".$this->getMargin($parents)."%; display: none;";
			else
				$margin = "display: none;";
			
			// display children
			$this->displayComments($pid,$comments[$i][0],$parents + 1);
		}
	}
	
	// Delete comments from post
	// Will delete all the comments attched to the post with $pid
	// WARNING: This will not display a confirmation or check user permissions
	public function deleteCommentsFromPost($pid)
	{
		$this->deleteData("post_id",$pid);
	}
	
	// Get post ID from a comment
	// Postcondition: Will return the post ID assocated with the comment with the ID provided
	public function getPostIdFromComment($cid)
	{
		parent::changeFields(array("post_id"));
		$post = $this->dbOutput(array("cid=",$cid));
		return $post[0][0];
	}
	
	// Does this comment ID want to be notified of replies?
	// Postcondition: Returns true if the scecified ID wants to notify the poster if you have relpied to it
	public function wantReplyNotify($cid)
	{
		parent::changeFields(array("msg_reply"));
		$reply = $this->dbOutput(array("cid=",$cid));
		// Return true or false
		if((int)$reply[0][0] == 1)
			return true;
		else
			return false;
	}
	
	// Get ID of comment poster
	// Postcondition: Will return the userID of the person who posted the comment with the ID provided
	public function getPosterID($cid)
	{
		parent::changeFields(array("poster_id"));
		$poster = $this->dbOutput(array("cid=",$cid));
		return $poster[0][0];
	}
	
	// Get latest comments for sidebar
	public function getLatestComments($limit=5)
	{
		global $tmplPath;
		parent::changeFields(array("cid","text","post_id"));
		// Get the total number of posts
		$comments = $this->dbOutput( array(),$limit,"ORDER BY cid DESC" );
		$outputHtml = NULL; // Initializse output HTML variable
		
		for($i=0; $i < count($comments); $i++)
		{
			// Shorten comment text
			$comments[$i][1] = substr($comments[$i][1],0,50). "...";
			
			$outputHtml .= htmlOutput($tmplPath."/sidebarListComment.txt",array("cid","text","pid"),$comments[$i],true);
		}
		return $outputHtml;
	}
}