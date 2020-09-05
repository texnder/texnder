<?php

namespace sessnex\cookie;

/**
 * basic cookie handler to get and set
 * cookies for web Application
 */
class CookieHandler
{
	/**
	 * cookie name 
	 *
	 * @var string 
	 */
	protected $cookie = 'texnder_session';

	/**
	 * cookie expiry time 
	 *
	 * @var integer (unix timestamp)
	 */
	protected $expiry;

	/**
	 * uri path for cookie 
	 *
	 * @var string 
	 */
	protected $path = "/";

	/**
	 * cookie domain name
	 *
	 * @var null|string
	 */
	protected $domain = null;

	/**
	 * http or https protocol
	 *
	 * @var bool
	 */
	protected $secure = false;

	/**
	 * cookie set through script or not
	 *
	 * @var bool
	 */
	protected $http_only = true;

	/**
	 * mathod to set new cookie for application
	 *
	 * @param 	string 		$name
	 * @param 	mix 		$content
	 * @param 	unix time 	$expiry
	 * @param 	string 		$path
	 * @param 	bool 		$secure
	 * @param 	bool 		$http_only
	 */
	public function set($name, $content, $expiry, $path = "/", $domain = null, $secure = false, $http_only = true)
	{
		$this->cookie = $name;
		$this->content = $content;
		$this->expiry = $expiry;
		$this->path = $path;
		$this->domain = $domain;
		$this->secure = $secure;
		$this->http_only = $http_only;
		$this->setcookie();
	}

	/** 
	 * set new cookie
	 *
	 * @return null
	 */
	public function setCookie()
	{
		setcookie($this->cookie,$this->content,$this->expiry,$this->path,$this->domain,$this->secure,$this->http_only);
	}

	/**
	 * get existing cookie if set
	 *
	 * @param 	string 	$name
	 * @return 	existing cookie
	 */
	public function get($name)
	{
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : "";
	}
}