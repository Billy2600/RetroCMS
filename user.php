<?php
/* ***************************************************
// Description: This file displays the user page
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
require_once "inc/users.php";

// Display front page posts
$sqlOutput = new users();
// Make sure post id is set, if not, throw an error
if(isset($_GET['id']))
	$sqlOutput->displayUser($_GET['id']);
else
	echo displayMessage("No user ID specified!");
?>