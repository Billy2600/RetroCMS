<?php
// ******************************************************
// Description: Class to handle all post data, outputing
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
require_once "comments.php";
require_once "votes.php";

class posts extends database
{
	// PRIVATE
	// Set up post data for displaying
	// This function sets up the data for posts, including replacing things with HTML, retrieving other information
	// such as user info, etc. Takes one array, then returns two, contained in another array
	// If you'd like to set a cutoff for posts, set $cutoff to the maximum amount of characters you'd like to show
	// CUTOFF NOTE: If <!-- pagebreak --> (case insensitive) exists in a post, the amount you enter will be ignored. You can
	// force it by setting $forceCutOff to true
	public function setUpPostData($data,$cutoff = false,$forceCutOff = false, $noHTML = false)
	{
		// Logged in info
		$session = new sessions();
		$loggedIn = $session->checkValid();
		$user_id = $session->getUserIdFromSession();
		
		// Cut off text
		if($cutoff != false)
		{
			$cutoffText = "<!-- pagebreak -->"; // cutoff text for posts
			// Find special cutoff text
			$cutoffPos = stripos($data[2],$cutoffText);
			if($cutoffPos !== false && $forceCutOff == false)
			{
				// Cut off everything after the special cutoff text
				$data[2] = substr($data[2],0,$cutoffPos);
			}
			else
			{
				// Cutoff text not found, just snip the string
				$data[2] = substr($data[2],0,$cutoff);
			}
			// Add end paragraph tag to the end. It's hacky, I know, I'll think of something better later
			$data[2] .= "</p>";
		}
		// Just replace cutoff text
		else
			$data[2] = str_ireplace("<!-- pagebreak -->","",$data[2]);
		$data[2] = stripslashes($data[2]);
		
		// If image is not null, make it an image tag, from template, and remove thumb from array
		if($data[5] != NULL && $data[5] != "NULL" && !$noHTML)
		{
			$img = $data[5]; // temp varible for image url
			$data[5] = htmlOutput("./tmpl/postImage.txt",array("img","thumb","alt"),
				array($img,$data[6],$data[1]),true);		
		}
		// Get user info, if applicable
		$user = new users(array("avatar","username"));
		$userInfo = $user->dbOutput(array("uid","=".$data[7]));
		// Substitute information if no user was found
		if(count($userInfo) < 1)
		{
			$userInfo[0][0] = "/img/default-av.png";
			// Use user name from post info, again, if applicable
			$this->changeFields( array("name") );
			$guestname = $this->dbOutput( array("pid=",$data[0]) );
			if(trim($guestname[0][0]) != "")
				$userInfo[0][1] = $guestname[0][0];
			else
				$userInfo[0][1] = "User Not Found";
		}
		
		// merge this info into the $posts array
		$data = array_merge($data,$userInfo[0]);
		// Get number of comments
		$comments = new comments();
		$numCom = $comments->getNoOfComments($data[0]);
		$data[] = $numCom;
		// Use old thumb spot for continue text
		$replace = array("id","title","text","date","tags","img","cont","userid","avatar"
			,"username","com","rating","canon","editlink"); // Things to replace, when we're done
		// Just replace the cont text with nothing if we don't cut off the text
		if($cutoff == false)
			$data[6] = "";
		else
			$data[6] = htmlOutput("./tmpl/contLink.txt",array("id"),array($data[0]),true);
		// Turn the tags into links
		$tags = explode(",",$data[4]);
		$data[4] = ""; // Re-initialize tags field
		for($i = 0; $i < count($tags); $i++)
		{
			$tag = trim($tags[$i]);
			$tagLink = str_replace(" ","%20",$tag);
			$data[4] .= htmlOutput("./tmpl/tagLink.txt",array("tag","tag_link"),array($tag,$tagLink),true);
			// Add comma and space, if not the last one
			if($i < (count($tags) - 1))
				$data[4] .= ", ";
		}
		// Convert date and time
		//$dateTime = explode(" ",$data[3]); // Gotta explode to separate date and time into varaibles
		$data[3] = $this->convertDateTime($data[3],true);
		
		// Add thumbs up/down links
		if( $loggedIn )
			$ratingHtml = "./tmpl/forms/ratinglinks.txt";
		else
			$ratingHtml = "./tmpl/forms/ratingtext.txt";
		$voteObj = new votes();
		$data[] = htmlOutput( $ratingHtml,
			array( "pid","up","down","type" ),
			array( $data[0],$voteObj->GetNoThumbsUp( $data[0] ),$voteObj->GetNoThumbsDown( $data[0] ),"0" ),
			true );
			
		// Get canonical link
		$data[] = MakeCanonicalLink($data[1]);
		
		// Add in edit link if we own this post, or are an admin
		if($loggedIn && $user_id == $data[7])
			$data[] = htmlOutput("./tmpl/man/editlink.txt",array("pid"),array($data[0]),true);
		// Hide editlink
		else
			$data[] = "";
		
		// Return both arrays
		$output = array($replace,$data);
		return $output;
	}
	
