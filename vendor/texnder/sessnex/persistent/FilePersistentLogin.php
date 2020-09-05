<?php

namespace sessnex\persistent;

use \SessionManager;
use sessnex\handler\FileSessionHandler;
use sessnex\persistent\persistentInterface;
use sessnex\persistent\persistentTrait;

class FilePersistentLogin extends FileSessionHandler implements persistentInterface
{
	/**
	 * session Manager instance
	 * 
	 * @var Object SessionManager::class
	 */
	protected $SessionManager;

	use persistentTrait;


	public function __construct(SessionManager $SessionManager)
	{
		$this->SessionManager = $SessionManager;
		parent::__construct($SessionManager->file->save_path);
	}

	/**
	 * persistent login if user clicks remember me
	 * create new file to save session data
	 *
	 * @return 	null
	 */
	public function persistentLogin()
	{
		static::$user_key = $this->getUserkey($_SESSION[$this->sess_username]);
		$token = $this->generateToken();
		$this->setPersistentCookie($token);
		file_put_contents("$this->path/cookies/".$_SESSION[$this->sess_username]."_".$token,session_encode());
	}

	/**
	 * get user key by encoding username
	 *
	 * @return 	string 	
	 */
	public function getUserkey($username)
	{
		return bin2hex($username);
	}

	/** 
	 * check cookie token file if exist, return file data
	 *
	 * @param 	string 	$storedToken
	 * @return  mix (file session data)
	 */
	public function checkCookieToken($storedToken)
	{

		if ($this->IsCookieTrash($_SESSION[$this->sess_username], $storedToken)) {
			$_SESSION = [];
			$this->deleteCookie();
			$this->destroySession();
			$this->clearAll();
			return;
		}

		$file = "$this->path/cookies/".$_SESSION[$this->sess_username]."_". $storedToken;

		if ($this->getFileStatus($file) && filemtime($file) + (60 * 60 * 24 * $this->SessionManager->file->lifetimeDays) > time()) {
			return true;
		}

	}

	/**
	 * store file with cookie token as file name
	 *
	 * @param 	string 	$token
	 */
	public function storeToken($token)
	{
		file_put_contents("$this->path/cookies/".$_SESSION[$this->sess_username]."_".$token,session_encode());
	}
	
	/**
	 * get file data if exist
	 *
	 * @param 	string 	$storedToken
	 * @return 	mix 	(file data)
	 */
	public function getExistingData($storedToken)
	{
		return (string)@file_get_contents("$this->path/cookies/".$_SESSION[$this->sess_username]."_".$storedToken);
	}


	/**
	 * login using cookie token after validation
	 *
	 * @param 	string 	$storedToken
	 */
	public function cookieLogin($storedToken)
	{	
		$data = $this->getExistingData($storedToken);
		session_decode($data);
		$this->TrashToken($data, $storedToken);
		session_regenerate_id(true);
		$_SESSION[$this->sess_cookie_auth] = true;
		$newToken = $this->generateToken();
		$this->storeToken($newToken);
		$this->setPersistentCookie($newToken);
	}

	/**
	 * check cookie creadentials and try to log in
	 * if token is valid..
	 *
	 * @param 	string 	$cookie 
	 */
	public function checkcreadentials($cookie)
	{
		if ($storedToken = $this->parseCookie($cookie)) {
			$this->clearOld("$this->path/cookies/".$_SESSION[$this->sess_username]."_*");
			if ($this->checkCookieToken($storedToken)) {
				if (!isset($_SESSION[$this->sess_cookie_auth])) {
					$this->cookieLogin($storedToken);
				}
			}
		}
	}


	/**
	 * get file status is exists or not
	 * 
	 * @param 	string 	$path
	 * @return 	true|false 	
	 */
	public function getFileStatus($path)
	{
		return file_exists($path);
	}

	/**
	 * trash token if used 
	 *
	 * @param 	mix|any 	$data
	 * @param 	string 		$storedToken
	 * @return  true|false 	(on success)
	 */
	public function TrashToken($data, $storedToken)
	{
		
		$trashPath = "$this->path/cookies/trash/".$_SESSION[$this->sess_username]."_";

		$this->clearOld("$trashPath*");
		unlink("$this->path/cookies/".$_SESSION[$this->sess_username]."_".$storedToken);
		$this->clearOld("$this->path/sessions/sess_*");
		return file_put_contents($trashPath.$storedToken, $data);
	}


	/**
	 * check for token file already used
	 * 
	 * @param 	string 		$username
	 * @param 	string 		$storedToken
	 * @return 	true|false 	
	 */
	public function IsCookieTrash($username, $storedToken)
	{
		return $this->getFileStatus("$this->path/cookies/trash/".$username."_". $storedToken);
	}

	/**
	 * delete old files which are expired already
	 * 
	 * @param 	string 		$pattern
	 */
	public function clearOld($pattern)
	{
		foreach (glob($pattern) as  $file) {
			if (filemtime($file) + (60 * 60 * 24 * $this->SessionManager->file->lifetimeDays) < time()) {
                unlink($file);
            }
		}
	}


	/**
	 * deep clean on call
	 * 
	 * @return 	null
	 */
	public function clearAll()
	{
		$arry1 = glob("$this->path/cookies/".$_SESSION[$this->sess_username]."_*");
		$arry2 = glob("$this->path/cookies/trash".$_SESSION[$this->sess_username]."_*");
		$trashArr = array_merge($arry1,$arry2);
		
		foreach ($trashArr as  $file) {
			if (file_exists($file)) {
				unlink($file);
			}
		}
	}


	/**
	 * trash login creadentials while log out
	 *
	 * @return null
	 */
	public function trashLoginCreadentials()
	{
		
		$this->removeCookieToken();

		unset($_SESSION[$this->sess_auth]);
		unset($_SESSION[$this->sess_username]);

		if (isset($_SESSION[$this->sess_cookie_auth])) {
			unset($_SESSION[$this->sess_cookie_auth]);
		}

		$this->destroySession();
		$this->deleteCookie();
	}


	
	/** 
	 * remove cookie token from directory
	 * 
	 * @return null
	 */
	public function removeCookieToken()
	{
		if ($token = $this->parseCookie($this->SessionManager->file->cookie)) {
			if ($this->getFileStatus("$this->path/cookies/".$_SESSION[$this->sess_username]."_".$token)) {
				unlink("$this->path/cookies/".$_SESSION[$this->sess_username]."_".$token);
			}
		}

		if ($this->getFileStatus("$this->path/sessions/sess_".session_id())) {
			unlink("$this->path/sessions/sess_".session_id());
		}
	}

	
}