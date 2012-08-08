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
	protected $field_order;
	protected $paged = [null, null, 0];
	protected $tags;
	protected $table;
	protected $identity;
	protected $conditions = [];

	/**
	 * @return \app\Table_Snatcher $this
	 */
	function query($query)
	{
		$this->query = $query;

		return $this;
	}
	
	function identity($identity)
	{
		$this->identity = $identity;
		
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
	function on(array $equality_conditions)
	{
		$this->conditions = $equality_conditions;
		
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
		
		$key = $this->identity.__METHOD__.'p'.$page.'l'.$limit.'o'.$offset;
		
		// create order hash
		$sql_order = \app\Collection::implode(', ', $this->field_order, function ($k, $o) {
			return $k.' '.$o;
		});
		
		if ( ! empty($sql_order))
		{
			$sql_order = 'ORDER BY '.$sql_order;
			$key .= '__'.\sha1($sql_order);
		}

		$result = \app\Stash::get($key, null);
		
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
			
			$where = \app\Collection::implode(' AND ', $this->conditions, function ($k, $i) {
				return '`'.$k.'` = :'.$k; 
			});
			
			if ( ! empty($where))
			{
				$where = 'WHERE '.$where;
			}
			
			$sql = 
				'
					SELECT '.$query.'
					  FROM `'.$this->table.'` '.$where.'
					 LIMIT :limit OFFSET :offset '.$sql_order.'
				';
			
			 $statement = \app\SQL::prepare(__METHOD__, $sql)
				->page($page, $limit, $offset);
					
			foreach ($this->conditions as $key => $value)
			{
				$statement->set(':'.$key, $value);
			}
					
			$result = $statement->execute()->fetch_all();
			
			\app\Stash::store($key, $result, $this->tags);
		}
		
		return $result;
	}

} # class
