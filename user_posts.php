<?php
/* ***************************************************
// Description: This file displays all of posts made
//	by a user
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
require_once $incPath."/posts.php";

// Connect to mysql
mysql_connect($DATABASE_HOST,$DATABASE_USER,$DATABASE_PASS);
@mysql_select_db($DATABASE_NAME) or die("Unable to select database");

if( isset( $_GET["id"] ) ) 
{
	// Get user's info
	$userObj = new users( array( "username" ) );
	$userInfo = $userObj->dbOutput( array( "uid=",$_GET['id'] ) );
	// Get post info
	$postObj = new posts( array( "pid","title","date" ) );
	$postInfo = $postObj->dbOutput( array( "hidden=0 AND poster_id=",$_GET['id'] ),false,"ORDER BY `date` DESC" );

	htmlHeader( "Posts by " . $userInfo[0][0]." - ",true );
	// Loop through and output posts
	for( $i=0; $i < count( $postInfo ); $i++ )
	{
		// Make every even <tr> different colored
		$postOutput .= '<tr';
		if( ($i %2)==0 )
		{
			$postOutput .= ' class="even"';
		}
		$postOutput .= ">\n";
		$postOutput .= "<td>" . ($i+1) . "</td>\n";
		$postOutput .= '<td><a href="/p/'.$postInfo[$i][0].'/">' . $postInfo[$i][1] . "</a></td>\n";
		$postOutput .= '<td>' . $postObj->convertDateTime( $postInfo[$i][2],true ) . "</td>\n";
		$postOutput .= "</tr>\n";
	}
	htmlOutput( "tmpl/user_posts.txt",array( "username","posts" ), array( $userInfo[0][0],$postOutput ) );
	
	htmlFooter( true );
}
else
{
	htmlHeader( "Error - " );
	displayMessage( "No user specified", "goback" );
	htmlFooter();
}

// Close mysql
mysql_close();
?>