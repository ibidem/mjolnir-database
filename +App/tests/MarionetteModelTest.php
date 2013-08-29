<?php namespace mjolnir\database\tests;

use \mjolnir\database\MarionetteModel;

class MarionetteModelTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\MarionetteModel'));
	}

	// @todo tests for \mjolnir\database\MarionetteModel

} # test
