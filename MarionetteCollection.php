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

#
# The GET process
#

	/**
	 * Retrieve collection members.
	 *
	 * @return array
	 */
	function get(array $conf = null)
	{
		$conf !== null or $conf = [];

		// forbid direct JOIN injection
		$conf['joins'] = null;

		$defaults = array
			(
				'fields' => null,
				'joins' => null,
				'constraints' => null,
				'limit' => null,
				'offset' => 0,
				'postprocessors' => null
			);

		$plan = $this->generate_executation_plan($conf, $defaults);

		$entries = $this->db->prepare
			(
				__METHOD__,
				'
					SELECT '.$plan['fields'].'
					'.$plan['joins'].'
					  FROM `'.static::table().'` entry
					'.$plan['constraints'].'
					'.$plan['limit'].'
				'
			)
			->run()
			->fetch_all();

		if ($plan['postprocessors'] !== null)
		{
			foreach ($entries as & $entry)
			{
				foreach ($plan['postprocessors'] as $processor)
				{
					$entry = $processor($entry);
				}
			}

			return $entries;
		}
		else # no postprocessors step
		{
			return $entries;
		}
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
	 *
	 * @return array entry
	 */
	function post(array $input)
	{
		// 1. normalize entry
		$input = $this->parse($input);

		try
		{
			$this->db->begin();

			// 2. run compile steps against entry
			$input = $this->run_drivers_post_compile($input);

			// 3. check for errors
			$auditor = $this->auditor();
			if ($auditor->fields_array($input)->check())
			{
				// 4. persist to database
				$entry_id = $this->do_post($input);

				// success?
				if ($entry_id !== null)
				{
					// get entry
					$entry = $this->model()->get($entry_id);

					// 5. run latecompile steps against entry
					$entry = $this->run_drivers_post_latecompile($entry, $input);

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

	# 2. run drivers against entry

		# see: Marionette

	# 3. check for errors

	/**
	 * Auditor should always handle parsed values.
	 *
	 * @return \mjolnir\types\Validator
	 */
	final function auditor()
	{
		return $this->model()->auditor();
	}

	# 4. persist to database

	/**
	 * @return int new entry id
	 */
	protected function do_post($entry)
	{
		// create field list
		$spec = static::config();
		$fieldlist = $this->make_fieldlist($spec);

		// inject driver based dependencies
		$fieldlist = $this->run_drivers_post_compilefields($fieldlist);

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
				function ($k, $in)
				{
					return "`$in`";
				}
			);

		$keyfields = \app\Arr::implode
			(
				', ',
				$fields,
				function ($k, $in)
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

		$this->cachereset(null, 'post');

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
		$this->db->begin();

		try
		{
			$this->delete();
			foreach ($collection as $entry)
			{
				$this->post($entry);
			}

			$this->commit();
		}
		catch (\Exception $e)
		{
			$this->db->rollback();
			throw $e;
		}

		return $this;
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
		if ( ! empty($this->filters))
		{
			#
			# SQL delete will not accept aliases, so we need to strip alias
			# definitions from the filters.
			#

			$barefilters = [];

			foreach ($this->filters as $key => $condition)
			{
				$barefilters[\preg_replace('#^entry\.#', '', $key)] = $condition;
			}

			$constraints = 'WHERE '.\app\SQL::parseconstraints($barefilters);
		}
		else
		{
			$constraints = '';
		}

		$this->db->prepare
			(
				__METHOD__,
				'
					DELETE FROM `'.static::table().'`
					'.$constraints.'
				'
			)
			->run();

		$this->cachereset(null, 'delete');

		return $this;
	}

} # class
