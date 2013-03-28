<?php //Bismillah
//MVC: fake MVC controller parent class; REMOVE during migration
require_once("fakeMVCController.php");

/*------------------------*\
|   userController Class   |
\*------------------------*/
/*
DESCRIPTION:
	Provides interface between xbox.js + gamesController and userModel
MVC MIGRATION:
	Belongs in Controllers folder

*/
class UsersController extends fakeMVCController
{
	//userModel
	public $user;
	
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
		
		//MVC: Create instance of userModel; Substitute with appropriate method for MVC (e.g. for CodeIgniter: $this->load->model('userModel','user');
		require_once('User.php');
		$this->user = new User();
		
		//MVC: Set data from $_POST
		if (isset($_POST))
		{
			$this->data = $_POST;
		}
		
		//Set default timezone (prevents E_STRICT notice in PHP 5.2)
		date_default_timezone_set('America/Chicago');
	}
	
	//MVC: default action
	public function index()
	{
		$this->getAll();
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

//Prevent from being called when included within user controller
if (count(get_included_files()) < 3)
{
	//MVC: simulates somc MVC functions inherited from controller; drop this if migrated to a MVC framework
	$user = new UsersController();
	$user->simulateFramework();
}

?>