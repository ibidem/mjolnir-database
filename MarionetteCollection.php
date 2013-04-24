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
			$class = $this->camelsingular().'Model';
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
	function get(array $conf)
	{
		throw new \app\Exception_NotImplemented();
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
		$entry = $this->parse($entry);
		$auditor = $this->auditor();
		
		try
		{
			$this->db->begin();
			$entry = $this->run_drivers($entry);
			if ($auditor->fields_array($entry)->check())
			{
				$new_entry = $this->do_post($entry);
				
				// success?
				if ($new_entry !== null)
				{
					$this->db->commit();
					return $new_entry;
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
	function parse(array $input)
	{
		return $input;
	}
	
	# 2. check for errors
	
	/**
	 * Auditor should always handle parsed values.
	 * 
	 * @return \mjolnir\types\Validator
	 */
	function auditor()
	{
		return \app\Auditor::instance();
	}
	
	# 3. run drivers against entry
	
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
	
	# 4. persist to database
	
	/**
	 * @return array
	 */
	protected function do_post($entry)
	{
		// create field list
		$spec = static::config();
		$fieldlist = $this->make_fieldlist($spec);

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
					INSERT INTO :table 
					       ('.$sqlfields.') 
					VALUES ('.$keyfields.')
				'
			)
			->strs($entry, $fieldlist['strs'])
			->nums($entry, $fieldlist['nums'])
			->bools($entry, $fieldlist['bools'])
			->str(':table', static::table())
			->run();
		
		$entry_id = $this->db->last_inserted_id();
		
		return $this->model()->get($entry_id);
	}
	
	/**
	 * @return array normalized fieldlist
	 */
	protected function make_fieldlist($spec)
	{
		$fieldlist = [ 'nums' => [], 'strs' => [], 'bools' => [] ];
		foreach ($spec['fields'] as $field => $fieldinfo)
		{
			// do not handle model key
			if ($field === $spec['key'])
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
		throw new \app\Exception_NotImplemented();
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
		throw new \app\Exception_NotImplemented();
	}
	
} # class
