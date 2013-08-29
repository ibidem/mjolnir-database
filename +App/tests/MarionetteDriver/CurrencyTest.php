<?php namespace mjolnir\database\tests;

use \mjolnir\database\MarionetteDriver_Currency;

class MarionetteDriver_CurrencyTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\MarionetteDriver_Currency'));
	}

	// @todo tests for \mjolnir\database\MarionetteDriver_Currency

} # test
