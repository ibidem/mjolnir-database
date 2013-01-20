<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Schematic_Mjolnir_Registry extends \app\Instantiatable implements \mjolnir\types\Schematic
{
	use \app\Trait_Schematic;

	/**
	 * ...
	 */
	function down()
	{
		\app\Schematic::destroy
			(
				\app\Register::table()
			);
	}

	/**
	 * ...
	 */
	function up()
	{
		\app\Schematic::table
			(
				\app\Register::table(),
				'
					`key`   :title,
					`value` :block,

					PRIMARY KEY (`key`)
				'
			);
	}

	/**
	 * ...
	 */
	function build()
	{
		// populate register
		$key = null;
		$value = null;
		$statement = \app\SQL::prepare
			(
				__METHOD__,
				'
					INSERT INTO `'.\app\Register::table().'`
					(`key`, `value`) VALUES (:key, :value)
				',
				'mysql'
			)
			->bindstr(':key', $key)
			->bindstr(':value', $value);

		foreach (\app\CFS::config_file('mjolnir/register')['keys'] as $target => $default)
		{
			$key = $target;
			$value = $default;
			$statement->run();
		}
	}

} # class
