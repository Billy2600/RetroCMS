<?php
/* ***************************************************
// Description: This file logs users in
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
require_once $incPath."/users.php";
require_once $incPath."/sessions.php";

$session = new sessions();

// Check if user is already logged in, if so redirect them to main page
if($session->checkValid())
{
	header("Location: /");
}
// Not logged in
else
{
	// Have they used the form yet or not?
	if(isset($_POST['submit']))
	{
		// Create session
		$session->create($_POST['name'],$_POST['pass']);
		
		// Display success message
		htmlHeader("Now Logged In");
		displayMessage("You are now logged in. Redirecting, or <a href=\"/\">click here</a>","redirect","/");
		htmlFooter();
	}
	// Form not used yet, display form
	else
	{
		htmlHeader("Login");
		htmlOutput("./tmpl/forms/login.txt");
		htmlFooter();
	}
}
?>