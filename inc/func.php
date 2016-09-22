<?php
// ******************************************************
// Description: Contains all the miscellaneous functions used
// throughout the CMS
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

// Required files
require_once "messages.php";
require_once "Mobile_Detect.php";
require_once "posts.php";
require_once "comments.php";
require_once "users.php";
require_once "sessions.php";

// Output data to HTML
// Postcondition: The data in the $data array is printed (not returned!) using the $template template file by
// replacing the names from the $replace array
// If $return is set to true, it'll return instead of printing
// If you don't wish to replace anything and just want to print/return an HTML file, set $replace *and* $data to
// empty arrays, it will error out if one is set and the other isn't
// Note: Do not include [] brackets in what you want replaced while calling this function
function htmlOutput($templateFile, $replace = false, $data = false,$return = false)
{
	// Include the template file as a string
	$template = file_get_contents($templateFile);
	// If either array are empty or false, Just print the template
	if($data == false && $replace == false)
	{
		if($return == true) return $template;
		else
		{
			echo $template;
			return;
		}
	}
	
	// Replace everything from data, with the labels given
	for($i = 0; $i < count($data); $i++)
	{
		// First time we've gone through this grab template and replace into output
		if($i == 0) $output = str_replace("[".$replace[$i]."]",$data[$i],$template);
		// Second through last, continue replacing output
		else $output = str_replace("[".$replace[$i]."]",$data[$i],$output);
	}
	// Output
	if($return == true) return $output;
	else echo $output;
}

// Display a message
// You can choose from three types or none at all:
// 	Redirect to another page (specified by $url)
//	Offer a 'go back' button
//	Make the user confirm, includes a 'no' button that acts the same as 'go back'
function displayMessage($message,$type = "none",$url = "http://google.com",$time = 5)
{
	global $tmplPath;
	htmlOutput($tmplPath."/message.txt",array("message"),array($message));
	// Redirect user
	if($type == "redirect")
	{
		htmlOutput($tmplPath."/msgRedirect.txt",array("time","url"),array($time,$url));
	}
	// Offer go back button
	elseif($type == "goback")
	{
		htmlOutput($tmplPath."/msgGoBack.txt");
	}
		// Offer a yes/no option
	elseif($type == "confirm")
	{
		htmlOutput($tmplPath."/msgConfirm.txt",array("url"),array($url));
	}
}

// Display HTML header
// Postcondition: Prints out the top of the page, from the template file
//	You can also force the sidebar (need to be set in this and footer function)
function htmlHeader($title = "", $forceSideBar = false, $og_img = "http://retrooftheweek.net/img/logo.png")
{
	require_once "users.php";
	global $tmplPath;
	// Create objects
	$session = new sessions();
	$currentUser = new users();
	// Check for login and get user id
	$loggedIn = $session->checkValid();
	$user_id = $session->getUserIdFromSession();
	
	// Get user bar info
	// Logged in
	if($loggedIn)
	{
		// Get login bar information
		$data = $currentUser->getLoginBarForUser($user_id );
		// Get Manage link if applicable
		if($currentUser->getUserType($user_id ) > 0)
		{
			$data[2] = htmlOutput($tmplPath."/loginBarManageLink.txt",array(),array(),true);
		}
		else
			$data[2] = " ";
		$loginbar = htmlOutput($tmplPath."/loginBarUser.txt",array("id","username","manage"),$data,true);
	}
	// Guest
	else
	{
		$loginbar = htmlOutput($tmplPath."/loginBarGuest.txt",false,false,true);
	}
	// Detect mobile device
	$detect = new Mobile_Detect();
	// Use mobile css
	if($detect->isMobile() || $detect->isTablet()) $css = "/css/mobile.css";
	// Get CSS
	elseif(isset($_COOKIE['css'])) $css = "/css/".$_COOKIE['css'].".css";
	else $css = "/css/megasis.css";
	// Get categories
	$catString = ""; // Initialize string used for final output
	$categories = new database("categories",array("name"));
	$cats = $categories->dbOutput();
	for($i = 0; $i < count($cats); $i++)
	{
		$catString .= htmlOutput($tmplPath."/catLink.txt",array("name"),array($cats[$i][0]),true);
	}
	// Check title for "Retro of the week - "
	if(substr(strtolower($title),0,20) == "retro of the week - ")
		$title = substr($title,-20);
	if(substr(strtolower($title),0,18) == "retro of the week:")
		$title = substr($title,-18);

	// Add seperator to title, if applicable
	if($title != "")
	{
		$title .= " - ";
	}

	//Give main section full width if we're not on the index
	if( $_SERVER["PHP_SELF"] == "/index.php" || $_SERVER["PHP_SELF"] == "/post.php" ||
		$_SERVER["PHP_SELF"] == "/search.php" || $forceSideBar == true )
	{
		$fullwidth = NULL;
	}
	else
	{
		$fullwidth = 'class="nonindex_main"';
	}
		
	// Output header
	htmlOutput($tmplPath."/header.txt",array("title","desc","og_img","loginbar","css","cats","fullwidth"),array($title,getDescription(),$og_img,$loginbar,$css,$catString,$fullwidth));
	// Display unread messages box if user indeed has unread message
	if($loggedIn && !isset($_GET['do'])) // Don't do any of this if we're not logged in or in the messenger
	{
		$unread = new messages();
		if($unread->checkUnreadMsg($user_id) == true)
			htmlOutput($tmplPath."/message.txt",array("message"),array('You have unread message(s). <a href="/ucp/msg/">Go to inbox</a>'));
	}
	// Display birthday message
	if($loggedIn)
	{
		$currentUser->changeFields(array("username","birthday"));
		$uinfo = $currentUser->dbOutput( array("uid=",$user_id) );
	}
	if($loggedIn && substr($uinfo[0][1],-5) == date('m-d') && !isset($_COOKIE['read_bday']))
	{
		htmlOutput($tmplPath."/message.txt",array("message"),array('Happy birthday '.$uinfo[0][0].'! Have a good one. <a href="/misc/readbday.php" style="float: right; padding-right: 50px; font-size: 70%">Remove</a>'));
	}
}

