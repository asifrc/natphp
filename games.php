<?php //Bismillah
error_reporting(E_ALL | E_STRICT); //DEBUG

//Include userController
require_once("user.php");


/*--------------------*\
|   gamesModel Class   |
\*--------------------*/
/*
DESCRIPTION:
	Provides interface between gamesController and SOAP services

MVC MIGRATION:
	Belongs in Model folder

*/
class gamesModel
{
	//Soap Service
	protected $soapURL = "http://xbox2.sierrabravo.net/xbox2.asmx?wsdl";
	protected $apiKey = "c18316f737b74b7636e06e24a99d6d6e";
	protected $soap; //SoapClient
	protected $soapEnv; //Soap Envelope
	
	//Values to be returned to controllers
	public $games = array();
	public $error = false;
	
	//MVC: Make sure to uncomment call to parent constructor when migrating to a real MVC
	public function __construct()
	{
		//MVC: call parent constructor
		//parent::__construct();
		
		//Attempt to connect to SOAP Service and create a SoapClient
		try
		{
			@$this->soap = new SoapClient($this->soapURL); //Warning supressed, as invalid url triggers both warnings and catch();
		}
		catch (Exception $e)
		{
			$this->error = $e->faultstring;
			return false;
		}
		
		//Insert apiKey into Soap Envelope Array
		$this->soapEnv = array('apiKey'=> $this->apiKey);
		
		//Validate API Key
		return $this->checkKey();
	}
	
	//MVC: Sends Response to Controller
	public function respond()
	{
		//Controller expects an array containing a games array and an error string/bool
		$response = array('games'=>$this->games, 'error'=>$this->error);
		return $response;
	}		
	
	//Validate Key
	protected function checkKey()
	{
		$client = $this->soap;
		try
		{
			$valid = $client->checkKey($this->soapEnv);
			if (!$valid->CheckKeyResult)
			{
				$this->error = "Error: Invalid API Key provided to SOAP Service.";
				return false;
			}
			else
			{
				return true;
			}
		}
		catch(Exception $e)
		{
			$this->error = $e->faultstring;
			$this->respond();
			return false;
		}
	}
	
	
	//Retrieve All Games
	public function getGames()
	{
		try
		{
			$client = $this->soap;
			$games = $client->getGames($this->soapEnv);
			//If any games are found
			if (isset($games->GetGamesResult->XboxGame))
			{
				//Return games (else empty games array will be returned)
				$this->games = $games->GetGamesResult->XboxGame;
			}
		}
		catch (Exception $e)
		{
			$this->error = $e->faultstring;
		}
		return $this->respond();
	}
	
	//Add New Game
	public function addGame($title)
	{
		try
		{
			$client = $this->soap;
			$this->soapEnv['title'] = $title;
			$client->addGame($this->soapEnv);
		}
		catch (Exception $e)
		{
			$this->error = $e->faultstring;
		}
		return $this->respond();
	}
	
	//Vote for Game
	public function addVote($id)
	{
		try
		{
			$client = $this->soap;
			$this->soapEnv['id'] = $id;
			$client->addVote($this->soapEnv);
		}
		catch (Exception $e)
		{
			$this->error = $e->faultstring;
		}
		return $this->respond();
	}
	
	//Purchase Game
	public function setGotIt($id)
	{
		try
		{
			$client = $this->soap;
			$this->soapEnv['id'] = $id;
			$client->setGotIt($this->soapEnv);
		}
		catch (Exception $e)
		{
			$this->error = $e->faultstring;
		}
		return $this->respond();
	}
	
	//Clear Games
	public function clearGames()
	{
		try
		{
			$client = $this->soap;
			$client->clearGames($this->soapEnv);
		}
		catch (Exception $e)
		{
			$this->error = $e->faultstring;
		}
		return $this->respond();
	}
}

/*-------------------------*\
|   gamesController Class   |
\*-------------------------*/
/*
DESCRIPTION:
	Provides interface between xbox.js and gamesModel
MVC MIGRATION:
	Belongs in Controllers folder

*/
class gamesController
{
	//Response Properties
	public $games = array();
	public $error = false;
	public $json = "";
	
	//gamesModel
	protected $soap; 
	protected $gameProperties = array('Id', 'Title', 'Status', 'Votes');
	
	//userController
	protected $user;
	
	//Posteds Data
	protected $data;
	
