<?php
/* ***************************************************
// Description: Allows an editor to create a TinyURL
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
require_once "../config.php";
require_once $incPath."/func.php";
require_once $incPath."/users.php";
require_once "manFunc.php";

// Check for logged in and admin/editor status
checkLoginAdminEditor();

// Prompt for input
if(!isset($_POST['url']))
{
	htmlHeader(" - Generate a Tiny URL");
	// Display admin options if we are one
	$adminOptions = getAdminOptions();
	// Get form
	$form = htmlOutput("../tmpl/man/tinyurl.txt",array(),array(),true);
	// Display
	htmlOutput("../tmpl/man/main.txt",array("title","form","admin"),array("Create a TinyURL",$form,$adminOptions));
	htmlFooter();
}
// Create and return tiny URL
else if(isset($_POST['url']))
{
	htmlHeader(" - Tiny URL Generated");
	$tinyUrl = GetTinyURL($_POST['url']);
	// Display admin options if we are one
	$adminOptions = getAdminOptions();
	// Display result
	$form = htmlOutput("../tmpl/man/tinyurl_result.txt",array("tinyurl"),array($tinyUrl),true);
	// Display
	htmlOutput("../tmpl/man/main.txt",array("title","form","admin"),array("TinyURL Created",$form,$adminOptions));
	htmlFooter();
}
// Error
else
{
	htmlHeader(" - Error");
	displayMessage("Error","goback");
	htmlFooter();
}
?>