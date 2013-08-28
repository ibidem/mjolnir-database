<?php namespace mjolnir\database\tests;

use \mjolnir\database\Schematic_Mjolnir_Registry;

class Schematic_Mjolnir_RegistryTest extends \PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\Schematic_Mjolnir_Registry'));
	}

	// @todo tests for \mjolnir\database\Schematic_Mjolnir_Registry

} # test