	//MVC: Make sure to uncomment call to parent constructor when migrating to a real MVC
	public function __construct()
	{
		//MVC: call parent constructor
		//parent::__construct();
		
		//Create instance of gamesModel
		$this->soap = new gamesModel();
		if ($this->soap->error)
		{
			$this->error = $this->soap->error;
			$this->respond();
		}
		
		//Create an instance of a userController
		$this->user = new userController();
		
		//MVC: Set data from $_POST
		if (isset($_POST))
		{
			$this->data = $_POST;
		}
	}
	
	//MVC: Sends JSON Response to View
	public function respond()
	{
		//Ensure games is received by xbox.js as an array and not an object
		$this->arrGames();
		
		//Ensure games objects have the necessary properties for xbox.js to function properly
		$this->checkGames();
		
		//Package, encode, and send JSON; Xbox.js expects a JSON object with a games array and error string/false
		$json = array('games'=>$this->games, 'error'=>$this->error);
		$this->json = json_encode($json);
		header("Content-Type:text/json");
		die($this->json);
	}		
	
	//Checks if games object contains all required properties
	protected function checkGames()
	{
		//If there isn't already an error, and if games isn't empty
		if (!$this->error && is_array($this->games) && count($this->games) > 0)
		{
			$errStr = "";
			$sep = "";
			//Go through each property and ensure it exists
			foreach ($this->gameProperties as $prop)
			{
				//Use first game as sample
				$g = array_slice($this->games,0,1); 
				if (!property_exists($g[0], $prop))
				{
					//Add to errorstring if property is not present
					 $errStr .= $sep.$prop;
					 $sep = ", ";
				}
			}
			//If at least on property is missing
			if ($errStr!="")
			{
				//Send Xbox.js a warning that the games array may not function as expected
				$this->error = "Warning: Games objects missing necessary properties: ".$errStr;
				return false;
			}
		}
		return true;
	}
	
	//Checks if data field exists, returns false if it doesn't
	protected function checkData($field = false)
	{
		if ($field)
		{
			$this->data[$field] = (isset($this->data[$field])) ? $this->data[$field] : '';
			return $this->data[$field];
		}
		return false;
	}
	
	//Checks if games is in array format, wraps games in array if not (occurs when zero or one game exists)
	protected function arrGames()
	{
		if (!is_array($this->games))
		{
			$this->games = array($this->games);
		}
		return $this->games;
	}
	
	//Sorts Games during loadGames()
	protected function sortGames($sort = false)
	{
		//If the games array isn't empty
		if (is_array($this->games) && count($this->games) > 0)
		{
			//Set Default Sort to Title
			$sort = (!$sort) ? "Title" : $sort;
			
			//NOTE: I would generally use closures for usort instead of predefining callbacks, but am restricted by PHP 5.2
			
			//Sort function callbacks
			if (!function_exists('srtTitle')) //Prevent redeclaration
			{
				function srtTitle($a, $b)
				{
					return strcmp(strtolower($a->Title),strtolower($b->Title));
				}
			}
			if (!function_exists('srtVotes')) //Prevent redeclaration
			{
				function srtVotes ($a, $b)
				{
					if ($a->Votes==$b->Votes)
					{
						return 0;
					}
					return ($a->Votes > $b->Votes) ? -1 : 1;
				}
			}
			
			//Sort games
			usort($this->games, "srt".$sort);
		}
		return $this->games;
	}
	
	//Filters Games during loadGames()
	protected function filterGames($param = false, $value = false)
	{
		//NOTE: I would use an anonymous function in place of $fltStr if I weren't restricted by PHP 5.2
		
		//Filter Callback: If parameter matches, return true, else return false
		$fltStr = "return (strtolower(\$game->".$param.") == strtolower(\"".$value."\")) ? true : false;";
		
		//Ensure games is an array
		$this->arrGames();

		//If at least one game exists to filter
		if (is_array($this->games) && count($this->games) > 0)
		{
			//If a parameter and a value are provided
			if ($param&&$value)
			{
				//If parameter is valid (using first game as sample)
				$g = array_slice($this->games,0,1);
				if (property_exists($g[0], $param))
				{
					//Filter Games
					$this->games = array_filter($this->games, create_function("\$game", $fltStr));
				}
				else
				{
					//Else send warning to xbox.js
					$this->error = "Warning: invalid filter parameter '".$param."'";
				}
			}
		}
		return $this->games;
	}
	
