<?php
/* **************************************************************
// Description: Allows an editor to post into the social networks
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
// **************************************************************/

// Required files
require_once "../config.php";
require_once $incPath."/func.php";
require_once $incPath."/posts.php";
require_once "manFunc.php";

// Check for logged in and admin/editor status
checkLoginAdminEditor();
$session = new sessions();
$user_id = $session->getUserIdFromSession();

$post = new posts();
// Make sure all values set
if(!isset($_POST["title"]) || !isset($_POST["text"]) || !isset($_POST["tags"]))
{
	htmlHeader("Error");
	displayMessage("Not all data was entered","goback");
	htmlFooter();
	// Stop here
	die();
}
// Pass it on
$post->displayPreview($_POST["title"],$_POST["text"],$_POST["tags"]);
?>