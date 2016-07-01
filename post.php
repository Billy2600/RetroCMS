<?php
/* ***************************************************
// Description: This file displays the post page
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

// Display front page posts
$postObj = new posts();
// Make sure post id is set, if not, throw an error
if(isset($_GET['id']))
{
	// Increment post views
	$postObj->addView($_GET['id']);
	// Display the post
	$postObj->displayPost($_GET['id']);
}
else
{
	htmlHeader("Error");
	echo displayMessage("No post ID specified!","goback");
	htmlFooter();
}
?>