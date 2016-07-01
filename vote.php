<?php
// =================================================
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
require_once "config.php";
require_once $incPath."/votes.php";

$votesObj = new votes();

// Make sure input is all good
if( isset( $_GET['pid'] ) && isset( $_GET['value'] ) && isset( $_GET['type'] ) )
{
	// If add vote fails, try to edit a currently existing vote
	if( !$votesObj->AddVote( $_GET['pid'],(bool)$_GET['value'] ,(int)$_GET['type'] ))
	{
		$votesObj->ChangeVote( $_GET['pid'],(bool)$_GET['value'],(int)$_GET['type']  );
	}
}

// In every case, redirect back to the last page
header ("Location: ".$_SERVER['HTTP_REFERER']);
?>