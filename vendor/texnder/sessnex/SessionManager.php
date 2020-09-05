<?php

use sessnex\persistent\persistentTrait;
use sessnex\exception\ConfigurationException;
use sessnex\persistent\FilePersistentLogin;
use sessnex\persistent\DatabasePersistentLogin;


class SessionManager
{
	/**
	 * configuration file 
	 *
	 * @var array
	 */
	public $file;

	/**
	 * driver environment for session data storage
	 *
	 * @var file|database
	 */
	public $driver; 

	/**
	 * csrf token name
	 *
	 * @var string
	 */
	public $token = "_token";

	/**
	 * calling handler instance as per defined in configration file
	 *
	 * @var sessnex\persistent\FilePersistentLogin::class or
	 * 		sessnex\persistent\DatabasePersistentLogin::class
	 */
	public static $instance;

	use persistentTrait;

	/**
	 * initiallise session handler here. and,
	 * start new session
	 * 
	 * @param 	filePath 	$config_file
	 */
	public function __construct($config_file)
	{

		$this->file = (object) include $config_file;

		$this->loginUrl();
		$this->logoutUrl();
		$this->initialize();

		session_set_save_handler(self::$instance, true);
		session_start();

		$_SESSION['_token'] = $this->generateToken();
		
		$this->checkCookieCreadentials();

	}

	/**
	 * initialise session handler..
	 *
	 * @return null
	 */
	private function initialize()
	{
		if ($this->driver = $this->getSessionDriver()) {
			
			if ($this->driver === "file") {

				$this->invokeFileHandler();
					
			}elseif ($this->driver === "database") {
				
				$this->checkDatabaseEnvironment();
				$this->invokeDatabaseHandler();
			}
		}
	}


	/**
	 * check config file for pdo instance.
	 * if driver is database..
	 * 
	 * @return null
	 */
	public function checkDatabaseEnvironment()
	{
		$pdo = $this->getpdo(); 

		if ($pdo instanceof PDO) {
			return ;
		}
		
		throw new ConfigurationException("'connection' should be pdo instance in config file");
		
	}

	/**
	 * invoke file session handler
	 */
	public function invokeFileHandler()
	{
		self::$instance = new FilePersistentLogin($this);
	}

	/**
	 * invoke database session handler
	 */
	public function invokeDatabaseHandler()
	{
		self::$instance = new DatabasePersistentLogin($this);
	}


	/**
	 * set user login url for application..
	 *
	 * @return null
	 */
	public function loginUrl()
	{
		if (isset($this->file->login) && $this->file->login) {
			define("USER_LOGIN", $this->file->login);
		}
	}

	/**
	 * set user logout url for application..
	 *
	 * @return null
	 */
	public function logoutUrl()
	{

		if (isset($this->file->logout) && $this->file->logout) {
			define("USER_LOGOUT", $this->file->logout);
		}
	}

	/**
	 * get pdo instance while using database
	 *
	 * @throws  ConfigurationException on fail
	 * @return  pdo instance..
	 */
	public function getpdo()
	{
		if (!isset($this->file->connection)) {
			throw new ConfigurationException("connection filed cannot be empty in config file, while using database!");
		}		

		return $this->file->connection;
	}

	/**
	 * get directory path where to store session data
	 *
	 * @return 	string (dirPath)
	 */
	public function getPath()
	{
		if (!isset($this->file->save_path)) {
			throw new ConfigurationException("'save_path' is not set in config file");
		}

		return rtrim($this->file->save_path,"/");
	}

	/**
	 * get session driver name from config file
	 *
	 * @return 	string 	file|database
	 */
	protected function getSessionDriver()
	{
		if (!isset($this->file->driver)) {
			throw new ConfigurationException("'driver' must be set in config file");
		}

		return $this->file->driver;
	}


	/**
	 * get session handler instance
	 *
	 * @return 	object 	(handler instance)
	 */
	public function instance()
	{
		return self::$instance;
	}


	/**
	 * check cookie creadentials if cookie is set already
	 *
	 * @return null
	 */
	public function checkCookieCreadentials()
	{

		if ($this->IsPersistent()) {
			if (!isset($_SESSION[$this->sess_auth])) {
				$this->checkForCookie();
			}
		}
	}

	/**
	 * login is persistent or not in application
	 * 
	 * @return 	true|false|null
	 */
	public function IsPersistent()
	{
		return isset($this->file->persistent) ?  $this->file->persistent : null;
	}

	/**
	 * check cookie credeantials if set
	 * if cookie is set, auto log in user
	 * by setting session variable..
	 */
	public function checkForCookie()
	{
		$cookie = isset($this->file->cookie) ? $this->file->cookie : "";

		if ($cookie) {
			if (isset($_COOKIE[$cookie])) {
				self::instance()->checkcreadentials($this->file->cookie);
			}
		}
	}

}