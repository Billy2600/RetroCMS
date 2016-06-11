<?php
/* ***************************************************
// Description: This file logs users out
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
require_once $incPath."/sessions.php";

// Connect to mysql
mysql_connect($DATABASE_HOST,$DATABASE_USER,$DATABASE_PASS);
@mysql_select_db($DATABASE_NAME) or die("Unable to select database");

$session = new sessions();

// Check if user is logged in
if($session->checkValid())
{
	// Log them out
	$session->remove();
	// Display success message
	htmlHeader("Now Logged Out - ");
	displayMessage("You are now logged out. Redirecting, or <a href=\"/\">click here</a>","redirect","/");
	htmlFooter();
}
// Not logged in, just redirect to the main page
else
{
	header("Location: /");
}

mysql_close();
?>