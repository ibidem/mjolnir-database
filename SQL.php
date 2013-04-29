<?php namespace mjolnir\database;

/**
 * Static library that acts as shortcut for running statements on default 
 * database. All statements are esentially equivalent to doing 
 * \app\SQLDatabase::instance() and then calling the equivalent method.
 * 
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012 Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class SQL
{	
	/**
	 * @var string database used
	 */
	protected static $database = 'default';
	
	/**
	 * Sets the default database to be used.
	 */
	static function database_key($database)
	{
		static::$database = $database;
	}
	
	/**
	 * Retrieves the current database key
	 * 
	 * @return string
	 */
	static function database()
	{
		return \app\SQLDatabase::instance(static::$database);
	}
	
	/**
	 * @return \mjolnir\types\SQLStatement
	 */
	static function prepare($key, $statement = null, $lang = null)
	{
		return \app\SQLDatabase::instance(static::$database)->prepare($key, $statement, $lang);
	}
	
	/**
	 * @return string quoted version
	 */
	static function quote($value)
	{
		return \app\SQLDatabase::instance(static::$database)->quote($value);
	}
	
	/**
	 * @return mixed 
	 */
	static function last_inserted_id($name = null)
	{
		return \app\SQLDatabase::instance(static::$database)->last_inserted_id($name);
	}
	
	/**
	 * Begin transaction.
	 * 
	 * @return \mjolnir\types\SQLDatabase
	 */
	static function begin()
	{
		return \app\SQLDatabase::instance(static::$database)->begin();
	}
	
	/**
	 * Commit transaction.
	 * 
	 * @return \mjolnir\types\SQLDatabase
	 */
	static function commit()
	{
		return \app\SQLDatabase::instance(static::$database)->commit();
	}
	
	/**
	 * Rollback transaction.
	 * 
	 * @return \mjolnir\types\SQLDatabase
	 */
	static function rollback()
	{
		return \app\SQLDatabase::instance(static::$database)->rollback();
	}
	
	// ------------------------------------------------------------------------
	// General Helpers
	
	/**
	 * join format: 
	 * [
	 *	'table' => static::table(), 
	 *	'ref' => 'something.id', 
	 *	'key' => 'this.something'
	 * ];
	 * 
	 * @return string compiled joins
	 */
	static function parsejoins(array $joins)
	{
		$joins = '';
		
		foreach ($joins as $join)
		{
			$joins .= "JOIN `{$join['table']}` ON {$join['ref']} = {$join['key']}";
		}
		
		return $joins;
	}
	
	/**
	 * [!!] Intentionally not permitting null for constraints, please perform 
	 * the check in context because this method only returns the parameters to
	 * a WHERE clause not the entire WHERE clause.
	 * 
	 * [!!] DO NOT expect this method to always return a non-empty value; it is
	 * possible for a constraint to resolve to nothing such as a value being
	 * constraint between null. Always check if the value return is not empty.
	 * 
	 * eg.
	 * 
	 *		\app\SQL::parse_constraints
	 *			(
	 *				[
	 *					'datetime' => ['between' => [$start, $end]], 
	 *					'type' => 1,
	 *					'id' => ['>=' => 10000],
	 *					'given_name' => ['in' => ['John', 'Alex, 'Steve']],
	 *					'family_name => ['like' => 'B%'],
	 *				]
	 *			);
	 * 
	 * If an operator is missing here, simply overwrite the method, add handling
	 * to detect and resolve your paramter, remove it from the list then pass 
	 * the list to this method for processing additional paramters, and combine 
	 * the result with your result.
	 * 
	 * @return string
	 */
	static function parseconstraints(array $constraints)
	{
		$parameter_resolver = function ($k, $value, $operator)
			{
				if (\is_bool($value))
				{
					return "$k $operator ".($value ? 'TRUE' : 'FALSE');
				}
				else if (\is_numeric($value))
				{
					return "$k $operator $value";
				}
				else if (\is_null($value))
				{
					if ($operator == '=' || $operator == '<=>')
					{
						return "$k IS NULL";
					}
					else # assume some form of negative
					{
						return "$k IS NOT NULL";
					}
				}
				else if (\preg_match('#like#', \strtolower($operator)))
				{
					return "$k $operator ".\app\SQL::quote($value);
				}
				else if (\is_array($value))
				{
					// the value to be compared an array. meaning we have 
					// additional parameter processing and the operator itself 
					// needs to be handled in a special way
					
					// we perform the preg_match because there is a NOT variant
					// to all of the following
					if (\preg_match('#in#', \strtolower($operator)))
					{
						return "$k $operator (".\app\Arr::implode
							(
								', ', $value,
								function ($i, $value)
								{
									return \app\SQL::quote($value);
								}
							).')';
					}
					else if (\preg_match('#between#', \strtolower($operator)))
					{
						if ($value[0] !== null && $value[1] !== null)
						{
							return "$k $operator ".\app\SQL::quote($value[0])." AND ".\app\SQL::quote($value[1]);
						}
						else if ($value[0] === null && $value[1] === null)
						{
							return false; # \app\Arr::implode will ignore this item
						}
						else # convert constraint to comparison
						{
							$start = $value[0];
							$end = $value[1];
							// is negative?
							if (\preg_match('#not#', \strtolower($operator)))
							{
								// process as NOT in interval
								if ($start !== null) # $end === null
								{
									$start = \app\SQL::quote($start);
									return "$k < $start";
								}
								else # $end !== null && $start == null
								{
									$end = \app\SQL::quote($end);
									return "$k > $end";
								}
							}
							else # positive comparison
							{
								if ($start !== null) # $end === null
								{
									$start = \app\SQL::quote($start);
									return "$k >= $start";
								}
								else # $end !== null && $start == null
								{
									$end = \app\SQL::quote($end);
									return "$k <= $end";
								}
							}
						}
					}
					else # unknown operator
					{
						throw new \app\Exception("Unsupported operator [$operator].");
					}
				}
				else # string, or string compatible
				{
					return "$k $operator ".\app\SQL::quote($value);
				}
			};
			
		return \app\Arr::implode
			(
				' AND ', # delimiter
				$constraints, # source

				function ($k, $value) use ($parameter_resolver) {

					$k = \strpbrk($k, ' .()') === false ? '`'.$k.'`' : $k;

					if (\is_array($value))
					{
						return $parameter_resolver($k, \current($value), \key($value));
					}
					else # non-array
					{
						return $parameter_resolver($k, $value, '<=>'); # null safe equals
					}
				}
			);
	}
	
} # class
