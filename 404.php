<?php
// **************************************************
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
// **************************************************
// Required files
require_once "config.php";
require_once $incPath."/func.php";

// Connect to mysql
mysql_connect($DATABASE_HOST,$DATABASE_USER,$DATABASE_PASS);
@mysql_select_db($DATABASE_NAME) or die("Unable to select database");

htmlHeader(" - 404 Document Not Found");

// Pick a random image
$images = array(
	"/img/404/2600pac.gif",
	"/img/404/2600cake.jpg",
);
$randomImg = $images[rand(0,(count($images) -1))];
?>
		<div class="post_title">
			404 Not Found
		</div>
		<div class="post_body" style="text-align: center;">
			<a href="/">Home Page</a> - <a href="javascript: history.go(-1)">Go Back</a><?php
			if(isset($_COOKIE['userid']))
				echo ' - <a href="/ucp/addmsg/Billy/">PM Administrator</a>';
			?>
			
			<h1><img src="<?php echo $randomImg; ?>" alt="404 Not Found!" /></h1>
		</div>
<?php
htmlFooter();
mysql_close();
?>