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
			->paged($page, $limit, $offset)
			->order($order)
			->fetch_all();
	}

	/**
	 * @return array
	 */
	static function entry($id) 
	{
		return static::sql
			(
				__METHOD__,
				'
					SELECT *
					  FROM :table
					 WHERE id = :id
				'
			)
			->set_int(':id', $id)
			->fetch_array();
	}

	/**
	 * @return int
	 */
	static function count() 
	{
		return static::sql
			(
				__METHOD__,
				'
					SELECT COUNT(1)
					  FROM :table
				'
			)
			->fetch_array()
			['COUNT(1)'];
	}

} # trait
