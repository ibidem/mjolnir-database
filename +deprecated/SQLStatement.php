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
	 * @return \app\SQLStatement
	 */
	static function instance(\PDOStatement $statement = null, $query = null)
	{
		if ($statement === null)
		{
			throw new \app\Exception('No statement provided.');
		}

		$instance = parent::instance();
		$instance->statement($statement, $query);
		return $instance;
	}

	/**
	 * @return \mjolnir\database\SQLStatement $this
	 */
	function statement(\PDOStatement $statement, $query = null)
	{
		$this->statement = $statement;
		$this->query = $query;
		
		return $this;
	}

	/**
	 * @return \mjolnir\database\SQLStatement $this
	 */
	function bind($parameter, & $variable)
	{
		$this->statement->bindParam($parameter, $variable, \PDO::PARAM_STR);
		return $this;
	}

	/**
	 * @return \mjolnir\database\SQLStatement $this
	 */
	function bind_int($parameter, & $variable)
	{
		$this->statement->bindParam($parameter, $variable, \PDO::PARAM_INT);
		return $this;
	}

	/**
	 * @return \mjolnir\database\SQLStatement $this
	 */
	function bind_date($parameter, & $variable)
	{
		$this->statement->bindValue($parameter, $variable, \PDO::PARAM_STR);
		return $this;
	}

	/**
	 * @return \mjolnir\database\SQLStatement $this
	 */
	function bind_bool($parameter, & $variable)
	{
		if ($variable === true || $variable === false)
		{
			$this->statement->bindValue($parameter, $variable, \PDO::PARAM_BOOL);
		}
		else
		{
			static $map = array
				(
					'true' => true,
					'on' => true,
					'yes' => true,
					'false' => false,
					'off' => false,
					'no' => false,
				);

			if (isset($map[$variable]))
			{
				$this->statement->bindValue($parameter, $map[$variable], \PDO::PARAM_BOOL);
			}
			else
			{
				throw new \app\Exception('Unrecognized boolean value passed.');
			}

		}

		return $this;
	}

	/**
	 * @return \mjolnir\database\SQLStatement $this
	 */
	function set($parameter, $constant)
	{
		$this->statement->bindValue($parameter, $constant, \PDO::PARAM_STR);
		return $this;
	}

	/**
	 * @return \mjolnir\database\SQLStatement $this
	 */
	function set_int($parameter, $constant)
	{
		$this->statement->bindValue($parameter, $constant, \PDO::PARAM_INT);
		return $this;
	}

	/**
	 * @return \mjolnir\database\SQLStatement $this
	 */
	function set_bool($parameter, $constant)
	{
		if ($constant === true || $constant === false)
		{
			$this->statement->bindValue($parameter, $constant, \PDO::PARAM_BOOL);
		}
		else
		{
			static $map = array
				(
					'true' => true,
					'on' => true,
					'yes' => true,
					'false' => false,
					'off' => false,
					'no' => false,
				);

			if (isset($map[$constant]))
			{
				$this->statement->bindValue($parameter, $map[$constant], \PDO::PARAM_BOOL);
			}
			else
			{
				throw new \app\Exception('Unrecognized boolean value passed.');
			}

		}

		return $this;
	}

	/**
	 * @return \mjolnir\database\SQLStatement $this
	 */
	function set_date($parameter, $constant)
	{
		$this->statement->bindValue($parameter, $constant, \PDO::PARAM_STR);
		return $this;
	}

	/**
	 * Stored procedure argument.
	 *
	 * @return \mjolnir\database\SQLStatement $this
	 */
	function bind_arg($parameter, & $variable)
	{
		$this->statement->bindParam
			(
				$parameter,
				$variable,
				\PDO::PARAM_STR|\PDO::PARAM_INPUT_OUTPUT
			);

		return $this;
	}

	/**
	 * @return \mjolnir\types\SQLStatement $this
	 */
	function mass_set(array $keys, array $values)
	{
		foreach ($keys as $key)
		{
			$this->set(':'.$key, isset($values[$key]) ? $values[$key] : null);
		}

		return $this;
	}

	/**
	 * @return \mjolnir\types\SQLStatement $this
	 */
	function mass_int(array $keys, array $values, $default = null)
	{
		foreach ($keys as $key)
		{
			$this->set_int(':'.$key, isset($values[$key]) ? $values[$key] : $default);
		}

		return $this;
	}

	/**
	 * Key map may be in the form of:
	 * 
	 *     'true_key' => true, 
	 *     'false_key' => false
	 * 
	 * etc.
	 * 
	 * @return \mjolnir\types\SQLStatement $this
	 */
	function mass_bool(array $keys, array $values, array $map = null)
	{
		if ($map === null)
		{
			$map = array
				(
					'true' => true,
					'on' => true,
					'yes' => true,
					'1' => true,
					'false' => false,
					'off' => false,
					'no' => false,
					'0' => false,
				);
		}

		foreach ($keys as $key)
		{
			$this->set_bool(':'.$key, isset($values[$key]) ? $map[$values[$key]] : false);
		}

		return $this;
	}

	/**
	 * Automatically calculates and sets :offset and :limit based on a page 
	 * input. If page or limit are null, the limit will be set to the maximum
	 * value.
	 *
	 * @return \mjolnir\database\SQLStatement $thiss
	 */
	function page($page, $limit = null, $offset = 0)
	{
		if ($page === null || $limit === null)
		{
			// retrieve all rows
			$this->set_int(':offset', $offset);
			$this->set_int(':limit', PHP_INT_MAX);
		}
		else # $page != null
		{
			$this->set_int(':offset', $limit * ($page - 1) + $offset);
			$this->set_int(':limit', $limit);
		}

		return $this;
	}

	/**
	 * Execute the statement.
	 *
	 * @return \mjolnir\database\SQLStatement $this
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
			
			\mjolnir\masterlog('Database', $message, 'Database/');
			
			throw $exception;
		}
		
		return $this;
	}

	/**
	 * Featch as object.
	 *
	 * @param string class
	 * @param array paramters to be passed to constructor
	 * @return mixed
	 */
	function fetch_object($class = 'stdClass', array $args = null)
	{
		return $this->statement->fetchObject($class, $args);
	}

	/**
	 * Fetch associative array of row.
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
				$this->format_entry($result, $format);
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
	function fetch_all(array $format = null)
	{
		if ($format === null)
		{
			return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
		}
		else # format not null
		{
			$result = $this->statement->fetchAll(\PDO::FETCH_ASSOC);
			foreach ($result as & $entry)
			{
				$this->format_entry($entry, $format);
			}

			return $result;
		}
	}

	// ------------------------------------------------------------------------
	// Helpers
	
	/**
	 * Formats an entry.
	 */
	protected function format_entry( & $entry, & $format)
	{
		foreach ($format as $field => $operation)
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
