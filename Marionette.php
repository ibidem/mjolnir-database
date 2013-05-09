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
	 * @var \mjolnir\types\SQLDatabase
	 */
	protected $db;
	
	/**
	 * @var array (String => \mjolnir\types\Compiler)
	 */
	protected $driverpool;
	
	/**
	 * @return static
	 */
	static function instance(\mjolnir\types\SQLDatabase $db = null)
	{
		if ($db == null)
		{
			$db = \app\SQLDatabase::instance();
		}
		
		$in = parent::instance();
		$in->db = $db;
		
		return $in;
	}

	// -- Utility -------------------------------------------------------------
	
	/**
	 * @return string
	 */
	static function table()
	{
		$dbconfig = \app\CFS::config('mjolnir/database');
		
		if (isset(static::$table))
		{
			return $dbconfig['table_prefix'].static::$table;
		}
		else # static::$table attribute not provided
		{
			return $dbconfig['table_prefix'].static::codegroup();
		}
	}
	
	/**
	 * @return type
	 */
	static function config()
	{
		static $config = null;
		
		if ($config === null)
		{
			if (isset(static::$configfile))
			{
				$config = \app\CFS::config(static::$configfile);
			}
			else # dynamically resolve configuration
			{
				$configfile = \str_replace('_', '-', \strtolower(\preg_replace('/.*\\\/', '', \get_called_class())));
				$configfile = \preg_replace('#(model|collection)$#', '', $configfile);
				$config = \app\CFS::config($configfile);
			}
			
			if (empty($config))
			{
				throw new \app\Exception('Missing configuration file for '.\get_called_class().'. File: '.(isset(static::$configfile) ? static::$configfile : $configfile));
			}
			
			static::normalizeconfig($config);
		}
		
		return $config;
	}
	
	/**
	 * @return array
	 */
	protected static function normalizeconfig(array & $config)
	{
		isset($config['key']) or $config['key'] = 'id';
		isset($config['fields']) or $config['fields'] = [];
		
		foreach ($config['fields'] as $field => & $fieldconf)
		{
			if (\is_string($fieldconf))
			{
				$fieldconf = [ 'type' => $fieldconf, 'visibility' => 'public' ];
			}
			else # array
			{
				if ( ! isset($fieldconf['visibility']))
				{
					$fieldconf['visibility'] = 'public';
				}
			}
		}
	}
	
	// -- Context -------------------------------------------------------------
	
	/**
	 * @return string
	 */
	static function singular()
	{
		return static::config()['name'];
	}
	
	/**
	 * @return string
	 */
	static function plural()
	{
		$config = static::config();
		return isset($config['plural']) ? $config['plural'] : $config['name'].'s';
	}
	
	/**
	 * @return string
	 */
	static function keyfield()
	{
		return static::config()['key'];
	}
	
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
		else # driver no in pool, or pool empty
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
	protected function run_drivers_compile(array $entry)
	{
		$spec = static::config();
		
		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			if (isset($fieldinfo['driver']))
			{
				$driver = $this->getdriver($field, $fieldinfo['driver'], $fieldinfo);
				$entry = $driver->compile($entry);
			}
		}
		
		return $entry;
	}
	
	/**
	 * @return array processed entry
	 */
	protected function run_drivers_latecompile(array $entry, array $input)
	{
		$spec = static::config();
		
		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			if (isset($fieldinfo['driver']))
			{
				$driver = $this->getdriver($field, $fieldinfo['driver'], $fieldinfo);
				$entry = $driver->latecompile($entry, $input);
				
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
	protected function run_drivers_compilefields(array $fieldlist)
	{
		$spec = static::config();
		
		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			if (isset($fieldinfo['driver']))
			{
				$driver = $this->getdriver($field, $fieldinfo['driver'], $fieldinfo);
				$fieldlist = $driver->compilefields($fieldlist);
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
		$conf = \app\Arr::merge($defaults, $conf);
		$conf = $this->basicfieldhandlers($conf);
		$conf = $this->run_drivers_inject($conf);
		
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
