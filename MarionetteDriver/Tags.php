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
	function post_latecompile(array $entry, array $input)
	{
		$field = $this->field;
		$conf = $this->config;
		$tags = $input[$field];

		if ( ! empty($tags))
		{
			// instance of tag collection
			$tag_collection = $this->collection();

			// since this is POST there are no tag associations to remove,
			// we just need to add associations

			$entry_id = $entry[$this->context->keyfield()];

			// instantiate reference collection
			$class = $this->resolveclassname($conf['assoc']);
			$assoc_collection = $class::instance($this->db);

			// compute key fields
			$tagkey = $tag_collection->codename();
			$self = $this->context->codename();

			foreach ($tags as $tag)
			{
				$assoc_collection->post
					(
						[
							$self => $entry_id,
							$tagkey => $tag['id'],
						]
					);
			}
		}

		// at this point we've inserted the tags corresponding to the entry
		// however the entry itself is not correct, since it was processed
		// while the tags were not in place, so we need to update the entry

		$entry = $this->context->model()->get($entry[$this->context->keyfield()]);

		return $entry;
	}

	/**
	 * @return array
	 */
	function patch_compile($id, array $input)
	{
		if (isset($input[$this->field]))
		{
			$this->unlinktags($id);
		}

		return $input;
	}

	/**
	 * Resolve dependencies after the entry has been patched.
	 *
	 * @return array entry
	 */
	function patch_latecompile($id, array $entry, array $input)
	{
		if ( ! isset($input[$this->field]))
		{
			return $entry;
		}

		return $this->post_latecompile($entry, $input);
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
	 * Execute after entry has been removed.
	 */
	function predelete($id)
	{
		$this->unlinktags($id);
	}

	/**
	 * @return array
	 */
	function normalizeconfig(array $conf)
	{
		isset($conf['automake']) or $conf['automake'] = false;

		return $conf;
	}

	// ------------------------------------------------------------------------
	// Helpers

	/**
	 * Remove tag association.
	 */
	protected function unlinktags($id)
	{
		$conf = $this->config;

		// instantiate reference collection
		$class = $this->resolveclassname($conf['assoc']);
		$assoc_collection = $class::instance($this->db);

		// remove tag association
		$assoc_collection->tags_unset($id, $this->context->singular());
	}

} # class
