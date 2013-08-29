<?php namespace mjolnir\database\tests;

use \mjolnir\database\Schematic_Base;

class Schematic_BaseTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\Schematic_Base'));
	}

	// @todo tests for \mjolnir\database\Schematic_Base

} # test
