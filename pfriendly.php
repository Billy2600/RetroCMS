<?php
/* ***************************************************
// Description: This file displays the post page, but
// in printer friendly form
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
require_once "inc/posts.php";

// Display front page posts
$sqlOutput = new posts();
// Make sure post id is set, if not, throw an error
if(isset($_GET['id']))
{
	$id = $_GET['id'];
	// Re-set the fields
	$sqlOutput->changeFields(array("pid","title","text","date","tags","img","thumb","poster_id"));
	$post = $sqlOutput->dbOutput(array("pid","=$id"));
	// Check if post does not exist
	if(count($post) == 0)
	{
		htmlHeader("Error");
		displayMessage("Post with that ID does not exist","goback");
		htmlFooter();
		// Stop here
		die();
	}
	
	// Display in printer friendly format
	$postData = $sqlOutput->setUpPostData($post[0]);
	htmlOutput("./tmpl/pfriendly.txt",$postData[0],$postData[1]);
}
else
{
	htmlHeader("Error");
	echo displayMessage("No post ID specified!","goback");
	htmlFooter();
}
?>