	// CONSTRUCTOR
	// Sets the field array based on a parameter, fields are usually not required
	function __construct($fld = array("pid","title","date"))
	{
		// Call parent constructor
		parent::__construct("posts",$fld);
	}
	
	// PUBLIC
	// Display a post, for a post page
	// Postcondition: Will load the post with the ID given in the parameters, then print out it's contents to the
	// appropriate HTML template
	public function displayPost($id)
	{
		// Login info
		$session = new sessions();
		$loggedIn = $session->checkValid();
		$user_id = $session->getUserIdFromSession();
		
		// Re-set the fields
		$this->changeFields(array("pid","title","text","date","tags","img","thumb","poster_id"));
		$post = $this->dbOutput(array("pid","=$id"));
		// Check if post does not exist
		if(count($post) == 0)
		{
			htmlHeader("Error");
			displayMessage("Post with that ID does not exist","goback");
			htmlFooter();
			// Stop here
			die();
		}
		// Display header
		htmlHeader($post[0][1],false,"http://retrooftheweek.net" . $post[0][5]); // Gotta put the title in there, for the title bar, and the og image
			
		// Display post
		$postData = $this->setUpPostData($post[0]);
		htmlOutput("./tmpl/displayPost.txt",$postData[0],$postData[1]);
		
		// Display comments
		htmlOutput("./tmpl/com/comHeader.txt");
		$comments = new comments();
		$comments->displayComments($id);
		// Display add comment form
		htmlOutput("./tmpl/com/addComHeader.txt");
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
		// If user is not logged in, display captcha
		if($loggedIn)
			$captcha = "";
		else
		{
			require_once('recaptchalib.php');
			// the error code from reCAPTCHA, if any
			$error = null;
			$captcha = recaptcha_get_html(RECAPTCHA_PUBLIC_KEY, $error);
		}
		// If logged in, provide the option to be notified of replies
		if($loggedIn)
			$msg_reply = htmlOutput("tmpl/forms/msg_reply.txt",array("checked"),array(""),true);
		else
			$msg_reply = "";
		
		// Output form
		htmlOutput("./tmpl/forms/comment.txt",array("style","post_id","user_id","reply","hidename","hideclose","captcha","msg_reply"),
			array("",$id,$user,0,$hidename,"display: none",$captcha,$msg_reply));
		
		// Display footer
		htmlFooter();
	}
	
