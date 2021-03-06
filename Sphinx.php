<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Sphinx extends \app\Instantiatable implements \mjolnir\types\Paged
{
	use \app\Trait_Paged;

	/**
	 * @var \SphinxClient
	 */
	protected $sphinx = null;

	/**
	 * @return static
	 */
	static function instance()
	{
		$instance = parent::instance();

		$config = \app\CFS::config('mjolnir/sphinx');

		$sphinx = $instance->sphinx = new \SphinxClient();
		$sphinx->SetServer($config['searchd']['host'], $config['searchd']['listen']['api']);

		$sphinx->SetMatchMode($config['default.matchmode']);
		$sphinx->SetSelect('*');
		$sphinx->SetSortMode($config['default.sortmode']);
		$sphinx->SetConnectTimeout($config['timeout']);

		return $instance;
	}

	/**
	 * @return static $this
	 */
	function filter($attribute, $values, $exclude = false)
	{
		$this->sphinx->SetFilter($attribute, $values, $exclude);
		return $this;
	}

	/**
	 * @return static $this
	 */
	function matchmode($matchmode)
	{
		$this->sphinx->SetMatchMode($matchmode);
		return $this;
	}

	/**
	 * @return static $this
	 */
	function sortmode($sortmode)
	{
		$this->sphinx->SetSortMode($sortmode);
		return $this;
	}

	/**
	 * @return static $this
	 */
	function page($page, $limit = null, $offset = 0)
	{
		if ($page !== null && $limit !== null)
		{
			$this->sphinx->SetLimits($offset + ($page - 1) * $limit, $limit);
		}

		return $this;
	}

	/**
	 * @return array ids
	 */
	function fetch_ids($search, $index = '*')
	{
		$result = $this->sphinx->Query($search, $index);

		// check for errors

		$error = $this->sphinx->GetLastError();

		if ( ! empty($error))
		{
			\mjolnir\log('Sphinx', $error);
			throw new \Exception($error);
		}

		// check for warnings

		$warning = $this->sphinx->GetLastWarning();

		if ( ! empty($warning))
		{
			\mjolnir\log('Sphinx', $warning);
		}

		if ($result === false)
		{
			throw new \Exception('Sphinx Error - '.$this->sphinx->GetLastError());
		}
		else if (empty($result['error']))
		{
			return empty($result['matches']) ? [] : \array_keys($result['matches']);
		}
		else # got error
		{
			\mjolnir\log('Sphinx', $result['error']);
			throw new \Exception($result['error']);
		}
	}

} # class
