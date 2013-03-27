<?php //Bismillah
/*-------------------*\
|   userModel Class   |
\*-------------------*/
/*
DESCRIPTION:
	Provides interface between userController and cookie contianing user information
*/
class userModel
{
	protected $lastVote;
	
	//Sets cookie with last vote as Unix timestamp
	public function setLastVote()
	{
		$lv = time();
		setcookie('lastVote', $lv, time()+(60*60*24) );
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
		setcookie('lastVote', "", time()-3600);
	}
}
/*------------------------*\
|   userController Class   |
\*------------------------*/
/*
DESCRIPTION:
	Provides interface between View + gamesController and userModel
*/
class userController
{
	protected $user;
	protected $data;
	protected $error = false;
	protected $eligible = false;
	
	//MVC: The constructor contains code that would be appropriate within the controller class, outside function declarations
	public function __construct()
	{
		//Create instance of userModel
		$this->user = new userModel();
		
		//Set data from $_POST
		if (isset($_POST))
		{
			$this->data = $_POST;
		}
		
		//Set default timezone (prevents E_STRICT notice in PHP 5.2)
		date_default_timezone_set('America/Chicago');
	}
	
	//MVC: Sends JSON Response to View
	public function respond()
	{
		$json = array('eligible'=>$this->eligible, 'error'=>$this->error);
		$this->json = json_encode($json);
		header("Content-Type:text/json");
		die($this->json);
	}
	
	//Check Eligibility and return to a View via JSON
	public function isEligible()
	{
		$this->checkEligible();
		$this->respond();
	}
	
	//Return the eligibility of the user to a controller
	public function checkEligible()
	{
		$t = time();
		//if it's not a weekend ( dayofweek mod 6 will return 0 if it is either Sunday (0) or Saturday (6) )
		if (date('w', $t)%6)
		{
			//If lastVote cookie is set
			if ($lv = $this->user->getLastVote())
			{
				//If today isn't the same as the last vote
				if (date('mdY', $t)!=date('mdY',$lv))
				{
					//Then the user is Eligible because it's not the same day
					$this->eligible = true;
				}
			}
			else
			{
				//The user is eligible because there's no record of voting
				$this->eligible = true;
			}
		}
		//Return bool of eligibility
		return $this->eligible;
	}
	
	//DEBUG: Resets user's last vote
	public function resetVote()
	{
		$this->user->resetLastVote();
		$this->eligible = true;
		$this->respond();
	}
	
	//Sets that a vote has been cast
	public function castVote()
	{
		$this->user->setLastVote();
	}
		
	//MVC: Simulates some of the functionality of a framework, remove if you migrate to an MVC framework
	public function simulateFramework()
	{
		//Set action to posted action or use default action
		$action = (isset($this->data['action'])) ? $this->data['action'] : 'isEligible';
		
		//VALIDATION: Check to see if requested action is valid
		if (method_exists($this, $action))
		{
			//VALIDATION: Allow access to public function only
			$refl = new ReflectionMethod($this, $action);
			if ($refl->isPublic())
			{
				//Call requested action
				$this->$action();
			}
			else
			{
				//ERROR: Attempted to access non-public function
				$this->error = "Error: method '".$action."' is not public!";
				$this->respond();
			}
		}
		else
		{
			//ERROR: Function does not exist
			$this->error = "Error: method '".$action."' does not exist!";
			$this->respond();
		}
	}
}

//-----------------------------------------------------------------------------
//MVC: Everything below is not necessary when migrating to a real MVC framework
//-----------------------------------------------------------------------------

//Call index only if user.php is not included in any other files
if (count(get_included_files()) < 2)
{
	$user = new userController();
	//MVC: simulates somc MVC functions inherited from controller; drop this if migrated to a MVC framework
	$user->simulateFramework();
}

?>