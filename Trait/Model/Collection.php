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
	static function entries($page, $limit, $offset = 0, $order = [], $constraints = [])
	{
		return static::snatch('*')
			->on($constraints)
			->page($page, $limit, $offset)
			->order($order)
			->id(__FUNCTION__)
			->fetch_all();
	}
	
	/**
	 * @return array
	 */
	static function entry($id) 
	{
		$cachekey = \get_called_class().'_ID'.$id;
		$entry = \app\Stash::get($cachekey, null);

		if ($entry === null)
		{
			$entry = static::statement
				(
					__METHOD__,
					'
						SELECT *
						  FROM :table
						 WHERE id = :id
					'
				)
				->set_int(':id', $id)
				->execute()
				->fetch_array();
			
			\app\Stash::set($cachekey, $entry);
		}
		
		return $entry;
	}
	
	/**
	 * @return array of arrays
	 */
	static function find(array $criteria, $page = null, $limit = null, $offset = null)
	{
		return static::entries($page, $limit, $offset, [], $criteria);
	}
	
	/**
	 * @return array or null
	 */
	static function find_entry(array $criteria)
	{
		$result = static::entries(1, 1, 0, [], $criteria);
		
		if (empty($result))
		{
			return null;
		}
		else # non empty result
		{
			return $result[0];
		}
	}
	
	/**
	 * @param int id of entry
	 */
	static function clear_entry_cache($id)
	{
		\app\Stash::delete(\get_called_class().'_ID'.$id);
	}

	/**
	 * @param array user id's 
	 */
	static function delete(array $entries)
	{
		$entry = null;
		$partial_cachekey = \get_called_class().'_ID';
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
		
		foreach ($entries as $entry)
		{
			$statement->execute();
			static::clear_entry_cache($entry);
		}
		
		\app\SQL::commit();
		
		\app\Stash::purge(\app\Stash::tags(\get_called_class(), ['change']));
	}
	
	/**
	 * @return int
	 */
	static function count($constraints = []) 
	{
		$cachekey = __FUNCTION__;
		$where = '';
		if ( ! empty($constraints))
		{
			$where = 'WHERE '.\app\Collection::implode(' AND ', $constraints, function ($k, $i) {
				return '`'.$k.'` = :'.$k;
			});
			
			$cachekey .= '__'.\sha1($where);
		}
		
		$statement = static::stash
			(
				__METHOD__,
				'
					SELECT COUNT(1)
					  FROM :table '.$where.'
				'
			)
			->key($cachekey);
		
		if ( ! empty($constraints))
		{
			foreach ($constraints as $key => $value)
			{
				$statement->set(':'.$key, $value);
			}	
		}
		
		return (int) $statement->fetch_array()['COUNT(1)'];
	}
	
	/**
	 * Checks if a value exists in the table, given a key. By default the title
	 * key is assumed.
	 * 
	 * @return bool
	 */
	static function exists($value, $key = 'title', $context = null)
	{
		// we don't cache existential checks since we want to be 100% sure
		// they go though unobstructed by potential cache errors; since they
		// can be crucial in model checks
		
		$count = (int) static::statement
			(
				__METHOD__,
				'
					SELECT COUNT(1)
					  FROM :table
					 WHERE `'.$key.'` = :value
					   AND NOT `id` <=> '.($context === null ? 'NULL' : $context).'
				'
			)
			->set(':value', $value)
			->execute()
			->fetch_array()['COUNT(1)'];
		
		return $count !== 0;
	}

} # trait
