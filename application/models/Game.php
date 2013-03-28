<?php //Bismillah
/*--------------------*\
|   gamesModel Class   |
\*--------------------*/
/*
DESCRIPTION:
	Provides interface between gamesController and SOAP services

MVC MIGRATION:
	- Copy to Models folder
	- All migration steps are found within the class declaration line or the constructor
	Migration Steps:
		1. Extend a proper Model parent class (e.g. CI_Model or AppModel)
		2. Uncomment call to parent constructor

*/
class Game extends CI_Model// <- Step 1.) extend proper Model class
{
	//Soap Service
	protected $_soapURL = "http://xbox2.sierrabravo.net/xbox2.asmx?wsdl";
	protected $_apiKey = "c18316f737b74b7636e06e24a99d6d6e";
	protected $_soap; //SoapClient
	protected $_soapEnv; //Soap Envelope
	
	//Values to be returned to controllers
	public $games = array();
	public $error = false;
	
	//Properties that must exist in the Games array for xbox.js to funciton properly
	protected $_gameProperties = array('Id', 'Title', 'Status', 'Votes');
	
	
	//Constructor - Connects to the SOAP service and validates api key
	public function __construct()
	{
		//MVC: call parent constructor
		parent::__construct(); // <- Step 2.) UNCOMMENT when migrating to an MVC
		
		//Attempt to connect to SOAP Service and create a SoapClient
		try
		{
			@$this->_soap = new SoapClient($this->_soapURL); //Warning supressed, as invalid url triggers both warnings and catch();
		}
		catch (Exception $e)
		{
			$this->error = $e->faultstring;
			return false;
		}
		
		//Insert apiKey into Soap Envelope Array
		$this->_soapEnv = array('apiKey'=> $this->_apiKey);
		
		//Validate API Key
		return $this->_checkKey();
	}
	
	//SOAP: Validate Key
	protected function _checkKey()
	{
		$client = $this->_soap;
		try
		{
			$valid = $client->checkKey($this->_soapEnv);
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
			return false;
		}
	}
	
	//MVC: Sends Response to Controller
	protected function _respond()
	{
		//Ensure games is received by xbox.js as an array and not an object
		$this->_arrGames();
		
		//Ensure games objects have the necessary properties for xbox.js to function properly
		$this->_checkGames();
		
		//Controller expects an array containing a games array and an error string/bool
		$response = array('games'=>$this->games, 'error'=>$this->error);
		return $response;
	}		
	
