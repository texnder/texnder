<?php

namespace sessnex\persistent;

use Cookie;

trait persistentTrait
{
	/**
	 * session veriable for cookie authentication
	 *
	 * @var string
	 */
	public $sess_cookie_auth = "cookie_auth";

	/**
	 * session veriable for username
	 *
	 * @var string
	 */
	public $sess_username = "username";

	/**
	 * session veriable for authentication
	 *
	 * @var string
	 */
	public $sess_auth = "authenticated";

	/**
	 * user unique key
	 *
	 * @var string
	 */
	public static $user_key;


	/**
	 * destroy session by, internal function
	 */
	public function destroySession()
	{
		session_destroy();
	}

	/**
	 * genrate random token
	 * 
	 * @return string
	 */
	public function generateToken()
	{
		return bin2hex(openssl_random_pseudo_bytes(16));
	}

	/**
	 * delete cookie when no longer use
	 *
	 * @return null
	 */
	public function deleteCookie()
	{
		$params= session_get_cookie_params();
		Cookie::set(session_name(), '', time()-86400, $params['path'],$params['domain'],$params['secure'],$params['httponly']);
		
		if (Cookie::get($this->SessionManager->file->cookie)) {
			Cookie::set($this->SessionManager->file->cookie,"",time() - 86400);
		}
	}

	/**
	 * set peristent cookie in users device
	 *
	 * @return null
	 */
	public function setPersistentCookie($token)
	{
		$merged = str_split($token);
		array_splice($merged, hexdec($merged[rand(0,31)]),0,static::$user_key);
		$merged=implode("", $merged);
	    $token = $_SESSION[$this->sess_username] .'|'. $merged;
	    Cookie::set($this->SessionManager->file->cookie,$token,time() + ($this->SessionManager->file->lifetimeDays * 60 * 60 * 24));
	}

	/**
	 * Parse client side cookie for persistent login
	 *
	 * @return string|false (saved token)
	 */
	public function parseCookie($name)
	{
		if ($cookie = Cookie::get($name)) {

			$parts = explode("|", $cookie);
			$_SESSION[$this->sess_username] = $parts[0];
			$token = $parts[1];

			// set user key statically..
			if (static::$user_key = $this->getUserkey($_SESSION[$this->sess_username])) {
			 	return str_replace(static::$user_key, '', $token);
			 }else{
			 	return false;
			 }

		}

	}


}