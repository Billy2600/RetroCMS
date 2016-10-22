<?php
/* ***************************************************
// Description: This file handles displaying searches
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

// Has a search been issued?
if(isset($_GET['q']))
{
	// Display header
	htmlHeader("Search in Progress");
	// display search form with data entered, if hideform isn't set
	if(!isset($_GET['hideform']))
	{
		$replace = array("q","tags","title","post","single","all");
		// Select which one... is selected
		$post = "";
		$title = "";
		$tags = "";
		$type_all = "";

		if($_GET['type'] == "tags")
		{
			$tags = "selected=\"selected\"";
		}
		elseif($_GET['type'] == "title")
		{
			$title = "selected=\"selected\"";
		}
		elseif($_GET['type'] == "post")
		{
			$post = "selected=\"selected\"";
		}
		elseif($_GET['type'] == "all")
		{
			$type_all = "selected=\"selected\"";
		}
		else
		{
			// Display error
			displayMessage("Search type not entered!","goback");
			die();
		}
		// Same with single vs all
		if($_GET['scope'] == "single")
		{
			$single = "selected=\"selected\"";
			$all = "";
		}
		elseif($_GET['scope'] == "all")
		{
			$all = "selected=\"selected\"";
			$single = "";
		}
		$data = array($_GET['q'],$tags,$title,$post,$single,$all);
		htmlOutput("tmpl/searchForm.txt",$replace,$data);
	}
	// Display posts
	$searchPosts = new posts();
	// account for the user not entering everything
	if(isset($_GET['type'])) $type = $_GET['type'];
	else $type = NULL;
	if(isset($_GET['scope'])) $scope = $_GET['scope'];
	else $scope = NULL;
	if(isset($_GET['start'])) $start = $_GET['start'];
	else $start = NULL;
	$searchPosts->displaySearch($_GET['q'],$type,$scope,$start);
	// Display footer
	htmlFooter();
}
// Search just the tags
elseif(isset($_GET['tags']))
{
	$searchPosts = new posts();
	// Header
	htmlHeader("Search for tags:".$_GET['tags'], true);
	// $start not required
	if(isset($_GET['start'])) $start = $_GET['start'];
	else $start = NULL;
	// Display tag search
	$searchPosts->displaySearch($_GET['tags'],"tags","start",$start,"/tags/".$_GET['tags']."/start/");
	// Footer
	htmlFooter(true);
}
// No search entered, display search form
else
{
	htmlHeader("Search"); // display header
	// Display search form with default data
	$replace = array("q","tags","title","post","single","all");	
	$data = array("(Separate search terms with commas. e.g.: this is term 1, term 2)","selected=\"selected\"",
		"","","selected=\"selected\"","");
	htmlOutput("tmpl/searchForm.txt",$replace,$data);
	htmlFooter(); // display footer
}
?>