	//Loads game from gamesModel
	protected function loadGames($sort = false, $param = false, $value = false)
	{
		//Get games from gamesModel, and ensure it's an array
		$rs = $this->soap->getGames();
		$this->arrGames();
		
		//Load games and error into gamesController's properties
		$this->games = $rs['games'];
		$this->error = $rs['error'];
		
		//Check for errors during initialization
		if ($this->error)
		{
			//ERROR: Send gamesModel's error to xbox.js
			$this->respond();
		}
		
		//Filter Games
		$this->filterGames($param, $value);
		
		//Sort Games
		$this->sortGames($sort);
	}
	
	//Return All Games
	public function getAll()
	{
		//Check for a sort value, load games, and send to xbox.js
		$sort = $this->checkData('sort');
		$this->loadGames($sort);
		$this->respond();
	}
	
	//Return Filtered games
	public function find()
	{
		//Check for sort and filter values, load games accordingly, and send to xbox.js
		$sort = $this->checkData('sort');
		$param = $this->checkData('param');
		$value = $this->checkData('value');
		$this->loadGames($sort, $param, $value);
		$this->respond();
	}
	
	//Add a Game
	public function add()
	{
		$title = $this->checkData('title');
		
		//VALIDATION: check user eligibility
		if (!$this->user->checkEligible())
		{
			//ERROR: user is ineligible
			$this->error = "You are not eligible to add a game.";
			$this->respond();
		}
		
		//VALIDATION: check for blank title
		if (!$title)
		{
			//ERRROR: blank title
			$this->error = "You must provide a title.";
			$this->respond();
		}
		
		//VALIDATION: check for duplicate title
		$this->loadGames(false, "Title", $title);
		if (is_array($this->games) && count($this->games) > 0)
		{
			//Check if game is purchased (to help user locate game)
			$pch = ".";
			if ($this->games[0]->Status=="gotit")
			{
				$pch = " and has been purchased.";
			}
			//ERROR: Duplicate title
			$this->error = "'".$this->games[0]->Title."' already exists".$pch;
			$this->respond();
		}
		
		//Add game via model
		$rs = $this->soap->addGame($title);
		
		//If there are no errors, tell user model to set lastVote
		if (!$rs['error'])
		{
			$this->user->castVote();
		}
		
		//Return ONLY new game to xbox.js
		$this->loadGames(false, "Title", $title);
		$this->respond();
	}
		
	//Cast a Vote for a Title
	public function vote()
	{
		$id = $this->checkData('id');
		
		//VALIDATION: Check to see if game exists
		$this->loadGames(false, "Id", $id);
		if (count($this->games) != 1)
		{
			//ERROR: Invalid Id
			$this->error = "Error: attempted to vote for an invalid Id - ".$id.".";
			$this->respond();
		}
		
		//VALIDATION: Check to see if user can vote
		if (!$this->user->checkEligible())
		{
			//ERROR: User is Ineligible
			$this->error = "Error: user is ineligible to vote.";
			$this->respond();
		}
		
		//Tell Model to add vote
		$rs = $this->soap->addVote($id);
		
		//If there are no errors, tell user model to set lastVote
		if (!$rs['error'])
		{
			$this->user->castVote();
		}
		
		//Return gamesModel's response to View
		$this->loadGames();
		$this->error = $rs['error'];
		$this->respond();
	}
	
	//Purchase a game
	public function purchase()
	{
		$id = $this->checkData('id');
		
		//VALIDATION: Check to see if game exists
		$this->loadGames(false, "Id", $id);
		if (count($this->games) != 1)
		{
			//ERROR: Invalid Id
			$this->error = "Error: attempted to purchase with an invalid Id - ".$id.".";
			$this->respond();
		}
		
		//Tell Model to purchase game
		$rs = $this->soap->setGotIt($id);
		
		//Return gamesModel's response to View
		$this->loadGames();
		$this->error = $rs['error'];
		$this->respond();
	}
	
	//Clear All Games
	public function clearAll()
	{
		//Tell Model to clear all games
		$rs = $this->soap->clearGames();
		
		//Return gamesModel's response to View
		unset($this->games);
		$this->games = array();
		$this->error = $rs['error'];
		$this->respond();
	}
}

//-------------------------------------------------------------------------------
//MVC: Everything below should be removed when migrating to a real MVC framework
//-------------------------------------------------------------------------------

class fakeMVC extends gamesController
{
	//MVC: Simulates some of the functionality of a framework, remove if you migrate to an MVC framework
	public function simulateFramework()
	{
		//Default Action
		$defaultAction = 'getAll';
	
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

//MVC: simulates somc MVC functions inherited from controller; drop this if migrated to a MVC framework
$games = new fakeMVC();
$games->simulateFramework();

?>