<?php namespace mjolnir\database;

/**
 * PROTOTYPE - subject to change
 * 
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class MarionetteDriver_Reference extends \app\Instantiatable implements \mjolnir\types\MarionetteDriver
{
	use \app\Trait_MarionetteDriver;
	
	/**
	 * @return array
	 */
	function compile($field, array $entry, array $conf = null)
	{
		if (empty($entry[$field]))
		{
			$entry[$field] = null;
		}
		else # got entry
		{
			// retrieve reference collection class
			if (\strpos($conf['collection'], '\\') === false)
			{
				$class = '\app\\'.$conf['collection'];
			}
			else # namespaced class
			{
				$class = $conf['collection'];
			}
			
			$keyfield = $class::keyfield();
			
			if (isset($entry[$field][$keyfield]))
			{
				$entry[$field] = $entry[$field][$keyfield];
			}
			else # new model for given collection
			{
				$collection = $class::instance($this->db);
				$new_ref = $collection->post($entry[$field]);
				
				if ($new_ref !== null)
				{
					$entry[$field] = $new_ref[$keyfield];
				}
				else # got validation fail state
				{
					throw new \app\Exception("Failed to create reference for [$field] in {$conf['collection']}.");
				}
			}
		}
		
		return $entry;
	}

	/**
	 * @return array
	 */
	function compilefields($field, array $fieldlist, array $conf = null)
	{
		$fieldlist['nums'][] = $field;
		return $fieldlist;
	}
	
} # class
