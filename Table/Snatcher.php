<?php namespace ibidem\database;

/**
 * @package    ibidem
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
	 * @return \app\Table_Snatcher $this
	 */
	function query($query)
	{
		$this->query = $query;

		return $this;
	}
	
	/**
	 * @return \app\Table_Snatcher $this
	 */
	function identity($identity)
	{
		$this->identity = $identity;
		
		return $this;
	}
	
	/**
	 * @return \app\Table_Snatcher $this
	 */
	function id($id)
	{
		$this->id = $id;
		
		return $this;
	}
	
	/**
	 * @return \app\Table_Snatcher $this
	 */
	function table($table)
	{
		$this->table = $table;
		
		return $this;
	}
	
	/**
	 * @param array tags
	 * @return \app\Table_Snatcher $this
	 */
	function timers(array $tags)
	{
		$this->tags = $tags;
		
		return $this;
	}
		
	/**
	 * @return \app\SQLStash $this
	 */
	function constraints(array $constraints)
	{
		$this->constraints = $constraints;
		
		return $this;
	}

	/**
	 * @return \app\Table_Snatcher $this
	 */
	function order(array $field_order)
	{
		$this->field_order = $field_order;

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
	function fetch_all()
	{
		$page = $this->paged[0];
		$limit = $this->paged[1];
		$offset = $this->paged[2];
		
		if (empty($this->table))
		{
			throw new \app\Exception_NotApplicable
				('Table not provided for snatch query.');
		}
		
		if (empty($this->identity))
		{
			throw new \app\Exception_NotApplicable
				('Identity not provided for snatch query.');
		}
		
		if (empty($this->id))
		{
			throw new \app\Exception_NotApplicable
				('Id not provided for snatch query.');
		}
		
		$cache_key = $this->identity.'_'.$this->id.'__Snatch_fetch_all__'.'p'.$page.'l'.$limit.'o'.$offset;
		
		// create order hash
		$sql_order = \app\Collection::implode(', ', $this->field_order, function ($k, $o) {
			return $k.' '.$o;
		});
		
		if ( ! empty($sql_order))
		{
			$sql_order = 'ORDER BY '.$sql_order;
			$cache_key .= '__'.\sha1($sql_order);
		}
		
		// where hash	
		$where = \app\Collection::implode
			(
				' AND ', # delimiter
				$this->constraints, # source

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
		
		if ( ! empty($where))
		{
			$where = 'WHERE '.$where;
			$cache_key .= '__'.\sha1($where);
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
				$query = \app\Collection::implode(', ', $this->query, function ($k, $i) {
					return '`'.$i.'`';
				});
			}
			
			$sql = 
				'
					SELECT '.$query.'
					  FROM `'.$this->table.'` '.$where.' '.$sql_order.'
					 LIMIT :limit OFFSET :offset 
				';
			
			 $statement = \app\SQL::prepare(__METHOD__, $sql)
				->page($page, $limit, $offset);
					
			$result = $statement->execute()->fetch_all();
			
			\app\Stash::store($cache_key, $result, $this->tags);
		}
		
		return $result;
	}

} # class
