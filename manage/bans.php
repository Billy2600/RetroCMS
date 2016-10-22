<?php
/* ***************************************************
// Description: Allows an admin to add or remove IP bans
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
require_once "../inc/func.php";
require_once "manFunc.php"; // Manage section functions

// Check for logged in and admin/editor status
checkLoginAdminEditor();
// This is an admin only page, so make sure we're an admin, not an editor
$session = new sessions();
$userType = $curUser->getUserType($session->getUserIdFromSession());
// Editors get out
if($userType == 2)
	header("Location: /manage/");

// Add a ban, display success message
if(isset($_GET['addban']))
{
	// Check for empty IP input
	if(empty($_GET['ip']))
	{
		htmlHeader("Error");
		displayMessage("No IP address specified!","goback");
		htmlFooter();
		die();
	}
	addBan($_GET['ip']);
	// Display success message
	htmlHeader("Ban Added");
	$txt = 'Ban successfully added, now redirecting back to ban page or click <a href="/manage/bans">here</a>.';
	displayMessage($txt,"redirect","/manage/bans/");
	htmlFooter();
}
// Remove a ban
elseif(isset($_POST['delban']))
{
	// Get contents of .htaccess file
	$contents = file_get_contents("../.htaccess");
	// Replace ban with nothing
	$contents = str_replace("deny from ".$_POST['ip']."\n","",$contents);
	// Write to file
	$fp = fopen('../.htaccess', 'w');
	fwrite($fp, $contents);
	// Display success message
	htmlHeader("Ban Removed");
	$txt = 'Ban successfully removed, now redirecting back to ban page or click <a href="/manage/bans">here</a>.';
	displayMessage($txt,"redirect","/manage/bans/");
	htmlFooter();
}
// Display current bans
else
{
	// Get contents of .htaccess file
	$contents = file_get_contents("../.htaccess");
	// Filter out anything before the banlist
	$start = strpos($contents,"order allow,deny");
	$contents = substr($contents,$start);
	// Remove other htaccess stuff
	$contents = str_replace("order allow,deny","",$contents);
	$contents = str_replace("allow from all","",$contents);
	$contents = str_replace("deny from ","",$contents);
	// Put what's left over into an array
	$banned_ips = explode("\n",$contents);
	
	htmlHeader("Bans");
	// Display admin options if we are one
	$adminOptions = getAdminOptions();
	$form = ""; // Initialize form string
	// Generate ban list, ignore first and last entry in array
	foreach($banned_ips as $ip)
	{
		// Remove whitespace, linebreaks, etc.
		$ip = trim($ip);
		if(!empty($ip)) // Don't display empty ones
			$form .= htmlOutput("../tmpl/man/ban.txt",array("ip"),array($ip),true);
	}
	// Also, the 'add ban' form
	$form .= htmlOutput("../tmpl/man/addBan.txt",NULL,NULL,true);
	// Display it all
	htmlOutput("../tmpl/man/main.txt",array("title","form","admin"),array("Current Bans",$form,$adminOptions));
	htmlFooter();
}
?>