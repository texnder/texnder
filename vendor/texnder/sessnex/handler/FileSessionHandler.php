<?php

namespace sessnex\handler;

class FileSessionHandler implements \SessionHandlerInterface
{
	/**
	 * session file 
	 * 
	 * @var array
	 */
	protected $file;

	/**
	 * time for session
	 *
	 * @var integer
	 */
	protected $minutes;

	/**
	 * directory path for storage
	 *
	 * @var 	string
	 */
	protected $path;

	public function __construct($path)
	{
		$this->path = $path;
	}
	

	/**
     * {@inheritdoc}
     */
	public function open($save_path,$name)
	{

		if (!is_dir($this->path)) {
			mkdir($this->path,0777);
			mkdir("$this->path/sessions",0777);
			mkdir("$this->path/cookies",0777);
			mkdir("$this->path/cookies/trash",0777);
		}

		return true;
	}


	/**
     * {@inheritdoc}
     */
	public function read($session_id)
	{
		if (file_exists($this->file = "$this->path/sessions/sess_".$session_id)) {
			if (filemtime($this->file) >= $this->subminute(time() +(int) ini_get('session.gc_maxlifetime'))) {
				return (string)@file_get_contents($this->file);
			}
		}

		return "";
	}


	/**
     * {@inheritdoc}
     */
	public function write($session_id,$data)
	{
		return file_put_contents($this->file, $data) === false ? false : true;
	}


	/**
     * {@inheritdoc}
     */
	public function close()
	{
		return true;
	}


	/**
     * {@inheritdoc}
     */
	public function destroy($session_id)
	{
		if (file_exists($this->file)) {
            unlink($this->file);
        }

		return true;
	}


	/**
     * {@inheritdoc}
     */
	public function gc($lifetime)
	{
		foreach (glob("$this->path/sessions/sess_*") as $file) {
            if (filemtime($file) + $lifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }
	    
		return true;
	}

	/**
	 * negative value
	 * 
	 * @param 	integer 	$value
	 * @return 	integer
	 */
	protected function subminute($value)
	{
		return -1 * $value;
	}

}	



