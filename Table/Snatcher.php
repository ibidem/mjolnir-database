<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012 Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Table_Snatcher extends \app\Instantiatable
{
	protected $query;
	protected $field_order = [];
	protected $paged = [null, null, 0];
	protected $tags;
	protected $table;
	protected $identity;
	protected $id;
	protected $constraints = [];

	/**
	 * @return static $this
	 */
	function query($query)
	{
		$this->query = $query;

		return $this;
	}

	/**
	 * @return static $this
	 */
	function identity($identity)
	{
		$this->identity = $identity;

		return $this;
	}

	/**
	 * @return static $this
	 */
	function id($id)
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * @return static $this
	 */
	function table($table)
	{
		$this->table = $table;

		return $this;
	}

	/**
	 * @return static $this
	 */
	function timers(array $tags)
	{
		$this->tags = $tags;

		return $this;
	}

	/**
	 * @return static $this
	 */
	function constraints(array $constraints = null)
	{
		if ($constraints !== null)
		{
			$this->constraints = $constraints;
		}
		else # null constraints
		{
			$this->constraints = [];
		}

		return $this;
	}

	/**
	 * @return static $this
	 */
	function order(array $field_order = null)
	{
		if ($field_order !== null)
		{
			$this->field_order = $field_order;
		}
		else # null field order
		{
			$this->field_order = [];
		}

		return $this;
	}

	/**
	 * Sepcify pages for query.
	 */
	function page($page, $limit, $offset = 0)
	{
		$page = $page === null ? null : (int) $page;
		$limit = $limit === null ? null : (int) $limit;
		$offset = (int) $offset;

		$this->paged = [$page, $limit, $offset];

		return $this;
	}

	/**
	 * @return array of arrays
	 */
	function fetch_all($fieldformat = [])
	{
		$page = $this->paged[0];
		$limit = $this->paged[1];
		$offset = $this->paged[2];

		if (empty($this->table))
		{
			throw new \app\Exception
				('Table not provided for snatch query.');
		}

		if (empty($this->identity))
		{
			throw new \app\Exception
				('Identity not provided for snatch query.');
		}

		if (empty($this->id))
		{
			throw new \app\Exception
				('Id not provided for snatch query.');
		}

		$cache_key = $this->identity.'_'.$this->id.'__Snatch_fetch_all__'.'p'.$page.'l'.$limit.'o'.$offset;

		// create order hash
		$sql_order = \app\Arr::implode(', ', $this->field_order, function ($k, $o) {
			$k = \strpbrk($k, ' .()') === false ? '`'.$k.'`' : $k;
			return $k.' '.$o;
		});

		if ( ! empty($sql_order))
		{
			$sql_order = 'ORDER BY '.$sql_order;
			$cache_key .= '__'.\sha1($sql_order);
		}

		// where hash
		$where = '';
		if ( ! empty($this->constraints))
		{
			$constraints = \app\SQL::parseconstraints($this->constraints);
			if ( ! empty($constraints))
			{
				$where = 'WHERE '.$constraints;
				$cache_key .= '__'.\sha1($where);
			}
		}

		$result = \app\Stash::get($cache_key, null);

		if ($result === null)
		{
			if ($this->query[0] == '*')
			{
				$query = '*';
			}
			else # query is not *
			{
				$query = \app\Arr::implode(', ', $this->query, function ($k, $i) {
					return '`'.$i.'`';
				});
			}

			$sql =
				'
					SELECT '.$query.'
					  FROM `'.$this->table.'` entry
					  '.$where.'
					  '.$sql_order.'
					 LIMIT :limit OFFSET :offset
				';

			$statement = \app\SQL::prepare(__METHOD__, $sql)
				->page($page, $limit, $offset);

			$result = $statement->run()->fetch_all();
			\app\Stash::store($cache_key, $result, $this->tags);
		}

		foreach ($result as &$entry)
		{
			\app\SQLStatement::format_entry($entry, $fieldformat);
		}

		return $result;
	}

} # class
