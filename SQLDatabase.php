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
	 * @var \PDO
	 */
	protected $dbh;

	/**
	 * Database setup; null if already executed.
	 *
	 * @var callable
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
							throw new \app\Exception("[db:$database] ".$e->getMessage());
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
	 * eg. $db->prepare('SELECT * FROM customers');
	 *
	 * @return \mjolnir\types\SQLStatement
	 */
	function prepare($statement = null, array $placeholders = null)
	{
		$this->check_setup();

		if ($placeholders !== null)
		{
			return \app\SQLStatement::instance($this->dbh, \strtr($statement, $placeholders));
		}
		else # placeholders === null
		{
			return \app\SQLStatement::instance($this->dbh, $statement);
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
			$this->prepare('SAVEPOINT save'.$this->savepoint);
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
			$this->prepare('RELEASE SAVEPOINT save'.$this->savepoint);
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
			$this->prepare('ROLLBACK TO SAVEPOINT save'.$this->savepoint);
		}

		return $this;
	}

	// ------------------------------------------------------------------------
	// Helpers

	/**
	 * ...
	 */
	protected function check_setup()
	{
		if ($this->setup !== null)
		{
			/** @var callable $setup */
			$setup = $this->setup;
			$setup();
			$this->setup = null;
		}
	}

} # class
