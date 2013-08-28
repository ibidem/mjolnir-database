<?php namespace mjolnir\database\tests;

use \mjolnir\database\Trait_Model_Collection;

class Trait_Model_Collection_Tester
{
	use Trait_Model_Collection;
}

class Trait_Model_CollectionTest extends \PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\trait_exists('\mjolnir\database\Trait_Model_Collection'));
	}

	// @todo tests for \mjolnir\database\Trait_Model_Collection

} # test
