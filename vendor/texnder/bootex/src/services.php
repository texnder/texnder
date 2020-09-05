<?php

namespace bootex;

/**
 * get all directory or subdirectory files namespace recursively 
 *
 * requirement: psr4 namespace convension..
 */
class services
{
	/**
	 * all scaned files, with full qualified names,
	 * Psr4 namespacing..
	 *
	 * @param array
	 */
	public $services = [];

	/**
	 * start compilation..
	 *
	 * @param 	string 		$psr4Path
	 */
	public function __construct(string $psr4Path = '')
	{
		$composerPSR4 = $this->getcomposerPSR4($psr4Path);
		$this->startCompilation($composerPSR4);
	}

	/**
	 * get composer psr4 array
	 * 
	 * @param 	string 	$psr4Path
	 */
	private function getcomposerPSR4($psr4Path)
	{
		$psr4 = $this->truePath($psr4Path);

		if (is_array($psr4)) {
			return $this->mergeDefaultPSR4($psr4);
		}
		
		return $this->psr4();
	}


	/**
	 * set service container
	 *
	 * @param 	$services 	array(ServiceFilesList)
	 * @param 	array
	 */
	private function setServices(array $servicefiles)
	{
		foreach ($servicefiles as $servicefile) {
			if (is_string($servicefile)) {

				$servicefile = str_replace(".php", "", $servicefile);

				if (!array_search($servicefile, $this->services)) {
					array_push($this->services, $servicefile);
				}

			}

			if (is_array($servicefile)) {
				$this->setServices($servicefile);
			}
		}
	}


	/**
	 * listing all files of specific directory and subdirectory,
	 * return full qualified namespace for every class,
	 * for psr4 convention..
	 * 
	 * @param 	$dir 			String(directory Path)
	 * @param 	$namespace		String(parent directory namespace)
	 * @return 	array
	 */
	private function ServiceFilesList(string $namespace,string $dir)
	{
	    $scandir = $this->directoryFiles($dir);

	    $scandir = array_diff($scandir, array('.','..'));

	    // prevent empty ordered elements
	    if (count($scandir) < 1)
	    	return;

	    $class = [];
	    foreach($scandir as $dir_or_file){

	    	if (preg_match("/\.php/", $dir_or_file))
	    		$class[] = ($namespace !== "") ? $namespace."\\".$dir_or_file : $dir_or_file;

	        if(is_dir($dir.'/'.$dir_or_file)){
	        	$class[] = $this->ServiceFilesList($this->generateNamespace($namespace,$dir_or_file),$dir.'/'.$dir_or_file);
	        }
	    }
	    return $class;
	}

	/**
	* get namespace for the file..
	*
	* @param 		$dir 		string(parent directory)
	* @param 		$sub_dir	string(could be directory or file)
	* @return 		String
	*/
	private function generateNamespace($dir,$sub_dir)
	{
		return ($dir !== "") ? $dir."\\".$sub_dir : $sub_dir;
	}

	
	/**
	 * set service container
	 *
	 * @param 	$composerPSR4 	array
	 */
	private function startCompilation($composerPSR4)
	{
		foreach ($composerPSR4 as $namespace => $dir) {
			if (count($dir) > 1) {
				$classes = $this->pushEachFilesInArray(trim($namespace,"\\"), $dir);
				
			}else{
				$classes = $this->ServiceFilesList(trim($namespace,"\\"),$dir[0]);
			}

			$this->setServices($classes);
		}
	}

	/**
	 * if one namespace uses many time for diffrent directory
	 *
	 * @param 	$dir 			String(directory Path)
	 * @param 	$namespace		String(parent directory namespace)
	 * @return 	array
	 */
	private function pushEachFilesInArray($namespace, $dir)
	{ 
		$arry = array();

		foreach ($dir as $path) {

			$arry[] = $this->ServiceFilesList($namespace,$path);
		}

		return $arry;
	}

	
	/**
	 * scan directory for file names..
	 * 
	 * @param 	string 	$directory
	 */
	private function directoryFiles($directory)
	{
		if (is_dir($directory)) {

			return scandir($directory);

		}else

			throw new \Exception("'{$directory}' failed to open dir: No such file or directory");
		
	}

	/**
	 * merge default psr4 file array
	 *
	 * @param 	array 	$composerPSR4
	 * @return 	array
	 */
	private function mergeDefaultPSR4($composerPSR4)
	{
		if (is_array($composerPSR4)) {
			return array_merge($this->psr4(), $composerPSR4);
		}
	}


	/**
	 * return default psr4 file array
	 *
	 * @return 	array
	 */
	private function psr4()
	{
		$psr4 = include __DIR__. "/.." ."/psr4.php";
		return $psr4;
	}


	/**
	 * check, Is path exist 
	 * 
	 * @param 	string 	$psr4Path
	 * @return 	array or null 	
	 */
	private function truePath($psr4Path)
	{
		if (file_exists($psr4Path)) {
			$psr4 = include "{$psr4Path}";
			return $psr4;
		}
	}
}
