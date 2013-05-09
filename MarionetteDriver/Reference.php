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
	 * On POST, resolve input dependencies (happens before validation).
	 * 
	 * @return array updated entry
	 */
	function compile(array $entry)
	{
		$conf = $this->config;
		$field = $this->field;
		
		if (empty($entry[$field]))
		{
			$entry[$field] = null;
		}
		else # got entry
		{
			$collection = $this->collection();
			$keyfield = $collection->keyfield();
			
			if (isset($entry[$field][$keyfield]))
			{
				$entry[$field] = $entry[$field][$keyfield];
			}
			else # new model for given collection
			{
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
	 * On POST, field processing before POST database communication.
	 * 
	 * @return array updated fieldlist
	 */
	function compilefields(array $fieldlist)
	{
		$fieldlist['nums'][] = $this->field;
		return $fieldlist;
	}
	
	/**
	 * On GET, manipulate execution plan.
	 * 
	 * @return array updated execution plan
	 */
	function inject(array $plan)
	{
		$field = $this->field;
		
		$plan['fields'][] = $field;
		$collection = $this->collection();
		$model = $collection->model();
		
		$plan['postprocessors'][] = function ($entry) use ($field, $model) 
			{
				$entry[$field] = $model->get($entry[$field]);
				return $entry;
			};
		
		return $plan;
	}
	
} # class
