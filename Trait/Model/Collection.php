<?php namespace ibidem\database;

/**
 * Assumes Model_Master trait was used, or similar interface is available.
 * 
 * @package    ibidem
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012 Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
trait Trait_Model_Collection
{	
	/**
	 * @return array of arrays
	 */
	static function entries($page, $limit, $offset = 0, $order = []) 
	{
		return static::snatch('*')
			->page($page, $limit, $offset)
			->order($order)
			->fetch_all();
	}

	/**
	 * @return array
	 */
	static function entry($id) 
	{
		return static::stash
			(
				__METHOD__,
				'
					SELECT *
					  FROM :table
					 WHERE id = :id
				'
			)
			->set_int(':id', $id)
			->key(__FUNCTION__.'ID'.$id)
			->fetch_array();
	}

	/**
	 * @param array user id's 
	 */
	static function delete(array $IDs)
	{
		$entry = null;
		$statement = static::statement
			(
				__METHOD__,
				'
					DELETE FROM :table
					 WHERE id = :id
				'
			)
			->bind_int(':id', $entry);
		
		\app\SQL::begin();
		
		foreach ($IDs as $entry)
		{
			$statement->execute();
		}
		
		\app\SQL::commit();
		
		\app\Stash::purge(\app\Stash::tags(\get_called_class(), ['change']));
	}
	
	/**
	 * @return int
	 */
	static function count() 
	{
		return static::stash
			(
				__METHOD__,
				'
					SELECT COUNT(1)
					  FROM :table
				'
			)
			->key(__FUNCTION__)
			->fetch_array()
			['COUNT(1)'];
	}
	
	/**
	 * Checks if a value exists in the table, given a key. By default the title
	 * key is assumed.
	 * 
	 * @return bool
	 */
	static function exists($value, $key = 'title')
	{
		$count = static::snatch('COUNT(1)')
			->on([$key => $value])
			->page(1, 1)
			->fetch_all()
			['COUNT(1)'];
		
		return ((int) $count) != 0;
	}

} # trait
