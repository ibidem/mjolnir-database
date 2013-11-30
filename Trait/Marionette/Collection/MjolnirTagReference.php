<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2013 Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
trait Trait_Marionette_Collection_MjolnirTagReference
{
	/**
	 * Remove tags.
	 *
	 * @return static
	 */
	function tags_unset($ref_id, $field)
	{
		$this->db->prepare
			(
				'
					DELETE FROM `'.static::table().'`
					 WHERE `'.$field.'` = :ref_id
				'
			)
			->num(':ref_id', $ref_id)
			->run();

		return $this;
	}

} # trait
