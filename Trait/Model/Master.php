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
	protected static function sql($identifier, $sql)
	{
		return \app\SQLStash::prepare($identifier, $sql)
			->tags(\app\Stash::tags(\get_class(), static::$timers))
			->table(static::table());
	}

	/**
	 * @return \app\Table_Snatcher
	 */
	protected static function snatch($args)
	{		
		$args = \func_get_args();
		return \app\Table_Snatcher::instance()
			->tags(\app\Stash::tags(\get_class(), static::$timers))
			->table(static::table())
			->query($args);
	}

	/**
	 * @return \app\SQLCache
	 */
	protected static function sql_insert(array $fields, array $keys)
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
			->tags(\app\Stash::tags(\get_class(), static::$timers))
			->table(static::table())
			->is('change');
	}

	/**
	 * @return \app\SQLCache
	 */
	protected static function sql_update($id, array $fields, array $keys)
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
			->table(static::table())
			->is('change');
	}

} # trait
