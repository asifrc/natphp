<?php //Bismillah
/*-------------------*\
|   userModel Class   |
\*-------------------*/
/*
DESCRIPTION:
	Provides interface between userController and cookie contianing user information
MVC MIGRATION:
	Belongs in Model folder

*/
class User
{
	protected $lastVote;
	
	//Sets cookie with last vote as Unix timestamp
	public function setLastVote()
	{
		$lv = time();
		$expires = $lv + (60*60*24); //Expires in one day
		setcookie('lastVote', $lv, $expires, '/' );
		return $lv;
	}
	
	//Gets last vote from cookie
	public function getLastVote()
	{
		//Return cookie if it is set, or return false
		return (isset($_COOKIE['lastVote'])) ? 'test'.$_COOKIE['lastVote'] : false;
	}
	
	//DEBUG: resets vote for debugging purposes
	public function resetLastVote()
	{
		$expires = 0; //Expired
		setcookie('lastVote', false, $expires, '/' );
		return false;
	}
}
?>