<?php
/**
* Iq_data handles all sql queries
*
* Using the new PHP implementation class PDO 
* to handle database queries since mysql_query will be 
* deprecated by PHP
*
* @category   iQuestion
* @package    iQuestion-4.1
* @subpackage iq_data
* @author 	  Tien Nguyen <tien@completemr.com>
* @copyright  Copyright (c) 2005-2013 CompleteMR (http://www.completemr.com)
* @license    http://ilinkresearch.com.au
* @version    Release: iQuestion-4.1
* @link		  http://www.php.net/manual/en/book.pdo.php
*/

require_once 'PEAR.php';

class Iq_data  {
	
	private $_connection;
	private $_config = array();
	private $_dbName;
	private $_dbHost;
	private $_dbCon;
    public static $instances = NULL;
	private $_queries = array();
	public $objSurvey;
    /** 
	 * construct class of iq_data
	 * using the default database as ilink
	 *
     * @param array $config configuration array contain
	 *						database driver type
	 *						database host - normally localhost
	 *						database username 
	 *						database password 
     * @param str   $dbName Database Name
     *
     * @return void returns void if successfully executed, otherwise return debug error
     * @throws PDOException  PDO $e->getMessage()
     * @access public
     * @since Method available since Release 4.1
     */	
	
	private function __construct($dbName, $config = null) {
		# If no new config is set, use our normal configuration
		global $conf;
		if (!isset($conf)) {
			include "/www/http/superGlobalConfig.php";
			include_once INCLUDE_PATH . DIRECTORY_SEPARATOR . "config_class.php";
			$conf 	    	= new Config4_1();
		}
		$this->_dbName 	= $dbName;
        require_once ILINK_PATH."/utilities/MYSQLPASS.php";
		include_once $conf->includeCheck("iq_functions.php");
		include_once $conf->includeCheck("iq_mail.php");

		global $MySQLuser, $MySQLpass, $MySQLhost;
			
        try {	
			$this->_connection = new PDO("mysql:host={$MySQLhost};dbname={$dbName}", 
											$MySQLuser,
											$MySQLpass,
											array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
													PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
											)
										);
	
			$this->_connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
			$this->_connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {         
            self::debug('mysql', "QUERY:" . $e->getMessage());
        }
    }
	
	/**
	 * Initiating the Singleton instances
	 *
	 */
	
    public static function getInstance($dbName) {
        if (!isset(self::$instances[$dbName])) {
            $className = __CLASS__;
            self::$instances[$dbName] = new $className($dbName);
        }
        return self::$instances[$dbName];
    }	
	
    /** 
	 * default debug function - this is your best friend
	 * returns green debug messages accepting mix types of inputs
	 * with the ability to send emails to bugs@completemr.com
	 *
     * @param str $config type of error/debug 
     * @param mix $value ebugging message 
     * @return void returns void if successfully executed, otherwise send email to bugs@completemr.com
     * @access public
	 * @static static
     * @since Method available since forever
     */		
	static public function debug($type, $value) {
		global $arrDEBUG, $objSurvey;		
		
		$arrDEBUG[$type][] = "\n {$value}";
		
		$strMsg = "Debug {$type} : {$value}";
		$arrTestStatus = array("testing", "suspended", "suspend");

		if (!isset($objSurvey) && $type != 'mysql') {
			//echo "<pre><font color=\"green\">Debug Message: {$value}</font></pre>";
		} elseif (!in_array($objSurvey->status, $arrTestStatus) ) {
			/*echo $value;
			$objSurvey->log(print_r(debug_backtrace(),1));
			$objSurvey->log($strMsg);*/                        
			sendDebugMail($strMsg);
		} elseif ($objSurvey->enableDebug != 2 ) {
			//echo "<pre><font color=\"green\">Debug Message: {$value}</font></pre>";
		}
		
	}
	
    /** 
	 * simple mysql query execution
	 * returns results when require
	 *
     * @param str $dbQuery mysql query string
     * @param str $dbName database name
     *
     * @return void returns void if successfully executed, otherwise send email to bugs@completemr.com
     * @access public
     * @since Method available since 4.1
	 * @throws PDOException  PDO $e->getMessage()
	 
     */		
	public function dbQuery($dbQuery, $dbName, $values) {
		$this->_queries[]  = $dbQuery;	
		$dbCon = self::getInstance($dbName);
		try {
			$fetch = $dbCon->_connection->prepare($dbQuery);
			$fetch->execute($values);			
			if (!is_object($fetch)) {
				ob_start();
				debug_print_backtrace();
				$trace = ob_get_contents();
				ob_end_clean();
				@file_put_contents('/var/log/ilink/iq-4.1-db-error.log', $trace);
			}
			if($fetch->rowCount() == 1) {
				return current($fetch->fetch());
			} else {
				return $fetch->fetch();
			}		
		}  catch(PDOException $e) {         
            self::debug('mysql', "QUERY:" . $e->getMessage() . " <br /> Attempted: " . $dbQuery);
        }
	}
	

    /** 
	 * simple mysql query execution
	 * returns number affected rows when acquire
	 *
     * @param str $dbQuery mysql query string
     * @param str $dbName database name
     *
     * @return int affected rows
     * @access public
     * @since Method available since 4.1
	 * @throws PDOException  PDO $e->getMessage()
	 * @todo find ways to "quote($dbQuery)"
     */		
	public function dbUpdate($dbQuery, $dbName,$values) {
		$this->_queries[]  = $dbQuery;	
		$dbCon = self::getInstance($dbName);				
		try {
			$fetch = $dbCon->_connection->prepare($dbQuery);
			$fetch->execute($values);
			return $fetch->rowCount();
		} catch(PDOException $e) {         
            self::debug('mysql', "QUERY:" . $e->getMessage() . " <br /> Attempted: " . $dbQuery);
        }
	}

