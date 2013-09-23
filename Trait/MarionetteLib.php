<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Library
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
trait Trait_MarionetteLib
{
	use Trait_ModelLib;

	/**
	 * @return string
	 */
	static function table()
	{
		$class = static::marionette_model_class();
		return $class::table();
	}

	// ------------------------------------------------------------------------
	// Factory

	/**
	 * @return \mjolnir\types\Validator
	 */
	static function check(array $fields, $context = null)
	{
		return static::marionette_model()
			->auditor()
				->fields_array($fields);
	}

	/**
	 * @return array|null errors or null
	 */
	static function push(array $fields)
	{
		return static::marionette_collection()
			->post($fields);
	}

	// ------------------------------------------------------------------------
	// Helpers

	/**
	 * @return string
	 */
	protected static function marionette_base_class()
	{
		// remove namespace
		$bareclass = \preg_replace('/^.*\\\/', '', \get_called_class());
		// remove Lib suffix
		return '\app\\'.\preg_replace('/Lib$/', '', $bareclass);
	}

	/**
	 * @return string
	 */
	protected static function marionette_model_class()
	{
		return static::marionette_base_class().'Model';
	}

	/**
	 * @return \mjolnir\types\MarionetteModel
	 */
	protected static function marionette_model()
	{
		static $marionette_model_instance = null;

		if ($marionette_model_instance === null)
		{
			$class = static::marionette_model_class();
			$marionette_model_instance = $class::instance();
		}

		return $marionette_model_instance;
	}

	/**
	 * @return string
	 */
	protected static function marionette_collection_class()
	{
		return static::marionette_base_class().'Collection';
	}

	/**
	 * @return \mjolnir\types\MarionetteCollection
	 */
	protected static function marionette_collection()
	{
		static $marionette_collection_instance = null;

		if ($marionette_collection_instance === null)
		{
			$class = static::marionette_collection_class();
			$marionette_collection_instance = $class::instance();
		}

		return $marionette_collection_instance;
	}

} # trait