	// Display the front page post list
	// Postcondition: Will load a set number of posts, then print out it's contents to the appropriate HTML template
	public function displayPostsFrontPage($start = false)
	{
		$limit = 8; // The limit of posts per page
		// Re-set the fields
		$this->changeFields(array("pid","title","text","date","tags","img","thumb","poster_id"));
		$posts = $this->dbOutput(array("hidden","=0"), $limit, "ORDER BY date DESC",$start);
		
		// Display header
		htmlHeader();
		// Begin looping and outputting posts
		for($i = 0; $i < count($posts); $i++)
		{
			$postData = $this->setUpPostData($posts[$i],1000);
			htmlOutput("./tmpl/displayPost.txt",$postData[0],$postData[1]);
		}
		// Display older/newer post buttons, if required
		$numRows = count($this->dbOutput(false, false, "ORDER BY date DESC"));
		if($numRows - ($start + $limit) >= 1) // Check if posts older than this one
		{
			// Display older posts button, add five to start, if applicable
			if($start == false) $startOld = $limit;
			else $startOld = $start + $limit;
			htmlOutput("./tmpl/olderPosts.txt",array("url","start"),array("/start/",$startOld."/"));
		}
		// Is start set and not zero? Display newer posts button
		if($start != false && $start > 1)
		{
			$startNew = $start - $limit;
			htmlOutput("./tmpl/newerPosts.txt",array("url","start"),array("/start/",$startNew."/"));
		}
		// Display footer
		htmlFooter();
	}
	
	// Display search results
	// Postcondition: Will display results that match the query and scope given in the paramters, then print out
	// it's contents to the appropriate HTML template
	// NOTE: This function DOES NOT print out the html header and footer like other ones
	public function displaySearch($q,$type = "tags",$scope = "single",$start=false,$url = NULL)
	{
		// Keep things secure
		/*$q = addslashes($q);
		$type = addslasheS($type);
		$scope = addslashes($scope);
		$start = addslashes($start); */
		$limit = 10; // The limit of posts per page
		// Re-set the fields
		$this->changeFields(array("pid","title","text","date","tags","img","thumb","poster_id"));
		
		// Set things up before entered the query
		// Explode search queries into array
		$searchArray = explode(",",$q);
		// Change field to be searched based on type
		if($type == "tags") $searchField = "tags";
		elseif($type == "title")$searchField = "title";
		elseif($type == "post") $searchField = "text";
		else
		{
			displaymessage("Search type not entered!","goback");
			return;
		}
		
		// Do the actual search
		$like = " LIKE '%".trim($searchArray[0]," ")."%'";
		// If type is single, search with OR, if not AND
		if($scope == "single") $andOr = " OR ";
		else $andOr = " AND ";
		// Go through all search queries, except the first (already did it)
		for($i = 1; $i < count($searchArray); $i++)
		{
			$like .= $andOr."$searchField LIKE '%".trim($searchArray[$i]," ")."%'";
		}
		$posts = $this->dbOutput(array($searchField,$like), $limit," AND hidden=0 ORDER BY date DESC",$start,true);
		// If no results were returned, say so, and stop here
		if(count($posts) < 1)
		{
			displaymessage("No results found","goback");
			return;
		}
		
		// Begin looping and outputting posts
		for($i = 0; $i < count($posts); $i++)
		{
			$postData = $this->setUpPostData($posts[$i],500);
			htmlOutput("./tmpl/displayPost.txt",$postData[0],$postData[1]);
		}
		// Display older/newer post buttons, if required
		$this->changeFields(array("pid"));
		$numRows = count( $this->dbOutput(array($searchField,$like), false,false,false,true) );
		// Set up URL if it's not set already
		if(!isset($url))
		{
			$url = "?q=$q&type=$type&scope=$scope&start=";
		}
		if($numRows - ($start + $limit) >= 1) // Check if posts older than this one
		{
			// Display older posts button, add five to start, if applicable
			if($start == false) $startOld = $limit;
			else $startOld = $start + $limit;
			htmlOutput("./tmpl/olderPosts.txt",array("url","start"),array($url,$startOld));
		}
		// Is start set and not zero? Display newer posts button
		if($start != false && $start > 1)
		{
			$startNew = $start - $limit;
			htmlOutput("./tmpl/newerPosts.txt",array("url","start"),array($url,$startNew));
		}
	}
	
