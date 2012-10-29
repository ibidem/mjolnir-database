<?php namespace mjolnir\database;

/**
 * Assumes Model_Master trait was used, or similar interface is available.
 *
 * @package    mjolnir
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
			->constraints($constraints)
			->page($page, $limit, $offset)
			->order($order)
			->id(__FUNCTION__)
			->fetch_all(static::field_format());
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
						 WHERE '.static::unique_key().' = :id
					'
				)
				->set_int(':id', $id)
				->execute()
				->fetch_array();

			\app\Stash::store($cachekey, $entry, \app\Stash::tags(\get_called_class(), ['change']));
		}

		return $entry;
	}

	/**
	 * Utility function for creating the search query along with the
	 * coresponding order by query.
	 */
	protected static function search_query_parameters($term, array & $columns, & $where, & $order)
	{
		$term = '%'.$term.'%';
		$query = 'LIKE '.\app\SQL::quote($term);
		$where = 'WHERE '.\app\Arr::implode(' OR ', $columns, function ($k, $v) use ($query) {
			return '`'.$v.'` '.$query;
		});
		$query = 'LOCATE ('.\app\SQL::quote($term).', `:column`) ASC';
		$order = 'ORDER BY '.\app\Arr::implode(', ', $columns, function ($k, $v) use ($query) {
			return \strtr($query, [':column' => $v]);
		});
	}

	/**
	 * Search takes the term and tries to match it against all the columns.
	 *
	 * @return array of arrays
	 */
	static function search($term, array $columns, $page = null, $limit = null, $offset = null)
	{
		$where = null;
		$order = null;
		static::search_query_parameters($term, $columns, $where, $order);

		$cache_key = __FUNCTION__.'__t'.$term.'__p'.$page.'l'.$limit.'o'.$offset.'__'.\sha1($where);

		$entries = static::stash
			(
				__METHOD__,
				'
					SELECT *
					  FROM :table
					  '.$where.'
					  '.$order.'
				',
				'mysql'
			)
			->page($page, $limit, $offset)
			->key($cache_key)
			->fetch_all();

		return $entries;
	}

	/**
	 * @return int entry count for given search
	 */
	static function search_count($term, array $columns)
	{
		$where = null;
		$order = null;
		static::search_query_parameters($term, $columns, $where, $order);

		$cache_key = __FUNCTION__.'__t'.$term.'__'.\sha1($where);

		$entries = static::stash
			(
				__METHOD__,
				'
					SELECT COUNT(1)
					  FROM :table
					  '.$where.'
					  '.$order.'
				',
				'mysql'
			)
			->key($cache_key)
			->fetch_all();

		return $entries[0]['COUNT(1)'];
	}

	/**
	 * @shorthand for entries
	 * @return array of arrays
	 */
	static function find(array $criteria, $page = null, $limit = null, $offset = null)
	{
		return static::entries($page, $limit, $offset, [], $criteria);
	}

	/**
	 * @shorthand for entries
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
	 * @return string
	 */
	static function unique_key()
	{
		if (isset(static::$unique_key))
		{
			return static::$unique_key;
		}
		else
		{
			return 'id';
		}
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
					 WHERE '.static::unique_key().' = :id
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

		// reset related caches
		foreach (static::related_caches() as $related_cache)
		{
			\app\Stash::purge(\app\Stash::tags($related_cache[0], $related_cache[1]));
		}
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
			$where = 'WHERE ';
			$where .= \app\Collection::implode
				(
					' AND ', # delimiter
					$constraints, # source

					function ($k, $value) {

						$k = \strpbrk($k, ' .()') === false ? '`'.$k.'`' : $k;

						if (\is_bool($value))
						{
							return $k.' = '.($value ? 'TRUE' : 'FALSE');
						}
						else if (\is_numeric($value))
						{
							return $k.' = '.$value;
						}
						else if (\is_null($value))
						{
							return $k.' IS NULL';
						}
						else # string, or string compatible
						{
							return $k.' = '.\app\SQL::quote($value);
						}
					}
				);

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

		return (int) $statement->fetch_array()['COUNT(1)'];
	}

	/**
	 * Checks if a value exists in the table, given a key. By default the title
	 * key is assumed.
	 *
	 * Key defaults to 'title' if not set.
	 *
	 * @return boolean
	 */
	static function exists($value, $key = null, $context = null)
	{
		$key = $key === null ? 'title' : $key;

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
					   AND NOT '.static::unique_key().' <=> '.($context === null ? 'NULL' : $context).'
				'
			)
			->set(':value', $value)
			->execute()
			->fetch_array()['COUNT(1)'];

		return $count !== 0;
	}

	/**
	 * Syntactic sugar for negation of `exists`.
	 *
	 * @return boolean
	 */
	static function is_unique($value, $key = null, $context = null)
	{
		return ! static::exists($value, $key, $context);
	}

} # trait
