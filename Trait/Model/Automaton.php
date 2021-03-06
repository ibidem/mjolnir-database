<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012 Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
trait Trait_Model_Automaton
{
	// -------------------------------------------------------------------------
	// Factory interface

	/**
	 * @return \mjolnir\types\Validator
	 */
	static function check(array $fields, $context = null)
	{
		$errors = isset(static::$automaton['errors']) ? static::$automaton['errors'] : [ 'id' => [ ':exists' => 'Entry does not exist.' ] ];

		$validator = \app\Validator::instance($fields)
			->adderrormessages($errors)
			->rule(static::$automaton['fields'], 'not-empty');

		if (isset(static::$automaton['unique']))
		{
			foreach (static::$automaton['unique'] as $field)
			{
				if ( ! isset($fields[$field]))
				{
					if (\app\CFS::config('mjolnir/base')['development'])
					{
						throw new \app\Exception
							('Model Automaton - missing key ['.$field.']');
					}
					else
					{
						throw new \app\Exception_NotApplicable
							('The data recieved appears to be corrupt. Request canceled to avoid errors.');
					}

				}

				$validator->rule($field, ':unique', ! static::exists($fields[$field], $field, $context));
			}
		}

		return $validator;
	}

	/**
	 * ...
	 */
	static function process(array $fields)
	{
		static::inserter($fields, static::$automaton['fields'])->run();
		static::$last_inserted_id = \app\SQL::last_inserted_id();

		// reset related caches
		foreach (static::related_caches() as $related_cache)
		{
			\app\Stash::purge(\app\Stash::tags($related_cache[0], $related_cache[1]));
		}
	}

	/**
	 * ...
	 */
	static function update_process($id, array $fields)
	{
		$update_fields = static::$automaton['fields'];
		if (static::unique_key() !== 'id')
		{
			if (($key = \array_search(static::unique_key(), $update_fields)) !== false)
			{
				unset($update_fields[$key]);
			}
		}

		static::updater($id, $fields, $update_fields)->run();
		static::clear_entry_cache($id);

		// reset related caches
		foreach (static::related_caches() as $related_cache)
		{
			\app\Stash::purge(\app\Stash::tags($related_cache[0], $related_cache[1]));
		}
	}

} # trait
