<?php
// ******************************************************
// Description: Class to handle all unvalidated posts.
//	None of this will likely be see outisde of the
//	management section.
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
require_once "database.php";
require_once "users.php";

class UvPosts extends database
{
	// CONSTRUCTOR
	// Sets the field array based on a parameter, fields are usually not required
	function __construct($fld = array("pid","title"))
	{
		// Call parent constructor
		parent::__construct("unvalidated_posts",$fld);
	}
	
	// Get the total number of unvalidated posts (i.e. all of them in the table)
	// Returns an integer of the total number of posts
	public function GetTotal()
	{
		$this->changeFields(array("pid"));
		$postsArray = $this->dbOutput(); // Get everything
		// Count it
		return count($postsArray);
	}
	
	// List all unvalidated posts
	// Returns all of the unvalidated posts with basic information as an html string
	public function ListPosts()
	{
		$this->changeFields(array("pid","title","user","name","email")); // We want these fields
		// Get data
		$posts = $this->dbOutput();
		$output = ""; // Init output string
		
		// If there are no validating post
		if(count($posts) < 1)
		{
			return "There are no posts awaiting validation";
		}
		
		// Loop through and add posts
		for($i=0; $i < count($posts); $i++)
		{
			// Build user link
			if((int)$posts[$i][2] != 0) // Existing user
			{
				// Get this users info
				$user = new Users();
				$userName = $user->getNameFromID((int)$posts[$i][2]);
				// Add in user link
				$user = htmlOutput("../tmpl/com/userLink.txt",
					array("id","name"),
					array($posts[$i][2],$userName),
					true);
			}
			else // Guest post
			{
				$user = htmlOutput("../tmpl/man/guest_link.txt",
					array("email","name"),
					array($posts[$i][4],$posts[$i][3]),
					true);
			}
		
			$output .= htmlOutput("../tmpl/man/uv_post.txt",
				array("pid","title","user"), // replace these
				array($posts[$i][0],$posts[$i][1],$user), // with these
				true);
		}
		
		return $output;
	}
	
	// Display post
	// Prints out a preview of the post in question
	public function DisplayPost($pid)
	{
		// Get post information
		$this->changeFields(array("title","text","img","user","name","email","tags")); // Set fields
		$postInfo = $this->dbOutput(array("`pid`","=".$pid)); // Extract info for this post
		$post = $postInfo[0]; // Take just the first dimension of array
		
		// Get existing user info
		if((int)$post[3] != 0)
		{
			$userId = (int)$post[3];
			$user = new Users(array("avatar","username"));
			$userInfo = $user->dbOutput(array("uid","=".$userId));
			$avatar = $userInfo[0][0];
			$userName = $userInfo[0][1];
		}
		// Set up guest info
		else
		{
			$avatar = "/img/default-av.png";
			$userId = $post[5];
			$userName = $post[4];
		}
		// Get image
		$img = '<a href="'.$post[2].'" target="_blank"><img src="'.$post[2].'" style="width:150px" class="postimg" /></a>';
		// Append information and a back link to the text
		$text = $post[1];
		$text .= "\n".'<br><br><br><div><strong>Note:</strong> Does not fully represent final look of post. <a href="/manage/validate.php">Click here</a> to go back.</div>';
		
		// Print out post
		htmlOutput("../tmpl/displayPost.txt",
			array("avatar","id","canon","title","editlink","userid","username","date","rating","img","text","cont","com","tags"),
			array($avatar,0,"",$post[0],"",$userId,$userName,"","",$img,$text,"",0,$post[6]));
	}
	
	// Get Approval Info
	// Will return an array of all the information you need to add post with id of $pid post to the posts table
	public function GetApprovalInfo($pid)
	{
		// Get post info
		$this->changeFields(array("title","text","img","tags","user","name","email")); // Set our fields, will be returned in this order
		// Retrieve information
		$output = $this->dbOutput(array("pid=",$pid));
		// Cut down to single dimension array and return
		return $output[0];
	}
}
?>