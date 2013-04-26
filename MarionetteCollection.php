<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class MarionetteCollection extends \app\Marionette implements \mjolnir\types\MarionetteCollection
{
	use \app\Trait_MarionetteCollection;
	
	/**
	 * @return \mjolnir\types\MarionetteModel for this collection
	 */
	function model(\mjolnir\types\MarionetteModel $model = null)
	{
		static $model_instance = null;
		
		if ($model !== null)
		{
			$model_instance = $model;
		}
		else if ($model_instance === null)
		{
			$class = '\app\\'.$this->camelsingular().'Model';
			$model_instance = $class::instance($this->db);
		}
		
		return $model_instance;
	}
	
#
# The GET process
#
	
	/**
	 * Retrieve collection members.
	 * 
	 * @return array
	 */
	function get(array $conf = null)
	{
		// forbid JOIN injection
		$conf['joins'] = null;
		
		$defaults = array
			(
				'fields' => null,
				'joins' => null,
				'group_by' => null,
				'constraints' => null,
				'limit' => null,
				'offset' => 0,
			);
		
		$conf = \app\Arr::merge($defaults, $conf);
		
		$this->run_drivers_inject($conf);
		
		// join format: ['table' => static::table(), 'ref' => 'something.id', 'for' => 'this.something'];
		
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
			
			if (isset($conf['constraints']))
			{
				$constraints .= \app\SQL::parseconstraints($conf['constraints']);
			}
		}
		
		$constraints = \trim($constraints);
		empty($constraints) or $constraints = "WHERE $constraints";
		empty($limit) or $limit = "LIMIT $limit OFFSET $offset";
		
		return $this->db->prepare
			(
				__METHOD__,
				'
					SELECT *
					  FROM `'.static::table().'`
					'.$constraints.'  
					'.$limit.'
				'
			)
			->run()
			->fetch_all();
	}
	
#
# The POST process
#

	/**
	 * [post] generates a new entry. [post] expects all fields in raw form and 
	 * on success returns the created version of the entry, primarily this means
	 * the fields provided with the id, but results may vary.
	 * 
	 * In case of validation failure null is returned so the callee may know if
	 * they can provide validation information to the initial caller in hopes
	 * of getting a passing state. Any other failure will be thrown as an 
	 * exception, because it's assumed as unrecoverable.
	 * 
	 * If there is a special exception state that is recoverable, simply return 
	 * null from do_create.
	 */
	function post(array $entry)
	{
		// 1. normalize entry
		$entry = $this->parse($entry);
		
		try
		{
			$this->db->begin();
			
			// 2. run drivers against entry
			$entry = $this->run_drivers_compile($entry);
			
			// 3. check for errors
			$auditor = $this->auditor();
			if ($auditor->fields_array($entry)->check())
			{
				// 4. persist to database
				$entry_id = $this->do_post($entry);
				
				// success?
				if ($entry_id !== null)
				{
					$this->db->commit();
					return $this->model()->get($entry_id);
				}
				else # recoverable failure
				{
					$this->db->rollback();
					return null;
				}
			}
			else # failed validation; recoverable failure
			{
				$this->db->rollback();
				return null;
			}
		}
		catch (\Exception $e)
		{
			$this->db->rollback();	
			throw $e;
		}
	}
	
	# 1. normalize entry

	/**
	 * Normalizing value format, filling in optional components, etc.
	 * 
	 * @return array normalized entry
	 */
	final function parse(array $input)
	{
		return $this->model()->parse($input);
	}
	
	# 2. run drivers against entry
	
		# see: Marionette
	
	# 3. check for errors
	
	/**
	 * Auditor should always handle parsed values.
	 * 
	 * @return \mjolnir\types\Validator
	 */
	final function auditor()
	{
		return $this->model()->auditor();
	}
	
	# 4. persist to database
	
	/**
	 * @return int new entry id
	 */
	protected function do_post($entry)
	{
		// create field list
		$spec = static::config();
		$fieldlist = $this->make_fieldlist($spec);

		// inject driver based dependencies
		$fieldlist = $this->run_drivers_compilefields($fieldlist);
		
		// create templates
		$fields = [];
		foreach ($fieldlist as $type => $list)
		{
			foreach ($list as $fieldname)
			{
				$fields[] = $fieldname;
			}
		}
		
		$sqlfields = \app\Arr::implode
			(
				', ', 
				$fields, 
				function ($key, $in)
				{
					return "`$in`";
				}
			);
			
		$keyfields = \app\Arr::implode
			(
				', ', 
				$fields, 
				function ($key, $in)
				{
					return ":$in "; # space is intentional
				}
			);
		
		$this->db->prepare
			(
				__METHOD__,
				'
					INSERT INTO `'.static::table().'` 
					       ('.$sqlfields.')
					VALUES ('.$keyfields.')
				'
			)
			->strs($entry, $fieldlist['strs'])
			->nums($entry, $fieldlist['nums'])
			->bools($entry, $fieldlist['bools'])
			->run();
		
		return $this->db->last_inserted_id();
	}
	
#
# The PUT process
#
	
	/**
	 * Replace entire collection.
	 * 
	 * @return static $this
	 */
	function put(array $collection)
	{
		$this->db->begin();
		
		try
		{
			$this->delete();
			foreach ($collection as $entry)
			{
				$this->post($entry);
			}
			
			$this->commit();
		}
		catch (\Exception $e)
		{
			$this->db->rollback();
			throw $e;
		}
		
		return $this;
	}

#
# The DELETE process
#

	/**
	 * Empty collection.
	 * 
	 * @return static $this
	 */
	function delete()
	{
		$this->db->prepare
			(
				__METHOD__,
				'
					DELETE FROM `'.static::table().'`
				'
			)
			->run();
	}
	
} # class
