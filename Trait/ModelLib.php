<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Library
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
trait Trait_ModelLib
{
	#
	# This traits is a shorthand, it includes common model traits. The trait
	# is updated with new generic traits as they are added to the library.
	#

	use \app\Trait_Model_Factory;
	use \app\Trait_Model_Collection;
	use \app\Trait_Model_Utilities;

} # trait