// Display HTML footer
// Postcondition: Prints out the bottom of the page, from the template file
//	You can also force the sidebar to display
function htmlFooter( $forceSideBar = false )
{
	global $tmplPath;
	$posts = new posts();
	$comments = new comments();
	$userObj = new users();
	
	// Display sidebar if this is the index page
	if( $_SERVER["PHP_SELF"] == "/index.php" || $_SERVER["PHP_SELF"] == "/post.php" ||
		$_SERVER["PHP_SELF"] == "/search.php" || $forceSideBar == true )
	{
		// Get trending post list
		//$trending = $posts->getFeatured();
		//$trending = $posts->getRandom();
		// Get latest posts, if not on front page
		if($_SERVER["PHP_SELF"] != "/index.php")
		{
			// get latest posts
			$latestPosts = $posts->GetLatestPosts();
			// Put into box
			$latestPostBox = htmlOutput( $tmplPath."/latest_box.txt",array("latestPosts"), array($latestPosts), true);
		}
		else
			$latestPostBox = "";
		$replace = array( "latest"); // Replace this
		$data = array($latestPostBox); // with this
		
		// Get forum posts, if not local and not ROW
		if(!defined('ROTW'))
		{
			if($_SERVER['SERVER_NAME'] == "localhost" || $_SERVER['SERVER_NAME'] == "127.0.0.1")
				$forumPosts = "Cannot get forum posts while testing locally.";
			else
				include "forumposts.php"; // Gives us $forumPosts
			// Add to arrays
			$replace[] = "forumPosts";
			$data[] = $forumPosts;
		}
		// Get random posts if ROTW
		else
		{
			// Add to arrays
			$replace[] = "trending";
			$data[] = $posts->getRandom();
		}
		
		// Get latest comments
		$replace[] = "latest_com";
		$data[] = $comments->getLatestComments();
		
		// Add sidebar
		$sidebar = htmlOutput( $tmplPath."/sidebar.txt",$replace,$data,true );
	}
	else
		$sidebar = "";
		
	// Get popular post list
	if(defined('rotw')) 
		$num = 5;
	else
		$num = 10;
	$popular = $posts->getMostViewed(5);
	// Get contributors	
	$contributors = $userObj->getContributors();
	
	htmlOutput($tmplPath."/footer.txt",array("sidebar","popular","contributors"),array($sidebar,$popular,$contributors));
}

