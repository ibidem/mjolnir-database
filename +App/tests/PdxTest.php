<?php namespace mjolnir\database\tests;

use \mjolnir\database\Pdx;

class PdxTest extends \PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\Pdx'));
	}

	// @todo tests for \mjolnir\database\Pdx

} # test
