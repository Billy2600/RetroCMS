<?php
/* ***************************************************
// Description: Sets a cookie to change the stylesheet
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

// If user already has win95 css, change them back
if($_COOKIE['css'] == "win95" && $_GET['css'] == "win95")
	setcookie("css", "megasis", time()+60*60*24*360,"/"); // Expires in a year
else
	// Set css cookie to the option selected
	setcookie("css", $_GET['css'], time()+60*60*24*360,"/"); // Expires in a year
	
// Redirect back to previous page, if there is one
if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != NULL)
	header("Location: ".$_SERVER['HTTP_REFERER']);
// No previous page, just go to the homepage
else
	header("Location: /");
?>