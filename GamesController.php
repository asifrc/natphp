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
		2. Modify the code to properly load the Game model
				CodeIgniter: replace both lines with $this->load->model('Game');
				CakePHP: delete both lines, since $this->Game is automatically accessible
		3. Modiy the code to properly load the Users model
				CodeIgniter: replace both lines with $this->load->model('User');
				CakePHP: replace first line with App::uses('User', 'Model'); and leave second line
*/
class GamesController extends fakeMVCController // <- Step 1.) Change to proper Controller class when migrating to MVC framework
{
	//Response Properties
	public $response = array();
	public $json = "";
	
	//userController
	public $User;
	
	//Constructor - loads Game and User models and sets Game's data to $_POST
	public function __construct()
	{
		//Call parent constructor
		parent::__construct();
		
		//MVC: Create instance of gamesModel
		require_once('Game.php'); // <- Step 2.)
		$this->Game = new Game(); // <- ^
		
		//MVC: Create an instance of a userController
		require_once('User.php'); // <- Step 3.)
		$this->User = new User(); // <- ^
		
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