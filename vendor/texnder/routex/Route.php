<?php
namespace Routex;

use Routex\Exception\RouteException;
use Routex\method\get;
use Routex\method\post;
use Aditex\src\Container;
use Routex\http\Response;


/**
 * to defined your routes for your application
 * this class provides you two methods get and post
 * use this method to defined authenticated urls
 * for your application..except these url all routes 
 * will throw you 404 error page..
 */
class Route
{

	/**
	* @var array
	*/
	public static $authGetRequests = [];


	/**
	* @var array
	*/
	public static $authPostRequests = [];


	/**
	 * set view directory path and extension 
	 * 
	 * @param 	string 	$viewPath
	 * @param 	file extension 	$extension
	 */
	public function __construct($viewPath,$extension = ".php")
	{
		define('APP_VIEW', $viewPath);
		define('VIEW_FILE_EXT', $extension);
		$this->sendClientResponse();
	}


	/**
	 * set get request urls and corressponding view for them
	 * 
	 * @param 	string 	$url
	 * @param 	string or collable 	$view
	 */
	public static function get(string $url, $view)
	{
		$getObj = new get($url,$view);
		array_push(self::$authGetRequests, $getObj);
		return $getObj;
	}


	/**
	 * new aditex container
	 *
	 * @return object 
	 */
	public function container()
	{
		return new Container();
	}
	
	/**
	 * set post request urls and corressponding view for them
	 * 
	 * @param 	string 	$url
	 * @param 	string or collable 	$view
	 */
	public static function post(string $url, $view)
	{
		$postObj = new post($url,$view);
		array_push(self::$authPostRequests, $postObj);
		return $postObj;
	}

	/**
	 * return application Routes 
	 *
	 * @var $method
	 */
	public static function urls(string $method)
	{
		if (strtoupper($method) === "GET") {
			return self::$authGetRequests;
		}elseif (strtoupper($method) === "POST") {
			return self::$authPostRequests;
		}
	}

	/**
	 * send response data for client request
	 *
	 * @return client response
	 */
	public function sendClientResponse()
	{
		return $this->container()
				->create(Response::class)->get()
				->getResponse();
	}

}