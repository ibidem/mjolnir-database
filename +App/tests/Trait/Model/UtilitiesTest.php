<?php namespace mjolnir\database\tests;

use \mjolnir\database\Trait_Model_Utilities;

class Trait_Model_Utilities_Tester
{
	use Trait_Model_Utilities;
}

class Trait_Model_UtilitiesTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\trait_exists('\mjolnir\database\Trait_Model_Utilities'));
	}

	// @todo tests for \mjolnir\database\Trait_Model_Utilities

} # test
