<?php 

namespace Routex\http;

use Routex\Exception\httpRedirectException;

class Redirect
{
	/**
	 * redirect to this..
	 *
	 * @var 	string
	 */
	public $to;

	/**
	 * redirect to this uri
	 *
	 * @var 	string
	 */
	public $cleanUrl;

	/** 
	 * page headers for redirecting page
	 *
	 * @var 	array
	 */
	protected $headers;
	
	/** 
	 * client response status 
	 *
	 * @var 	integer
	 */
	protected $status;

	/** 
	 * http or https
	 *
	 * @var 	bool|null
	 */
	protected $secure;

	/**
	 * container for all redirective urls
	 *
	 * @var 	array
	 */
	public static $redirectContainer = [];
	

	public function __construct($to, $headers = [], $status = 302, $secure = null)
	{
		// TODO: add header managing functions
		$this->to = $to;
		$this->status = $status;
		$this->secure = $secure;
		$this->headers = $headers;
		$this->redirect();
	}

	/**
	 * redirect url if another host server
	 * 
	 * else check Uri authentication  for redirecting there
	 *
	 * @return 	redirecting header
	 */
	private function Redirect()
	{
		if (! $this->thisServer()) {
			return header('Location: '. $this->to);
		}
	}

	/** 
	 * check url is of same server?
	 *
	 * @return bool
	 */
	private function thisServer()
	{
		$urlConfig = parse_url($this->to);

		// if any scheme set..
		if (isset($urlConfig['scheme'])) {
			$this->secure = $urlConfig['scheme'];
		}

		if (isset($urlConfig['host'])) {
			return ($urlConfig['host'] === $_SERVER['HTTP_HOST']) ? true : false;
		}elseif (isset($urlConfig['path'])) {
			$this->cleanPath($urlConfig['path']);
			return true;
		}
	}

	/**
	 * set clean current and redirecting url
	 *
	 * @param string 	$uri
	 */
	private function cleanPath($uri)
	{
		$this->cleanUrl = trim($uri, "/");
	}

	/**
	 * get redirect container by calling this method
	 * 
	 * @return 	array 
	 */
	public function container()
	{
		return static::$redirectContainer;
	}
}
