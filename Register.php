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
	protected static $table = 'mjolnir__registery';

	/**
	 * @return string register table
	 */
	static function table()
	{
		$database_config = \app\CFS::configfile('mjolnir/database');
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
				'
					SELECT registry.key,
						   registry.value
					  FROM `[registry]` registry
					 WHERE registry.key = :key
				',
				[
					'[registry]' => static::table()
				]
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
				'
					UPDATE `[registry]` registry
					   SET registry.value = :value
					 WHERE registry.key = :key
				',
				[
					'[registry]' => static::table()
				]
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
				'
					SELECT COUNT(1)
					  FROM `[registry]` registry
					 WHERE registry.key = :key
					 LIMIT 1
				',
				[
					'[registry]' => static::table(),
				]
			)
			->str(':key', $key)
			->run()
			->fetch_calc();

		$count = (int) $count;

		if ($count === 0)
		{
			\app\SQL::prepare
				(
					'
						INSERT INTO `[registry]`
						(`key`, `value`) VALUES (:key, :value)
					',
					[
						'[registry]' => static::table()
					]
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
