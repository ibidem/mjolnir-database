<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Register
{
	/**
	 * @var string
	 */
	protected static $table = 'mjolnir_registery';

	/**
	 * @return string register table
	 */
	static function table()
	{
		$database_config = \app\CFS::config_file('mjolnir/database');
		return $database_config['table_prefix'].static::$table;
	}

	/**
	 * @return mixed
	 */
	static function key($key)
	{
		return static::pull([$key])[$key];
	}

	/**
	 * Retrieves a set of keys.
	 *
	 * @return array
	 */
	static function pull(array $keys)
	{
		$statement = \app\SQL::prepare
			(
				__METHOD__,
				'
					SELECT registry.key,
						   registry.value
					  FROM `'.static::table().'` registry
					 WHERE registry.key = :key
				',
				'mysql'
			)
			->bindstr(':key', $key);

		$resultset = [];
		foreach ($keys as $target)
		{
			$key = $target;
			$resultset[$target] = $statement->run()->fetch_entry()['value'];
		}

		return $resultset;
	}

	/**
	 * Update key.
	 */
	static function push($key, $value)
	{
		\app\SQL::prepare
			(
				__METHOD__,
				'
					UPDATE `'.static::table().'` registry
					   SET registry.value = :value
					 WHERE registry.key = :key
				',
				'mysql'
			)
			->str(':key', $key)
			->str(':value', $value)
			->run();
	}

	/**
	 * Insert new key.
	 */
	static function inject($key, $value)
	{
		// check if it exists
		$count = \app\SQL::prepare
			(
				__METHOD__.':method_exists',
				'
					SELECT COUNT(1)
					  FROM `'.static::table().'` registry
					 WHERE registry.key = :key
					 LIMIT 1
				',
				'mysql'
			)
			->str(':key', $key)
			->run()
			->fetch_entry()
			['COUNT(1)'];

		$count = (int) $count;

		if ($count === 0)
		{
			\app\SQL::prepare
				(
					__METHOD__,
					'
						INSERT INTO `'.static::table().'`
						(`key`, `value`) VALUES (:key, :value)
					',
					'mysql'
				)
				->str(':key', $key)
				->str(':value', $value)
				->run();
		}
		else # count !== 0
		{
			throw new \app\Exception
				('Registry key with the same name already exists.');
		}
	}

} # class
