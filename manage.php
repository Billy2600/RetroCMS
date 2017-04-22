<?php
/* ***************************************************
// Description: The main file, of the manage section,
// everything in the management section stems from here
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
require_once "inc/manage.php";

$manage = new manage();

$section = "";
if(isset($_GET["section"])) $section = $_GET["section"];

switch($section)
{
	case "bans":
		$manage->Bans();
		break;
	case "users":
		$manage->Users();
		break;
	case "imguploads":
		$manage->ImageUploads();
		break;
	case "add":
		$manage->AddPost();
		break;
	case "edit":
		$manage->EditPost();
		break;
	case "delete":
		$manage->DeletePost();
		break;
	case "delete_com":
		$manage->DeleteComment();
		break;
	case "tinyurl":
		$manage->Tinyurl();
		break;
	default:
		$manage->ShowIndex();
		break;
}
?>