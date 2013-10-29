<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012 Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class SQLDatabase extends \app\Instantiatable implements \mjolnir\types\SQLDatabase
{
	use \app\Trait_SQLDatabase;

	/**
	 * @var array
	 */
	protected static $instances = [];

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
	 * Database setup; null if already executed.
	 */
	protected $setup = null;

	/**
	 * @return static
	 */
	static function instance($database = 'default')
	{
		if ( ! isset(static::$instances[$database]))
		{
			$instance = static::$instances[$database] = parent::instance();

			$instance->setup = function () use ($instance, $database)
				{
					try
					{
						// attempt to load configuration
						$pdo_config = \app\CFS::config('mjolnir/database');
						$pdo = $pdo_config['databases'][$database];

						if (empty($pdo))
						{
							throw new \app\Exception('Missing database configuration.');
						}

						// setup database handle
						$dbh = $instance->dbh = new \PDO
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
						$instance->dialect_default = $pdo['dialect_default'];
						$instance->dialect_target = $pdo['dialect_target'];

						$base_config = \app\CFS::config('mjolnir/base');
						// set charset
						$instance->dbh->exec("SET CHARACTER SET '{$base_config['charset']}'");
						$instance->dbh->exec("SET NAMES '{$base_config['charset']}'");
						// set timezone
						$offset = \app\Date::default_timezone_offset();
						$instance->dbh->exec("SET time_zone='$offset';");
					}
					catch (\PDOException $e)
					{
						if (\preg_match('#driver#', $e->getMessage()))
						{
							throw new \app\Exception('PHP PDO interface error: '.$e->getMessage().'. Hint: the cause of the error is with your server, if on a console check your CLI php.ini configuration (seperate from web php.ini by default on some servers).');
						}
						else # non-driver error
						{
							throw new \app\Exception($e->getMessage());
						}

					}
				};

			return static::$instances[$database];
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
	 * The key is usually __METHOD__ but any key may be provided so long as
	 * it accuratly identifies the method.
	 *
	 * eg.
	 *
	 *     $db->prepare(__METHOD__, 'SELECT * FROM customers');
	 *     $db->prepare(__METHOD__.':users', 'SELECT * FROM users');
	 *
	 * The : in ':users' above is the keysplit.
	 *
	 * @return \mjolnir\types\SQLStatement
	 */
	function prepare($key, $statement = null, $lang = null)
	{
		$this->check_setup();

		if ($this->requires_translation($statement, $lang))
		{
			return $this->run_stored_statement($key);
		}
		else # translation not required
		{
			$rawstatement = $statement.' -- '.\str_replace('//', '/', \str_replace(':', '/', $key));
			$prepared_statement = $this->dbh->prepare($rawstatement);
			return \app\SQLStatement::instance($prepared_statement, $rawstatement);
		}
	}

	/**
	 * @return string quoted version
	 */
	function quote($value)
	{
		$this->check_setup();

		return $this->dbh->quote($value);
	}

	/**
	 * @return mixed
	 */
	function last_inserted_id($name = null)
	{
		if ($this->setup !== null)
		{
			return null;
		}

		return $this->dbh->lastInsertId($name);
	}

	/**
	 * Begin transaction or savepoint.
	 *
	 * @return static $this
	 */
	function begin()
	{
		$this->check_setup();

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
	 * Commit transaction or savepoint.
	 *
	 * @return static $this
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
	 * Rollback transaction or savepoint.
	 *
	 * @return static $this
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

	// ------------------------------------------------------------------------
	// Helpers

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
			$file = \mjolnir\cfs\CFSInterface::CNFDIR
				. '/sql/'.$this->dialect_target.'/'.$file;

			throw new \app\Exception
				(
					'Missing key ['.$key.'] in ['.$file.'].', # message
					'Database Translation Error' # title
				);
		}
		return $statements[$key]($this->dbh);
	}

	/**
	 * ...
	 */
	protected function check_setup()
	{
		if ($this->setup !== null)
		{
			$setup = $this->setup;
			$setup();
			$this->setup = null;
		}
	}

} # class
