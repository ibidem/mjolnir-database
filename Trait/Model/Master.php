<?php namespace ibidem\database;

/**
 * @package    ibidem
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012 Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
trait Trait_Model_Master
{
	protected static $timers = ['change'];

	/**
	 * @return \app\SQLCache
	 */
	protected static function stash($identifier, $sql)
	{
		$identity = \join('', \array_slice(\explode('\\', \get_called_class()), -1));
		return \app\SQLStash::prepare($identifier, $sql)
			->timers(\app\Stash::tags(\get_called_class(), ['change']))
			->table(static::table())
			->identity($identity);
	}
	
	/**
	 * @return \ibidem\types\SQLStatement
	 */
	protected static function statement($identifier, $sql, $lang = null)
	{
		$sql = \strtr($sql, [':table' => static::table()]);
		return \app\SQL::prepare($identifier, $sql, $lang);
	}

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
	 * @return \app\SQLCache
	 */
	protected static function inserter(array $fields, array $keys)
	{
		$table_keys = \app\Collection::convert($keys, function ($k) { return '`'.$k.'`'; });
		$value_keys = \app\Collection::convert($keys, function ($k) { return ':'.$k; });
		
		return \app\SQLStash::prepare
			(
				__METHOD__, 
				'
					INSERT INTO :table
						('.\implode(', ', $table_keys).')
					VALUES
						('.\implode(', ', $value_keys).')
				'
			)
			->mass_set($fields, $keys)
			->timers(\app\Stash::tags(\get_called_class(), ['change']))
			->table(static::table())
			->is('change');
	}

	/**
	 * @return \app\SQLCache
	 */
	protected static function updater($id, array $fields, array $keys)
	{
		$assignments = \app\Collection::convert
			(
				$keys, 
				function ($k) { 
					return '`'.$k.'` = :'.$k;
				}
			);

		return \app\SQLStash::prepare
			(
				__METHOD__, 
				'
					UPDATE :table
					   SET '.\implode(', ', $assignments).'
					 WHERE id = :id
				'
			)
			->mass_set($fields, $keys)
			->set_int(':id', $id)
			->timers(\app\Stash::tags(\get_called_class(), ['change']))
			->table(static::table())
			->is('change');
	}

} # trait
