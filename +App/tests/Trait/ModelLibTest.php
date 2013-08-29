<?php namespace mjolnir\database\tests;

use \mjolnir\database\Trait_ModelLib;

class Trait_ModelLib_Tester
{
	use Trait_ModelLib;
}

class Trait_ModelLibTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\trait_exists('\mjolnir\database\Trait_ModelLib'));
	}

	// @todo tests for \mjolnir\database\Trait_ModelLib

} # test
