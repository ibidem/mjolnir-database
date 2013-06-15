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
	function put($id, array $entry)
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
	function patch($id, array $partial_entry)
	{
		// 1. normalize entry
		$input = $this->parse($partial_entry);

		try
		{
			$this->db->begin();

			// 2. run compile steps against entry
			$input = $this->run_drivers_patch_compile($id, $input);

			// 3. check for errors
			$auditor = $this->auditor();

			if ($auditor->fields_array($input)->check())
			{
				// 4. persist to database
				$entry_id = $this->do_patch($id, $input);

				// success?
				if ($entry_id !== null)
				{
					// get entry
					$entry = $this->model()->get($entry_id);

					// 5. run latecompile steps against entry
					$entry = $this->run_drivers_patch_latecompile($id, $entry, $partial_entry);

					if ($entry !== null)
					{
						$this->db->commit();
						return $entry;
					}
					else # failed latecompile
					{
						$this->db->rollback();
						return null;
					}
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
	function do_patch($id, array $entry)
	{
		// create field list
		$spec = static::config();
		$fieldlist = $this->make_fieldlist($spec, \array_keys($entry));

		// inject driver based dependencies
		$fieldlist = $this->run_drivers_patch_compilefields($fieldlist);

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

		// it's possible all relevant fields are powered by drivers which work
		// exclusively with associated models and hence this operation may not
		// need to set anything
		if (\trim($setfields) !== '')
		{
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
		}

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
		$this->run_drivers_predelete($id);

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

		$this->run_drivers_postdelete($id);

		return $this;
	}

} # class
