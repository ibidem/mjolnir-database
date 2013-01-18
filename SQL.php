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
	
} # class
