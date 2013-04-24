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
			$class = $this->camelsingular().'Collection';
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
		throw new \app\Exception_NotImplemented();
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
		throw new \app\Exception_NotImplemented();
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
		throw new \app\Exception_NotImplemented();
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
		throw new \app\Exception_NotImplemented();
	}
	
} # class
