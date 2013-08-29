<?php namespace mjolnir\database\tests;

use \mjolnir\database\MarionetteDriver_Reference;

class MarionetteDriver_ReferenceTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\MarionetteDriver_Reference'));
	}

	// @todo tests for \mjolnir\database\MarionetteDriver_Reference

} # test
