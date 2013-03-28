<?php //Bismillah
/*------------------------*\
|   userController Class   |
\*------------------------*/
/*
DESCRIPTION:
	Provides interface between xbox.js + gamesController and userModel
MVC MIGRATION:
	- Copy to Controllers folder
	- All migration steps are found within the class declaration line or the constructor
	Migration Steps:
		1. Change the parent class to a proper Controller parent class (e.g. CI_Controller or AppController)
		2. Modify code to properly instantiate User model
			CodeIgniter: replace both lines with $this->load->model('User');
			CakePHP: delete both lines, User model is automatically available (Constructor function becomes unnecessary in this case)

*/
class UsersController extends fakeMVCController // <- Step 1.) Change to proper Controller class when migrating to MVC framework
{	
	//Posted Data
	public $data;
	
	//Response Data
	public $error = false;
	public $eligible = false;
	
	//Constructor - Load User Model
	public function __construct()
	{
		//MVC: call parent constructor
		parent::__construct();
		
		//MVC: Create instance of userModel
		require_once('User.php'); // <- Step 1.)
		$this->User = new User(); // <- ^
	}
	
	//MVC: default action
	public function index()
	{
		$this->isEligible();
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
		$this->eligible = $this->User->checkEligible();
		$this->respond();
	}
	
	//DEBUG: Resets user's last vote
	public function resetVote()
	{
		$this->eligible = $this->User->resetLastVote();
		$this->respond();
	}	
}
?>