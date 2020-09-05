<?php
namespace Routex\http;

use Routex\Route;
use Routex\Exception\httpRequestException;

class Request
{
	/**
	* http request method
	*
	* @var String,
	*/
	private $method;

	/**
	* server request uri..
	*
	* @var Object
	*/
	private $requestUri;

	/**
	 * requested Object
	 *
	 * @var $requestObj
	 */
	public $requestedObj;


	/**
	 * initialise checking for requested uri and 
	 * set requested object for response generation
	 */
	public function __construct()
	{
		$this->method = $_SERVER['REQUEST_METHOD'];

		$this->requestUri = trim($_SERVER['REQUEST_URI'],"/");

		$this->checkUriCredentials($this->requestUri);
	}

	/**
	 * set requested object
	 * 
	 * @param 	object 	$route_obj
	 */
	private function setRequest($route_obj)
	{
		$this->requestedObj = $route_obj;
	}

	/**
	 * check requested uri credentials
	 *
	 * @param 	string 		$request_uri
	 */
	private function checkUriCredentials(string $request_uri)
	{
		if ($this->method === "GET") {
			// if Url has "?" parse as normal get request..
			if ($path = $this->hasQuery($request_uri)) {
				$route_obj = $this->SearchInRoutes(trim($path,"/"),Route::urls('get'));
				is_object($route_obj) ? $this->setRequest($route_obj) : E404();
			}
			// if Url does not have "?" search in defined path in application..
			elseif ($route_obj = $this->ConstructCleanUrl($request_uri,Route::urls('get'))) {
				$this->setRequest($route_obj);
			}
			// if similar url not found it means either requested url has url encoded data.
			// or wrong url..
			else{
				$route_obj = $this->decodeAndCheckInRoutes(Route::urls('get'));
				is_object($route_obj) ? $this->setRequest($route_obj) : E404();
			}

		}elseif ($this->method === "POST") {
			if ($route_obj = $this->SearchInRoutes($request_uri,Route::urls('post'))) {
				$this->setRequest($route_obj);
			}else{
				E404();
			}
		}
	}

	/**
	 * if requested Url has key=value pair itself..
	 * return path of the url..
	 *
	 * @param 	String 		$request_uri
	 *
	 * @return 	string or bool		
	 */
	public function hasQuery(string $request_uri)
	{
		$uri = parse_url($request_uri);

		$path = $this->isHome($uri);
		
		return isset($uri['query']) ? $path : false;
	}


	/**
	 * search in authenticated urls..
	 * if match found return respected Object
	 *
	 * @param 	string 		$path
	 * @param  	array 		$routes
	 * @return 	object 		$route_obj
	 */
	private function SearchInRoutes(string $path,array $routes)
	{
		foreach ($routes as $route_obj) {
			
			if($path === $route_obj->url){
				return $route_obj;
			}

		}
	}

	/**
	 * check if requested url conflicts with application Urls..
	 * it's a rare condition, let ,
	 * 1st url "home/{key}", 2nd url is home/ins/{key}
	 * suppose if requested url is "home/ins"
	 * here, for 1st url ins is key name,
	 * but for 2nd url it's part of url itself..
	 * in this case, we are throughing an error to stop execution..
	 *
	 * @param 	string 		$url
	 * @param 	array 		$app_routes
	 * @return 	Object 		$route_obj
	 */
	private function ConstructCleanUrl(string $request_uri,array $app_routes)
	{

		if ($route_obj = $this->SearchInRoutes($request_uri,$app_routes)) {

			$uri = $this->doubleCheck($request_uri);
	
			if ($uri_obj = $this->SearchInRoutes($uri,$app_routes)) {
				$this->IsAnyConflict($uri_obj);
			}

			// update super global arrays
			$this->globalArrayUpdate($route_obj);
			return $route_obj;
		}
	}

	/**
	 * uri to check conflicts
	 *
	 * @param 	string 		$request_uri
	 */
	private function doubleCheck($request_uri)
	{
		$uri = explode("/", $request_uri);
		array_pop($uri);
		return implode("/", $uri);
	}

	/**
	 * throw Error if conflict occurs
	 *
	 * @param 	Object 		$route_obj
	 */
	private function IsAnyConflict($route_obj)
	{
		// check for queryKeys available in Object
		if (count($route_obj->queryKeys) > 0 ) {
			throw new httpRequestException("URL conflict occurs!");
		}
	}

