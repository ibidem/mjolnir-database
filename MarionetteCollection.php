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
	function get(array $conf)
	{
		return $this->db->prepare
			(
				__METHOD__,
				'
					SELECT *
					  FROM `'.static::table().'`
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
		$entry = $this->parse($entry);
		
		try
		{
			$this->db->begin();
			$entry = $this->run_drivers($entry);
			
			$auditor = $this->auditor();
			if ($auditor->fields_array($entry)->check())
			{
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
	
	# 2. check for errors
	
	/**
	 * Auditor should always handle parsed values.
	 * 
	 * @return \mjolnir\types\Validator
	 */
	final function auditor()
	{
		return $this->model()->auditor();
	}
	
	# 3. run drivers against entry
	
		# see: Marionette
	
	# 4. persist to database
	
	/**
	 * @return int new entry id
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
