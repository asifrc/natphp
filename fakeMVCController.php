<?php //Bismillah
/*---------------------------*\
|   fakeMVCController Class   |
\*---------------------------*/
/*
DESCRIPTION:
	Simulates some of the routing functions of an MVC framework
MVC MIGRATION:
	Do not include in MVC migration
	Make sure to change the games and user classes to extend the proper MVC Controller class
	^ Same goes for Model classes

*/
class fakeMVCController
{
	//MVC: Models; MVC framework will likely create $this->User and $this->Game
	public $Game;
	public $User;
	public $load;
	
	//MVC: Constructor for children to safely calll parent::__construct()
	public function __construct()
	{
		//MVC: makes CodeIgniter style $this->load->model() available to my controllers
		$this->load = new modelLoader($this);
	}
	
	//MVC: Simulates some of the Routing functionality of a framework
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


//Loads a mode CodeIgniter style
class modelLoader
{
	public $obj;
	
	//Receives fakeMVC object so that this class can update $Game and $User
	public function __construct($obj)
	{
		$this->obj = $obj;
	}
	//MVC: Loads Models using CodeIgniter Style $this->load->model();
	public function model($model)
	{
		require_once($model.".php");
		$this->obj->$model = new $model();
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