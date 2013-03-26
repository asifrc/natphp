<?php //Bismillah
error_reporting(E_ALL | E_STRICT);
/*---------------*\
|   gamesModel Class   |
\*---------------*/
/*
DESCRIPTION:
	Provides interface between gamesController and SOAP services

*/

class gamesModel
{
	
	protected $soapURL = "http://xbox2.sierrabravo.net/xbox2.asmx?wsdl";
	protected $apiKey = "c18316f737b74b7636e06e24a99d6d6e";
	
	protected $soap;
	protected $soapEnv;
	
	public $games = array();
	public $error = false;
	
	//Constructor
	public function __construct()
	{
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
		$response = array('games'=>$this->games, 'error'=>$this->error);
		return $response;
	}		
	
	//SOAP: Validate Key
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
	
	
	//SOAP: Retrieve All Games
	public function getGames()
	{
		$client = $this->soap;
		$games = $client->getGames($this->soapEnv);
		$this->games = $games->GetGamesResult->XboxGame;
		return $this->respond();
	}
	
	//SOAP: Add New Game
	
	//SOAP: Vote for Game
	
	//SOAP: Purchase Game
	
	//SOAP: Clear Games
	
}

class gamesController
{
	public $games = array();
	public $error = false;
	public $json = "";
	
	protected $soap; //gamesModel
	protected $gameProperties = array('Id', 'Title', 'Status', 'Votes');
	
	protected $data;
	
	public function __construct()
	{
		$this->index();
	}
	
	//MVC: This contains the code that would be appropriate inside the index() or indexAction() function of the Games Controller within an MVC framework
	public function index()
	{
		$this->soap = new gamesModel();
		if ($this->soap->error)
		{
			$this->error = $this->soap->error;
			$this->respond();
		}
		
		//Set data from $_POST
		if (isset($_POST))
		{
			$this->data = $_POST;
		}
		
		//Set action to posted action or use default action
		$action = (isset($this->data['action'])) ? $this->data['action'] : 'getAll';
		
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
	
	//MVC: Sends JSON Response to View
	public function respond()
	{
		$this->checkGames();
		$json = array('games'=>$this->games, 'error'=>$this->error);
		$this->json = json_encode($json);
		header("Content-Type:text/json");
		die($this->json);
	}		
	
	//Checks if games object contains all required properties
	protected function checkGames()
	{
		//If there isn't already an error, and if games isn't empty
		if (!$this->error && count($this->games)>0)
		{
			$errStr = "";
			$sep = "";
			//Go through each property and ensure it exists
			foreach ($this->gameProperties as $prop)
			{
				$g = array_slice($this->games,0,1); //Use first game as sample
				if (!property_exists($g[0], $prop))
				{
					 $errStr .= $sep.$prop;
					 $sep = ", ";
				}
			}
			if ($errStr!="")
			{
				$this->error = "Warning: Games objects missing necessary properties: ".$errStr;
				return false;
			}
		}
		return true;
	}
	
	//Checks if data field exists, returns empty string if it doesn't
	protected function checkData($field = false)
	{
		if ($field)
		{
			$this->data[$field] = (isset($this->data[$field])) ? $this->data[$field] : '';
			return $this->data[$field];
		}
		return false;
	}
	//Sorts Games
	protected function sortGames($sort= false)
	{
		//Set Default Sort to Title
		$sort = (!$sort) ? "Title" : $sort;
		
		//Sort function callbacks
		function srtTitle($a, $b)
		{
			return strcmp(strtolower($a->Title),strtolower($b->Title));
		}
		function srtVotes ($a, $b)
		{
			if ($a->Votes==$b->Votes)
			{
				return 0;
			}
			return ($a->Votes > $b->Votes) ? -1 : 1;
		}
		
		//Sort games
		usort($this->games, "srt".$sort);
		return $this->games;
	}
	
	//Filters Games
	protected function filterGames($param = false, $value = false)
	{
		//Filter Callback: If parameter matches, return true, else return false
		$fltStr = "return (strtolower(\$game->".$param.") == strtolower(\"".$value."\")) ? true : false;";
			
		//If at least one game exists to filter
		if (count($this->games) > 0)
		{
			//If a parameter and a value are provided
			if ($param&&$value)
			{
				//If parameter is valid
				$g = array_slice($this->games,0,1); //Use first game as sample
				if (property_exists($g[0], $param))
				{
					//Filter Games
					$this->games = array_filter($this->games, create_function("\$game", $fltStr));
				}
				else
				{
					$this->error = "Warning: invalid filter parameter '".$param."'";
				}
			}
		}
		return $this->games;
	}
	
	//Loads game from gamesModel
	protected function loadGames($sort = false, $param = false, $value = false)
	{
		//Load games from gamesModel
		$rs = $this->soap->getGames();
		$this->games = $rs['games'];
		$this->error = $rs['error'];
		//Check for errors during initialization
		if ($this->error)
		{
			$this->respond();
		}
		
		//Filter Games
		$this->filterGames($param, $value);
		
		//Sort Games
		$this->sortGames($sort);
		
		//Send Json response
		$this->respond();
		
	}
	
	//Get All Games
	public function getAll()
	{
		$sort = $this->checkData('sort');
		$this->loadGames($sort);
	}
	
	//Get Filtered games
	public function find()
	{
		$sort = $this->checkData('sort');
		$param = $this->checkData('param');
		$value = $this->checkData('value');
		$this->loadGames($sort, $param, $value);
	}
}
$asif = new gamesController();

?>