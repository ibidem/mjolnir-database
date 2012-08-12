<?php namespace ibidem\database;

/**
 * @package    ibidem
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012 Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
trait Trait_Model_Factory
{
	protected static $last_inserted_id;

	/**
	 * @return int last inserted id
	 */
	static function last_inserted_id()
	{
		return static::$last_inserted_id;
	}

	/**
	 * @return string table
	 */
	static function table()
	{
		$config = \app\CFS::config('ibidem/database');
		return $config['table_prefix'].static::$table;
	}

	/**
	 * Verifies and creates entry.
	 *
	 * @return array or null
	 */
	static function push(array $fields)
	{
		// check for errors
		$errors = static::check($fields)->errors();

		if (empty($errors))
		{
			\app\SQL::begin();
			try
			{
				static::process($fields);
				\app\SQL::commit();
			}
			catch (\Exception $e)
			{
				\app\SQL::rollback();
				throw $e;
			}

			return null;
		}
		else # got errors
		{
			return $errors;
		}
	}

	/**
	 * Verifies and updates entry.
	 * 
	 * @return array or null
	 */
	static function update($id, array $fields)
	{
		// check for errors
		$errors = static::update_check($id, $fields)->errors();

		if (empty($errors))
		{
			\app\SQL::begin();
			try
			{
				static::update_process($id, $fields);
				\app\SQL::commit();
			}
			catch (\Exception $e)
			{
				\app\SQL::rollback();
				throw $e;
			}

			return null;
		}
		else # got errors
		{
			return $errors;
		}
	}
	
	/**
	 * @return \app\Validator
	 */
	static function update_check($id, array $fields)
	{
		return static::check($fields, $id)
			->test('id', ':exists', static::exists($id, 'id'));
	}

} # trait