	// E-mail poster
	// Postcondition: Will e-mail the person who posted $postID that someone has commented on it;
	// 	Email will contain a link to the post, who commented, and what they said
	public function emailPoster($postID,$commenterID,$postText,$commenterName = NULL)
	{
		// Get the poster for this post
		parent::changeFields(array("poster_id","email_author")); // We want their ID, and whether we're emailing at all
		$postInfo= $this->dbOutput(array("pid","=$postID"));
		// Check if we need to e-mail at all
		if(intval($postInfo[0][1]) != 1)
			return;
		// Get the poster's e-mail
		$poster = new users(array("uid","username")); // Get their id and name
		$email = $poster->getEmail($postInfo[0][0]);
		$posterName = $poster->dbOutput(array("uid","=".$postInfo[0][0]));
		// Get commenter's name, if required
		if($commenterID != false)
		{
			$commenter = new users(array("username"));
			$commenterName = $commenter->dbOutput(array("uid","=$commenterID"));
			$comName = $commenterName[0][0]; // First and only result.
		}
		// Pass along commenter Name
		else
			$comName = $commenterName;
		// Send e-mail
		$text = htmlOutput("./tmpl/emailAuthor.txt",array("name","comtext","composter","post_id"),
			array($posterName[0][0],$postText,$comName,$postID),true);
		mail($email,"Someone commented on your post",$text,"From: billymcfly@yahoo.com");
	}
	
	// Get the id of who posted a post
	// Postcondition: Will return the uid of the person who posted the post with id of $pid
	public function getPosterID($pid)
	{
		parent::changeFields(array("poster_id"));
		$poster = $this->dbOutput(array("pid","=$pid"));
		return $poster[0][0];
	}
	
	// Update a post
	// Postcondition: Will update a post of $id with the data given in the array $data
	public function updatePost($id,$data)
	{
		
	}
	
	// Function to check if a post ID specified exists
	// Postcondition: Will return true if post exists, otherwise returns false
	public function checkPostExistsID($postID)
	{
		$this->changeFields(array("pid"));
		$postCheck = $this->dbOutput(array("pid","=$postID"));
		// Check if post does not exist
		if(count($postCheck) == 0)
			return false;
		else
			return true;
	}
	
	// Check if post is hidden
	// Postcondition: will return true if post is hidden, otherwise returns false
	public function CheckHidden($postID)
	{
		$this->changeFields(array("hidden"));
		$postCheck = $this->dbOutput(array("pid","=$postID"));
		// Check if post is hidden
		if((int)$postCheck[0][0] == 0)
			return false;
		else
			return true;
	}
	
	// Get latest posts from a user
	// Postcondition: Will return a 2 dimensional array of the users latest post with the fields pid and title,
	//		for the specified user ID, and with the limit of $limit
	public function getUsersLatestPosts($uid,$limit=5)
	{
		$this->changeFields(array("pid","title"));
		$latestPosts = $this->dbOutput(array("poster_id","=".$uid),$limit,"ORDER BY date DESC");
		return $latestPosts;
	}
	
	// Delete a post
	// Postcondition: Will delete the post with ID of $pid
	// WARNING: This will not display a confirmation or check user permissions
	public function deletePost($pid)
	{
		// Delete post
		$this->deleteData("pid",$pid);
		// Now delete comments attched to this post
		$comments = new comments();
		$comments->deleteCommentsFromPost($pid);
		// Now delete the image/thumb if they exist
		$this->changeFields(array("img","thumb"));
		$imgThumb = $this->dbOutput(array("pid","=$pid"));
		// Delete images
		foreach($imgThumb as $img)
		{
			if(file_exists($_SERVER['DOCUMENT_ROOT'].$img))
				unlink($_SERVER['DOCUMENT_ROOT'].$img);
		}
	}
	
	// Update view count for a post
	// Postcondition: View count for the post with $id will increment by 1, if current user is not the author
	public function addView($id)
	{
		// Logged in info
		$session = new sessions();
		$loggedIn = $session->checkValid();
		$user_id = $session->getUserIdFromSession();
		
		// Check if current user is the author
		if($loggedIn && $this->getPosterID($id) != $user_id)
		{
			// Retrive current view count
			parent::changeFields(array("views"));
			$output = $this->dbOutput(array("pid","=".$id));
			// Convert to int and extract from array
			$numViews = (int)$output[0][0];
			//Increment
			$numViews++;
			// Put back into database
			$this->dbUpdate(array($numViews),"pid",$id);
		}
	}
	
