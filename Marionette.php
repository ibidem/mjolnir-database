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
		return $dbconfig['table_prefix'].static::codegroup();
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
				$config = \app\CFS::config(static::configfile);
			}
			else # dynamically resolve configuration
			{
				$configfile = \str_replace('_', '-', \strtolower(\preg_replace('/.*\\\/', '', \get_called_class())));
				$configfile = \preg_replace('#(model|collection)$#', '', $configfile);
				$config = \app\CFS::config($configfile);
			}
			
			static::normalize_config($config);
		}
		
		return $config;
	}
	
	/**
	 * @return array
	 */
	protected static function normalize_config(array & $config)
	{
		isset($config['key']) or $config['key'] = 'id';
		isset($config['fields']) or $config['fields'] = [];
		
		foreach ($config['fields'] as $field => & $fieldconf)
		{
			if (\is_string($fieldconf))
			{
				$fieldconf = [ 'type' => $fieldconf ];
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
	protected function getdriver($driver_id)
	{
		if ($this->driverpool && $this->driverpool[$driver_id])
		{
			return $this->driverpool[$driver_id];
		}
		else # driver no in pool, or pool empty
		{
			// auto-resolve driver
			$class = $this->driver_class_for($driver_id);
			return $this->driverpool[$driver_id] = $class::instance($this->db);
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
	 * @return return processed entry
	 */
	protected function run_drivers(array $entry)
	{
		$spec = static::config();
		
		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			if (isset($fieldinfo['driver']))
			{
				$driver = $this->getdriver($fieldinfo['driver']);
				$entry = $driver->compile($field, $entry, $fieldinfo);
			}
		}
		
		return $entry;
	}
	
} # class
