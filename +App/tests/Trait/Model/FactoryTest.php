<?php namespace mjolnir\database\tests;

use \mjolnir\database\Trait_Model_Factory;

class Trait_Model_Factory_Tester
{
	use Trait_Model_Factory;
}

class Trait_Model_FactoryTest extends \PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\trait_exists('\mjolnir\database\Trait_Model_Factory'));
	}

	// @todo tests for \mjolnir\database\Trait_Model_Factory

} # test
