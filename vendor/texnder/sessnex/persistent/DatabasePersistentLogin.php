<?php
namespace sessnex\persistent;

use \SessionManager;
use \PDO;
use sessnex\persistent\persistentInterface;
use sessnex\persistent\persistentTrait;
use sessnex\handler\DatabaseSessionHandler;


class DatabasePersistentLogin extends DatabaseSessionHandler implements persistentInterface
{
	/**
	 * session Manager instance
	 * 
	 * @var Object SessionManager::class
	 */
	protected $SessionManager;

	/**
	 * cookie token expiry time
	 *
	 * @var unix time
	 */
	protected $token_expiry;

	/**
	 * required users table name for verification
	 * 
	 * @var string
	 */
	protected $table_user = "users";

	/**
	 * users column for username
	 * 
	 * @var string
	 */
	protected $col_user = "username";

	/**
	 * users column for unique user key
	 * 
	 * @var string
	 */
	protected $col_ukey = "user_key";

	/**
	 * autologin table in database to
	 * store cookies token
	 * 
	 * @var string
	 */
	protected $table_autologin = "autologin";

	/**
	 * autologin column for cookie token
	 * 
	 * @var string
	 */
	protected $col_token = "token";

	/**
	 * autologin column for cookie expiry
	 * 
	 * @var string
	 */
	protected $col_expiry = "expiry";

	/**
	 * autologin column for session data
	 * 
	 * @var string
	 */
	protected $col_data = "data";

	/**
	 * autologin column for check
	 * whether, token is used or not
	 * 
	 * @var string
	 */
	protected $col_used = "used";

	use persistentTrait;

	/**
	 * construct session handler constructor
	 * and set parameters to intialise
	 *
	 * @param 	Object 	SessionManger::class
	 */
	public function __construct(SessionManager $SessionManager)
	{
		$this->SessionManager = $SessionManager;

		$this->pdo = $this->SessionManager->file->connection;

		$this->table_sess = $this->SessionManager->file->table;

		parent::__construct($this->pdo);
		

		if (PDO::ATTR_ERRMODE !== PDO::ERRMODE_EXCEPTION) 
		{
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
		}

		$this->token_expiry = time() + ($this->SessionManager->file->lifetimeDays * 60 * 60 * 24);
	}


	/**
	 * update session data in database for persistent
	 * login using cookie token
	 *
	 * @param 	string 	$session_id
	 * @param 	mix 	$data
	 * @return 	bool(true) 
	 */
	public function write($session_id,$data)
	{
		parent::write($session_id,$data);

		if (isset($_SESSION[$this->sess_auth]) && $token = $this->parseCookie($this->SessionManager->file->cookie)) {
			$sql = "UPDATE $this->table_autologin SET $this->col_data = :data,$this->col_expiry = :expiry WHERE $this->col_token = :token AND $this->col_used = :used ";
			$stmt= $this->pdo->prepare($sql);
			$stmt->bindParam(':data',$data);
			$stmt->bindParam(':expiry',$this->token_expiry);
			$stmt->bindParam(':token',$token);
			$stmt->bindValue(':used',false,PDO::PARAM_BOOL);
			$stmt->execute();

		}

		return true;
		
	}

	/**
	 * persistent login if user clicks remember me
	 *
	 * @return 	null
	 */
	public function persistentLogin()
	{
		static::$user_key = $this->getUserkey();
		$token = $this->generateToken();
		$this->setPersistentCookie($token);
		$this->storeToken($token);
	}

	/**
	 * trash login creadentials while log out
	 *
	 * @return null
	 */
	public function trashLoginCreadentials()
	{
		$this->clearOld($this->table_sess);
		$this->removeCookieToken();

		unset($_SESSION[$this->sess_auth]);
		unset($_SESSION[$this->sess_username]);

		if (isset($_SESSION[$this->sess_cookie_auth])) {
			unset($_SESSION[$this->sess_cookie_auth]);
		}
		
		$this->destroySession();
		$this->deleteCookie();
	}

	/**
	 * delete cookie token from database
	 *
	 * @return 	null
	 */
	public function removeCookieToken()
	{
		if ($token = $this->parseCookie($this->SessionManager->file->cookie)) {
			$sql= "DELETE FROM $this->table_autologin WHERE $this->col_ukey = :key AND $this->col_token= :token";
			$stmt= $this->pdo->prepare($sql);
			$stmt->bindParam(':key',static::$user_key);
			$stmt->bindParam(':token',$token);
			$stmt->execute();
		}

		$sql= "DELETE FROM $this->table_sess WHERE $this->col_sid= :sid";
		$stmt= $this->pdo->prepare($sql);
		$stmt->bindValue(':sid',session_id());
		$stmt->execute();

	}