    /** 
	 * simple mysql retrieve function
	 * returns simple array
	 *
     * @param str $dbQuery mysql query string
     * @param str $dbName database name
     *
     * @return array simple array of single row
     * @access public
     * @since Method available since 4.1
	 * @throws PDOException  PDO $e->getMessage()
     */		
	public function dbRetrieve($dbQuery, $dbName, $values) {
		$this->_queries[]  = $dbQuery;	
		$dbCon = self::getInstance($dbName);
			
		try {
			$fetch = $dbCon->_connection->prepare($dbQuery);
			$fetch->execute($values);				
			return $fetch->fetch();
		}  catch(PDOException $e) {         
            self::debug('mysql', "QUERY:" . $e->getMessage() . " <br /> Attempted: " . $dbQuery);
        }
	}	

    /** 
	 * simple mysql retrieve function
	 * returns assoc array
	 *
     * @param str $dbQuery mysql query string
     * @param str $dbName database name
     *
     * @return array simple array of single row
     * @access public
     * @since Method available since 4.1
	 * @throws PDOException  PDO $e->getMessage()
     */		
	public function dbRsRetrieve($dbQuery, $dbName, $values) {
		$this->_queries[]  = $dbQuery;		
		$dbCon = self::getInstance($dbName);	
		try {
			$stmt = $dbCon->_connection->prepare($dbQuery);

			$stmt->execute($values);
			return $stmt;
		} catch(PDOException $e) {	
			//echo $e->getMessage();
            self::debug('mysql', "QUERY:" . $e->getMessage() . " Attempted: " . $dbQuery);
        }
	}		
	
	
	
	function __destruct() {
	#	$dbCon = self::getInstance($dbName);	
	}
}

/** 
 * default debug function - this is your best friend
 * returns green debug messages accepting mix types of inputs
 * with the ability to send emails to bugs@completemr.com
 *
 * @param str $config type of error/debug 
 * @param mix $value ebugging message 
 * @return void returns void if successfully executed, otherwise send email to bugs@completemr.comc
 * @since Method available since forever
 */	
 
function Debug($type, $value = '') {
    $conn = Iq_data::getInstance($dbName);
	return $conn->debug($type, $value);
}
	

/** 
 * simple mysql query execution
 * returns results when require
 *
 * @param str $dbQuery mysql query string
 * @param str $dbName database name
 *
 * @return void returns void if successfully executed, otherwise send email to bugs@completemr.com
 * @access public
 * @since Method available since 4.1
 * @throws PDOException  PDO $e->getMessage()
 */	
	 
function ildb_query($query, $values = array(), $dbName = 'ilink') {
	if(is_string($values))
	{
	$dbName = $values;
    $conn = Iq_data::getInstance($dbName);
	return $conn->dbQuery($query, $dbName,array());
	}
	else {
    $conn = Iq_data::getInstance($dbName);
	return $conn->dbQuery($query, $dbName,$values);	
	}
}

/** 
 * simple mysql query execution
 * returns number affected rows when acquire
 *
 * @param str $dbQuery mysql query string
 * @param str $dbName database name
 *
 * @return int affected rows
 * @access public
 * @since Method available since 4.1
 * @throws PDOException  PDO $e->getMessage()
 */	
 
function ildb_update($dbQuery,$values = array(),$dbName = 'ilink') {
	if(is_string($values))
	{
		$dbName = $values;
		$conn = Iq_data::getInstance($dbName);
		return $conn->dbUpdate($dbQuery, $dbName,array());
	}
	else {
		$conn = Iq_data::getInstance($dbName);
		return $conn->dbUpdate($dbQuery, $dbName,$values);	
	}	
}

/**
 *	alias of ildb_update
 */
function ildb_exec($dbQuery, $values = array(), $dbName = 'ilink') {
	if(is_string($values))
	{
		$dbName = $values;
		$conn = Iq_data::getInstance($dbName);
		return $conn->dbUpdate($dbQuery, $dbName,array());
	}
	else {
		$conn = Iq_data::getInstance($dbName);
		return $conn->dbUpdate($dbQuery, $dbName,$values);	
	}	
}



/** 
 * simple mysql retrieve function
 * returns simple array
 *
 * @param str $dbQuery mysql query string
 * @param str $dbName database name
 * @return array simple array of single row
 * @since Method available since 4.1
 * @throws PDOException  PDO $e->getMessage()
 */		
function ildb_retrieve($dbQuery,$values = array(), $dbName = 'ilink') {
	if(is_string($values))
	{
		$dbName = $values;
		$dbCon = Iq_data::getInstance($dbName);
		return $dbCon->dbRetrieve($dbQuery, $dbName,array());
	}
	else {
		$dbCon = Iq_data::getInstance($dbName);
		return $dbCon->dbRetrieve($dbQuery, $dbName,$values);	
	}	
}


/** 
 * simple mysql retrieve function
 * returns assoc array
 *
 * @param str $dbQuery mysql query string
 * @param str $dbName database name
 * @return array simple array of single row
 * @since Method available since 4.1
 * @throws PDOException  PDO $e->getMessage()
 */
	 
function ildb_rsretrieve($dbQuery,$values = array(),$dbName = 'ilink') {
	if(is_string($values))
	{
		$dbName = $values;
		$conn = Iq_data::getInstance($dbName);
		return $conn->dbRsRetrieve($dbQuery, $dbName, array());
	}
	else {
		$conn = Iq_data::getInstance($dbName);
		return $conn->dbRsRetrieve($dbQuery, $dbName, $values);
	}	
}
