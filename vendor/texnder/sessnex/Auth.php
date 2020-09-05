<?php

use bootex\bluff;
use sessnex\exception\ConfigurationException;

/**
 * its a middelware class which handler method execute before 
 * response delivery to client
 */
class Auth extends bluff
{

	public function handler()
	{

		if (! self::checkLoginStatus()) {
			if (defined("USER_LOGIN")) {
				return redirect(USER_LOGIN);
			}else
				throw new ConfigurationException("this page needs user authorization, login url not set in sessnex config file!");
		}
	}



	/**
	 * set class accessor for bluff
	 */
	public static function getAccessor()
	{
		return 'bootex\Middelware';
	}


}