	/**
	 * check cookie creadentials and log in
	 *
	 * @return 	null
	 */
	public function checkcreadentials($cookie)
	{

		if ($storedToken= $this->parseCookie($cookie)) {
			 	$this->clearOld($this->table_autologin);
			 	if ($this->checkCookieToken($storedToken,false)) {
					$this->cookieLogin($storedToken);
					$newToken= $this->generateToken();
					$this->storeToken($newToken);
					$this->setPersistentCookie($newToken);
				}elseif ($this->checkCookieToken($storedToken,true)) {
					$_SESSION = [];
					$this->deleteAll(true);
					$this->deleteCookie();
					$this->destroySession();
			 	}
			}
	}

	/**
	 * clear expiry data from database tables
	 *
	 * @param 	string 	$table
	 */
	protected function clearOld($table)
	{
		$sql= "DELETE FROM $table WHERE  $this->col_expiry < :expiry ";
		$stmt= $this->pdo->prepare($sql);
		$stmt->bindValue(':expiry', time());
		$stmt->execute();
	}


	/**
	 * delete unwanted rows from database tables
	 *
	 * @param 	bool 	$used
	 */
	protected function deleteAll($used = false)
	{
		$sql= "DELETE FROM $this->table_autologin WHERE $this->col_ukey = :key ";
		if (!$used) {
			$sql .=  " AND $this->col_used = :used";
		}
		$stmt= $this->pdo->prepare($sql);
		$stmt->bindParam(':key',static::$user_key);
		!$used ? $stmt->bindValue(':used', true) : null;
		$stmt->execute();
	}

	/**
	 * save cookie token and data in database autologin table
	 *
	 * @param 	string 	$token
	 */
	public function storeToken($token)
	{
		
		try{
			$sql = "INSERT INTO $this->table_autologin ($this->col_ukey,$this->col_token,$this->col_expiry,$this->col_data) VALUES (:key,:token,:expiry,:data)";
			$stmt= $this->pdo->prepare($sql);
			$stmt->bindParam(':key',static::$user_key);
			$stmt->bindParam(':token',$token);
			$stmt->bindParam(':expiry',$this->token_expiry);
			$stmt->bindValue(':data',session_encode(),PDO::PARAM_STR);
			$stmt->execute();
		}
		catch(PDOException $e){
			if($this->pdo->inTransaction()){
				$this->pdo->rollBack();
			}
			throw $e;
		}
	}

	/**
	 * login using cookie
	 *
	 * @param 	string 	$token
	 */
	public function cookieLogin($token)
	{
		try{
			$this->getExistingData($token);
			$sql= "UPDATE $this->table_autologin SET $this->col_used = :used WHERE $this->col_ukey = :key AND $this->col_token= :token";
			$stmt= $this->pdo->prepare($sql);
			$stmt->bindParam(':key',static::$user_key);
			$stmt->bindParam(':token',$token);
			$stmt->bindValue(':used',true,PDO::PARAM_BOOL);
			$stmt->execute();

			session_regenerate_id(true);

			$_SESSION[$this->sess_cookie_auth]=true;
			
		}catch(PDOException $e){
			    if($this->pdo->inTransaction()){
			    	$this->pdo->rollBack();
			    }
			    throw $e;
		}
	}

	/**
	 * get existing session data for cookie token
	 *
	 * @param 	string 	$storedToken
	 */
	public function getExistingData($storedToken)
	{
		$sql="SELECT $this->col_data FROM $this->table_autologin WHERE $this->col_ukey= :key AND $this->col_token = :token AND $this->col_used = :used";
		$stmt= $this->pdo->prepare($sql);
		$stmt->bindParam(':key',static::$user_key);
		$stmt->bindParam(':token',$storedToken);
		$stmt->bindValue(':used',false,PDO::PARAM_BOOL);
		$stmt->execute();

		if($data = $stmt->fetchColumn())
		{
			session_decode($data);
		}
		$stmt->closeCursor();
	}


	/**
	 * validate cookie token if exist
	 *
	 * @param 	string 	$token
	 * @param 	bool 	$used
	 * @return 	true|false
	 */
	protected function checkCookieToken($token,$used)
	{
		$sql= "SELECT COUNT(*) FROM $this->table_autologin WHERE $this->col_ukey= :key AND $this->col_token= :token 
		AND $this->col_used= :used";
		$stmt= $this->pdo->prepare($sql);
		$stmt->bindParam(':key',static::$user_key);
		$stmt->bindParam(':token',$token);
		$stmt->bindParam(':used',$used,PDO::PARAM_BOOL);
		$stmt->execute();
		if ($stmt->fetchColumn()>0) {
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * get user key from users table
	 *
	 * @return 	string 	
	 */
	public function getUserkey()
	{
		$sql= "SELECT $this->col_ukey FROM $this->table_user WHERE $this->col_user = :username";
		$stmt= $this->pdo->prepare($sql);
		$stmt->bindParam(':username',$_SESSION[$this->sess_username]);
		$stmt->execute();
		return $stmt->fetchColumn();
	}
	
}