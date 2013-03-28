<?php //Bismillah
/*-------------------*\
|   userModel Class   |
\*-------------------*/
/*
DESCRIPTION:
	Provides interface between userController and cookie contianing user information
MVC MIGRATION:
	- Copy to Models folder
	Migration Steps:
		1. Extend a proper Model parent class (e.g. CI_Model or AppModel)

*/
class User extends CI_Model// <- Step 1.) extend proper Model class
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
		return (isset($_COOKIE['lastVote'])) ? $_COOKIE['lastVote'] : false;
	}
	
	//DEBUG: resets vote for debugging purposes
	public function resetLastVote()
	{
		$expires = 0; //Expired
		setcookie('lastVote', 0, $expires, '/' );
		return true;
	}
	
	//Return the eligibility bool of the user to a controller
	public function checkEligible()
	{
		$eligible = false;
		$t = time();
		
		//Set default timezone (prevents E_STRICT notice from date())
		date_default_timezone_set('America/Chicago');
		
		//if it's not a weekend ( dayofweek mod 6 will return 0 if it is either Sunday (0) or Saturday (6) )
		if (date('w', $t)%6)
		{
			$lv = $this->getLastVote();
			//If lastVote cookie is set
			if ($lv)
			{
				//If today isn't the same as the last vote
				if (date('mdY', $t)!=date('mdY',$lv))
				{
					//Then the user is Eligible because it's not the same day
					$eligible = true;
				}
			}
			else
			{
				//The user is eligible because there's no record of voting
				$eligible = true;
			}
		}
		//Return bool of eligibility
		return $eligible;
	}
}
?>