<?php //Bismillah
/*---------------------------*\
|   fakeMVCController Class   |
\*---------------------------*/
/*
DESCRIPTION:
	Simulates some of the routing functions of an MVC framework
MVC MIGRATION:
	Do not include in MVC migration
	Remove require_once of this file from controllers (found at the very top of the files)
	Make sure to change the games and user classes to extend the proper MVC Controller class
		Same goes for Model classes
	Make sure to remove all code after class declarations in games and user classes

*/
class fakeMVCController
{
	//MVC: Models; MVC framework will automatically create $this->User and $this->Game
	public $User;
	public $Game; 
	
	//MVC: Empty Constructor for children to safely calll parent::__construct()
	public function __construct()
	{
		//Empty
	}
	
	//MVC: Simulates some of the functionality of a framework, remove if you migrate to an MVC framework
	public function simulateFramework()
	{
		//Default Action
		$defaultAction = 'index';
	
		//Set action to posted action or use default action
		$action = (isset($_POST['action'])) ? $_POST['action'] : $defaultAction;
		
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


//-------------------------------------------------------------------------------
//MVC: The procedural code below simulates a framework's inversion of control
//-------------------------------------------------------------------------------

//Error Reporting
error_reporting(E_ALL | E_STRICT);

//Include Controller Classes
require_once("GamesController.php");
require_once("UsersController.php");

//Call Controller based on $_GET['c'] value
$controller = "Games";
if (isset($_GET['c']))
{
	$controller = $_GET['c'];
}
//Instantiate requested controller and simulate framework
$class = $controller."Controller";
$mvc = new $class();
$mvc->simulateFramework();
?>