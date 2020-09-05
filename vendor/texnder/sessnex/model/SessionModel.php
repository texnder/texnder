<?php

/**
 * this is database model for session classes
 * all these tables must be available before using 
 * database as session driver, developer can add other database
 * columns too. for there futher use according to architecture of there application
 */

class SessionModel
{
	/**
	 * to migrate tables we required pdo connection
	 *
	 * @var \PDO::class
	 */
	public $pdo;

	/**
	 * database queries
	 *
	 * @var array
	 */
	public $queries = [];
		

	function __construct(PDO $pdo)
	{
		$this->pdo = $pdo; 

	}

	/**
	 * migrate tables in database
	 */
	public function migrate()
	{
		foreach ($this->queries as  $sql) {
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute();
		}
	}

	/**
	 * sessions table and there required fields to 
	 * use database as session driver
	 *
	 * @param 	string 	$table 
	 * @return 	$this
	 */
	public function sessions($table = 'sessions')
	{
		$this->queries[] = "CREATE TABLE ".$table." (
			sid VARCHAR(40)  PRIMARY KEY,
			expiry INT(10) UNSIGNED NOT NULL,
			data TEXT NOT NULL
		)";

		return $this;
	}

	/**
	 * autologin table and there required fields to 
	 * persistent login using cookie
	 *
	 * @param 	string 	$table 
	 * @return 	$this
	 */
	public function autologin($table = 'autologin')
	{
		$this->queries[] = "CREATE TABLE ".$table." (
			user_key VARCHAR(8)  NOT NULL,
			token VARCHAR(32)  NOT NULL,
			data TEXT NOT NULL,
			expiry INT(10) UNSIGNED NOT NULL,
			used  BOOLEAN DEFAULT 0,
			created_at TIMESTAMP,
			CONSTRAINT PRIMARY KEY (user_key,token)
		)";

		return $this;
	}

	/**
	 * users table and there required fields to 
	 * save user authentication data
	 *
	 * @param 	string 	$table 
	 * @return 	$this
	 */
	public function users($table = 'users')
	{
		$this->queries[] = "CREATE TABLE ".$table." (
			id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_key VARCHAR(8)  NOT NULL  UNIQUE,
			username VARCHAR(255)  NOT NULL UNIQUE,
			password VARCHAR(128)  NOT NULL
		)";

		return $this;
	}

	/**
	 * to drop any of the table from database use this method
	 * NOTICE: double check before dropping any table from database
	 * table data could not be retrive once you drop it..
	 *
	 * @param 	multiple string 	$tables 
	 * @return 	$this
	 */
	public function rollback(...$tables)
	{
		foreach ($tables as $table) {
			$stmt = $this->pdo->prepare("DROP TABLE ".$table."");
			$stmt->execute();
		}
	}
}