	/**
	 * parse data from URL if method is GET
	 * and update global $_GET and $_REQUEST
	 * 
	 * @param 	$route_obj 		Object
	 * @return 	null
	 */
	private function globalArrayUpdate($route_obj)
	{
		$queryValues = $this->queryValues($route_obj->url);

		if ($route_obj->queryKeys) {

			// for each key set corresponding value..
			foreach ($route_obj->queryKeys as $key => $queryKey) {

				$value = isset($queryValues[$key]) ? $queryValues[$key] : null;

				$_GET[$queryKey] = $value;

				$_REQUEST[$queryKey] = $value;

			}
		}
	}

	/**
	 * prase URL and return values present in Url..
	 *
	 * @param 	String 		$url
	 * @return 	Array
	 */
	private function queryValues(string $url){

		$queryString = substr($this->requestUri,strlen($url));
		return (strncmp("/",$queryString,1) == 0) ? explode("/",trim($queryString,"/")) : array();
	}

	/**
	 * match app Routes and request Uri 
	 * if match found push into an array
	 * than retrive absolute path and search in array
	 * 
	 * @param 	Array 		$app_routes
	 * @return 	Object
	 */
	private function decodeAndCheckInRoutes(array $app_routes)
	{
		
		$similarRoutes = $this->fetchSimilarRoutes($app_routes);
		
		if (count($similarRoutes) > 0) {

			$path = $this->getAbsolutePath($similarRoutes);

			if (is_null($path)) {
				E404();
			}

			$route_obj = $this->SearchInRoutes($path, $app_routes);

			if (count($route_obj->queryKeys) > 0 ) {
				$this->globalArrayUpdate($route_obj);
			}

			return $route_obj;
		}else{
			E404();
		}
	}



	/**
	 * Comparing most related application urls with Request Url..
	 * if match found,
	 * apllication urls return in array for further authentication..
	 * 
	 * @param 	Array 		$app_routes
	 * @return 	Array
	 */
	private function fetchSimilarRoutes(array $app_routes)
	{
		$similarRoutes = [];
		
		foreach ($app_routes as $route_obj) {
			
			if ($this->urlCmp($route_obj->url)) {
				array_push($similarRoutes, $route_obj->url);
			}
		}
		return $similarRoutes;
	}


	/**
	 * comparing application url with requested url..
	 * if match found return True else false..
	 *
	 * @param 	string 		$url
	 */
	private function urlCmp(string $url)
	{
		$url = str_replace("/", "\/", $url);
		return preg_match("/".$url."/", $this->requestUri);
	}


	/**
	 * check if uri is home
	 *
	 * @param 	array 		$parse_url
	 * @return 	string 		
	 */
	private function isHome($parse_url)
	{
		return isset($parse_url['path']) ? $parse_url['path'] : "/";
	}
	

	/**
	 * validate requested Url..
	 * return actual path if Url consist data or 
	 * extanded Url written for SEO purpose..
	 * 
	 * @param 	array 		$valid_urls
	 * @return 	string 		$path
	 */
	private function getAbsolutePath(array $valid_urls)
	{
		for ($i=0; $i <= count($valid_urls); $i++) {

			$url_len_map = array_map('strlen',$valid_urls);
			$max_length = max($url_len_map);
			$key = array_search($max_length,$url_len_map);

			if ($path = $this->TrimRequestUri($max_length)) {
				if ($path === $valid_urls[$key]) {
					return $path;
				}
				
				else{
					unset($valid_urls[$key]);
				}
			}
			
			else{
				unset($valid_urls[$key]);
			}
		}

		E404();
	}


	/**
	 * spliting first n of character from requested URL..
	 * for validation..
	 *
	 * @param 	int 	$url_len
	 * @return 	string
	 */
	private function TrimRequestUri(int $url_len){

		$url = substr($this->requestUri,0,$url_len+1);

		return (substr($url,-1) == "/") ? substr($this->requestUri,0,$url_len) : false ;

	}

	/**
	 * get requested object
	 * 
	 * @return object
	 */
	public function methodObj()
	{
		return $this->requestedObj;
	}

}