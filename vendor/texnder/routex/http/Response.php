<?php
namespace Routex\http;

use Routex\http\Request;
use Aditex\src\Container;
use Routex\Exception\httpResponseException;


class Response
{

	/**
	 * httpRequest class Object..
	 *
	 * @var Object..
	 */
	public $httpRequest;

	/**
	 * view file path in application
	 * 
	 * @var string
	 */
	public $viewPath;
	
	public function __construct(Request $request)
	{
		$this->httpRequest = $request->methodObj();
	}

	/**
	 * include file if exist.
	 */
	private function returnView($data)
	{

		if (is_array($data)) {
			 extract($data);
		}

		require_once $this->viewPath;
	}

	/**
	 * get views directory path and
	 * match view file exist in 
	 * directory or not
	 *
	 * @param 	string 		$dirname
	 * @param 	string 		$viewPath
	 * @return 	bool 		true or false
	 */
	private function checkViewFileExist($dirname,$viewPath)
	{	
		$this->viewPath = rtrim($dirname,"/")."/".trim($viewPath,"/").VIEW_FILE_EXT;
		return file_exists($this->viewPath);
	}

	/**
	 * calling controller method specifed in route
	 *
	 * @param 	string 		$controller 
	 * @param 	string 		$controllermethod
	 * @return 	any 		method returned data
	 */
	private function callController($controller, $Method)
	{
		$service = new Container();
		return $service->create($controller)->exec($Method, $_REQUEST);
	}

	/**
	 * get response data for requested url 
	 *
	 * @param 	null
	 * @return 	html page or data
	 */
	public function getResponse()
	{
		// execute middelware class method handler
		// if middelware set..
		if (isset($this->httpRequest->middelware)) {
			$middelware = Container::call($this->httpRequest->middelware)
							->exec('handler');
			
			if ($this->redirectInstance($middelware)) {
				return $this->setLocHeader($middelware->cleanUrl);
			}
		}
		
		if (is_string($this->httpRequest->view)) {
			
			if ($this->checkViewFileExist(APP_VIEW,$this->httpRequest->view)) {
				return $this->returnView($this->httpRequest->data);
			}else{
				
				throw new httpResponseException("View {$this->httpRequest->view} not exist!!");
			}
		}
		
		elseif (is_string($this->httpRequest->controller) && is_string($this->httpRequest->method)) {
			$data = $this->callController($this->httpRequest->controller,$this->httpRequest->method);
			if (is_object($data)) {

				if ($this->redirectInstance($data)) {
					return $this->setLocHeader($data->cleanUrl);
				}
				
				$view = property_exists($data, "path") ? $data->path : null;
				$viewData = property_exists($data, "data") ? $data->data : null;

				if ($this->checkViewFileExist(APP_VIEW,$view)) {

					return $this->returnView($viewData);

				}else{

					return print_r($data);

				}
			}else{
				return print_r($data);
			}
		}else{

			if ($this->redirectInstance($this->httpRequest->callback)) {
				return $this->setLocHeader($this->httpRequest->callback->cleanUrl);
			}

			return print_r($this->httpRequest->callback);
		}
	}

	/**
	 * check for redirect instance
	 *
	 * @param 	mixed 	$data
	 */
	private function redirectInstance($data)
	{
		if ($data instanceof Redirect) {
			return true;
		}
	}

	/**
	 * set location header and redirect it
	 *
	 * @param 	string 	$uri
	 * @return 	header
	 */
	private function setLocHeader($uri)
	{
		return header("Location: ". url($uri));
	}
}