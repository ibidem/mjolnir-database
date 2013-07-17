<?php namespace mjolnir\database;

/**
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
			->fetch_all(static::fieldformat());
	}

	/**
	 * Given a list of ids, this function will return an equivalent result to
	 * entries for those ids. The unique_key is assumed numeric and as a
	 * consequence this function will treat it as such.
	 *
	 * For different behaviour re-implement this function in your class.
	 *
	 * You may pass constraints as a security check.
	 *
	 * @return array of arrays
	 */
	static function select_entries(array $entries = null, array $constraints = null)
	{
		if (empty($entries))
		{
			return [];
		}

		$cache_key = __FUNCTION__.'__entries'.\implode(',', $entries);

		$constraintskey = '';
		if ( ! empty($constraints))
		{
			$constraintskey = \app\SQL::parseconstraints($constraints);
			if ( ! empty($constraintskey))
			{
				$constraintskey = ' AND '.$constraintskey;
			}
		}

		return static::stash
			(
				__METHOD__,
				'
					SELECT *
					  FROM :table
					 WHERE `'.static::unique_key().'` IN ('.\app\Arr::implode(', ', $entries, function ($i, $v) { return \app\SQL::quote($v); }).')
						   '.$constraintskey.'
				'
			)
			->key($cache_key)
			->fetch_all(static::fieldformat());
	}

	/**
	 * You may pass constraints to ensure any security conditions. Constraints
	 * won't be taken into account when caching.
	 *
	 * @return array
	 */
	static function entry($id, array $constraints = null)
	{
		$cachekey = \get_called_class().'_ID'.$id;
		$entry = \app\Stash::get($cachekey, null);

		if ($entry === null)
		{
			$constraintskey = '';
			if ( ! empty($constraints))
			{
				$constraintskey = \app\SQL::parseconstraints($constraints);
				if ( ! empty($constraintskey))
				{
					$constraintskey = ' AND '.$constraintskey;
				}
			}

			$entry = static::statement
				(
					__METHOD__,
					'
						SELECT *
						  FROM :table
						 WHERE '.static::unique_key().' = :id '.$constraintskey.'
					'
				)
				->num(':id', $id)
				->run()
				->fetch_entry();

			\app\Stash::store($cachekey, $entry, \app\Stash::tags(\get_called_class(), ['change']));
		}

		if ($entry !== null)
		{
			\app\SQLStatement::format_entry($entry, static::fieldformat());
		}

		return $entry;
	}

	/**
	 * Utility function for creating the search query along with the
	 * coresponding order by query.
	 */
	protected static function search_query_parameters($term, array &$columns, &$where, &$order)
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
			->fetch_all(static::fieldformat());

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
	 * Shorthand for entries.
	 *
	 * @return array of arrays
	 */
	static function find(array $criteria, $page = null, $limit = null, $offset = null)
	{
		return static::entries($page, $limit, $offset, [], $criteria);
	}

	/**
	 * Shorthand for find when retrieving single entry.
	 *
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
	 * Wipes cash for specified tags
	 */
	static function clear_cache($tags = ['change'])
	{
		\app\Stash::purge(\app\Stash::tags(\get_called_class(), $tags));

		// reset related caches
		foreach (static::related_caches() as $related_cache)
		{
			\app\Stash::purge(\app\Stash::tags($related_cache[0], $related_cache[1]));
		}
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
		else # standard field name
		{
			return 'id';
		}
	}

	/**
	 * Deletes given entries (primary key assumed). Constraints can be used to
	 * ensure no entries not belonging to the given entity are deleted.
	 */
	static function delete(array $entries, array $constraints = null)
	{
		$entry = null;

		if ( ! empty($constraints))
		{
			$constraintkey = \app\SQL::parseconstraints($constraints);
			if ( ! empty($constraintkey))
			{
				$constraintkey = ' AND '.$constraintkey;
			}
		}
		else # no constraints
		{
			$constraintkey = '';
		}

		$statement = static::statement
			(
				__METHOD__,
				'
					DELETE FROM :table
					 WHERE `'.static::unique_key().'` = :id '.$constraintkey.'
				'
			)
			->bindnum(':id', $entry);

		\app\SQL::begin();

		foreach ($entries as $entry)
		{
			$statement->run();
			static::clear_entry_cache($entry);
		}

		\app\SQL::commit();

		static::clear_cache();

		// reset related caches
		foreach (static::related_caches() as $related_cache)
		{
			\app\Stash::purge(\app\Stash::tags($related_cache[0], $related_cache[1]));
		}
	}

	/**
	 * @return int
	 */
	static function count($constraints = null)
	{
		$cachekey = __FUNCTION__;
		$where = '';
		if ( ! empty($constraints))
		{
			$where = \app\SQL::parseconstraints($constraints);
			if ( ! empty($where))
			{
				$where = 'WHERE '.$where;
				$cachekey .= '__'.\sha1($where);
			}
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

		return (int) $statement->fetch_entry()['COUNT(1)'];
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
			->str(':value', $value)
			->run()
			->fetch_entry()['COUNT(1)'];

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

	/**
	 * Remove all entries from the table.
	 */
	static function truncate()
	{
		// we could do a truncate, but truncate won't resolve any extra logic
		// that might be required when deleting entries, so it's safer to
		// grab all the IDs and do a delete. Also if any constraints are set,
		// which (when constraits are used) is the case 90% of the time,
		// truncate will simply not work

		static::delete
			(
				\app\Arr::gather
					(
						static::statement
							(
								__METHOD__,
								'
									SELECT `'.static::unique_key().'`
									  FROM :table
								'
							)
							->run()
							->fetch_all(),
						static::unique_key()
					)
			);

		static::clear_cache();
	}

	/**
	 * Re-assignes ID. The id key is based on unique_key.
	 */
	static function reforge_id($old_id)
	{
		$idkey = static::unique_key();

		static::statement
			(
				__METHOD__,
				'
					INSERT :table (`'.$idkey.'`) VALUES (null)
				'
			)
			->run();

		$new_id = \app\SQL::last_inserted_id();

		static::statement
			(
				__METHOD__,
				'
					DELETE FROM :table
					 WHERE `'.$idkey.'` = :new_id
				'
			)
			->num(':new_id', $new_id)
			->run();

		static::statement
			(
				__METHOD__,
				'
					UPDATE :table
					   SET `'.$idkey.'` = :new_id
					 WHERE `'.$idkey.'` = :old_id
				'
			)
			->num(':old_id', $old_id)
			->num(':new_id', $new_id)
			->run();

		static::clear_cache();

		return $new_id;
	}

} # trait
