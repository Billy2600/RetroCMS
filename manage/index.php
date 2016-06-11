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
require_once "../config.php";
require_once $incPath."/func.php"; // Global Functions
require_once "manFunc.php"; // Manage section functions
require_once $incPath."/users.php";

// Connect to mysql
mysql_connect($DATABASE_HOST,$DATABASE_USER,$DATABASE_PASS);
@mysql_select_db($DATABASE_NAME) or die("Unable to select database");

// Check for logged in and admin/editor status
checkLoginAdminEditor();

// Display manage main page
htmlHeader("Management Panel - ");
// Display admin options if we are one
$adminOptions = getAdminOptions();
// Get main page text
$mainText = htmlOutput("../tmpl/man/main_text.txt",NULL,NULL,true);
htmlOutput("../tmpl/man/main.txt",array("title","form","admin"),array("Manage",$mainText,$adminOptions));
htmlFooter();

// Close mysql
mysql_close();
?>