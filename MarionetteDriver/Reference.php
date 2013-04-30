<?php namespace mjolnir\database;

/**
 * The Reference driver allows you to link a field to the public version of 
 * another field in another table. The reference field in the parent table is
 * a numeric id and will be linked via keyfield of the referenced collection.
 * 
 * On get the field will be translated to an array of the reference table.
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
	
	/**
	 * @return array updated execution plan
	 */
	function inject($field, array $plan, array $conf = null)
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
		
		$collection = $class::instance($this->db);
		$model = $collection->model();
		
		$plan['postprocessors'][] = function ($entry) use ($field, $model) 
			{
				$entry[$field] = $model->get($entry[$field]);
				return $entry;
			};
		
		return $plan;
	}
	
} # class
