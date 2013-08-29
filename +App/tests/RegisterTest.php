<?php namespace mjolnir\database\tests;

use \mjolnir\database\Register;

class RegisterTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\Register'));
	}

	// @todo tests for \mjolnir\database\Register

} # test
