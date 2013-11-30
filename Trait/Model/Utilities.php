<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012 Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
trait Trait_Model_Utilities
{
	/**
	 * @var array
	 */
	protected static $timers = ['change'];

	/**
	 * @return \app\Table_Snatcher
	 */
	protected static function snatch($args)
	{
		$args = \func_get_args();
		$identity = \join('', \array_slice(\explode('\\', \get_called_class()), -1));
		return \app\Table_Snatcher::instance()
			->timers(\app\Stash::tags(\get_called_class(), ['change']))
			->table(static::table())
			->identity($identity)
			->query($args);
	}

	/**
	 * @return \app\SQLStash
	 */
	protected static function inserter(array $fields, array $strs, array $bools = [], array $nums = [])
	{
		// compile keys for statement

		$keys = [];
		foreach ($strs as $str)
		{
			$keys[] = $str;
		}

		foreach ($nums as $num)
		{
			$keys[] = $num;
		}

		foreach ($bools as $bool)
		{
			$keys[] = $bool;
		}

		$table_keys = \app\Arr::convert($keys, function ($k) { return '`'.$k.'`'; });
		$value_keys = \app\Arr::convert($keys, function ($k) { return ':'.$k; });

		return \app\SQL::prepare
			(
				'
					INSERT INTO `[table]`
						('.\implode(', ', $table_keys).')
					VALUES
						('.\implode(', ', $value_keys).')
				',
				[
					'[table]' => static::table()
				]
			)
			->strs($fields, $strs)
			->nums($fields, $nums)
			->bools($fields, $bools);
	}

	/**
	 * @return \app\SQLStash
	 */
	protected static function updater($id, array $fields, array $strs, array $bools = [], array $nums = [])
	{
		// compile keys for statement

		$keys = [];
		foreach ($strs as $str)
		{
			$keys[] = $str;
		}

		foreach ($nums as $num)
		{
			$keys[] = $num;
		}

		foreach ($bools as $bool)
		{
			$keys[] = $bool;
		}

		$assignments = \app\Arr::convert
			(
				$keys,
				function ($k) {
					return '`'.$k.'` = :'.$k;
				}
			);

		return \app\SQL::prepare
			(
				'
					UPDATE `[table]`
					   SET '.\implode(', ', $assignments).'
					 WHERE '.static::unique_key().' = :id
				',
				[
					'[table]' => static::table()
				]
			)
			->strs($fields, $strs)
			->nums($fields, $nums)
			->bools($fields, $bools)
			->num(':id', $id);
	}

	/**
	 * @return \mjolnir\types\SQLStatement
	 */
	protected static function statement($statement, array $placeholder = null)
	{
		$statement = \strtr($statement, ['[table]' => static::table()]);
		return \app\SQL::prepare($statement, $placeholder);
	}

	/**
	 * @return array
	 */
	static function fieldformat()
	{
		if (isset(static::$fieldformat))
		{
			if (\is_array(static::$fieldformat))
			{
				return static::$fieldformat;
			}
			else # configuration path
			{
				if (isset(\app\CFS::config(static::$fieldformat)['fieldformat']))
				{
					return \app\CFS::config(static::$fieldformat)['fieldformat'];
				}
				else # missing fieldformat key
				{
					throw new \app\Exception('Missing key [fieldformat] in configuration file: '.static::$fieldformat);
				}
			}
		}
		else # no field format set
		{
			$noformat = [];
			return $noformat;
		}
	}

	/**
	 * Clears cache for given tags.
	 */
	static function purge_cache($tags)
	{
		\app\Stash::purge(\app\Stash::tags(\get_called_class(), $tags));
	}

	/**
	 * @return array
	 */
	static function fieldlist()
	{
		return static::$fields;
	}

} # trait
