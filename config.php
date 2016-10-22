<?php
// =================================================
// config.php: Store all configurations here
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
// =================================================

// MySQL Database Info
define("DB_NAME",''); // name of database
define("DB_USER",''); // database user name
define("DB_PASS",''); // database password
define("DB_HOST",''); // database host name
define("DB_PREFIX","ret_"); // Database prefix

define("SALT",""); // Type a bunch of random characters here

define("IMG_UPLOAD_DIR","./img/uploads/"); // Default image upload directory
define("THUMB_UPLOAD_DIR","./img/uploads/thumbs/"); // Default thumbnail upload directory
define("AVATAR_UPLOAD_DIR","./img/avatars/"); // Default avatar upload directory
define("IMG_EXTERN_DIR","/img/uploads/"); // Same as image upload directory, but as the browser sees it
define("THUMB_EXTERN_DIR","/img/uploads/thumbs/"); // Same as thumb director, but as browser sees it
define("AVATAR_EXTERN_DIR","/img/avatars/"); // Same as avatar directory, but as the browser sees it

define("RECAPTCHA_PUBLIC_KEY",""); // reCaptcha public key
define("RECAPTCHA_PRIVATE_KEY",""); // reCaptcha private key

define("ROTW","true"); // Rotw mode

// DO NOT EDIT BLOW THIS LINE, UNLESS YOU KNOW WHAT YOU'RE DOING
$rootPath = ".";//$_SERVER['DOCUMENT_ROOT'];
$incPath = $rootPath."/inc";
$tmplPath = $rootPath."/tmpl";
?>