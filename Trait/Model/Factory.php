<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012 Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
trait Trait_Model_Factory
{
	/**
	 * @var int or null
	 */
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
		if ( ! isset(static::$table))
		{
			throw new \app\Exception('No table defined.');
		}

		return \app\CFS::config('mjolnir/database')['table_prefix'].static::$table;
	}

	/**
	 * @return array
	 */
	static function related_caches()
	{
		if (isset(static::$related_caches))
		{
			return static::$related_caches;
		}
		else # cache_reset not set
		{
			return [];
		}
	}

	/**
	 * Clean up fields
	 */
	static function cleanup(array &$input)
	{
		// empty
	}

	/**
	 * Verifies and creates entry.
	 *
	 * @return array or null
	 */
	static function push(array $input)
	{
		static::cleanup($input);

		// check for errors
		$errors = static::check($input)->errors();

		if (empty($errors))
		{
			\app\SQL::begin();
			try
			{
				static::process($input);
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
	static function update($id, array $input)
	{
		static::cleanup($input);

		// check for errors
		$errors = static::update_check($id, $input)->errors();

		if (empty($errors))
		{
			\app\SQL::begin();
			try
			{
				static::update_process($id, $input);
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
	 * @return \mjolnir\types\Validator
	 */
	static function update_check($id, array $input)
	{
		return static::check($input, $id)
			->rule(static::unique_key(), 'exists', static::exists($id, static::unique_key()));
	}

} # trait
