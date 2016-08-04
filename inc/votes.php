<?php
// ******************************************************
// Description: Class to handle the voting system, that is
// thumbs up/down votes on articles
// This class inherits from database
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
require_once "posts.php";
require_once "comments.php";
require_once "sessions.php";

class votes extends database
{
	// Constructor
	// Sets the table and fields
	function __construct( $fld = array( "user_id","post_id","value","date" ) )
	{
		// Call parent constructor
		parent::__construct("votes",$fld);
	}
	
	// Add a vote
	public function AddVote($post_id,$value=1,$type=0)
	{
		parent::changeFields( array( "user_id","post_id","value","date","type" ) );

		$sessionObj = new sessions();
		$postObj = new posts();
		$user_id = (int)$sessionObj->getUserIdFromSession();

		// Make sure we're logged in
		if( !$sessionObj->checkValid() )
		{
			return false;
		}

		// Make sure we haven't voted
		if( $this->CheckVote( $post_id,$user_id,$type) )
		{
			return false;
		}
		// Make sure this isn't our post
		
		// Post
		if($type == 0)
		{
			if( $user_id == $postObj->getPosterID( $post_id ) )
			{
				return false;
			}
		}
		// Comment
		else if($type == 1)
		{
			$comObj = new Comments();
			if( $user_id == $comObj->getPosterID( $post_id ) )
			{
				return false;
			}
		}
		
		// Put vote in
		$this->dbInput( array( $user_id,$post_id,(int)$value,$this->getDateForMySQL(),$type ) );
		// Increment/decrement post rating
		if($type == 0) $postObj->IncDecRating($post_id,$value);
		
		return true;
	}
	
	// Change a vote
	public function ChangeVote( $post_id,$value=1,$type=0 )
	{
		$sessionObj = new sessions();
		$postObj = new posts();
		$user_id = (int)$sessionObj->getUserIdFromSession();

		if( !$sessionObj->checkValid() )
		{
			return false;
		}

		if($type == 0)
		{
			if( $user_id == $postObj->getPosterID( $post_id ) )
			{
				return false;
			}
		}
		// Comment
		else if($type == 1)
		{
			$comObj = new Comments();
			if( $user_id == $comObj->getPosterID( $post_id ) )
			{
				return false;
			}
		}
		parent::changeFields( array( "vid","value" ) );
		// Get vote ID and value
		$voteInfo = $this->dbOutput( array( "post_id=$post_id"," AND user_id=".$user_id." AND type=$type" ) );
		// Change it, if it's different
		if( (int)$value == $voteInfo[0][1] ) return false;
		else
		{
			parent::changeFields( array( "value" ) );
			$this->dbUpdate( array( (int)$value ),"vid",$voteInfo[0][0] );
			$postObj = new posts();
			if($type == 0) $postObj->IncDecRating($post_id,$value);
			return true;
		}
	}
	
	// Check if a user has already voted on this post
	// Postcondition: Returns true if user has already voted on the post,
	//	otherwise returns false
	public function CheckVote( $post_id,$user_id,$type=0 )
	{
		if ( count( $this->dbOutput( array ( "post_id=$post_id ","AND user_id=$user_id AND type=$type" ) ) ) >= 1 )
		{
			return true;
		}
		else
		{
			return false;
		}
		
	}
	
	// Get number of positive votes for a post, returns an int
	public function GetNoThumbsUp( $post_id,$type=0 )
	{
		parent::changeFields( array( "vid" ) );
		return count( $this->dbOutput( array( "post_id=$post_id"," AND value=1 AND type=$type" ) ) );
	}
	
	// Get number of negative votes for a post, returns an int
	public function GetNoThumbsDown( $post_id,$type=0 )
	{
		parent::changeFields( array( "vid" ) );
		return count( $this->dbOutput( array( "post_id=$post_id"," AND value=0 AND type=$type" ) ) );
	}
}
?>