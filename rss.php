<?php
/* ***************************************************
// Description: Display RSS Feed of posts
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
require_once $incPath."/posts.php";

// Get posts
$sqlOutput = new posts(array("pid","title","text","tags","date"));
$posts = $sqlOutput->dbOutput(array("hidden","=0"), 10, "ORDER BY date DESC");

// Display RSS header
htmlOutput("tmpl/rss_header.txt");

// Begin outputting posts
for($i = 0; $i < count($posts); $i++)
{
	// Set up data for outputting
	$replace = array("pid","title","desc","categories","datetime"); // The text in the template we will replace
																	// These will mostly match up with the fields
	// Explode tags
	$tags = explode(",",$posts[$i][3]);
	$categories = ""; // Initialize categories string
	// Give each one its proper tag
	for($o = 0; $o < count($tags); $o++)
	{
		$categories .= htmlOutput("tmpl/rss_category.txt",array("tag"),array($tags[$o]));
	}
	// Plug back into data
	$posts[$i][3] = $categories;
	
	// Strip and cut down post text
	$posts[$i][2] = substr($posts[$i][2],0,500);
	$posts[$i][2] = str_replace(array("\n","\r")," ",$posts[$i][2]);
	$posts[$i][2] = strip_tags($posts[$i][2]);
	
	// Finally, output this post
	htmlOutput("tmpl/rss_item.txt",$replace,$posts[$i]);
}

// Display RSS footer
htmlOutput("tmpl/rss_footer.txt");
?>