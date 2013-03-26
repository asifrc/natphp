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
			return $this->respond();
		}
		
		//Insert apiKey into Soap Envelope Array
		$this->soapEnv = array('apiKey'=> $this->apiKey);
		
		//Validate API Key
		return $this->checkKey();
	}
	
	//Sends Response to Controller
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
				$this->error = "Invalid API Key provided to SOAP Service.";
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

if ($asif = new gamesModel())
{
	echo $asif->error;
}
/*
echo "<pre>";
print_r($asif->getGames());
die();*/
$games = $asif->getGames();
$json = array('games'=>$games['games'], 'error'=>$games['error']);
header("Content-Type:text/json");
echo json_encode($json);

?>