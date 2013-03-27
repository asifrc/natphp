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
class userModel
{
	protected $lastVote;
	
	//Sets cookie with last vote as Unix timestamp
	public function setLastVote()
	{
		$lv = time();
		$expires = $lv + (60*60*24); //Expires in one day
		setcookie('lastVote', $lv, $expires );
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
	Provides interface between xbox.js + gamesController and userModel
MVC MIGRATION:
	Belongs in Controllers folder

*/
class userController
{
	//userModel
	protected $user;
	
	//Posted Data
	protected $data;
	
	//Response Data
	protected $error = false;
	protected $eligible = false;
	
	//MVC: Make sure to uncomment call to parent constructor when migrating to a real MVC
	public function __construct()
	{
		//MVC: call parent constructor
		//parent::__construct();
		
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
		
}

//-------------------------------------------------------------------------------
//MVC: Everything below should be removed when migrating to a real MVC framework
//-------------------------------------------------------------------------------

class fakeMVC extends userController
{
	//MVC: Simulates some of the functionality of a framework, remove if you migrate to an MVC framework
	public function simulateFramework()
	{
		//Default Action
		$defaultAction = 'isEligible';
	
		//Set action to posted action or use default action
		$action = (isset($this->data['action'])) ? $this->data['action'] : $defaultAction;
		
		//VALIDATION: Check to see if requested action exists
		if (method_exists($this, $action))
		{
			//VALIDATION: Allow access to public methods only
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

//Call framework simulation only if user.php is not included within any other files (e.g. games.php)
if (count(get_included_files()) < 2)
{
	//MVC: simulates somc MVC functions inherited from controller; drop this if migrated to a MVC framework
	$user = new fakeMVC();
	$user->simulateFramework();
}

?>