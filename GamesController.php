<?php //Bismillah
/*-------------------------*\
|   gamesController Class   |
\*-------------------------*/
/*
DESCRIPTION:
	Provides interface between xbox.js and gamesModel

MVC MIGRATION:
	- Copy to Controllers folder
	- All migration steps are found within the class declaration line or the constructor
	Migration Steps:
		1. Change the parent class to a proper Controller parent class (e.g. CI_Controller or AppController)
		- That's all for CodeIgniter, next two steps are for CakePHP
		2. Uncomment $uses array
		3. Delete both lines calling ->load->model();
*/
class GamesController extends fakeMVCController // <- Step 1.) Change to proper Controller class when migrating to MVC framework
{
	//Response Properties
	public $response = array();
	public $json = "";
	
	//MVC: Loads models for CakePHP
	//public $uses = array("Game", "User"); // <- Step 2.) UNCOMMENT for CakePHP
	
	//Constructor - loads Game and User models and sets Game's data to $_POST
	public function __construct()
	{
		//Call parent constructor
		parent::__construct();
		
		//MVC: Load Game and User models
		$this->load->model('Game'); // <- Step 3.) DELETE for CakePHP
		$this->load->model('User'); // <- ^

		
		if ($this->Game->error)
		{
			$this->error = $this->Game->error;
			$this->respond();
		}
		
		//Set data from $_POST
		if (isset($_POST))
		{
			$this->Game->data = $_POST;
		}
	}
	
	//MVC: default action
	public function index()
	{
		$this->getAll();
	}
	
	//MVC: Sends JSON Response to View
	public function respond($resp = false)
	{
		if ($resp)
		{
			$this->response = $resp;
		}
		//Encode response and send as JSON; Xbox.js expects a JSON object with a games array and error string/false
		$json = $this->response;
		$this->json = json_encode($json);
		header("Content-Type:text/json");
		die($this->json);
	}		
	
	//Return All Games
	public function getAll()
	{
		//Load sorted games and return to xbox.js
		$this->respond( $this->Game->loadGames() );
	}
	
	//Return Filtered games
	public function find()
	{
		//Load filtered games and return to xbox.js
		$this->respond( $this->Game->loadGames() );
	}
	
	//Add a Game
	public function add()
	{
		//Set eligibility in game model's data array
		$this->Game->data['eligible'] = $this->User->checkEligible();
		
		//Ask game model to add game
		$rs = $this->Game->add();
		
		//If there are no errors, tell user model to set lastVote
		if (!$rs['error'])
		{
			$this->User->setLastVote();
		}
		//Respond with new game
		$this->respond($rs);
	}
		
	//Cast a Vote for a Title
	public function vote()
	{
		//Set eligibility in game model's data array
		$this->Game->data['eligible'] = $this->User->checkEligible();
		
		//Ask game model to vote
		$rs = $this->Game->vote();
		
		//If there are no errors, tell user model to set lastVote
		if (!$rs['error'])
		{
			$this->User->setLastVote();
		}
		//Respond with updated games array
		$this->respond($rs);
	}
	
	//Purchase a game
	public function purchase()
	{
		//Purchase game and return updated games to xbox.js
		$this->respond( $this->Game->purchase() );
	}
	
	//Clear All Games
	public function clearAll()
	{
		//Clear games and return empty games array to xbox.js
		$this->respond( $this->Game->clearGames() );
	}
}
?>