// Replace special characters and BBCode
// Postcondition: String will be returned with html characters replaced, newlines turned into <br />, and any BBCode replaced with HTML
function replaceSpecial($string)
{
	// First, replace special html chars
	$string = htmlspecialchars($string);
	// Now turn newlines into <br />'s
	$string = nl2br($string);
	// Replace URLs
	$pattern = "@\b(https?://)?(([0-9a-zA-Z_!~*'().&=+$%-]+:)?[0-9a-zA-Z_!~*'().&=+$%-]+\@)?(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-zA-Z_!~*'()-]+\.)*([0-9a-zA-Z][0-9a-zA-Z-]{0,61})?[0-9a-zA-Z]\.[a-zA-Z]{2,6})(:[0-9]{1,4})?((/[0-9a-zA-Z_!~*'().;?:\@&=+$,%#-]+)*/?)@";
	$string = preg_replace($pattern, '<a href="\0">\0</a>', $string);
	// &mdash; exception
	$string = str_replace("&amp;mdash;","&mdash;",$string);
	// 4chan style greentext
	$string = preg_replace('/(&gt;.*)/', '<div style="color:#792">\1</div>', $string);
	// BBCode time
	$string = preg_replace('#\[b\](.+)\[\/b\]#iUs', '<strong>$1</strong>', $string);
	$string = preg_replace('#\[u\](.+)\[\/u\]#iUs', '<span style="text-decoration: underline">$1</span>', $string);
	$string = preg_replace('#\[s\](.+)\[\/s\]#iUs', '<span style="text-decoration: line-through">$1</span>', $string);
	$string = preg_replace('#\[i\](.+)\[\/i\]#iUs', '<em>$1</em>', $string);
	$string = preg_replace('#\[quote\](.+)\[\/quote\]#iUs', '<blockquote>$1</blockquote>', $string);
	
	return $string;
}

// Add preceeding zero to number
// Postcondition: Will return the number you give it with a preceeding zero, if it needs one
function addZero($num,$before = true)
{
	if(strlen($num) < 2)
	{
		// Add before
		if($before == true) $num = "0".$num;
		else $num = $num."0";
	}
	return $num;		
}

// Check a users login
// Postcondition: Will delete a users cookies if password is not set, or password doesn't match one in the database
/* function checkLogin()
{
	// Don't do anything if userid cookie is not set
	if(!isset($_COOKIE['userid'])) return;
	// Check if userid is not numeric
	if(!is_numeric($_COOKIE['userid']))
	{
		// Userid not int, delete cookie
		setcookie("userid", "0", time()-3600);
		setcookie("upass", "0", time()-3600);
		// Redirect back to current page
		header("Location: ".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
		return;
	}
	// Password cookie not set
	if(!isset($_COOKIE['upass']))
	{
		// Delete cookie, if it's set
		if(isset($_COOKIE['userid'])) setcookie("userid", "0", time()-3600);
		// Redirect back to current page
		header("Location: ".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
		return;
	}
	require_once "users.php";
	$user = new users(array("password"));
	// Get password from database
	$id = $_COOKIE['userid'];
	$db_pass = $user->dbOutput(array("uid","=$id"));
	// Check if passwords match, if not, delete cookies
	if($_COOKIE['upass'] != $db_pass[0][0])
	{
		setcookie("userid", "0", time()-3600);
		setcookie("upass", "0", time()-3600);
		// Redirect back to current page
		header("Location: ".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
	}
} */

// Replace directory name

