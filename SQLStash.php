<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012 Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class SQLStash extends \app\Instantiatable implements \mjolnir\types\SQLStatement
{
	use \app\Trait_SQLStatement;

	protected $sql;
	protected $identifier;
	protected $tags = [];
	protected $identity;
	protected $table;

	protected $page = null;
	protected $partial_key;

	protected $strs = [];
	protected $nums = [];
	protected $bools = [];
	protected $dates = [];

	protected $order;
	protected $group_by;
	protected $constraints = [];

	/**
	 * @return \app\SQLStash
	 */
	static function prepare($identifier, $sql)
	{
		$instance = static::instance();
		$instance->sql = $sql;
		$instance->identifier = $identifier;

		return $instance;
	}

	/**
	 * Once a timer is called the cache resets itself.
	 */
	function timers(array $tags)
	{
		$this->tags = $tags;

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
	function constraints(array $constraints)
	{
		$this->constraints = $constraints;

		return $this;
	}

	/**
	 * Sets the identity of the operation; to be used when processing cache
	 * effects
	 *
	 * @return static $this
	 */
	function is($identity)
	{
		$this->identity = $identity;

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
	function str($param, $value)
	{
		$this->strs[$param] = $value;

		return $this;
	}

	/**
	 * @return static $this
	 */
	function num($param, $value)
	{
		$this->nums[$param] = $value;

		return $this;
	}

	/**
	 * @return static $this
	 */
	function bool($param, $value, array $map = null)
	{
		$this->bools[$param] = $this->booleanize($value, $map);

		return $this;
	}

	/**
	 * @return static $this
	 */
	function date($param, $value)
	{
		$this->dates[$param] = $value;

		return $this;
	}

	/**
	 * @return static $this
	 */
	function bindstr($param, &$variable)
	{
		return $this->str($param, $variable);
	}

	/**
	 * @return static $this
	 */
	function bindnum($param, &$variable)
	{
		return $this->num($param, $variable);
	}

	/**
	 * @return static $this
	 */
	function bindbool($param, &$variable)
	{
		return $this->bool($param, $variable);
	}

	/**
	 * @return static $this
	 */
	function binddate($param, &$variable)
	{
		return $this->date($param, $variable);
	}

	/**
	 * @return static $this
	 */
	function arg($param, &$variable)
	{
		throw new \app\Exception('Operation not supported by SQLStash.');
	}

	/**
	 * @return static $this
	 */
	function order(array &$order)
	{
		$this->order = $order;

		return $this;
	}

	/**
	 * @return static $this
	 */
	function key($partial_key)
	{
		$this->partial_key = $partial_key;

		return $this;
	}

	/**
	 * @return static $this
	 */
	function page($page, $limit = null, $offset = 0)
	{
		$page = $page === null ? null : (int) $page;
		$limit = $limit === null ? null : (int) $limit;
		$offset = (int) $offset;
		$this->page = [$page, $limit, $offset];

		return $this;
	}

	/**
	 * @return \app\SQLStash $this
	 */
	function group_by($statement)
	{
		$this->group_by = $statement;
		return $this;
	}

	/**
	 * Executes the given query, and processes cache consequences.
	 */
	function run()
	{
		$statement = \app\SQL::prepare($this->identifier, \strtr($this->sql, [':table' => '`'.$this->table.'`']));

		static::process_statement($statement);

		$statement->run();

		// invalidte tags
		\app\Stash::purge($this->tags);

		return $this;
	}

	/**
	 * @return static $this
	 */
	function fetch_object($class = 'stdClass', array $args = null)
	{
		throw new \app\Exception('Operation not supported by SQLStash.');
	}

	/**
	 * Excutes and caches, or just returns from cache if present.
	 *
	 * @return array rows
	 */
	function fetch_all(array $format = null)
	{
		if (empty($this->identity))
		{
			throw new \app\Exception
				('Identity not provided for stash query.');
		}

		if (empty($this->partial_key))
		{
			throw new \app\Exception
				('Partial key not provided for stash query.');
		}

		$cachekey = $this->identity;

		if ( ! empty($this->partial_key))
		{
			$cachekey .= '__'.$this->partial_key;
		}

		$sql = $this->sql;
		if ( ! empty($this->constraints))
		{
			$constraints = ' WHERE ';
			$constraints .= \app\Arr::implode
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

			$sql .= $constraints;
			$cachekey .= '__con'.\sha1($constraints);
		}

		if ( ! empty($this->group_by))
		{
			$group_by = ' GROUP BY '.$this->group_by;
			$sql .= $group_by;
			$cachekey .= '__groupby'.\sha1($group_by);
		}

		if ( ! empty($this->order))
		{
			$order = ' ORDER BY ';
			$order .= \app\Arr::implode(', ', $this->order, function ($query, $order) {
				return \strpbrk($query, ' .') === false ? '`'.$query.'` '.$order : $query.' '.$order;
			});

			$sql .= $order;
			$cachekey .= '__order'.\sha1($order);
		}

		if ( ! empty($this->page))
		{
			$sql .= ' LIMIT :limit OFFSET :offset';
			$cachekey .= '__p'.$this->page[0].'l'.$this->page[1].'o'.$this->page[2];
		}

		$result = \app\Stash::get($cachekey, null);

		if ($result === null)
		{
			if (empty($this->table))
			{
				throw new \app\Exception
					('Table not provided for stash query.');
			}

			$statement = \app\SQL::prepare($this->identifier, \strtr($sql, [':table' => '`'.$this->table.'`']));

			if ($this->page !== null)
			{
				$statement->page($this->page[0], $this->page[1], $this->page[2]);
			}

			static::process_statement($statement);

			$result = $statement->run()->fetch_all();

			\app\Stash::store($cachekey, $result, $this->tags);
		}

		foreach ($result as &$entry)
		{
			\app\SQLStatement::format_entry($entry, $format);
		}

		return $result;
	}

	/**
	 * Excutes and caches, or just returns from cache if present.
	 *
	 * @return array single row
	 */
	function fetch_entry(array $format = null)
	{
		$result = $this->fetch_all($format);

		if (empty($result))
		{
			return null;
		}
		else
		{
			return $result[0];
		}
	}

	/**
	 * Include all sets in statement
	 */
	protected function process_statement($statement)
	{
		$statement->strs($this->strs, null, null);
		$statement->nums($this->nums, null, null);
		$statement->bools($this->bools, null, null, null);
		$statement->dates($this->dates, null, null);
	}

} # class