	//Checks if games object contains all required properties
	protected function _checkGames()
	{
		//If there isn't already an error, and if games isn't empty
		if (!$this->error && is_array($this->games) && count($this->games) > 0)
		{
			$errStr = "";
			$sep = "";
			//Go through each property and ensure it exists
			foreach ($this->_gameProperties as $prop)
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
	protected function _checkData($field = false)
	{
		if ($field)
		{
			$this->data[$field] = (isset($this->data[$field])) ? $this->data[$field] : '';
			return $this->data[$field];
		}
		return false;
	}
	
	//Checks if games is in array format, wraps games in array if not (occurs when zero or one game exists)
	protected function _arrGames()
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
		$this->_arrGames();

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
	
	//SOAP: Retrieve All Games from SOAP service
	protected function _getGames()
	{
		try
		{
			$client = $this->_soap;
			$games = $client->getGames($this->_soapEnv);
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
		return $this->_respond();
	}
	
	//Loads games and return to controller
	public function loadGames($sort = false, $param = false, $value = false)
	{
		//Set sort and filter parameters, prioritizes non-false arguments over data array
		$sort = (!$sort) ? $this->_checkData('sort') : $sort;
		$param = (!$param) ? $this->_checkData('param') : $param;
		$value = (!$value) ? $this->_checkData('value') : $value;
		
		//Get games from gamesModel, and ensure it's an array
		$rs = $this->_getGames();
		$this->_arrGames();
		
		//Check for errors during initialization
		if ($this->error)
		{
			//ERROR: Send gamesModel's error to xbox.js
			return $this->_respond();
		}
		
		//Filter Games
		$this->filterGames($param, $value);
		
		//Sort Games
		$this->sortGames($sort);
		
		//Respond to controller
		return $this->_respond();
	}
	
	//SOAP: Add New Game to SOAP Service
	protected function _addGame($title)
	{
		try
		{
			$client = $this->_soap;
			$this->_soapEnv['title'] = $title;
			$client->addGame($this->_soapEnv);
		}
		catch (Exception $e)
		{
			$this->error = $e->faultstring;
		}
		return $this->_respond();
	}
	
	//Add a Game
	public function add()
	{
		$title = $this->_checkData('title');
		$eligible = $this->_checkData('eligible');
		
		//VALIDATION: check user eligibility
		if (!$eligible)
		{
			//ERROR: user is ineligible
			$this->error = "You are not eligible to add a game.";
			return $this->_respond();
		}
		
		//VALIDATION: check for blank title
		if (!$title)
		{
			//ERRROR: blank title
			$this->error = "You must provide a title.";
			return $this->_respond();
		}
		
		//BUGFIX: PHP automatically adds slashes to singlequotes, Fixed with stripslashes()
		$title = stripslashes($title);
		
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
			return $this->_respond();
		}
		
		//Add game via model
		$rs = $this->_addGame($title);
		
		//Return ONLY new game to xbox.js
		$this->loadGames(false, "Title", $title);
		$this->error = $rs['error'];
		return $this->_respond();
	}
	
	//SOAP: Vote for Game via SOAP
	protected function _addVote($id)
	{
		try
		{
			$client = $this->_soap;
			$this->_soapEnv['id'] = $id;
			$client->addVote($this->_soapEnv);
		}
		catch (Exception $e)
		{
			$this->error = $e->faultstring;
		}
		return $this->_respond();
	}
	
	//Cast a Vote for a Title
	public function vote()
	{
		$id = $this->_checkData('id');
		$eligible = $this->_checkData('eligible');
		
		//VALIDATION: Check to see if game exists
		$this->loadGames(false, "Id", $id);
		if (count($this->games) != 1)
		{
			//ERROR: Invalid Id
			$this->error = "Error: attempted to vote for an invalid Id - ".$id.".";
			return $this->_respond();
		}
		
		//VALIDATION: Check to see if user can vote
		if (!$eligible)
		{
			//ERROR: User is Ineligible
			$this->error = "Error: user is ineligible to vote.";
			return $this->_respond();
		}
		
		//Add Vote via SOAP
		$rs = $this->_addVote($id);
		
		//Return soap's response to View
		$this->loadGames();
		$this->error = $rs['error'];
		return $this->_respond();
	}
	
	//Purchase Game
	protected function _setGotIt($id)
	{
		try
		{
			$client = $this->_soap;
			$this->_soapEnv['id'] = $id;
			$client->setGotIt($this->_soapEnv);
		}
		catch (Exception $e)
		{
			$this->error = $e->faultstring;
		}
		return $this->_respond();
	}
	
	//Purchase a game
	public function purchase()
	{
		$id = $this->_checkData('id');
		
		//VALIDATION: Check to see if game exists
		$this->loadGames(false, "Id", $id);
		if (count($this->games) != 1)
		{
			//ERROR: Invalid Id
			$this->error = "Error: attempted to purchase with an invalid Id - ".$id.".";
			return $this->_respond();
		}
		
		//Tell Model to purchase game
		$rs = $this->_setGotIt($id);
		
		//Return gamesModel's response to View
		$this->loadGames();
		$this->error = $rs['error'];
		return $this->_respond();
	}
	
	//Clear All Games
	public function clearGames()
	{
		try
		{
			$client = $this->_soap;
			$client->clearGames($this->_soapEnv);
			unset($this->games);
			$this->games = array();
		}
		catch (Exception $e)
		{
			$this->error = $e->faultstring;
		}
		return $this->_respond();
	}
}
?>