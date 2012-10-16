<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Base
 * @author     Ibidem Team
 * @copyright  (c) 2012 Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class SQLDatabase extends \app\Instantiatable
	implements \mjolnir\types\SQLDatabase
{
	/**
	 * @var array
	 */
	protected static $instances = array();

	/**
	 * @var boolean
	 */
	protected $cleanup = false;
	
	/**
	 * @var int
	 */
	protected $savepoint = 0;
	
	/**
	 * @var string 
	 */
	protected $dialect_default;
	
	/**
	 * @var string 
	 */
	protected $dialect_target;
	
	/**
	 * @var \PDO
	 */
	protected $dbh;
	
	/**
	 * @return \app\SQL
	 */
	static function instance($database = 'default')
	{
		if ( ! isset(static::$instances[$database]))
		{
			static::$instances[$database] = parent::instance();
			try 
			{
				// attempt to load configuration
				$pdo = \app\CFS::config('mjolnir/database');
				$pdo = $pdo['databases'][$database];
				if (empty($pdo))
				{
					$exception = new \app\Exception_NotFound
						('Missing database configuration.');
						
					throw $exception->set_title('Database Error');
				}
				// setup database handle
				$dbh = static::$instances[$database]->dbh = new \PDO
					(
						$pdo['connection']['dsn'], 
						$pdo['connection']['username'], 
						$pdo['connection']['password']
					);
				// set error mode
				$dbh->setAttribute
					(
						\PDO::ATTR_ERRMODE, 
						\PDO::ERRMODE_EXCEPTION 
					);
				// default SQL flavor
				static::$instances[$database]->dialect_default = $pdo['dialect_default'];
				static::$instances[$database]->dialect_target = $pdo['dialect_target'];
				
				return static::$instances[$database];
			}
			catch (\PDOException $e)
			{
				throw new \app\Exception
					(
						$e->getMessage(), # message
						'Database Error' # title
					);
			}
		}
		else # is set
		{
			return static::$instances[$database];
		}
	}
	
	/**
	 * Cleanup
	 */	
	function __destruct()
	{
		$this->dbh = null;
	}
	
	/**
	 * @param string statement
	 * @param string lang
	 * @return boolean requires translation?
	 */
	protected function requires_translation($statement, $lang)
	{
		return ($lang && $lang !== $this->dialect_target) 
			|| ($this->dialect_default !== $this->dialect_target)
			|| $statement === null;
	}
	
	/**
	 * @return string
	 */
	protected static function normalize_key($key)
	{
		// convert :: to :
		// convert \ to /
		// remove trailing /
		return \trim
			(
				\str_replace
					(
						'::', 
						':', 
						\str_replace('\\', '/', $key)
					), 
				'/'
			);
	}
	
	/**
	 * @param string key
	 * @return \mjolnir\types\SQLStatement
	 */
	protected function run_stored_statement($key)
	{
		$key = static::normalize_key($key);
		$splitter = \strpos($key, \mjolnir\types\SQLDatabase::KEYSPLIT);
		$file = \substr($key, 0, $splitter);
		$key = \substr($key, $splitter+1);
		$statements = \app\CFS::config('sql/'.$this->dialect_target.'/'.$file);
		if ( ! isset($statements[$key]))
		{
			$file = \mjolnir\cfs\CFSCompatible::CNFDIR
				. '/sql/'.$this->dialect_target.'/'.$file;
			
			throw new \app\Exception_NotFound
				(
					'Missing key ['.$key.'] in ['.$file.'].', # message
					'Database Translation Error' # title
				);
		}
		return $statements[$key]($this->dbh);
	}
	
	/**
	 * Configure database before executing statements.
	 */
	protected function pre_execution_configuration()
	{
		$base_config = \app\CFS::config('mjolnir/base');
		// set charset
		$this->dbh->exec("SET CHARACTER SET '{$base_config['charset']}'");
		$this->dbh->exec("SET NAMES '{$base_config['charset']}'");
		// set timezone
		$now = new \DateTime();  
		$mins = $now->getOffset() / 60;  
		$sgn = ($mins < 0 ? -1 : 1);  
		$mins = \abs($mins);  
		$hrs = \floor($mins / 60);
		$mins -= $hrs * 60;
		$offset = \sprintf('%+d:%02d', $hrs*$sgn, $mins);
		$this->dbh->exec("SET time_zone='$offset';");
	}
	
	/**
	 * @param string key
	 * @param string statement
	 * @param string language of statement
	 * @return \mjolnir\types\SQLStatement
	 */
	function prepare($key, $statement = null, $lang = null)
	{
		if ( ! $this->cleanup)
		{
			$this->pre_execution_configuration();
		}
		
		if ($this->requires_translation($statement, $lang))
		{
			return $this->run_stored_statement($key);
		}
		else # translation not required
		{
			$prepared_statement = $this->dbh->prepare($statement.' -- '.$key);
			return \app\SQLStatement::instance($prepared_statement);
		}
	}
	
	/**
	 * @param string raw version
	 * @return string quoted version
	 */
	function quote($value)
	{
		return $this->dbh->quote($value);
	}
	
	/**
	 * @param string 
	 * @return mixed
	 */
	function last_inserted_id($name = null)
	{
		return $this->dbh->lastInsertId($name);
	}
	
	/**
	 * Begin transaction.
	 * 
	 * @return \mjolnir\base\SQLDatabase $this
	 */
	function begin()
	{
		if ($this->savepoint == 0)
		{
			$this->dbh->beginTransaction();
		}
		else # we are in a transaction
		{
			$this->prepare(__METHOD__, 'SAVEPOINT save'.$this->savepoint, 'mysql');
		}
		++$this->savepoint;
		
		return $this;
	}
	
	/**
	 * Commit transaction.
	 * 
	 * @return \mjolnir\base\SQLDatabase $this
	 */
	function commit()
	{
		--$this->savepoint;
		if ($this->savepoint == 0)
		{
			$this->dbh->commit();
		}
		else # we are still in another transaction
		{
			$this->prepare(__METHOD__, 'RELEASE SAVEPOINT save'.$this->savepoint, 'mysql');
		}
		
		return $this;
	}
	
	/**
	 * Rollback transaction.
	 * 
	 * @return \mjolnir\base\SQLDatabase $this
	 */
	function rollback()
	{
		--$this->savepoint;
		if ($this->savepoint == 0)
		{
			$this->dbh->rollBack();
		}
		else # we are still in another transaction
		{
			$this->prepare(__METHOD__, 'ROLLBACK TO SAVEPOINT save'.$this->savepoint, 'mysql');
		}
		
		return $this;
	}
	
} # class
