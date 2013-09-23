<?php namespace mjolnir\database;

/**
 * A Marionette is a object based model class.
 *
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Marionette extends \app\Puppet implements \mjolnir\types\Marionette
{
	use \app\Trait_Marionette;

	/**
	 * @var array (String => \mjolnir\types\Compiler)
	 */
	protected $driverpool;

	/**
	 * @return array normalized fieldlist
	 */
	protected function make_fieldlist($spec, array $filter = null)
	{
		$fieldlist = [ 'nums' => [], 'strs' => [], 'bools' => [] ];
		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			// do not handle model key
			if ($field === $spec['key'])
			{
				continue;
			}

			if ($filter !== null && ! \in_array($field, $filter))
			{
				continue;
			}

			if ( ! isset($fieldinfo['type']) && ! isset($fieldinfo['driver']))
			{
				throw new \app\Exception("Missing type for $field");
			}
			else if (isset($fieldinfo['driver']))
			{
				continue;
			}

			// filter abstract type to usable transport type
			switch ($fieldinfo['type'])
			{
				case 'id':
					$fieldlist['nums'][] = $field;
					break;
				case 'key':
					$fieldlist['nums'][] = $field;
					break;
				case 'number':
					$fieldlist['nums'][] = $field;
					break;
				case 'string':
					$fieldlist['strs'][] = $field;
					break;
				case 'datetime':
					$fieldlist['strs'][] = $field;
					break;
				case 'currency':
					$fieldlist['nums'][] = $field;
					break;
				default:
					throw new \app\Exception("Unsuported field type: {$fieldinfo['type']}");
			}
		}

		return $fieldlist;
	}

	// -- Caching -------------------------------------------------------------

	/**
	 * While the Marionette system, unlike the static library system doesn't
	 * come with caching of it's own, this method is always called when data is
	 * being inserted, modified or deleted to ensure other systems that do use
	 * caching can be invoked.
	 *
	 * By default this method will try to invoke the library associated with
	 * the entry if it exists, ie. the \app\ClassnameLib
	 * and \app\Model_Classname classes.
	 */
	protected function cachereset($id = null, $operation = null)
	{
		// remove namespace
		$bareclass = \preg_replace('/^.*\\\/', '', \get_called_class());
		// remove Lib suffix
		$baseclass = \preg_replace('/(Model|Collection)$/', '', $bareclass);

		// try to invoke cache reset
		$class = '\app\\'.$baseclass.'Lib';
		if (\class_exists($class))
		{
			$class::clear_cache();
		}
		else # ClassnameLib class does not exists
		{
			// try Model_Classname
			$class = '\app\\Model_'.$baseclass;
			if (\class_exists($class))
			{
				$class::clear_cache();
			}
		}
	}

	// -- Drivers -------------------------------------------------------------

	/**
	 * @return static $this
	 */
	function registerdriver($driver_id, $driver)
	{
		$this->driverpool[$driver_id] = $driver;
		return $this;
	}

	/**
	 * @return \mjolnir\types\MarionetteDriver
	 */
	protected function getdriver($field, $driver_id, $driverconfig)
	{
		if ($this->driverpool && isset($this->driverpool[$field]) && isset($this->driverpool[$field][$driver_id]))
		{
			return $this->driverpool[$field][$driver_id];
		}
		else # driver not in pool, or pool empty
		{
			// auto-resolve driver
			$class = $this->driver_class_for($driver_id);
			isset($this->driverpool[$field]) or $this->driverpool[$field] = [];
			return $this->driverpool[$field][$driver_id] = $class::instance($this->db, $this, $field, $driverconfig);
		}
	}

	/**
	 * @return string
	 */
	protected function driver_class_for($driver_id)
	{
		$classname = \app\Arr::implode
			(
				'',
				\explode('-', $driver_id),
				function ($key, $value)
				{
					return \ucfirst($value);
				}
			);

		return '\app\MarionetteDriver_'.$classname;
	}

	/**
	 * @return array processed entry
	 */
	protected function run_drivers_post_compile(array $entry)
	{
		$spec = static::config();

		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			if (isset($fieldinfo['driver']))
			{
				$driver = $this->getdriver($field, $fieldinfo['driver'], $fieldinfo);
				$entry = $driver->post_compile($entry);
			}
		}

		return $entry;
	}

	/**
	 * @return array processed entry
	 */
	protected function run_drivers_post_latecompile(array $entry, array $input)
	{
		$spec = static::config();

		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			if (isset($fieldinfo['driver']))
			{
				$driver = $this->getdriver($field, $fieldinfo['driver'], $fieldinfo);
				$entry = $driver->post_latecompile($entry, $input);

				// driver rejected entry?
				if ($entry === null)
				{
					return null;
				}
			}
		}

		return $entry;
	}

	/**
	 * @return array processed fieldlist
	 */
	protected function run_drivers_post_compilefields(array $fieldlist)
	{
		$spec = static::config();

		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			if (isset($fieldinfo['driver']))
			{
				$driver = $this->getdriver($field, $fieldinfo['driver'], $fieldinfo);
				$fieldlist = $driver->post_compilefields($fieldlist);
			}
		}

		return $fieldlist;
	}

	/**
	 * @return array processed entry
	 */
	protected function run_drivers_patch_compile($id, array $entry)
	{
		$spec = static::config();

		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			if (isset($fieldinfo['driver']))
			{
				$driver = $this->getdriver($field, $fieldinfo['driver'], $fieldinfo);
				$entry = $driver->patch_compile($id, $entry);
			}
		}

		return $entry;
	}

	/**
	 * @return array processed entry
	 */
	protected function run_drivers_patch_latecompile($id, array $entry, array $input)
	{
		$spec = static::config();

		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			if (isset($fieldinfo['driver']))
			{
				$driver = $this->getdriver($field, $fieldinfo['driver'], $fieldinfo);
				$entry = $driver->patch_latecompile($id, $entry, $input);

				// driver rejected entry?
				if ($entry === null)
				{
					return null;
				}
			}
		}

		return $entry;
	}

	/**
	 * @return array processed fieldlist
	 */
	protected function run_drivers_patch_compilefields(array $fieldlist)
	{
		$spec = static::config();

		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			if (isset($fieldinfo['driver']))
			{
				$driver = $this->getdriver($field, $fieldinfo['driver'], $fieldinfo);
				$fieldlist = $driver->patch_compilefields($fieldlist);
			}
		}

		return $fieldlist;
	}

	/**
	 * @return array processed execution plan
	 */
	protected function run_drivers_inject(array $plan)
	{
		$spec = static::config();

		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			if (isset($fieldinfo['driver']))
			{
				$driver = $this->getdriver($field, $fieldinfo['driver'], $fieldinfo);
				$plan = $driver->inject($plan);
			}
		}

		return $plan;
	}

	/**
	 * Execute drivers before delete of entry happens.
	 */
	protected function run_drivers_predelete($id)
	{
		$spec = static::config();

		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			if (isset($fieldinfo['driver']))
			{
				$driver = $this->getdriver($field, $fieldinfo['driver'], $fieldinfo);
				$driver->predelete($id);
			}
		}
	}

	/**
	 * Execute drivers before delete of entry happens.
	 */
	protected function run_drivers_postdelete($id)
	{
		$spec = static::config();

		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			if (isset($fieldinfo['driver']))
			{
				$driver = $this->getdriver($field, $fieldinfo['driver'], $fieldinfo);
				$driver->postdelete($id);
			}
		}
	}

	/**
	 * @return array
	 */
	protected function basicfieldhandlers($conf)
	{
		$spec = static::config();

		// in case keyfield is not explicitly mentioned
		if ( ! isset($spec['fields'][$this->keyfield()]))
		{
			$conf['fields'][] = $this->keyfield();
		}

		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			if ($fieldinfo['visibility'] === 'public' && isset($fieldinfo['type']))
			{
				$conf['fields'][] = $field;
			}
		}

		return $conf;
	}

	/**
	 * Generates sql injectable version of configuration.
	 *
	 * @return array
	 */
	protected function generate_executation_plan($conf, $defaults)
	{
		// Run Drivers
		// -----------

		$conf = \app\Arr::merge($defaults, $conf);
		$conf = $this->basicfieldhandlers($conf);
		$conf = $this->run_drivers_inject($conf);

		// Inject Filters
		// --------------

		if ( ! empty($this->filters))
		{
			if ($conf !== null)
			{
				if (isset($conf['constraints']))
				{
					$conf['constraints'] = \app\Arr::merge($conf['constraints'], $this->filters);
				}
				else # no constraints set
				{
					$conf['constraints'] = $this->filters;
				}
			}
			else # empty configuration
			{
				$conf = [ 'constraints' => $this->filters ];
			}
		}

		// Normalize Configuration
		// -----------------------

		$constraints = null;
		$joins = null;
		$limit = null;
		$offset = 0;

		if ($conf !== null)
		{
			if (isset($conf['limit']))
			{
				$limit = \intval($conf['limit']);
			}

			if (isset($conf['offset']))
			{
				$offset = \intval($conf['offset']);
			}

			if (isset($conf['joins']))
			{
				$joins = \app\SQL::parsejoins($conf['joins']);
			}

			if (isset($conf['constraints']))
			{
				$constraints .= \app\SQL::parseconstraints($conf['constraints']);
			}
		}

		$constraints = \trim($constraints);
		empty($constraints) or $constraints = "WHERE $constraints";
		empty($limit) or $limit = "LIMIT $limit OFFSET $offset";

		if (empty($conf['fields']))
		{
			$fields = '*';
		}
		else // fields
		{
			$fields = \app\Arr::implode
				(
					', ',
					$conf['fields'],
					function ($k, $field)
					{
						if (\strpos($field, '.') === false)
						{
							return "`$field`";
						}
						else # field contains dot, may be reference from table
						{
							return $field;
						}
					}
				);
		}

		// all done!
		return array
			(
				'fields' => $fields,
				'joins' => $joins,
				'limit' => $limit,
				'constraints' => $constraints,
				'postprocessors' => & $conf['postprocessors']
			);
	}

} # class