	// Print out posts with the most views
	// Postcondition: Will return an HTML list of the most viewed posts, with a
	//	limit of $limit (default 5)
	public function getMostViewed($limit=5)
	{
		global $tmplPath;
		parent::changeFields(array("pid","title"));
		$output = $this->dbOutput(array("hidden","=0"),$limit,"ORDER BY views DESC");
		$outputHtml = NULL; // Initializse output HTML variable
		for($i=0; $i < count($output); $i++)
		{
			$outputHtml .= htmlOutput($tmplPath."/sidebarListItem.txt",array("pid","title"),$output[$i],true);
		}
		return $outputHtml;
	}
	
	// Print out random posts for sidebar
	public function getRandom($limit=5)
	{
		global $tmplPath;
		parent::changeFields(array("pid","title"));
		// Get the total number of posts
		$lastPost = $this->dbOutput( NULL,NULL,"ORDER BY pid DESC" );
		$lastPostID = $lastPost[0][0];
		$outputHtml = NULL; // Initializse output HTML variable
		for($i=0; $i < $limit; $i++)
		{
			$exists = false;
			// Make sure a post exists with this id
			while(!$exists)
			{
				$randomID = rand(1,$lastPostID);
				$exists = $this->checkPostExistsID($randomID) && !$this->CheckHidden($randomID);
			} 
			parent::changeFields(array("pid","title"));
			$output = $this->dbOutput( array( "pid","=".$randomID." && hidden=0" ) );
			$outputHtml .= htmlOutput($tmplPath."/sidebarListItem.txt",array("pid","title"),$output[0],true);
		}
		return $outputHtml;
	}
	
	// GetLatestPosts
	// Returns $limit number of the latest posts, for use in the sidebar
	public function GetLatestPosts($limit=5)
	{
		global $tmplPath;
		parent::changeFields(array("pid","title"));
		// Get the total number of posts
		$posts = $this->dbOutput( array("hidden=","0"),$limit,"ORDER BY pid DESC" );
		$outputHtml = NULL; // Initializse output HTML variable
		for($i=0; $i < count($posts); $i++)
		{
			$outputHtml .= htmlOutput($tmplPath."/sidebarListItem.txt",array("pid","title"),$posts[$i],true);
		}
		return $outputHtml;
	}
	
	// Increment/decrement rating
	// Set $inc to true to increment, false to decrement
	public function IncDecRating($post_id,$inc=true)
	{
		parent::changeFields( array( "rating" ) );
		// Get old value
		$oldValArray = $this->dbOutput( array( "pid=",$post_id ) );
		$oldVal = (int)$oldValArray[0][0];
		// Add/subtract
		if($inc)
		{
			$oldVal++;
		}
		else
		{
			$oldVal--;
		}
		// Put it back in
		$this->dbUpdate( array( $oldVal ),"pid",$post_id );
	}

	public function apiDisplayPost($id)
	{
		// Login info
		$session = new sessions();
		$loggedIn = $session->checkValid();
		$user_id = $session->getUserIdFromSession();
		
		// Re-set the fields
		$this->changeFields(array("pid","title","text","date","tags","img","thumb","poster_id"));
		$post = $this->dbOutput(array("pid","=$id"));
		// Check if post does not exist
		if(count($post) == 0)
		{
			apiPrintError("Post with that ID does not exist");
		}

		// Get post data
		$postData = $this->setUpPostData($post[0],false,false,true);

		// Build array
		$arr = array(
			"id" => $postData[1][0],
			"title" => $postData[1][1],
			"text" => strip_tags($postData[1][2]),
			"date" => $postData[1][3],
			"tags" => strip_tags($postData[1][4]),
			"img" => $postData[1][5],
			"userid" => $postData[1][7],
			"avatar" => $postData[1][8],
			"username" => $postData[1][9]
		);
		// Output encoded
		echo json_encode($arr);
	}
}