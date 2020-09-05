<?php
namespace sessnex\handler;

use \PDO;

class DatabaseSessionHandler implements \SessionHandlerInterface
{
	/**
	 * PDO instance for database connection
	 *
	 * @var PDO object
	 */
	protected $pdo;

	/**
	 * @var bool
	 */
	protected $useTransactions;

	/**
	 * session expiry time
	 *
	 * @var int
	 */
	protected $expiry;

	/**
	 * database table name..
	 * 
	 * @var string
	 */
	protected $table_sess = 'sessions';

	/**
	 * database colums, unique session id
	 *
	 * @var string
	 */
	protected $col_sid= "sid";

	/**
	 * database colums, expiry time
	 *
	 * @var integer
	 */
	protected $col_expiry= "expiry";

	/**
	 * session data
	 * 
	 * @var mix
	 */
	protected $col_data= "data";

	/**
	 * @var aray
	 */
	protected $unlockStatments= [];

	/**
	 * @var bool
	 */
	protected $collectGarbage =false;
	
	public function __construct(PDO $db, $useTransactions=true)
	{
		$this->pdo = $db;

		// set Error Attribute
		if (PDO::ATTR_ERRMODE !== PDO::ERRMODE_EXCEPTION) {
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
		}

		$this->useTransactions = $useTransactions;

		// ini file runtime confugration for session
		$this->expiry = time() +(int) ini_get('session.gc_maxlifetime');
	}
	

	/**
     * {@inheritdoc}
     */
	public function open($save_path,$name){
		return true;
	}


	/**
     * {@inheritdoc}
     */
	public function read($session_id)
	{
		try{

			if($this->useTransactions){
				// isolation level read commited..
				$this->pdo->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
				$this->pdo->beginTransaction();

			}else{

				$this->unlockStatments[]= $this->getlock($session_id);
			}

			$sql = "SELECT $this->col_expiry, $this->col_data FROM $this->table_sess WHERE $this->col_sid= :sid";
			
			if($this->useTransactions){
				$sql .= ' FOR UPDATE';
			}

			$selectStmt = $this->pdo->prepare($sql);
			$selectStmt->bindParam(':sid',$session_id);
			$selectStmt->execute();
			$result=$selectStmt->fetch(PDO::FETCH_ASSOC);
			
			if($result){

				if($result[$this->col_expiry]< time()){
					return "";
				}

				return $result[$this->col_data];
			}
			
			if($this->useTransactions){
				$this->initializeRecord($session_id,$selectStmt);
			}

			return "";
			
		}catch(PDOException $e){

			if($this->pdo->inTransaction()){
				$this->pdo->rollBack();
			}

			throw $e;
		}
	}
	

	/**
     * {@inheritdoc}
     */
	public function write($session_id,$data)
	{
		
		try{

			$sql= 	"INSERT INTO $this->table_sess ($this->col_sid,$this->col_expiry,$this->col_data) VALUES (:sid,:expiry,:data) ON DUPLICATE KEY UPDATE $this->col_expiry= :expiry , $this->col_data= :data";

			$stmt= $this->pdo->prepare($sql);

			$stmt->bindParam(':sid',$session_id);

			$stmt->bindParam(':expiry',$this->expiry,PDO::PARAM_INT);

			$stmt->bindParam(':data',$data);

			$stmt->execute();

			return true;
		}
		catch(PDOException $e){

			if($this->pdo->inTransaction()){
				$this->pdo->rollBack();
			}

			throw $e;
		}
	}
	

	/**
     * {@inheritdoc}
     */
	public function close()
	{
		if ($this->pdo->inTransaction()) {
			// commit transaction on closing..
			$this->pdo->commit();

		}elseif (count($this->unlockStatments) > 0) {
			// releasing session lock
			while ($unlstmt=array_shift($this->unlockStatments)) {
				$unlstmt->execute();
			}

		}

		// while gc invokes clean older session data
		if ($this->collectGarbage) {

			$sql="DELETE FROM $this->table_sess WHERE $this->col_expiry < :time";

			$stmt=$this->pdo->prepare($sql);

			$stmt->bindValue(':time',time(),PDO::PARAM_INT);

			$stmt->execute();

			$this->collectGarbage= false;

		}

		return true;
	}
	
	/**
     * {@inheritdoc}
     */
	public function destroy($session_id)
	{
		$sql="DELETE FROM $this->table_sess WHERE $this->col_sid = :sid ";

		try{

			$stmt= $this->pdo->prepare($sql);

			$stmt->bindParam(':sid',$session_id);

			$stmt->execute();

		}
		catch(PDOException $e){

			if($this->pdo->inTransaction()){
				$this->pdo->rollBack();
			}

			throw $e;
		}

		return true;
	}
	
	/**
     * {@inheritdoc}
     */
	public function gc($maxlifetime)
	{
		$this->collectGarbage =true;

		return true;	
	}

	/**
	 * lock session key to commit
	 * 
	 * @param 	string 	$session_id
	 * @return 	PDO prepare statement
	 */
	protected function getlock($session_id)
	{
		$stmt = $this->pdo->prepare("SELECT GET_LOCK(:key,50)");
		$stmt->bindValue(":key",$session_id);
		$stmt->execute();
		
		$relstatement = $this->pdo->prepare('DO RELEASE_LOCK(:key)');
		$relstatement->bindValue(':key',$session_id);
		
		return $relstatement;
	}

	/** 
	 * insert new session in database..
	 * 
	 * @param 	string 	$session_id
	 * @param 	string 	$selectStmt
	 */
	protected function initializeRecord($session_id,$selectStmt)
	{

		try{

			$sql="INSERT INTO $this->table_sess ($this->col_sid,$this->col_expiry,$this->col_data) VALUES (:sid,:expiry,:data)";

			$insertstmt= $this->pdo->prepare($sql);

			$insertstmt->bindParam(':sid',$session_id);

			$insertstmt->bindParam(':expiry',$this->expiry,PDO::PARAM_INT);

			$insertstmt->bindValue(':data',"");

			$insertstmt->execute();
		}catch(PDOException $e){
			
			if(0 === strpos($e->getCode(),'23')){

				$selectStmt->execute();

				$result= $selectStmt->fetch(PDO::FETCH_ASSOC);

				if($result){
					return $result[$this->col_data];
				}

				return "";
				
			}

			if($this->pdo->inTransaction()){
				$this->pdo->rollBack();
			}
			
			throw $e;
			
		}
		
	}
	
}
