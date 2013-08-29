<?php namespace mjolnir\database\tests;

use \mjolnir\database\Marionette;

class MarionetteTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\Marionette'));
	}

	// @todo tests for \mjolnir\database\Marionette

} # test
