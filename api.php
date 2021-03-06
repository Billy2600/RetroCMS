<?php
// ******************************************************
// Description: This file outputs api requests as json data
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
// *******************************************************
require_once "config.php";
require_once "inc/func.php";
require_once "inc/posts.php";

// What kind of request do we want?
if(!isset($_GET["type"]))
{
	apiPrintError("No type specified");
}

switch($_GET["type"])
{
// Post
case "post":
	if( !isset($_GET["post_id"]) )
		apiPrintError("No Post ID specified");

	$postObj = new posts();
	$postObj->apiDisplayPost((int)$_GET["post_id"]);
	break;
}
?>