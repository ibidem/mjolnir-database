<?php namespace mjolnir\database\tests;

use \mjolnir\database\Trait_Model_MjolnirSphinx;

class Trait_Model_MjolnirSphinx_Tester
{
	use Trait_Model_MjolnirSphinx;
}

class Trait_Model_MjolnirSphinxTest extends \PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\trait_exists('\mjolnir\database\Trait_Model_MjolnirSphinx'));
	}

	// @todo tests for \mjolnir\database\Trait_Model_MjolnirSphinx

} # test
