<?php
namespace bootex;

use sessnex\persistent\persistentTrait;
use SessionManager;

class Middelware
{
	
	use persistentTrait;
	
	/** 
	 * check user is logged in or authenticated
	 *
	 * @return  true|username
	 */	
	public function user($username = null)
	{
		if (!$username) {
			if (isset($_SESSION[$this->sess_auth]) && $_SESSION[$this->sess_auth]) {
				return $_SESSION[$this->sess_username];
			}
		}else{
			if (isset($_SESSION[$this->sess_username]) && $_SESSION[$this->sess_username] === $username) {
					return isset($_SESSION[$this->sess_auth]) ? $_SESSION[$this->sess_auth] : null;
			}
		}
	}


	/**
	 * login user using this method to set authentication veriables
	 *
	 * @return null
	 */
	public function login($username, $remember = false)
	{
		if (!isset($_SESSION[$this->sess_auth])) {
			$_SESSION[$this->sess_auth] = true;
			$_SESSION[$this->sess_username] = $username;
			if ($remember) {
				SessionManager::instance()->persistentLogin();
			}
		}
	}

	/**
	 * logout user if already logged in
	 */
	public function logout()
	{
		if (isset($_SESSION[$this->sess_auth])) {	
			SessionManager::instance()->trashLoginCreadentials();
		}
	}

	/**
	 * check login status weather it already logged in or not
	 */
	public function checkLoginStatus()
	{
		if (isset($_SESSION[$this->sess_auth]) ||  isset($_SESSION[$this->sess_cookie_auth])) {
			return true;
		}
	}

}