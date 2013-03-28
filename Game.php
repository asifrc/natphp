<?php //Bismillah
/*--------------------*\
|   gamesModel Class   |
\*--------------------*/
/*
DESCRIPTION:
	Provides interface between gamesController and SOAP services

MVC MIGRATION:
	Belongs in Model folder
	Make sure to extend MVC's Model class

*/
class Game
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
		//MVC: call parent constructor (uncomment when migrating to an MVC)
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
?>