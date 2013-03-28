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
		- That's all for CodeIgniter, next step is for CakePHP
		2. Delete whole __construct() function

*/
class UsersController extends CI_Controller // <- Step 1.) Change to proper Controller class when migrating to MVC framework
{	
	//Posted Data
	public $data;
	
	//Response Data
	public $error = false;
	public $eligible = false;
	
	//Constructor - Load User Model
	public function __construct() // <- Step 3.) DELETE whole __construct() function for CakePHP
	{
		//MVC: call parent constructor
		parent::__construct();
		
		//MVC: Create instance of userModel
		$this->load->model('User');
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
	
	//Check Eligibility and return bool
	public function checkEligible()
	{
		return $this->User->checkEligible();
	}
	
	//Return Eligibility to a View via JSON
	public function isEligible()
	{
		$this->eligible = $this->checkEligible();
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