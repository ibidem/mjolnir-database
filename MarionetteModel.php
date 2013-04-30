<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class MarionetteModel extends \app\Marionette implements \mjolnir\types\MarionetteModel
{
	use \app\Trait_MarionetteModel;

	/**
	 * @return \mjolnir\types\MarionetteModel for this collection
	 */
	function collection(\mjolnir\types\MarionetteCollection $collection = null)
	{
		static $collection_instance = null;
		
		if ($collection !== null)
		{
			$collection_instance = $collection;
		}
		else if ($collection_instance === null)
		{
			$class = '\app\\'.$this->camelsingular().'Collection';
			$collection_instance = $class::instance($this->db);
		}
		
		return $collection_instance;
	}
	
#
# The GET process
#
	
	/**
	 * Retrieve collection members.
	 * 
	 * @return array
	 */
	function get($id)
	{
		$defaults = array
			(
				'fields' => null,
				'joins' => null,
				'constraints' => [ static::keyfield() => $id ],
				'limit' => 1,
				'offset' => 0,
				'postprocessors' => null,
			);
		
		$plan = $this->generate_executation_plan([], $defaults);
				
		$entry = $this->db->prepare
			(
				__METHOD__,
				'
					SELECT '.$plan['fields'].'
					'.$plan['joins'].'
					  FROM `'.static::table().'`
					'.$plan['constraints'].'
				'
			)
			->run()
			->fetch_entry();
		
		if ($plan['postprocessors'] !== null)
		{
			foreach ($plan['postprocessors'] as $processor)
			{
				$entry = $processor($entry);
			}
			
			return $entry;
		}
		else # no postprocessors step
		{
			return $entry;
		}
	}
	
#
# The PUT process
#
	
	/**
	 * Replace entry.
	 * 
	 * @return static $this
	 */
	function put($id, $entry)
	{
		// @todo: check if all fields are present for more robust PUT
		
		return $this->patch($id, $entry);
	}
	
	/**
	 * Normalizing value format, filling in optional components, etc.
	 * 
	 * @return array normalized entry
	 */
	function parse(array $input)
	{
		return $input;
	}
	
	/**
	 * Auditor should always handle parsed values.
	 * 
	 * @return \mjolnir\types\Validator
	 */
	function auditor()
	{
		return \app\Auditor::instance();
	}
	
#
# The PATCH process
#
	
	/**
	 * Update specified fields in entry.
	 * 
	 * @return static $this
	 */
	function patch($id, $partial_entry)
	{
		$entry = $this->parse($partial_entry);
		$auditor = $this->auditor();
		
		try
		{
			$this->db->begin();
			$entry = $this->run_drivers($entry);
			if ($auditor->fields_array($entry)->check())
			{
				$entry_id = $this->do_patch($id, $entry);
				
				// success?
				if ($entry_id !== null)
				{
					$this->db->commit();
					return $this->get($entry_id);
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
	
	/**
	 * @return static $this
	 */
	function do_patch($id, $entry)
	{
		// create field list
		$spec = static::config();
		$fieldlist = $this->make_fieldlist($spec, \array_keys($entry));

		// create templates
		$fields = [];
		foreach ($fieldlist as $type => $list)
		{
			foreach ($list as $fieldname)
			{
				$fields[] = $fieldname;
			}
		}
		
		$setfields = \app\Arr::implode
			(
				', ', 
				$fields, 
				function ($key, $in)
				{
					return "`$in` = :$in";
				}
			);
		
		$this->db->prepare
			(
				__METHOD__,
				'
					UPDATE `'.static::table().'` 
					   SET '.$setfields.'
					 WHERE `id` = :id
				'
			)
			->num(':id', $id)
			->strs($entry, $fieldlist['strs'])
			->nums($entry, $fieldlist['nums'])
			->bools($entry, $fieldlist['bools'])
			->run();
		
		// the update may be the entire entry being replaced by another
		return $id;
	}

#
# The DELETE process
#
	
	/**
	 * Delete entry.
	 * 
	 * @return static $this
	 */
	function delete($id)
	{
		$this->db->prepare
			(
				__METHOD__,
				'
					DELETE FROM `'.static::table().'`
					 WHERE `'.static::keyfield().'` = :id
				'
			)
			->num(':id', $id)
			->run();
				
		return $this;
	}
	
} # class
