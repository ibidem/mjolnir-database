<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class PdxVersionMatcher extends \app\Instantiatable implements \mjolnir\types\VersionMatcher, \mjolnir\types\Versioned
{
	use \app\Trait_VersionMatcher;
	use \app\Trait_Versioned;

	const VERSION = '1.0.0';

	/**
	 * @return boolean
	 */
	function check()
	{
		throw new \app\Exception_NotImplemented();
	}


} # class