// Upload an image
// You must include a file from a form, in the format $_FILES['INPUT ID GOES HERE'] as the first parameter
// If you want a thumbnail uploaded, set $thumb to true, and specify the max height/width for an image to
//		be thumbnailed with the next two variables. Default is yes, 150x150
// You can also specify where the image and thumb will be uploaded, respectively. Defaults are the
//		/img/uploads/ and it's 'thumbs' subdirectory.
// POSTCONDITION: If no thumb was uploaded, returns the file location. If a thumb was uploaded, then
//		it will return an array of two index: 0 = img, 1 = thumb
function uploadImage($input_file,$thumb=true,$max_upload_width=150,$max_upload_height=150,
	$img_dir=IMG_UPLOAD_DIR, $thumb_dir=THUMB_UPLOAD_DIR,
	$extern_img_dir=IMG_EXTERN_DIR,$extern_thumb_dir=THUMB_EXTERN_DIR)
{
	switch($input_file["type"])
	{
	case "image/jpeg":
	case "image/pjpeg":
		$image_source = imagecreatefromjpeg($input_file["tmp_name"]);
		break;
	case "image/gif":
		$image_source = imagecreatefromgif($input_file["tmp_name"]);
		break;
	case "image/bmp":
		$image_source = imagecreatefromwbmp($input_file["tmp_name"]);
		break;
	case "image/png":
		$image_source = imagecreatefrompng($input_file["tmp_name"]);
		break;
	default:
		htmlHeader("Error");
		displayMessage("Error: No image specified!","goback");
		htmlFooter();
		die();
		break;
	}
	// Date for appending onto filenames, to avoid overwriting
	$date = date('mdYHis');
	// Add in userID, if we're logged in
	$session = new sessions();
	if($session->checkValid()) $id = $session->getUserIdFromSession()."_";
	else $id = "";
	// Copy file
	$remote_file = $img_dir.$id.$date.$input_file["name"];
	imagejpeg($image_source,$remote_file,100);
	chmod($remote_file,0644);
	
	// If we don't want a thumbnail, quit right here
	if($thumb != true)
	{
		imagedestroy($image_source);
		return $extern_img_dir.$id.$date.$input_file["name"];
	}
		
	// get width and height of original image
	list($image_width, $image_height) = getimagesize($remote_file);

	if($image_width>$max_upload_width || $image_height >$max_upload_height)
	{
		$proportions = $image_width/$image_height;
		
		if($image_width>$image_height)
		{
			$new_width = $max_upload_width;
			$new_height = round($max_upload_width/$proportions);
		}		
		else
		{
			$new_height = $max_upload_height;
			$new_width = round($max_upload_height*$proportions);
		}		
		$new_image = imagecreatetruecolor($new_width , $new_height);
		$image_source = imagecreatefromjpeg($remote_file);
		imagecopyresampled($new_image, $image_source, 0, 0, 0, 0, $new_width, $new_height, $image_width, $image_height);
		imagejpeg($new_image,$thumb_dir.$id.$date.$input_file["name"],100); //$remote_file,100);
		imagedestroy($new_image);
	}
imagedestroy($image_source);

// Return image and tnumbnail paths
$paths=  explode("/",$remote_file); // Explode file by forward slash
$filename = end($paths); // Filename is the last item in the array
// Return file paths
return array($extern_img_dir.$filename,$extern_thumb_dir.$filename);
}

// Delete file
// Must provide complete internal path to file
function deleteFile($file)
{
	if(file_exists($file))
	{
		unlink($file);
	}
}

// Get the description, based on where we are
function getDescription()
{
	// Switch by file currently accessed
	switch($_SERVER["SCRIPT_NAME"])
	{
	case "/post.php":
		if( isset($_GET['id']) ) // post id set
		{
			// Get description from post
			$posts = new posts( array("text") );
			$text = $posts->dbOutput( array("pid=",$_GET['id']) );
			$desc = $text[0][0];
		}
		else $desc = "Error"; // post id not set
		break;
	default:
		// Get description from about post
		$posts = new posts( array("text") );
		$text = $posts->dbOutput( array("pid=","27") );
		$desc = $text[0][0];
		break;
	}
	$desc = substr( strip_tags( $desc ),0,300 ); // Shorten string and remove html tags
	$desc = str_replace(array("\r\n", "\r", "\n")," ",$desc); // remove linebreaks
	return $desc;
}

// Returns test given in canonical form
// (Replaces spaces with dashes, etc)
function MakeCanonicalLink($url)
{
	$before = array("/[^a-z0-9]+/i");
	$after = array("-");
	return strtolower( preg_replace($before,$after,$url) );
}

// Check forms for empty input
// Returns *true* if input is empty
function CheckEmptyInput($input)
{
	if(!isset($input) || trim($input) == '')
		return true;
	else
		return false;
}

// Get TinyURL
// Useful for social networking
function GetTinyURL($url)
{
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, 'http://tinyurl.com/api-create.php?url='.$url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}


// Print error for API
// Print out an error, formatted as json
function apiPrintError($msg)
{
	$arr = array("error" => $msg);
	die(json_encode($arr));
}
?>