<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class MarionetteDriver_Tags extends \app\Instantiatable implements \mjolnir\types\MarionetteDriver
{
	use \app\Trait_MarionetteDriver;
	
	/**
	 * Resolve dependencies after the entry has been created.
	 * 
	 * @return array updated entry
	 */
	function latecompile(array $entry, array $input)
	{
		$field = $this->field;
		$conf = $this->config;
		
		$input[$field] = \trim($input[$field], ' ,');
		if ( ! empty($input[$field]))
		{
			// instance of TagCollection
			$tag_collection = $this->collection();
			
			// cleanup tags
			$tags = [];
			foreach (\explode(',', $input[$field]) as $tag)
			{
				$tag = \trim($tag);
				if ( ! empty($tag))
				{
					$tags[] = $tag;
				}
			}
			
			// resolve to IDs
			$tagids = [];
			if ( ! empty($tags))
			{
				foreach ($tags as $tag)
				{
					$tagentries = $tag_collection->get(['constraints' => ['title' => $tag]]);
					
					if (empty($tagentries))
					{
						if ($conf['automake'])
						{
							// new tag
							$tagentry = $tag_collection->post(['title' => $tag]);
							if ($entry !== null)
							{
								$tagids[] = $tagentry[$tag_collection->keyfield()];
							}
							# else: failed validation; no handling
						}
					}
					else # got existing entry
					{
						$tagids[] = $tagentries[0][$tag_collection->keyfield()];
					}
				}
			}
			
			if ( ! empty($tagids))
			{
				// since this is POST there are no tag associations to remove,
				// we just need to add associations
				
				$entry_id = $entry[$this->context->keyfield()];
				
				// instantiate reference collection
				$class = $this->resolveclassname($conf['assoc']);
				$assoc_collection = $class::instance($this->db);
				
				// compute key fields
				$tagkey = $tag_collection->codename();
				$self = $this->context->codename();

				foreach ($tagids as $tag_id)
				{
					$assoc_collection->post
						(
							[
								$self => $entry_id, 
								$tagkey => $tag_id,
							]
						);
				}
			}
		}
		
		// at this point we've inserted the tags corresponding to the entry
		// however the entry itself is not correct, since it was processed 
		// while the tags were not in place, so we need to update the entry
		
		$entry = $this->context->model()->get($entry[$this->context->keyfield()]);
		
		return $entry;
	}

	/**
	 * On GET, manipulate execution plan.
	 * 
	 * @return array updated execution plan
	 */
	function inject(array $plan)
	{
		$field = $this->field;
		
		$class = $this->resolveclassname($this->config['assoc']);
		$assoc_collection = $class::instance($this->db);
		$keyfield = $this->context->keyfield();
		
		// compute association key
		$self = $this->context->codename();
		$tag_collection = $this->collection();
		$tag_model = $this->collection()->model();
		$tagkey = $tag_collection->codename();
		
		$plan['postprocessors'][] = function ($entry) use ($field, $keyfield, $self, $tagkey, $assoc_collection, $tag_model)
			{
				$entry[$field] = [];
				$references = $assoc_collection->get(['constraints' => [$self => $entry[$keyfield]]]);
				
				foreach ($references as $ref)
				{
					$entry[$field][] = $tag_model->get($ref[$tagkey]);
				}
				
				return $entry;
			};
		
		return $plan;
	}
	
	/**
	 * @return array
	 */
	function normalizeconfig(array $conf)
	{
		isset($conf['automake']) or $conf['automake'] = false;
		
		return $conf;
	}
	
} # class
