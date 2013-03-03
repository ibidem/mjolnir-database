<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012 Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class SQLStatement extends \app\Instantiatable implements \mjolnir\types\SQLStatement
{
	use \app\Trait_SQLStatement;

	/**
	 * @var \PDOStatement
	 */
	protected $statement;

	/**
	 * @var string
	 */
	protected $query;

	/**
	 * @return static
	 */
	static function instance(\PDOStatement $statement = null, $query = null)
	{
		if ($statement === null)
		{
			throw new \app\Exception('No statement provided.');
		}

		$instance = parent::instance();
		$instance->statement = $statement;
		$instance->query = $query;
		return $instance;
	}

	// ------------------------------------------------------------------------
	// Basic assignment

	/**
	 * @return static $this
	 */
	function str($parameter, $value)
	{
		$this->statement->bindValue($parameter, $value, \PDO::PARAM_STR);
		return $this;
	}

	/**
	 * @return static $this
	 */
	function num($parameter, $value)
	{
		$this->statement->bindValue($parameter, $value, \PDO::PARAM_INT);
		return $this;
	}

	/**
	 * @return static $this
	 */
	function bool($parameter, $value, array $map = null)
	{
		if ($value === true || $value === false)
		{
			$this->statement->bindValue($parameter, $value, \PDO::PARAM_BOOL);
		}
		else # non-boolean
		{
			$this->statement->bindValue($parameter, $this->booleanize($value, $map), \PDO::PARAM_BOOL);
		}

		return $this;
	}

	/**
	 * @return static $this
	 */
	function date($parameter, $value)
	{
		$this->statement->bindValue($parameter, $value, \PDO::PARAM_STR);
		return $this;
	}

	// ------------------------------------------------------------------------
	// Basic Binding

	/**
	 * @return static $this
	 */
	function bindstr($parameter, &$variable)
	{
		$this->statement->bindParam($parameter, $variable, \PDO::PARAM_STR);
		return $this;
	}

	/**
	 * @return static $this
	 */
	function bindnum($parameter, &$variable)
	{
		$this->statement->bindParam($parameter, $variable, \PDO::PARAM_INT);
		return $this;
	}

	/**
	 * @return static $this
	 */
	function bindbool($parameter, &$variable)
	{
		$this->statement->bindValue($parameter, $variable, \PDO::PARAM_BOOL);

		return $this;
	}

	/**
	 * @return static $this
	 */
	function binddate($parameter, &$variable)
	{
		$this->statement->bindValue($parameter, $variable, \PDO::PARAM_STR);
		return $this;
	}

	// ------------------------------------------------------------------------
	// Stored procedure arguments

	/**
	 * @return static $this
	 */
	function arg($parameter, &$variable)
	{
		$this->statement->bindParam
			(
				$parameter,
				$variable,
				\PDO::PARAM_STR|\PDO::PARAM_INPUT_OUTPUT
			);

		return $this;
	}

	// ------------------------------------------------------------------------
	// etc

	/**
	 * Execute the statement.
	 *
	 * @return static $this
	 */
	function run()
	{
		try
		{
			$this->statement->execute();
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage();
			$message .= "\n\n".\app\Text::baseindent($this->query, "\t\t")."\n";

			\mjolnir\masterlog('Database', $message);

			throw $exception;
		}

		return $this;
	}

	/**
	 * Featch as object.
	 *
	 * @return mixed
	 */
	function fetch_object($class = 'stdClass', array $args = null)
	{
		return $this->statement->fetchObject($class, $args);
	}

	/**
	 * Fetch row as associative.
	 *
	 * @return array or null
	 */
	function fetch_entry(array $format = null)
	{
		$result = $this->statement->fetch(\PDO::FETCH_ASSOC);

		if ($result === false)
		{
			return null;
		}
		else # succesfully retrieved statement
		{
			if ($format !== null)
			{
				static::format_entry($result, $format);
			}

			return $result;
		}
	}

	/**
	 * Retrieves all rows. Rows are retrieved as arrays. Empty result will
	 * return an empty array.
	 *
	 * @return array
	 */
	function fetch_all(array $formatinfo = null)
	{
		if ($formatinfo === null)
		{
			return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
		}
		else # format not null
		{
			$result = $this->statement->fetchAll(\PDO::FETCH_ASSOC);
			foreach ($result as &$entry)
			{
				static::format_entry($entry, $formatinfo);
			}

			return $result;
		}
	}

	// ------------------------------------------------------------------------
	// etc

	/**
	 * Formats an entry.
	 */
	static function format_entry(&$entry, array &$formatinfo)
	{
		foreach ($formatinfo as $field => $operation)
		{
			if (\is_string($operation))
			{
				// preset operation
				switch ($operation)
				{
					case 'datetime':
						if (empty($entry[$field]))
						{
							$entry[$field] = null;
						}
						else # not empty
						{
							$entry[$field] = new \DateTime($entry[$field]);
							// confirm datetime is valid
							if ($entry[$field]->getLastErrors()['error_count'] !== 0)
							{
								$entry[$field] = null;
							}
						}

						break;

					default:
						throw new \app\Exception
							('Unknown post formatting operation.');
				}
			}
			else # non string (assume callback)
			{
				$entry[$field] = $operation($entry[$field]);
			}
		}
	}

} # class
