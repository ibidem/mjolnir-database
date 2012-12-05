<?php namespace mjolnir\database;

/**
 * Static library that acts as shortcut for running statements on default 
 * database. All statements are esentially equivalent to doing 
 * \app\SQLDatabase::instance() and then calling the equivalent method.
 * 
 * @package    mjolnir
 * @category   Base
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
	static function database($database)
	{
		static::$database = $database;
	}
	
	/**
	 * @param string key
	 * @param string statement
	 * @param string language of statement
	 * @return \mjolnir\types\SQLStatement
	 */
	static function prepare($key, $statement = null, $lang = null)
	{
		return \app\SQLDatabase::instance(static::$database)->prepare($key, $statement, $lang);
	}
	
	/**
	 * @param string raw version
	 * @return string quoted version
	 */
	static function quote($value)
	{
		return \app\SQLDatabase::instance(static::$database)->quote($value);
	}
	
	/**
	 * @param string name
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
	
	// Extended
	
	/**
	 * Test for expression - starts and ends with ().
	 * 
	 * @return boolean
	 */
	static function is_expression($str)
	{
		if($str[0] == '(' && $str[strlen($str) - 1] == ')')
		{
		    return true;
		};
		
		return false;
	}
	
} # class
