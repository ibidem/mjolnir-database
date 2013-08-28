<?php namespace mjolnir\database\tests;

use \mjolnir\database\Trait_Marionette_Collection_MjolnirTagReference;

class Trait_Marionette_Collection_MjolnirTagReference_Tester
{
	use Trait_Marionette_Collection_MjolnirTagReference;
}

class Trait_Marionette_Collection_MjolnirTagReferenceTest extends \PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\trait_exists('\mjolnir\database\Trait_Marionette_Collection_MjolnirTagReference'));
	}

	// @todo tests for \mjolnir\database\Trait_Marionette_Collection_MjolnirTagReference

} # test
