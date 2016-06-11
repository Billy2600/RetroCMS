<?php
/* ***************************************************
// Description: Display latest posts from the ABXD forum
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
require_once "func.php";
require_once "handleData.php";

$LIMIT = 5; // The number of forum posts this will show

// connect to forum database
mysql_close();
mysql_connect("","","",true);
@mysql_select_db("") or die("Unable to select database");

// Get latest forum posts
$forumPostObj = new handleData("posts",array("id","thread","user"),true); // Post information
$forumThreads = new handleData("threads",array("title"),true); // Thread information
$forumUsers = new handleData("users",array("id","name"),true); // User information
$forumPosts = ""; // Init output string

// Get latest posts
$latest = $forumPostObj->dbOutput(array("1"," ORDER BY `id` DESC"),$LIMIT,false,false,false,false);
// show last n in descending order
for($i = 0; $i < $LIMIT; $i++)
{
	// Get thread title
	$threadInfo = $forumThreads->dbOutput(array("id=",$latest[$i][1]));
	// Get user ID and name
	$userInfo = $forumUsers->dbOutput(array("id=",$latest[$i][2]));
	
	// Add post link to output var
	$forumPosts .= htmlOutput($tmplPath."/forum_post_link.txt",
		array("pid","title","uid","user"),
		array($latest[$i][0],$threadInfo[0][0],$userInfo[0][0],$userInfo[0][1]),
		true);
}
	
// Reconnect to previous database
mysql_close();
mysql_connect(DB_HOST,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die("Unable to select database");
?>