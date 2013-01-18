<?php namespace mjolnir\database;

/**
 * This class should only ever be extended at the project level. For all other
 * cases use callbacks.
 * 
 * @package    mjolnir
 * @category   Library
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class ValidatorRules
{
	/**
	 * @param mixed field
	 * @return bool 
	 */
	static function not_empty($field)
	{
		return $field === 0 || $field === '0' || ! empty($field);
	}
	
	/**
	 * @param string field
	 * @param integer maxlength
	 * @return bool
	 */
	static function max_length($field, $maxlength)
	{
		return \strlen($field) <= $maxlength;
	}
	
	/**
	 * @param string field
	 * @param integer minlength
	 * @return bool
	 */
	static function min_length($field, $minlength)
	{
		return \strlen($field) >= $minlength;
	}
	
	/**
	 * @param string field
	 * @param string other
	 * @return bool
	 */
	static function equal_to($field, $other)
	{
		return $field == $other;
	}
	
	/**
	 * @param string field
	 * @param array values
	 * @return bool
	 */
	static function only_values($field, array $values)
	{
		return \in_array($field, $values);
	}
	
	/**
	 *
	 * @param type $field 
	 * @return bool
	 */
	static function valid_date($field)
	{
		$matches = array();
		if (\preg_match('#^(?P<year>[0-9]+)-(?P<month>[0-9]+)-(?P<day>[0-9]+)$#', $field, $matches))
		{
			return \checkdate($matches['month'], $matches['day'], $matches['year']);
		}
		else # failed match
		{
			return false;
		}	
	}
	
	static function valid_number($field)
	{
		return \is_numeric($field);
	}

} # class
