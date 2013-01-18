<?php namespace mjolnir\database;

/**
 * The register manages key to value pairs in the database.
 *
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Register
{
	protected static $table = 'mjolnir_registery';

	static function table()
	{
		$database_config = \app\CFS::config_file('mjolnir/database');
		return $database_config['table_prefix'].static::$table;
	}

	static function pull(array $keys)
	{
		$statement = \app\SQL::prepare
			(
				__METHOD__,
				'
					SELECT reg.key,
						   reg.value
					  FROM `'.static::table().'` reg
					 WHERE reg.key = :key
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

	static function push($key, $value)
	{
		\app\SQL::prepare
			(
				__METHOD__,
				'
					UPDATE `'.static::table().'` reg
					   SET reg.value = :value
					 WHERE reg.key = :key
				',
				'mysql'
			)
			->str(':key', $key)
			->str(':value', $value)
			->run();
	}

	static function inject($key, $value)
	{
		// check if it exists
		$count = \app\SQL::prepare
			(
				__METHOD__.':method_exists',
				'
					SELECT COUNT(1)
					  FROM `'.static::table().'` reg
					 WHERE reg.key = :key
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
							(`key`, `value`)
						VALUES
							(:key, :value)
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
