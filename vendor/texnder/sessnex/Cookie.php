<?php

use bootex\bluff;

class Cookie extends bluff
{
	
	public static function getAccessor()
	{
		return "sessnex\cookie\CookieHandler";
	}
}